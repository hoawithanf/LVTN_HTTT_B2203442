<?php
include('includes/session.php');
include('includes/header.php');
include('includes/navbar.php');
include('includes/database.php');
require_once __DIR__ . '/../config/album_rating_helpers.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nln_public_asset_exists($relativePath)
{
    $relativePath = ltrim((string) $relativePath, '/');
    return is_file(__DIR__ . '/' . $relativePath);
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

    foreach (['assets/img/default.jpg', 'assets/img/home-bg.jpg'] as $fallback) {
        if (nln_public_asset_exists($fallback)) {
            return $fallback;
        }
    }

    return 'assets/img/home-bg.jpg';
}

function nln_format_date($dateTime)
{
    if (empty($dateTime)) {
        return '';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

$album_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($album_id <= 0) {
    ?>
    <div class="container px-4 px-lg-5 py-5">
        <div class="page-empty-state">
            <i class="fas fa-compact-disc"></i>
            <h3>Album không hợp lệ</h3>
            <p>Không thể tải thông tin album vì tham số không hợp lệ.</p>
            <a href="index.php" class="btn btn-primary mt-2">Quay về trang chủ</a>
        </div>
    </div>
    <?php
    include('includes/footer.php');
    exit;
}

$stmtAlbum = $conn->prepare("
    SELECT al.*, ar.artist_name, ar.artist_id
    FROM albums al
    INNER JOIN artists ar ON ar.artist_id = al.artist_id
    WHERE al.album_id = ?
    LIMIT 1
");
$stmtAlbum->bind_param("i", $album_id);
$stmtAlbum->execute();
$album = $stmtAlbum->get_result()->fetch_assoc();
$stmtAlbum->close();

if (!$album) {
    ?>
    <div class="container px-4 px-lg-5 py-5">
        <div class="page-empty-state">
            <i class="fas fa-compact-disc"></i>
            <h3>Album không tồn tại</h3>
            <p>Album bạn đang tìm không tồn tại hoặc đã bị xóa khỏi hệ thống.</p>
            <a href="index.php" class="btn btn-primary mt-2">Quay về trang chủ</a>
        </div>
    </div>
    <?php
    include('includes/footer.php');
    exit;
}

$cover = nln_public_album_cover_path($album['cover_image'] ?? null);

$songItems = [];
$stmtSongs = $conn->prepare("
    SELECT
        s.song_id,
        s.title,
        s.release_date,
        COUNT(sl.log_id) AS search_count
    FROM songs s
    LEFT JOIN search_logs sl ON sl.song_id = s.song_id
    WHERE s.album_id = ?
    GROUP BY s.song_id, s.title, s.release_date
    ORDER BY s.release_date ASC, s.title ASC
");
$stmtSongs->bind_param("i", $album_id);
$stmtSongs->execute();
$songsResult = $stmtSongs->get_result();
while ($row = $songsResult->fetch_assoc()) {
    $row['search_count'] = (int) ($row['search_count'] ?? 0);
    $songItems[] = $row;
}
$stmtSongs->close();

$totalViews = 0;
$stmtViews = $conn->prepare("
    SELECT COUNT(sl.log_id) AS total
    FROM songs s
    LEFT JOIN search_logs sl ON sl.song_id = s.song_id
    WHERE s.album_id = ?
");
$stmtViews->bind_param("i", $album_id);
$stmtViews->execute();
$totalViews = (int) ($stmtViews->get_result()->fetch_assoc()['total'] ?? 0);
$stmtViews->close();

$isFav = false;
if (isset($_SESSION['user_id'])) {
    $chk = $conn->prepare("SELECT 1 FROM album_favorites WHERE user_id = ? AND album_id = ? LIMIT 1");
    $chk->bind_param("ii", $_SESSION['user_id'], $album_id);
    $chk->execute();
    $isFav = $chk->get_result()->num_rows > 0;
    $chk->close();
}

$totalTracks = count($songItems);
$albumDescription = trim((string) ($album['description'] ?? ''));
$ratingSummary = nln_album_rating_summary($conn, $album_id);
$userRating = isset($_SESSION['user_id'])
    ? nln_album_user_rating($conn, $album_id, (int) $_SESSION['user_id'])
    : null;
?>

<header class="masthead album-hero" style="background-image: url('<?= h($cover) ?>')">
    <div class="album-hero-layer"></div>
    <div class="container px-4 px-lg-5 album-hero-container">
        <div class="album-hero-content text-center text-white">
            <img src="<?= h($cover) ?>" alt="<?= h($album['album_name']) ?>" class="album-hero-cover">
            <span class="album-kicker">Album Detail</span>
            <h1><?= h($album['album_name']) ?></h1>
            <p class="album-hero-subtitle">
                <?= h($album['artist_name']) ?>
                <?= !empty($album['release_year']) ? ' · ' . h((string) $album['release_year']) : '' ?>
            </p>

            <div class="album-hero-stats">
                <span><i class="fas fa-music me-2"></i><?= number_format($totalTracks) ?> bài hát</span>
                <span><i class="fas fa-search me-2"></i><?= number_format($totalViews) ?> lượt tìm kiếm</span>
                <span><i class="fas fa-user me-2"></i><?= h($album['artist_name']) ?></span>
            </div>
        </div>
    </div>
</header>

<div class="album-page pb-4">
    <div class="container px-4 px-lg-5 mt-4">
        <div class="album-stat-grid">
            <div class="album-stat-card">
                <span>Số bài hát</span>
                <strong><?= number_format($totalTracks) ?></strong>
                <small>Tracklist hiện có trong album</small>
            </div>
            <div class="album-stat-card">
                <span>Tổng lượt tìm</span>
                <strong><?= number_format($totalViews) ?></strong>
                <small>Mức độ quan tâm của người dùng</small>
            </div>
            <div class="album-stat-card">
                <span>Năm phát hành</span>
                <strong><?= !empty($album['release_year']) ? h((string) $album['release_year']) : '—' ?></strong>
                <small>Thông tin phát hành album</small>
            </div>
        </div>

        <a href="artist.php?id=<?= (int) $album['artist_id'] ?>" class="album-back-link">
            <i class="fas fa-arrow-left me-2"></i>Quay lại nghệ sĩ
        </a>

        <div class="row g-4 align-items-start">
            <div class="col-lg-5 mb-4">
                <section class="album-panel album-info-panel">
                    <div class="album-info-card">
                        <div class="album-info-top">
                            <img src="<?= h($cover) ?>" alt="<?= h($album['album_name']) ?>" class="album-info-cover">
                            <div class="album-info-copy">
                                <div class="album-info-heading-row">
                                    <div>
                                        <h2 class="album-info-title"><?= h($album['album_name']) ?></h2>
                                        <div class="album-info-meta">
                                            <a href="artist.php?id=<?= (int) $album['artist_id'] ?>"><?= h($album['artist_name']) ?></a>
                                            <?= !empty($album['release_year']) ? ' · ' . h((string) $album['release_year']) : '' ?>
                                        </div>
                                    </div>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button id="favAlbumBtn"
                                                type="button"
                                                data-id="<?= $album_id ?>"
                                                class="btn album-favorite-btn p-0 border-0 bg-transparent ms-2<?= $isFav ? ' is-favorited' : '' ?>"
                                                aria-pressed="<?= $isFav ? 'true' : 'false' ?>"
                                                title="Yêu thích album">
                                            <i id="favIcon"
                                               class="<?= $isFav ? 'fas' : 'far' ?> fa-heart album-favorite-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="album-info-stats">
                            <div class="album-info-item">
                                <span>Số bài hát</span>
                                <strong><?= number_format($totalTracks) ?></strong>
                            </div>
                            <div class="album-info-item">
                                <span>Lượt tìm kiếm</span>
                                <strong><?= number_format($totalViews) ?></strong>
                            </div>
                            <div class="album-info-item">
                                <span>Năm phát hành</span>
                                <strong><?= !empty($album['release_year']) ? h((string) $album['release_year']) : 'Đang cập nhật' ?></strong>
                            </div>
                        </div>

                        <div class="album-description-box">
                            <h3>Mô tả album</h3>
                            <p><?= nl2br(h($albumDescription !== '' ? $albumDescription : 'Album chưa có mô tả.')) ?></p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-lg-7 mb-4">
                <section class="album-panel">
                    <div class="panel-head">
                        <span class="panel-kicker">Tracklist</span>
                        <h2>Danh sách bài hát</h2>
                        <p>Nghe nhìn tổng quan tracklist, lượt tìm kiếm và mở nhanh bài hát chi tiết.</p>
                    </div>

                    <?php if (!empty($songItems)): ?>
                        <div class="album-track-list">
                            <?php foreach ($songItems as $index => $song): ?>
                                <div class="album-track-card">
                                    <div class="album-track-rank"><?= $index + 1 ?></div>
                                    <div class="album-track-copy">
                                        <div class="album-track-title">
                                            <a href="post.php?id=<?= (int) $song['song_id'] ?>"><?= h($song['title']) ?></a>
                                        </div>
                                        <div class="album-track-meta">
                                            <span><i class="fas fa-search me-1"></i><?= number_format((int) $song['search_count']) ?> lượt tìm</span>
                                            <?php if (!empty($song['release_date'])): ?>
                                                <span><i class="fas fa-calendar-alt me-1"></i><?= h(nln_format_date($song['release_date'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="album-track-actions">
                                        <a target="_blank"
                                           href="https://www.youtube.com/results?search_query=<?= urlencode($song['title'] . ' ' . $album['artist_name'] . ' lyrics video reaction') ?>"
                                           class="btn btn-sm btn-outline-danger">▶ YouTube</a>
                                        <a href="post.php?id=<?= (int) $song['song_id'] ?>" class="btn btn-sm btn-outline-primary">ℹ Chi tiết</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="page-empty-state small-state">
                            <i class="fas fa-music"></i>
                            <h3>Album chưa có bài hát</h3>
                            <p>Hiện chưa có bài hát nào được cập nhật trong album này.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="album-panel mt-4">
                    <div class="album-rating-card text-center">
                        <span class="panel-kicker">Community Feeling</span>
                        <h2 class="album-rating-title">Đánh giá album</h2>
                        <p class="album-rating-subtitle">Chọn số sao để thể hiện cảm nhận của bạn về album này.</p>
                        <div class="album-rating-overview">
                            <div class="album-rating-metric">
                                <strong id="albumAverageRating"><?= number_format((float) $ratingSummary['average_rating'], 1) ?></strong>
                                <span>điểm trung bình</span>
                            </div>
                            <div class="album-rating-metric">
                                <strong id="albumRatingCount"><?= number_format((int) $ratingSummary['rating_count']) ?></strong>
                                <span>lượt đánh giá</span>
                            </div>
                        </div>
                        <div id="ratingStars"
                             class="album-rating-stars<?= isset($_SESSION['user_id']) ? '' : ' is-disabled' ?>"
                             data-album-id="<?= $album_id ?>"
                             data-current-rating="<?= (int) ($userRating ?? 0) ?>"
                             data-can-rate="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="rating-star-btn" data-value="<?= $i ?>" aria-label="Đánh giá <?= $i ?> sao">
                                    <i class="<?= ($userRating !== null && $i <= $userRating) ? 'fas' : 'far' ?> fa-star rating-star-icon"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <p class="album-rating-note">Tính năng này hiện đang ở chế độ giao diện minh họa.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
const favBtn = document.getElementById('favAlbumBtn');
const favIcon = document.getElementById('favIcon');

function syncAlbumFavoriteState(isFavorited, pulse = false) {
    if (!favBtn || !favIcon) {
        return;
    }

    favBtn.classList.toggle('is-favorited', isFavorited);
    favBtn.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');
    favIcon.classList.toggle('fas', isFavorited);
    favIcon.classList.toggle('far', !isFavorited);

    if (pulse) {
        favBtn.classList.remove('is-bursting');
        void favBtn.offsetWidth;
        favBtn.classList.add('is-bursting');
    }
}

syncAlbumFavoriteState(favBtn?.classList.contains('is-favorited') ?? false);

favBtn?.addEventListener('click', function (event) {
    event.preventDefault();

    if (favBtn.disabled) {
        return;
    }

    const previousState = favBtn.classList.contains('is-favorited');
    const nextState = !previousState;

    favBtn.disabled = true;
    syncAlbumFavoriteState(nextState, true);

    fetch('includes/api_favorite_album.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams({ album_id: this.dataset.id })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            throw new Error('favorite_failed');
        }

        syncAlbumFavoriteState(!!d.favorited, true);
    })
    .catch(() => {
        syncAlbumFavoriteState(previousState);
    })
    .finally(() => {
        favBtn.disabled = false;
    });
});

const ratingWrap = document.getElementById('ratingStars');
const ratingMessage = document.querySelector('.album-rating-card .album-rating-note');
const averageRatingNode = document.getElementById('albumAverageRating');
const ratingCountNode = document.getElementById('albumRatingCount');

function renderAlbumStars(value) {
    ratingWrap?.querySelectorAll('.rating-star-btn').forEach((button) => {
        const icon = button.querySelector('.rating-star-icon');
        const starValue = Number(button.dataset.value || 0);
        const active = starValue <= value;
        icon.classList.toggle('fas', active);
        icon.classList.toggle('far', !active);
        icon.classList.toggle('is-active', active);
    });
}

function setAlbumRatingMessage(message, type = '') {
    if (!ratingMessage) {
        return;
    }

    ratingMessage.textContent = message;
    ratingMessage.classList.remove('is-success', 'is-error');
    if (type) {
        ratingMessage.classList.add(type);
    }
}

if (ratingWrap) {
    let selectedRating = Number(ratingWrap.dataset.currentRating || 0);
    const canRate = ratingWrap.dataset.canRate === '1';

    if (!canRate) {
        setAlbumRatingMessage('Đăng nhập để gửi đánh giá và đồng bộ cảm nhận của bạn.');
    } else if (selectedRating > 0) {
        setAlbumRatingMessage(`Bạn đã chấm ${selectedRating} sao cho album này.`);
    } else {
        setAlbumRatingMessage('Chưa có đánh giá của bạn. Hãy chọn số sao phù hợp.');
    }

    renderAlbumStars(selectedRating);

    ratingWrap.querySelectorAll('.rating-star-btn').forEach((button) => {
        button.addEventListener('mouseenter', () => {
            if (!canRate) {
                return;
            }
            renderAlbumStars(Number(button.dataset.value || 0));
        });

        button.addEventListener('focus', () => {
            if (!canRate) {
                return;
            }
            renderAlbumStars(Number(button.dataset.value || 0));
        });

        button.addEventListener('click', () => {
            if (!canRate) {
                return;
            }

            const value = Number(button.dataset.value || 0);
            setAlbumRatingMessage('Đang lưu đánh giá của bạn...');

            fetch('includes/api_rate_album.php', {
                method: 'POST',
                body: new URLSearchParams({
                    album_id: ratingWrap.dataset.albumId,
                    rating: value
                })
            })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) {
                        setAlbumRatingMessage(data.error || 'Không thể lưu đánh giá lúc này.', 'is-error');
                        renderAlbumStars(selectedRating);
                        return;
                    }

                    selectedRating = value;
                    ratingWrap.dataset.currentRating = String(value);
                    renderAlbumStars(selectedRating);

                    if (averageRatingNode) {
                        averageRatingNode.textContent = data.average_rating;
                    }
                    if (ratingCountNode) {
                        ratingCountNode.textContent = data.rating_count;
                    }

                    setAlbumRatingMessage(`Bạn đã chấm ${value} sao cho album này.`, 'is-success');
                })
                .catch(() => {
                    setAlbumRatingMessage('Không thể lưu đánh giá lúc này.', 'is-error');
                    renderAlbumStars(selectedRating);
                });
        });
    });

    ratingWrap.addEventListener('mouseleave', () => {
        renderAlbumStars(selectedRating);
    });
}
</script>

<style>
.album-page,
.album-page p,
.album-panel p,
.page-empty-state p {
    margin-top: 0;
}

.album-page {
    background: #fff;
}

.album-hero {
    padding-top: calc(5.5rem + 57px);
    padding-bottom: 4.5rem;
    margin-bottom: 0;
    overflow: hidden;
}

.album-hero::before {
    display: none;
}

.album-hero-layer {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, .82) 0%, rgba(15, 23, 42, .58) 100%);
}

.album-hero-container {
    position: relative;
    z-index: 2;
}

.album-hero-content {
    max-width: 860px;
    margin: 0 auto;
}

.album-hero-cover {
    width: 168px;
    height: 168px;
    object-fit: cover;
    border-radius: 20px;
    border: 4px solid rgba(255,255,255,.95);
    box-shadow: 0 18px 40px rgba(0,0,0,.25);
    margin-bottom: 1rem;
}

.album-kicker,
.panel-kicker {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .42rem .82rem;
    border-radius: 999px;
    font-family: 'Open Sans', sans-serif;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.album-kicker {
    color: #fff;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
    margin-bottom: .85rem;
}

.album-hero-content h1 {
    margin-bottom: .75rem;
    font-size: clamp(2.2rem, 5vw, 3.9rem);
    line-height: 1.15;
}

.album-hero-subtitle {
    color: rgba(255,255,255,.92);
    font-family: 'Open Sans', sans-serif;
    font-size: 1rem;
    line-height: 1.75;
    margin-bottom: 0;
}

.album-hero-stats {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .7rem;
    margin-top: 1.15rem;
}

.album-hero-stats span {
    display: inline-flex;
    align-items: center;
    padding: .62rem .95rem;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
    color: #fff;
    font-family: 'Open Sans', sans-serif;
    font-size: .9rem;
    font-weight: 700;
}

.album-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .9rem;
    margin-bottom: 1.1rem;
}

.album-stat-card,
.album-panel,
.album-info-card,
.album-track-card,
.album-rating-card,
.page-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(15, 23, 42, .08);
}

