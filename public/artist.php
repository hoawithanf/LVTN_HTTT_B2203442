<?php
include('includes/session.php');
include('includes/header.php');
include('includes/navbar.php');
include('includes/database.php');
require_once __DIR__ . '/../config/song_helpers.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nln_public_asset_exists($relativePath)
{
    $relativePath = ltrim((string) $relativePath, '/');
    return is_file(__DIR__ . '/' . $relativePath);
}

function nln_public_artist_avatar_path($filename = null)
{
    $filename = trim((string) $filename);

    if ($filename !== '') {
        $safeName = basename($filename);
        $candidate = 'assets/img/artists/' . $safeName;

        if (nln_public_asset_exists($candidate)) {
            return $candidate;
        }
    }

    $fallbacks = [
        'assets/img/default-artist.jpg',
        'assets/img/default.jpg',
        'assets/img/home-bg.jpg'
    ];

    foreach ($fallbacks as $fallback) {
        if (nln_public_asset_exists($fallback)) {
            return $fallback;
        }
    }

    return 'assets/img/home-bg.jpg';
}

function nln_public_album_cover_path($filename = null)
{
    $filename = trim((string) $filename);

    if ($filename !== '') {
        $safeName = basename($filename);
        $candidate = 'assets/img/albums/' . $safeName;

        if (nln_public_asset_exists($candidate)) {
            return $candidate;
        }
    }

    $fallbacks = [
        'assets/img/default.jpg',
        'assets/img/home-bg.jpg'
    ];

    foreach ($fallbacks as $fallback) {
        if (nln_public_asset_exists($fallback)) {
            return $fallback;
        }
    }

    return 'assets/img/home-bg.jpg';
}

$artist_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* ===============================
   GET ARTIST INFO
================================ */
$stmtArtist = $conn->prepare("SELECT * FROM artists WHERE artist_id = ? LIMIT 1");
$stmtArtist->bind_param("i", $artist_id);
$stmtArtist->execute();
$artist = $stmtArtist->get_result()->fetch_assoc();
$stmtArtist->close();

if (!$artist) {
    ?>
    <div class="container px-4 px-lg-5 py-5">
        <div class="content-empty-state">
            <i class="fas fa-microphone-alt"></i>
            <h3>Nghệ sĩ không tồn tại</h3>
            <p>Hồ sơ nghệ sĩ bạn đang tìm không tồn tại hoặc đã bị xóa khỏi hệ thống.</p>
            <a href="index.php" class="btn btn-primary mt-2">Quay về trang chủ</a>
        </div>
    </div>
    <?php
    include('includes/footer.php');
    exit;
}

$avatar = nln_public_artist_avatar_path($artist['avatar'] ?? null);
$artistBio = trim((string) ($artist['bio'] ?? ''));
$artistBioText = $artistBio !== '' ? $artistBio : 'Chưa có thông tin mô tả nghệ sĩ.';