.album-stat-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 18px;
    padding: 1rem 1.05rem;
}

.album-stat-card span,
.album-info-item span {
    display: block;
    margin-bottom: .24rem;
    color: #7d8795;
    font-family: 'Open Sans', sans-serif;
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.album-stat-card strong,
.album-info-item strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.album-stat-card small {
    display: block;
    margin-top: .28rem;
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .88rem;
    line-height: 1.5;
}

.album-back-link {
    display: inline-flex;
    align-items: center;
    margin-bottom: 1rem;
    color: #536277;
    text-decoration: none;
    font-family: 'Open Sans', sans-serif;
    font-size: .95rem;
    font-weight: 700;
}

.album-back-link:hover {
    color: #0d6efd;
    text-decoration: none;
}

.album-panel {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 24px;
    padding: 1.15rem;
    overflow: hidden;
}

.album-info-panel {
    position: relative;
}

.album-info-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 20px;
    padding: 1rem;
}

.album-info-top {
    display: grid;
    grid-template-columns: 132px minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
}

.album-info-cover {
    width: 132px;
    height: 132px;
    object-fit: cover;
    border-radius: 16px;
}

.album-info-heading-row {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: .75rem;
}

.album-info-title {
    margin: 0 0 .35rem;
    color: #162033;
    font-size: 1.45rem;
    line-height: 1.3;
}

.album-info-meta {
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .95rem;
    line-height: 1.5;
}

.album-info-meta a {
    color: #0d6efd;
    text-decoration: none;
}

.album-favorite-btn {
    position: relative;
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #9aa4b2;
    transition: transform .18s ease, background-color .18s ease, box-shadow .18s ease, color .18s ease;
}

.album-favorite-btn::before {
    content: '';
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    background: rgba(220, 38, 38, .12);
    transform: scale(.5);
    opacity: 0;
    transition: transform .22s ease, opacity .22s ease;
}

.album-favorite-btn:hover,
.album-favorite-btn:focus-visible {
    outline: none;
    transform: translateY(-1px) scale(1.03);
    background: rgba(15, 23, 42, .04);
}

.album-favorite-btn.is-favorited {
    color: #dc2626;
}

.album-favorite-btn.is-favorited::before {
    transform: scale(1);
    opacity: 1;
}

.album-favorite-btn:disabled {
    cursor: default;
    opacity: .88;
}

.album-favorite-icon {
    position: relative;
    z-index: 1;
    font-size: 1.8rem;
    line-height: 1;
    transition: transform .18s ease, color .18s ease;
}

.album-favorite-btn.is-favorited .album-favorite-icon {
    color: #dc2626;
}

.album-favorite-btn.is-bursting .album-favorite-icon {
    animation: albumHeartPulse .45s cubic-bezier(.2, .9, .25, 1.25);
}