/* ===============================
   CHECK FOLLOW
================================ */
$isFollowing = false;
if (isset($_SESSION['user_id'])) {
    $chk = $conn->prepare("
        SELECT 1
        FROM artist_follows
        WHERE user_id = ? AND artist_id = ?
        LIMIT 1
    ");
    $chk->bind_param("ii", $_SESSION['user_id'], $artist_id);
    $chk->execute();
    $isFollowing = $chk->get_result()->num_rows > 0;
    $chk->close();
}

/* ===============================
   STATS
================================ */
$followerCount = 0;
$totalSongs = 0;
$totalAlbums = 0;
$totalSearches = 0;

$stmtFollowers = $conn->prepare("SELECT COUNT(*) AS total FROM artist_follows WHERE artist_id = ?");
$stmtFollowers->bind_param("i", $artist_id);
$stmtFollowers->execute();
$followerCount = (int) ($stmtFollowers->get_result()->fetch_assoc()['total'] ?? 0);
$stmtFollowers->close();

$stmtSongCount = $conn->prepare("SELECT COUNT(*) AS total FROM songs WHERE artist_id = ?");
$stmtSongCount->bind_param("i", $artist_id);
$stmtSongCount->execute();
$totalSongs = (int) ($stmtSongCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtSongCount->close();

$stmtAlbumCount = $conn->prepare("SELECT COUNT(*) AS total FROM albums WHERE artist_id = ?");
$stmtAlbumCount->bind_param("i", $artist_id);
$stmtAlbumCount->execute();
$totalAlbums = (int) ($stmtAlbumCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtAlbumCount->close();

$stmtSearches = $conn->prepare("
    SELECT COUNT(sl.log_id) AS total
    FROM songs s
    LEFT JOIN search_logs sl ON sl.song_id = s.song_id
    WHERE s.artist_id = ?
");
$stmtSearches->bind_param("i", $artist_id);
$stmtSearches->execute();
$totalSearches = (int) ($stmtSearches->get_result()->fetch_assoc()['total'] ?? 0);
$stmtSearches->close();

/* ===============================
   TOP SONGS
================================ */
$topSongs = [];
$stmtTopSongs = $conn->prepare("
    SELECT
        s.song_id,
        s.title,
        s.cover_image AS song_cover,
        al.cover_image AS album_cover,
        COUNT(l.song_id) AS total
    FROM songs s
    LEFT JOIN search_logs l ON l.song_id = s.song_id
    LEFT JOIN albums al ON al.album_id = s.album_id
    WHERE s.artist_id = ?
    GROUP BY s.song_id, s.title, s.cover_image, al.cover_image
    ORDER BY total DESC, s.title ASC
    LIMIT 5
");
$stmtTopSongs->bind_param("i", $artist_id);
$stmtTopSongs->execute();
$topSongsResult = $stmtTopSongs->get_result();

while ($row = $topSongsResult->fetch_assoc()) {
    $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
    $row['total'] = (int) ($row['total'] ?? 0);
    $topSongs[] = $row;
}
$stmtTopSongs->close();

/* ===============================
   ALBUMS
================================ */
$albums = [];
$stmtAlbums = $conn->prepare("
    SELECT
        al.album_id,
        al.album_name,
        al.cover_image,
        al.release_year,
        COUNT(s.song_id) AS total_tracks
    FROM albums al
    LEFT JOIN songs s ON s.album_id = al.album_id
    WHERE al.artist_id = ?
    GROUP BY al.album_id, al.album_name, al.cover_image, al.release_year
    ORDER BY al.release_year DESC, al.album_name ASC
");
$stmtAlbums->bind_param("i", $artist_id);
$stmtAlbums->execute();
$albumsResult = $stmtAlbums->get_result();

while ($row = $albumsResult->fetch_assoc()) {
    $row['image_path'] = nln_public_album_cover_path($row['cover_image'] ?? null);
    $row['total_tracks'] = (int) ($row['total_tracks'] ?? 0);
    $albums[] = $row;
}
$stmtAlbums->close();

$featuredSong = $topSongs[0] ?? null;
$remainingSongs = array_slice($topSongs, 1);
?>

<header class="masthead artist-hero" style="background-image: url('<?= h($avatar) ?>')">
    <div class="artist-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center artist-heading-shell">
            <div class="artist-avatar-wrap">
                <img src="<?= h($avatar) ?>" alt="<?= h($artist['artist_name']) ?>" class="artist-hero-avatar">
            </div>

            <span class="artist-eyebrow">Artist Profile</span>
            <h1><?= h($artist['artist_name']) ?></h1>
            <span class="subheading artist-subheading">
                <?= h($artistBioText) ?>
            </span>

            <div class="artist-hero-chips">
                <span class="artist-chip">
                    <i class="fas fa-music me-2"></i>
                    <?= number_format($totalSongs) ?> bài hát
                </span>
                <span class="artist-chip">
                    <i class="fas fa-compact-disc me-2"></i>
                    <?= number_format($totalAlbums) ?> album
                </span>
                <span class="artist-chip">
                    <i class="fas fa-fire me-2"></i>
                    <?= number_format($totalSearches) ?> lượt tìm kiếm
                </span>
            </div>

            <div class="mt-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="followBtn"
                        type="button"
                        class="btn artist-follow-btn <?= $isFollowing ? 'btn-secondary' : 'btn-success' ?>"
                        data-id="<?= $artist_id ?>"
                        data-following="<?= $isFollowing ? '1' : '0' ?>">
                        <?= $isFollowing ? '✓ Đang theo dõi' : '+ Theo dõi' ?>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn artist-follow-btn btn-success">+ Theo dõi</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5 artist-page">
    <div class="artist-summary-bar">
        <div class="artist-summary-stat">
            <span>Người theo dõi</span>
            <strong id="artistFollowerCount"><?= number_format($followerCount) ?></strong>
            <small>Mức độ quan tâm hiện tại</small>
        </div>

        <div class="artist-summary-stat">
            <span>Bài hát nổi bật</span>
            <strong><?= number_format(count($topSongs)) ?></strong>
            <small>Top bài hát dựa trên tìm kiếm</small>
        </div>

        <div class="artist-summary-stat">
            <span>Kho album</span>
            <strong><?= number_format($totalAlbums) ?></strong>
            <small>Danh mục phát hành của nghệ sĩ</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7 mb-4">
            <section class="artist-panel">
                <div class="artist-panel-header">
                    <span class="section-kicker">Top Tracks</span>
                    <h4 class="artist-panel-title mb-1">Bài hát nổi bật</h4>
                    <p class="artist-panel-subtitle mb-0">
                        Các bài hát được quan tâm nhiều nhất của nghệ sĩ trên hệ thống.
                    </p>
                </div>

                <?php if ($featuredSong): ?>
                    <a href="post.php?id=<?= (int) $featuredSong['song_id'] ?>" class="featured-artist-song-card">
                        <img
                            src="<?= h($featuredSong['cover']) ?>"
                            alt="<?= h($featuredSong['title']) ?>"
                            class="featured-artist-song-cover"
                        >

                        <div class="featured-artist-song-copy">
                            <span class="featured-label">#1 nổi bật</span>
                            <h2><?= h($featuredSong['title']) ?></h2>
                            <p class="featured-meta"><?= number_format((int) $featuredSong['total']) ?> lượt tìm kiếm</p>
                        </div>
                    </a>

                    <div class="artist-song-list">
                        <?php foreach ($remainingSongs as $index => $s): ?>
                            <a href="post.php?id=<?= (int) $s['song_id'] ?>" class="artist-song-card">
                                <div class="artist-rank-badge"><?= $index + 2 ?></div>

                                <img
                                    src="<?= h($s['cover']) ?>"
                                    alt="<?= h($s['title']) ?>"
                                    class="artist-song-cover"
                                >

                                <div class="artist-main-copy">
                                    <div class="artist-song-title"><?= h($s['title']) ?></div>
                                    <div class="artist-song-subtitle">Ca khúc được quan tâm nhiều</div>
                                </div>

                                <div class="artist-side-meta">
                                    <strong><?= number_format((int) $s['total']) ?> lượt</strong>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-music"></i>
                        <h3>Chưa có bài hát nổi bật</h3>
                        <p>Hiện chưa đủ dữ liệu để xếp hạng bài hát nổi bật cho nghệ sĩ này.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="artist-panel mt-4">
                <div class="artist-panel-header">
                    <span class="section-kicker">Discography</span>
                    <h4 class="artist-panel-title mb-1">Albums</h4>
                    <p class="artist-panel-subtitle mb-0">
                        Khám phá danh sách album và xem nhanh tracklist từ giao diện preview.
                    </p>
                </div>

                <?php if (!empty($albums)): ?>
                    <div class="album-grid-modern">
                        <?php foreach ($albums as $al): ?>
                            <div class="album-card-modern"
                                 data-bs-toggle="modal"
                                 data-bs-target="#albumPreviewModal"
                                 data-id="<?= (int) $al['album_id'] ?>"
                                 data-name="<?= h($al['album_name']) ?>"
                                 data-year="<?= h((string) ($al['release_year'] ?? '')) ?>"
                                 data-img="<?= h($al['image_path']) ?>"
                                 data-track-count="<?= (int) $al['total_tracks'] ?>">
                                <img src="<?= h($al['image_path']) ?>" alt="<?= h($al['album_name']) ?>" class="album-card-cover">
                                <div class="album-card-copy">
                                    <div class="album-card-title"><?= h($al['album_name']) ?></div>
                                    <div class="album-card-meta">
                                        <?= !empty($al['release_year']) ? h((string) $al['release_year']) : 'Đang cập nhật' ?>
                                        ·
                                        <?= number_format((int) $al['total_tracks']) ?> bài
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-compact-disc"></i>
                        <h3>Chưa có album</h3>
                        <p>Nghệ sĩ này hiện chưa có album nào được cập nhật trên hệ thống.</p>
                    </div>
                <?php endif; ?>
            </section>

            <div id="albumSongsBox" class="mt-4 d-none"></div>
        </div>

        <div class="col-lg-5 mb-4">
            <section class="artist-panel artist-info-panel">
                <div class="artist-panel-header">
                    <span class="section-kicker">Artist Overview</span>
                    <h4 class="artist-panel-title mb-1">Thông tin nghệ sĩ</h4>
                    <p class="artist-panel-subtitle mb-0">
                        Hồ sơ tóm tắt giúp người dùng hiểu nhanh về nghệ sĩ này.
                    </p>
                </div>

                <div class="artist-info-card">
                    <div class="artist-info-item">
                        <span>Tên nghệ sĩ</span>
                        <strong><?= h($artist['artist_name']) ?></strong>
                    </div>

                    <div class="artist-info-item">
                        <span>Số bài hát</span>
                        <strong><?= number_format($totalSongs) ?></strong>
                    </div>

                    <div class="artist-info-item">
                        <span>Số album</span>
                        <strong><?= number_format($totalAlbums) ?></strong>
                    </div>

                    <div class="artist-info-item">
                        <span>Lượt tìm kiếm</span>
                        <strong><?= number_format($totalSearches) ?></strong>
                    </div>
                </div>

                <div class="artist-bio-box">
                    <h5>Tiểu sử / mô tả</h5>
                    <p><?= nl2br(h($artistBioText)) ?></p>
                </div>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="albumPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable album-preview-dialog">
        <div class="modal-content album-preview-modal">
            <div class="modal-header album-preview-header border-0">
                <div class="album-preview-heading-group">
                    <span class="album-preview-kicker">Album Preview</span>
                    <h5 class="modal-title album-preview-title" id="albumModalTitle">Album</h5>
                </div>
                <button type="button" class="btn-close album-preview-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body album-preview-body">
                <div class="album-preview-hero">
                    <img id="albumModalImg"
                         src=""
                         alt=""
                         class="album-preview-image">

                    <div class="album-preview-hero-copy">
                        <div class="album-preview-meta-row">
                            <span class="album-preview-meta-pill" id="albumModalYear">Năm phát hành: —</span>
                            <span class="album-preview-meta-pill album-preview-meta-pill-soft" id="albumModalTrackCount">0 bài hát</span>
                        </div>

                        <p class="album-preview-description mb-0">
                            Xem nhanh danh sách bài hát trong album trước khi chuyển sang trang chi tiết.
                        </p>
                    </div>
                </div>

                <div class="album-preview-section">
                    <div class="album-preview-section-head">
                        <div>
                            <span class="album-preview-section-kicker">Tracks</span>
                            <h6 class="album-preview-section-title mb-0">Danh sách bài hát trong album</h6>
                        </div>
                    </div>

                    <div id="albumModalSongs" class="album-preview-track-wrap">
                        <div class="album-preview-loading">
                            <div class="album-track-skeleton"></div>
                            <div class="album-track-skeleton"></div>
                            <div class="album-track-skeleton"></div>
                            <div class="album-track-skeleton"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer album-preview-footer border-0">
                <a id="albumDetailLink" class="btn btn-primary rounded-pill px-4">Xem chi tiết album</a>
                <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("followBtn")?.addEventListener("click", function() {
    fetch("includes/api_follow_artist.php", {
        method: "POST",
        body: new URLSearchParams({ artist_id: this.dataset.id })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        } else {
            alert("Không thể thực hiện.");
        }
    });
});

const originalFollowButton = document.getElementById("followBtn");
if (originalFollowButton) {
    const button = originalFollowButton.cloneNode(true);
    const followerCountNode = document.getElementById("artistFollowerCount");

    originalFollowButton.replaceWith(button);

    button.addEventListener("click", function() {
        button.disabled = true;

        fetch("includes/api_follow_artist.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: new URLSearchParams({ artist_id: button.dataset.id })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                throw new Error("follow_failed");
            }

            const isFollowing = !!d.following;
            button.dataset.following = isFollowing ? "1" : "0";
            button.classList.toggle("btn-success", !isFollowing);
            button.classList.toggle("btn-secondary", isFollowing);
            button.textContent = isFollowing ? "Đang theo dõi" : "+ Theo dõi";

            if (followerCountNode && typeof d.follower_count !== "undefined") {
                followerCountNode.textContent = new Intl.NumberFormat("vi-VN").format(d.follower_count);
            }
        })
        .catch(() => {
            alert("Không thể thực hiện.");
        })
        .finally(() => {
            button.disabled = false;
        });
    });
}

const albumModalTitle = document.getElementById('albumModalTitle');
const albumModalYear = document.getElementById('albumModalYear');
const albumModalTrackCount = document.getElementById('albumModalTrackCount');
const albumModalImg = document.getElementById('albumModalImg');
const albumDetailLink = document.getElementById('albumDetailLink');
const albumModalSongs = document.getElementById('albumModalSongs');

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderAlbumSongsLoading() {
    albumModalSongs.innerHTML = `
        <div class="album-preview-loading">
            <div class="album-track-skeleton"></div>
            <div class="album-track-skeleton"></div>
            <div class="album-track-skeleton"></div>
            <div class="album-track-skeleton"></div>
        </div>
    `;
}

function renderAlbumSongsFormatted(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html;

    const links = Array.from(temp.querySelectorAll('a'));

    if (!links.length) {
        const fallbackText = temp.textContent.trim();
        albumModalSongs.innerHTML = `
            <div class="album-preview-empty">
                ${escapeHtml(fallbackText || 'Chưa có danh sách bài hát trong album này.')}
            </div>
        `;
        return;
    }

    const items = links.map((link, index) => {
        const href = link.getAttribute('href') || '#';
        const title = (link.textContent || '').trim();

        return `
            <a href="${href}" class="album-preview-track-item">
                <span class="album-preview-track-index">${index + 1}</span>
                <div class="album-preview-track-copy">
                    <div class="album-preview-track-title">${escapeHtml(title)}</div>
                    <div class="album-preview-track-subtitle">Mở trang chi tiết bài hát</div>
                </div>
                <span class="album-preview-track-arrow">
                    <i class="fas fa-chevron-right"></i>
                </span>
            </a>
        `;
    }).join('');

    albumModalTrackCount.textContent = `${links.length} bài hát`;
    albumModalSongs.innerHTML = `<div class="album-preview-track-list">${items}</div>`;
}

document.querySelectorAll(".album-card-modern").forEach(card => {
    card.addEventListener("click", () => {
        const albumId = card.dataset.id;
        const albumName = card.dataset.name || 'Album';
        const albumYear = card.dataset.year || '—';
        const albumImage = card.dataset.img || '';
        const albumTrackCount = parseInt(card.dataset.trackCount || '0', 10);

        albumModalTitle.textContent = albumName;
        albumModalYear.textContent = 'Năm phát hành: ' + albumYear;
        albumModalTrackCount.textContent = `${albumTrackCount} bài hát`;
        albumModalImg.src = albumImage;
        albumModalImg.alt = albumName;

        const detailHref = 'album.php?id=' + encodeURIComponent(albumId);
        albumDetailLink.href = detailHref;

        renderAlbumSongsLoading();

        fetch("includes/api_album_songs.php?album_id=" + encodeURIComponent(albumId))
            .then(r => r.text())
            .then(html => {
                renderAlbumSongsFormatted(html);
            })
            .catch(() => {
                albumModalSongs.innerHTML = `
                    <div class="album-preview-empty">
                        Không thể tải danh sách bài hát.
                    </div>
                `;
            });
    });
});
</script>

<style>
.artist-page,
.artist-page * {
    box-sizing: border-box;
}

.artist-page .row {
    align-items: flex-start;
}

.artist-page p,
.artist-page .artist-panel-subtitle,
.artist-page .content-empty-state p,
.artist-page .artist-bio-box p,
.artist-page .featured-meta {
    margin: 0;
}

.artist-hero {
    overflow: visible;
    padding-bottom: 5rem;
}

.artist-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.78) 0%, rgba(16, 24, 40, 0.52) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
    backdrop-filter: blur(2px);
}

.artist-heading-shell {
    position: relative;
    z-index: 1;
}

.artist-avatar-wrap {
    margin-bottom: 1rem;
}

.artist-hero-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,.9);
    object-fit: cover;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .22);
}