.album-favorite-btn.is-bursting::before {
    animation: albumHeartHalo .45s ease;
}

@keyframes albumHeartPulse {
    0% { transform: scale(1); }
    40% { transform: scale(1.28); }
    100% { transform: scale(1); }
}

@keyframes albumHeartHalo {
    0% { transform: scale(.55); opacity: .12; }
    50% { transform: scale(1.1); opacity: .22; }
    100% { transform: scale(1); opacity: 1; }
}

.album-info-stats {
    display: grid;
    gap: .75rem;
    margin-bottom: 1rem;
}

.album-info-item,
.album-description-box {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 18px;
    padding: 1rem;
}

.album-description-box h3 {
    margin: 0 0 .75rem;
    color: #162033;
    font-size: 1.08rem;
    line-height: 1.3;
}

.album-description-box p,
.panel-head p,
.album-rating-subtitle,
.album-rating-note {
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .96rem;
    line-height: 1.7;
    margin-bottom: 0;
}

.panel-head {
    margin-bottom: 1rem;
}

.panel-kicker {
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
}

.panel-head h2,
.album-rating-title {
    margin: .4rem 0 .4rem;
    color: #162033;
    font-size: 1.9rem;
    line-height: 1.2;
}

.album-track-list {
    display: grid;
    gap: .8rem;
}

.album-track-card {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) auto;
    gap: .85rem;
    align-items: center;
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 18px;
    padding: .92rem;
}

.album-track-rank {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Open Sans', sans-serif;
    font-size: .95rem;
    font-weight: 800;
}

.album-track-title a {
    color: #162033;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.45;
}

.album-track-title a:hover {
    color: #0d6efd;
}

.album-track-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .95rem;
    margin-top: .28rem;
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .88rem;
    line-height: 1.5;
}

.album-track-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    justify-content: flex-end;
}

.album-track-actions .btn {
    border-radius: 999px;
    padding-left: .9rem;
    padding-right: .9rem;
    font-weight: 700;
}

.album-rating-card {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 22px;
    padding: 1.3rem 1rem;
}

.album-rating-overview {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
    margin: 1rem 0 .95rem;
}

.album-rating-metric {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 18px;
    padding: .9rem;
    background: linear-gradient(180deg, rgba(13, 110, 253, .06), rgba(13, 110, 253, .02));
}

.album-rating-metric strong {
    display: block;
    color: #162033;
    font-size: 1.65rem;
    line-height: 1.1;
}

.album-rating-metric span {
    display: block;
    margin-top: .2rem;
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .88rem;
    font-weight: 700;
}

.album-rating-stars {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: .45rem;
    margin: .9rem 0 .8rem;
}