.artist-eyebrow {
    display: inline-block;
    padding: .42rem .8rem;
    margin-bottom: .8rem;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.artist-heading-shell h1 {
    font-size: clamp(2.3rem, 5.2vw, 4rem);
}

.artist-subheading {
    display: block;
    max-width: 760px;
    margin: .7rem auto 0;
    font-size: 1.05rem;
    line-height: 1.7;
}

.artist-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.45rem;
}

.artist-chip {
    display: inline-flex;
    align-items: center;
    padding: .58rem .9rem;
    border-radius: 999px;
    color: #fff;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.14);
    font-family: "Open Sans", sans-serif;
    font-size: .86rem;
    font-weight: 700;
}

.artist-follow-btn {
    min-width: 150px;
    padding: .75rem 1rem;
    border-radius: 999px;
    font-weight: 700;
}

.artist-summary-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2.15rem;
    margin-bottom: 1.35rem;
    position: relative;
    z-index: 2;
}

.artist-summary-stat,
.artist-panel,
.featured-artist-song-card,
.artist-song-card,
.album-card-modern,
.artist-info-card,
.artist-bio-box,
.content-empty-state,
.album-preview-modal {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.artist-summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.artist-summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.artist-summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.artist-summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.artist-panel {
    height: auto;
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.artist-panel-header {
    margin-bottom: 1rem;
}

.section-kicker {
    display: inline-flex;
    margin-bottom: .45rem;
    padding: .36rem .68rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.artist-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.artist-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.featured-artist-song-card {
    display: grid;
    grid-template-columns: 140px minmax(0, 1fr);
    gap: 1rem;
    align-items: center;
    padding: .95rem;
    border-radius: 22px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: .95rem;
}

.featured-artist-song-card:hover,
.artist-song-card:hover,
.album-card-modern:hover {
    text-decoration: none;
    color: inherit;
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
}

.featured-artist-song-cover {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    object-fit: cover;
}

.featured-artist-song-copy h2 {
    margin-bottom: .42rem;
    color: #162033;
    font-size: 1.35rem;
    line-height: 1.35;
    font-weight: 800;
}

.featured-label {
    display: inline-flex;
    margin-bottom: .65rem;
    padding: .36rem .72rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.featured-meta {
    color: #6f7c91;
    font-family: "Open Sans", sans-serif;
    font-size: .93rem;
    line-height: 1.55;
}

.artist-song-list {
    display: grid;
    gap: .78rem;
}

.artist-song-card {
    display: grid;
    grid-template-columns: auto 68px minmax(0, 1fr) auto;
    gap: .8rem;
    align-items: center;
    padding: .82rem;
    border-radius: 18px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    transition: transform .2s ease, box-shadow .2s ease;
}

.artist-rank-badge {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: "Open Sans", sans-serif;
    font-size: .9rem;
    font-weight: 800;
}

.artist-song-cover {
    width: 100%;
    height: 68px;
    border-radius: 14px;
    object-fit: cover;
}

.artist-song-title {
    color: #162033;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.45;
}

.artist-song-subtitle {
    margin-top: .18rem;
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
}

.artist-side-meta {
    min-width: 88px;
    text-align: right;
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
    color: #6f7c91;
}

.artist-side-meta strong {
    display: block;
    color: #162033;
}

.album-grid-modern {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .9rem;
}

.album-card-modern {
    border-radius: 20px;
    padding: .78rem;
    border: 1px solid rgba(16, 24, 40, .06);
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
}

.album-card-cover {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: .75rem;
}

.album-card-title {
    color: #162033;
    font-size: .98rem;
    font-weight: 700;
    line-height: 1.45;
}

.album-card-meta {
    margin-top: .25rem;
    color: #6f7c91;
    font-size: .88rem;
    line-height: 1.5;
}

.artist-info-panel {
    position: sticky;
    top: 110px;
}

.artist-info-card {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: 1rem;
}

.artist-info-item {
    padding: .85rem 0;
    border-bottom: 1px solid rgba(16, 24, 40, .06);
}

.artist-info-item:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.artist-info-item span {
    display: block;
    margin-bottom: .2rem;
    color: #6f7c91;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-family: "Open Sans", sans-serif;
}

.artist-info-item strong {
    color: #162033;
    font-size: 1rem;
    line-height: 1.5;
}

.artist-bio-box {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.artist-bio-box h5 {
    margin-bottom: .75rem;
    color: #162033;
    font-size: 1.1rem;
    font-weight: 800;
}

.artist-bio-box p {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.8;
    font-family: "Open Sans", sans-serif;
}

.album-preview-dialog {
    max-width: 900px;
}

.album-preview-modal {
    border: 0;
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(10, 20, 40, .22);
}

.album-preview-header {
    padding: 1.15rem 1.25rem .6rem;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.album-preview-heading-group {
    min-width: 0;
}

.album-preview-kicker {
    display: inline-flex;
    margin-bottom: .35rem;
    padding: .35rem .7rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    font-family: "Open Sans", sans-serif;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.album-preview-title {
    margin: 0;
    color: #162033;
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.35;
    word-break: break-word;
}

.album-preview-close {
    box-shadow: none !important;
    opacity: .7;
}

.album-preview-body {
    padding: .4rem 1.25rem 1.2rem;
}

.album-preview-hero {
    display: grid;
    grid-template-columns: 170px minmax(0, 1fr);
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    border-radius: 22px;
    background: #f8fafc;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: 1rem;
}

.album-preview-image {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 18px;
    box-shadow: 0 16px 38px rgba(10, 20, 40, .12);
}

.album-preview-hero-copy {
    min-width: 0;
}

.album-preview-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: .6rem;
    margin-bottom: .8rem;
}

.album-preview-meta-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .48rem .8rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .10);
    color: #0d6efd;
    font-size: .82rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.album-preview-meta-pill-soft {
    background: #eef3fb;
    color: #536277;
}

.album-preview-description {
    color: #6f7c91;
    font-size: .94rem;
    line-height: 1.7;
    font-family: "Open Sans", sans-serif;
}

.album-preview-actions-top {
    margin-top: 1rem;
}

.album-preview-section {
    border: 1px solid rgba(16, 24, 40, .06);
    border-radius: 22px;
    background: #fff;
    overflow: hidden;
}

.album-preview-section-head {
    padding: 1rem 1rem .75rem;
    border-bottom: 1px solid rgba(16, 24, 40, .06);
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
}

.album-preview-section-kicker {
    display: inline-block;
    margin-bottom: .3rem;
    color: #6f7c91;
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    font-family: "Open Sans", sans-serif;
}

.album-preview-section-title {
    color: #162033;
    font-size: 1.05rem;
    font-weight: 800;
}

.album-preview-track-wrap {
    padding: .85rem;
    max-height: 420px;
    overflow-y: auto;
}

.album-preview-track-list {
    display: grid;
    gap: .65rem;
}

.album-preview-track-item {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) 18px;
    gap: .75rem;
    align-items: center;
    padding: .8rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
    background: #fff;
    color: inherit;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.album-preview-track-item:hover {
    color: inherit;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 14px 30px rgba(10, 20, 40, .08);
    border-color: rgba(13, 110, 253, .18);
}

.album-preview-track-index {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .88rem;
    font-weight: 800;
    font-family: "Open Sans", sans-serif;
}

.album-preview-track-copy {
    min-width: 0;
}

.album-preview-track-title {
    color: #162033;
    font-size: .98rem;
    font-weight: 700;
    line-height: 1.45;
    word-break: break-word;
}

.album-preview-track-subtitle {
    margin-top: .14rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.45;
    font-family: "Open Sans", sans-serif;
}

.album-preview-track-arrow {
    color: #9aa4b2;
    font-size: .88rem;
    text-align: right;
}

.album-preview-loading {
    display: grid;
    gap: .65rem;
}

.album-track-skeleton {
    height: 62px;
    border-radius: 18px;
    background: linear-gradient(90deg, #eef2f7 25%, #f7f9fc 50%, #eef2f7 75%);
    background-size: 200% 100%;
    animation: albumShimmer 1.35s infinite linear;
}

@keyframes albumShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.album-preview-empty {
    padding: 1rem;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid rgba(16, 24, 40, .06);
    color: #6f7c91;
    font-size: .94rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
    text-align: center;
}

.album-preview-footer {
    padding: .35rem 1.25rem 1.2rem;
    gap: .65rem;
}

.content-empty-state {
    padding: 2.2rem 1.1rem;
    border-radius: 22px;
    text-align: center;
    border: 1px solid rgba(16, 24, 40, .06);
}

.content-empty-state i {
    margin-bottom: .8rem;
    font-size: 1.6rem;
    color: #0d6efd;
}

.content-empty-state h3 {
    font-size: 1.3rem;
    color: #162033;
}

.content-empty-state p {
    max-width: 520px;
    margin: 0 auto;
    font-size: .95rem;
    color: #6f7c91;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.footer-nln {
    position: relative;
    z-index: 2;
    margin-top: 2rem;
    background: #fff;
}

@media (max-width: 991.98px) {
    .artist-summary-bar {
        grid-template-columns: 1fr;
        margin-top: 0;
    }

    .featured-artist-song-card {
        grid-template-columns: 1fr;
    }

    .album-grid-modern {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .artist-info-panel {
        position: static;
    }

    .album-preview-hero {
        grid-template-columns: 1fr;
    }

    .album-preview-image {
        max-width: 220px;
    }
}

@media (max-width: 767.98px) {
    .artist-hero {
        padding-bottom: 5rem;
    }

    .artist-hero-chips {
        justify-content: flex-start;
    }

    .artist-song-card {
        grid-template-columns: 1fr;
    }

    .artist-side-meta {
        text-align: left;
    }

    .album-grid-modern {
        grid-template-columns: 1fr;
    }

    .album-preview-track-item {
        grid-template-columns: 38px minmax(0, 1fr);
    }

    .album-preview-track-arrow {
        display: none;
    }

    .album-preview-footer {
        justify-content: stretch;
    }

    .album-preview-footer .btn,
    .album-preview-actions-top .btn {
        width: 100%;
    }
}
</style>

<?php include('includes/footer.php'); ?>