.rating-star-btn {
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 18px;
    background: transparent;
    color: #c5ceda;
    font-size: 1.7rem;
    transition: transform .18s ease, color .18s ease, background-color .18s ease, box-shadow .18s ease;
}

.rating-star-btn:hover,
.rating-star-btn:focus-visible {
    outline: none;
    transform: translateY(-2px) scale(1.04);
    background: rgba(255, 193, 7, .12);
    box-shadow: 0 12px 24px rgba(255, 193, 7, .18);
}

.rating-star-icon.is-active {
    color: #f4b400;
}

.album-rating-stars.is-disabled .rating-star-btn {
    cursor: default;
    opacity: .72;
}

.album-rating-note.is-success {
    color: #0f7b42;
}

.album-rating-note.is-error {
    color: #c0392b;
}

.album-rating-note {
    margin-top: .4rem;
}

.page-empty-state {
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 22px;
    padding: 2.2rem 1.2rem;
    text-align: center;
}

.small-state {
    padding: 2rem 1rem;
}

.page-empty-state i {
    display: block;
    margin-bottom: .8rem;
    color: #0d6efd;
    font-size: 1.7rem;
}

.page-empty-state h3 {
    margin: 0 0 .55rem;
    color: #162033;
    font-size: 1.35rem;
    line-height: 1.3;
}

.page-empty-state p {
    color: #6f7c91;
    font-family: 'Open Sans', sans-serif;
    font-size: .96rem;
    line-height: 1.65;
    margin-bottom: 0;
}

.footer-nln {
    margin-top: 1.5rem;
    position: relative;
    z-index: 2;
}

@media (max-width: 991.98px) {
    .album-stat-grid {
        grid-template-columns: 1fr;
    }

    .album-track-card {
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .album-track-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }

    .album-rating-overview {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .album-hero {
        padding-top: calc(6rem + 57px);
        padding-bottom: 3.5rem;
    }

    .album-hero-stats {
        justify-content: flex-start;
    }

    .album-info-top {
        grid-template-columns: 1fr;
    }

    .album-info-cover {
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
    }

    .rating-star-btn {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        font-size: 1.45rem;
    }
}
</style>

<?php include('includes/footer.php'); ?>
