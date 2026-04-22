<?php
// public/profile.php
include('includes/session.php');
include('includes/database.php');
include('includes/header.php');
include('includes/navbar.php');
require_once __DIR__ . '/../config/song_helpers.php';
require_once __DIR__ . '/../config/recommendation_helpers.php';
require_once __DIR__ . '/../config/playlist_helpers.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* ================= HELPERS ================= */
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
        'assets/img/default-avatar.png',
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

function nln_format_date($dateTime, $withTime = false)
{
    if (empty($dateTime)) {
        return '';
    }

    $timestamp = strtotime((string) $dateTime);
    if ($timestamp === false) {
        return '';
    }

    return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
}

function nln_profile_url(array $overrides = [])
{
    $base = [
        'q_song' => $_GET['q_song'] ?? '',
        'q_artist' => $_GET['q_artist'] ?? '',
        'from' => $_GET['from'] ?? '',
        'to' => $_GET['to'] ?? '',
    ];

    $query = array_merge($base, $overrides);
    $query = array_filter($query, static function ($value) {
        return $value !== '' && $value !== null;
    });

    $queryString = http_build_query($query);
    return 'profile.php' . ($queryString !== '' ? '?' . $queryString : '');
}

/* ================= FILTER INPUT ================= */
$qSong   = trim($_GET['q_song'] ?? '');
$qArtist = trim($_GET['q_artist'] ?? '');
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';

/* ================= USER INFO ================= */
$stmtUser = $conn->prepare("
    SELECT username, full_name, email, role, created_at, avatar
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$displayName = trim((string) ($user['full_name'] ?? '')) !== ''
    ? $user['full_name']
    : $user['username'];

$userAvatar = nln_public_artist_avatar_path($user['avatar'] ?? null);

/* ================= SEARCH HISTORY ================= */
$where   = ["user_id = ?"];
$params  = [$user_id];
$types   = "i";

if ($qSong !== '') {
    $where[]  = "song_title LIKE ?";
    $params[] = "%{$qSong}%";
    $types   .= "s";
}

if ($qArtist !== '') {
    $where[]  = "artist_name LIKE ?";
    $params[] = "%{$qArtist}%";
    $types   .= "s";
}

if ($from !== '') {
    $where[]  = "DATE(search_time) >= ?";
    $params[] = $from;
    $types   .= "s";
}

if ($to !== '') {
    $where[]  = "DATE(search_time) <= ?";
    $params[] = $to;
    $types   .= "s";
}

$whereSQL = implode(" AND ", $where);

$stmtHistory = $conn->prepare("
    SELECT song_id, song_title, artist_name, cover_image, search_time
    FROM search_logs
    WHERE $whereSQL
    ORDER BY search_time DESC
    LIMIT 100
");
$stmtHistory->bind_param($types, ...$params);
$stmtHistory->execute();
$rsHistory = $stmtHistory->get_result();

$history = [];
$lastSongId = null;

while ($row = $rsHistory->fetch_assoc()) {
    if ((int) $row['song_id'] !== (int) $lastSongId) {
        $row['cover_path'] = nln_public_song_cover_path($row['cover_image'] ?? null, null);
        $history[] = $row;
        $lastSongId = (int) $row['song_id'];
    }

    if (count($history) >= 10) {
        break;
    }
}
$stmtHistory->close();

/* ================= STATS ================= */
$stmtStat = $conn->prepare("
    SELECT
        COUNT(*) AS total_search,
        COUNT(DISTINCT song_id) AS distinct_song
    FROM search_logs
    WHERE user_id = ?
");
$stmtStat->bind_param("i", $user_id);
$stmtStat->execute();
$stat = $stmtStat->get_result()->fetch_assoc() ?: ['total_search' => 0, 'distinct_song' => 0];
$stmtStat->close();

/* ================= ARTISTS FOLLOWED ================= */
$artistsFollowed = [];
$stmtArtists = $conn->prepare("
    SELECT a.artist_id, a.artist_name, a.avatar
    FROM artist_follows af
    JOIN artists a ON af.artist_id = a.artist_id
    WHERE af.user_id = ?
    ORDER BY af.created_at DESC
");
$stmtArtists->bind_param("i", $user_id);
$stmtArtists->execute();
$rsArtists = $stmtArtists->get_result();

while ($row = $rsArtists->fetch_assoc()) {
    $row['avatar_path'] = nln_public_artist_avatar_path($row['avatar'] ?? null);
    $artistsFollowed[] = $row;
}
$stmtArtists->close();

/* ================= FAVORITE ALBUMS ================= */
$favAlbums = [];
$stmtFavAlbums = $conn->prepare("
    SELECT
        al.album_id,
        al.album_name,
        al.cover_image,
        ar.artist_name
    FROM album_favorites af
    JOIN albums al ON af.album_id = al.album_id
    JOIN artists ar ON al.artist_id = ar.artist_id
    WHERE af.user_id = ?
    ORDER BY af.created_at DESC
    LIMIT 12
");
$stmtFavAlbums->bind_param("i", $user_id);
$stmtFavAlbums->execute();
$rsFavAlbums = $stmtFavAlbums->get_result();

while ($row = $rsFavAlbums->fetch_assoc()) {
    $row['cover_path'] = nln_public_album_cover_path($row['cover_image'] ?? null);
    $favAlbums[] = $row;
}
$stmtFavAlbums->close();

/* ================= PLAYLISTS ================= */
$userPlaylists = nln_playlist_fetch_user_playlists($conn, $user_id, 8);

/* ================= RECOMMENDATION ================= */
$recommendationData = nln_profile_recommendations_fresh($conn, $user_id, 6, false);
$recommendItems = $recommendationData['items'] ?? [];
$recommendSummary = trim((string) ($recommendationData['summary'] ?? ''));

$joinDate = nln_format_date($user['created_at'] ?? '');
$recapUrl = 'recap.php?month=' . date('n') . '&year=' . date('Y');
$summaryCards = [
    [
        'label' => 'Lượt tìm kiếm',
        'value' => number_format((int) ($stat['total_search'] ?? 0)),
        'note'  => 'Tổng số lượt tìm đã ghi nhận'
    ],
    [
        'label' => 'Bài hát đã tìm',
        'value' => number_format((int) ($stat['distinct_song'] ?? 0)),
        'note'  => 'Số bài hát khác nhau từng xem'
    ],
    [
        'label' => 'Theo dõi nghệ sĩ',
        'value' => number_format(count($artistsFollowed)),
        'note'  => 'Nghệ sĩ bạn đang quan tâm'
    ],
    [
        'label' => 'Album yêu thích',
        'value' => number_format(count($favAlbums)),
        'note'  => 'Album đã đánh dấu yêu thích'
    ]
];
?>

<header class="masthead profile-hero" style="background-image:url('assets/img/home-bg.jpg')">
    <div class="profile-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center profile-heading-shell">
            <div class="profile-avatar-wrap">
                <img src="<?= h($userAvatar) ?>" alt="<?= h($displayName) ?>" class="profile-avatar">
            </div>

            <span class="profile-eyebrow">User Profile</span>
            <h1><?= h($displayName) ?></h1>
            <span class="subheading profile-subheading">
                Quản lý tài khoản, theo dõi hoạt động, lịch sử tìm kiếm và gợi ý âm nhạc dành riêng cho bạn.
            </span>

            <div class="profile-hero-chips">
                <span class="profile-chip">
                    <i class="fas fa-user me-2"></i>
                    @<?= h($user['username']) ?>
                </span>
                <span class="profile-chip">
                    <i class="fas fa-envelope me-2"></i>
                    <?= h($user['email']) ?>
                </span>
                <span class="profile-chip">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Tham gia từ <?= h($joinDate) ?>
                </span>
            </div>

            <div class="profile-hero-actions">
                <button class="btn btn-light profile-action-btn me-2 mb-2"
                        data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa hồ sơ
                </button>
                <button class="btn btn-outline-light profile-action-btn mb-2"
                        data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key me-2"></i>Đổi mật khẩu
                </button>
                <a href="<?= h($recapUrl) ?>" class="btn btn-outline-light profile-action-btn mb-2">
                    <i class="fas fa-chart-pie me-2"></i>Xem recap tháng này
                </a>
                <a href="persona.php" class="btn btn-outline-light profile-action-btn mb-2">
                    <i class="fas fa-compact-disc me-2"></i>Music Persona
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5 profile-page">
    <div class="profile-summary-bar">
        <?php foreach ($summaryCards as $card): ?>
            <div class="profile-summary-stat">
                <span><?= h($card['label']) ?></span>
                <strong><?= h((string) $card['value']) ?></strong>
                <small><?= h($card['note']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7 mb-4">
            <section class="profile-panel">
                <div class="profile-panel-header">
                    <span class="section-kicker">Search Activity</span>
                    <h4 class="profile-panel-title mb-1">Lịch sử tìm kiếm</h4>
                    <p class="profile-panel-subtitle mb-0">
                        Xem lại các bài hát đã tìm, lọc theo tên bài hát, nghệ sĩ hoặc khoảng thời gian.
                    </p>
                </div>

                <form method="get" class="profile-filter-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="profile-label" for="q_song">Tên bài hát</label>
                            <input id="q_song" name="q_song" value="<?= h($qSong) ?>" class="form-control profile-input" placeholder="Nhập tên bài hát">
                        </div>
                        <div class="col-md-4">
                            <label class="profile-label" for="q_artist">Nghệ sĩ</label>
                            <input id="q_artist" name="q_artist" value="<?= h($qArtist) ?>" class="form-control profile-input" placeholder="Nhập tên nghệ sĩ">
                        </div>
                        <div class="col-md-2">
                            <label class="profile-label" for="from">Từ ngày</label>
                            <input id="from" type="date" name="from" value="<?= h($from) ?>" class="form-control profile-input">
                        </div>
                        <div class="col-md-2">
                            <label class="profile-label" for="to">Đến ngày</label>
                            <input id="to" type="date" name="to" value="<?= h($to) ?>" class="form-control profile-input">
                        </div>
                    </div>

                    <div class="profile-filter-actions">
                        <button class="btn btn-primary profile-filter-btn">
                            <i class="fas fa-filter me-2"></i>Lọc kết quả
                        </button>
                        <a href="profile.php" class="btn btn-outline-secondary profile-filter-btn">
                            Đặt lại
                        </a>
                    </div>
                </form>

                <?php if (!empty($history)): ?>
                    <div class="profile-history-list">
                        <?php foreach ($history as $row): ?>
                            <a href="post.php?id=<?= (int) $row['song_id'] ?>" class="profile-history-card">
                                <img
                                    src="<?= h($row['cover_path']) ?>"
                                    alt="<?= h($row['song_title']) ?>"
                                    class="profile-history-cover"
                                >

                                <div class="profile-history-copy">
                                    <div class="profile-history-title"><?= h($row['song_title']) ?></div>
                                    <div class="profile-history-artist"><?= h($row['artist_name']) ?></div>
                                    <div class="profile-history-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= h(nln_format_date($row['search_time'], true)) ?>
                                    </div>
                                </div>

                                <div class="profile-history-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-history"></i>
                        <h3>Chưa có lịch sử tìm kiếm</h3>
                        <p>Hoạt động tìm kiếm của bạn sẽ xuất hiện tại đây sau khi bạn tra cứu bài hát hoặc nghệ sĩ.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="col-lg-5 mb-4">
            <section class="profile-side-stack">
                <div class="profile-panel profile-mini-panel">
                    <div class="profile-panel-header mb-3">
                        <span class="section-kicker">Account Summary</span>
                        <h4 class="profile-panel-title mb-1">Tổng quan tài khoản</h4>
                        <p class="profile-panel-subtitle mb-0">
                            Một số chỉ số nhanh để bạn theo dõi hoạt động cá nhân.
                        </p>
                    </div>

                    <div class="profile-mini-grid">
                        <div class="profile-mini-stat">
                            <span>Lượt tìm</span>
                            <strong><?= number_format((int) ($stat['total_search'] ?? 0)) ?></strong>
                        </div>

                        <div class="profile-mini-stat">
                            <span>Bài hát</span>
                            <strong><?= number_format((int) ($stat['distinct_song'] ?? 0)) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="profile-panel profile-mini-panel">
                    <div class="profile-panel-header mb-3">
                        <span class="section-kicker">Following</span>
                        <h4 class="profile-panel-title mb-1">Nghệ sĩ đang theo dõi</h4>
                        <p class="profile-panel-subtitle mb-0">
                            Danh sách nghệ sĩ bạn đã theo dõi để nhận cập nhật mới.
                        </p>
                    </div>

                    <?php if (!empty($artistsFollowed)): ?>
                        <div class="profile-follow-list">
                            <?php foreach ($artistsFollowed as $a): ?>
                                <a href="artist.php?id=<?= (int) $a['artist_id'] ?>" class="profile-follow-card">
                                    <img
                                        src="<?= h($a['avatar_path']) ?>"
                                        alt="<?= h($a['artist_name']) ?>"
                                        class="profile-follow-avatar"
                                    >
                                    <div class="profile-follow-copy">
                                        <strong><?= h($a['artist_name']) ?></strong>
                                        <span>Xem hồ sơ nghệ sĩ</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="content-empty-state content-empty-state-compact">
                            <i class="fas fa-user-plus"></i>
                            <h3>Bạn chưa theo dõi nghệ sĩ nào</h3>
                            <p>Hãy theo dõi nghệ sĩ bạn yêu thích để nhận thông tin và nội dung mới.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-panel profile-mini-panel">
                    <div class="profile-panel-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <span class="section-kicker">Personal Picks</span>
                            <h4 class="profile-panel-title mb-1">Gợi ý cho bạn</h4>
                            <p class="profile-panel-subtitle mb-0">
                                Gợi ý bài hát dựa trên hành vi nghe và tìm kiếm của bạn.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= h($recapUrl) ?>"
                               class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2">
                                <i class="fas fa-chart-pie me-1"></i>Recap
                            </a>
                            <a href="persona.php"
                               class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2">
                                <i class="fas fa-compact-disc me-1"></i>Persona
                            </a>
                            <button type="button"
                               id="refresh-recommendations-btn"
                               class="btn btn-sm btn-outline-primary rounded-pill px-3 py-2">
                                <i class="fas fa-rotate-right me-1"></i>Làm mới
                            </button>
                        </div>
                    </div>

                    <div id="profile-recommendations-region">
                    <?php if ($recommendItems && $recommendSummary !== ''): ?>
                        <div class="profile-note-box">
                            <?= h($recommendSummary) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($recommendItems)): ?>
                        <div class="profile-recommend-list">
                            <?php foreach ($recommendItems as $r): ?>
                                <?php
                                $img = $r['cover'] ?? nln_public_song_cover_path($r['song_cover'] ?? null, $r['album_cover'] ?? null);
                                ?>
                                <a href="post.php?id=<?= (int) $r['song_id'] ?>" class="profile-recommend-card">
                                    <img
                                        src="<?= h($img) ?>"
                                        alt="<?= h($r['title']) ?>"
                                        class="profile-recommend-cover"
                                    >
                                    <div class="profile-recommend-copy">
                                        <div class="profile-recommend-title"><?= h($r['title']) ?></div>
                                        <div class="profile-recommend-artist"><?= h($r['artist_name']) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="content-empty-state content-empty-state-compact">
                            <i class="fas fa-headphones"></i>
                            <h3>Chưa đủ dữ liệu gợi ý</h3>
                            <p>Hãy tiếp tục tìm kiếm và tương tác với nội dung để hệ thống đưa ra gợi ý chính xác hơn.</p>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="profile-panel mt-2">
        <div class="profile-panel-header">
            <span class="section-kicker">Playlists</span>
            <h4 class="profile-panel-title mb-1">Playlist của bạn</h4>
            <p class="profile-panel-subtitle mb-0">
                Những playlist cá nhân bạn đã tạo khi lưu bài hát từ trang chi tiết.
            </p>
        </div>

        <?php if (!empty($userPlaylists)): ?>
            <div class="profile-playlist-grid mb-4">
                <?php foreach ($userPlaylists as $playlist): ?>
                    <div class="profile-playlist-card">
                        <img
                            src="<?= h($playlist['cover_path']) ?>"
                            alt="<?= h($playlist['playlist_name']) ?>"
                            class="profile-playlist-cover"
                        >
                        <div class="profile-playlist-copy">
                            <div class="profile-playlist-title"><?= h($playlist['playlist_name']) ?></div>
                            <div class="profile-playlist-meta"><?= (int) $playlist['song_count'] ?> bài hát</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="content-empty-state mb-4">
                <i class="fas fa-list-music"></i>
                <h3>Bạn chưa có playlist nào</h3>
                <p>Hãy thêm bài hát từ trang chi tiết vào playlist để bộ sưu tập cá nhân xuất hiện ở đây.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="profile-panel mt-2">
        <div class="profile-panel-header">
            <span class="section-kicker">Favorites</span>
            <h4 class="profile-panel-title mb-1">Album yêu thích</h4>
            <p class="profile-panel-subtitle mb-0">
                Những album bạn đã lưu lại để quay lại nghe hoặc khám phá thêm.
            </p>
        </div>

        <?php if (!empty($favAlbums)): ?>
            <div class="profile-album-grid">
                <?php foreach ($favAlbums as $al): ?>
                    <a href="album.php?id=<?= (int) $al['album_id'] ?>" class="profile-album-card">
                        <img
                            src="<?= h($al['cover_path']) ?>"
                            alt="<?= h($al['album_name']) ?>"
                            class="profile-album-cover"
                        >
                        <div class="profile-album-copy">
                            <div class="profile-album-title"><?= h($al['album_name']) ?></div>
                            <div class="profile-album-artist"><?= h($al['artist_name']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="content-empty-state">
                <i class="fas fa-heart"></i>
                <h3>Bạn chưa có album yêu thích</h3>
                <p>Hãy đánh dấu các album bạn yêu thích để chúng xuất hiện ở đây cho lần truy cập sau.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.getElementById('refresh-recommendations-btn');
    const region = document.getElementById('profile-recommendations-region');

    if (!refreshBtn || !region) {
        return;
    }

    const defaultLabel = refreshBtn.innerHTML;

    refreshBtn.addEventListener('click', async function () {
        if (refreshBtn.disabled) {
            return;
        }

        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Dang tai';
        region.classList.add('profile-recommendations-loading');

        try {
            const response = await fetch('includes/api_profile_recommendations.php?refresh=1', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success || typeof data.html !== 'string') {
                throw new Error('recommendation_refresh_failed');
            }

            region.innerHTML = data.html;
        } catch (error) {
            console.error(error);
        } finally {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = defaultLabel;
            region.classList.remove('profile-recommendations-loading');
        }
    });
});
</script>

<style>
.profile-page,
.profile-page * {
    box-sizing: border-box;
}

.profile-page p,
.profile-page .profile-panel-subtitle,
.profile-page .content-empty-state p,
.profile-page .profile-note-box,
.profile-page .profile-album-artist,
.profile-page .profile-history-artist,
.profile-page .profile-history-time,
.profile-page .profile-follow-copy span,
.profile-page .profile-recommend-artist {
    margin: 0;
}

.profile-recommendations-loading {
    opacity: .65;
    transition: opacity .2s ease;
}

.profile-hero {
    overflow: visible;
    padding-bottom: 4.6rem;
}

.profile-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.78) 0%, rgba(16, 24, 40, 0.50) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.profile-heading-shell {
    position: relative;
    z-index: 1;
}

.profile-avatar-wrap {
    margin-bottom: 1rem;
}

.profile-avatar {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,.92);
    box-shadow: 0 18px 44px rgba(10, 20, 40, .22);
}

.profile-eyebrow {
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

.profile-heading-shell h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem);
}

.profile-subheading {
    display: block;
    max-width: 760px;
    margin: .7rem auto 0;
    font-size: 1.05rem;
    line-height: 1.7;
}

.profile-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.35rem;
}

.profile-chip {
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

.profile-hero-actions {
    margin-top: 1.2rem;
}

.profile-action-btn {
    min-width: 170px;
    border-radius: 999px;
    padding: .75rem 1rem;
    font-weight: 700;
}

.profile-playlist-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.profile-playlist-card {
    display: flex;
    gap: .9rem;
    align-items: center;
    padding: 1rem;
    border-radius: 22px;
    border: 1px solid rgba(16, 24, 40, .06);
    background: #f8fbff;
}

.profile-playlist-cover {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 18px;
    background: #e5e7eb;
    flex: 0 0 auto;
}

.profile-playlist-copy {
    min-width: 0;
}

.profile-playlist-title {
    color: #162033;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.35;
}

.profile-playlist-meta {
    margin-top: .18rem;
    color: #6f7c91;
    font-size: .9rem;
}

.profile-summary-bar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2rem;
    margin-bottom: 1.4rem;
    position: relative;
    z-index: 2;
}

.profile-summary-stat,
.profile-panel,
.profile-history-card,
.profile-follow-card,
.profile-recommend-card,
.profile-album-card,
.profile-mini-stat,
.content-empty-state,
.profile-note-box {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.profile-summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.profile-summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.profile-summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.profile-summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.profile-panel {
    height: 100%;
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.profile-panel-header {
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

.profile-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.profile-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.profile-filter-form {
    padding: 1rem;
    border: 1px solid rgba(16, 24, 40, .06);
    border-radius: 20px;
    background: #f8fafc;
    margin-bottom: 1rem;
}

.profile-label {
    display: block;
    margin-bottom: .4rem;
    color: #6f7c91;
    font-size: .82rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.profile-input {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid rgba(16, 24, 40, .10);
    box-shadow: none;
}

.profile-input:focus {
    border-color: rgba(13, 110, 253, .35);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .10);
}

.profile-filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.profile-filter-btn {
    border-radius: 999px;
    padding: .72rem 1rem;
    font-weight: 700;
}

.profile-history-list,
.profile-follow-list,
.profile-recommend-list,
.profile-side-stack {
    display: grid;
    gap: .8rem;
}

.profile-history-card,
.profile-follow-card,
.profile-recommend-card,
.profile-album-card {
    border: 1px solid rgba(16, 24, 40, .06);
    text-decoration: none;
    color: inherit;
    transition: transform .2s ease, box-shadow .2s ease;
}

.profile-history-card:hover,
.profile-follow-card:hover,
.profile-recommend-card:hover,
.profile-album-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
    text-decoration: none;
    color: inherit;
}

.profile-history-card {
    display: grid;
    grid-template-columns: 78px minmax(0, 1fr) auto;
    gap: .9rem;
    align-items: center;
    padding: .85rem;
    border-radius: 20px;
}

.profile-history-cover {
    width: 78px;
    height: 78px;
    object-fit: cover;
    border-radius: 16px;
}

.profile-history-title,
.profile-recommend-title,
.profile-album-title {
    color: #162033;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.45;
}

.profile-history-artist,
.profile-recommend-artist,
.profile-album-artist {
    margin-top: .18rem;
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
}

.profile-history-time {
    margin-top: .28rem;
    color: #6f7c91;
    font-size: .85rem;
    line-height: 1.5;
}

.profile-history-arrow {
    color: #9aa4b2;
    font-size: .95rem;
}

.profile-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .8rem;
}

.profile-mini-stat {
    padding: 1rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.profile-mini-stat span {
    display: block;
    color: #6f7c91;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    font-family: "Open Sans", sans-serif;
    margin-bottom: .3rem;
}

.profile-mini-stat strong {
    color: #162033;
    font-size:  .3rem;
}

.profile-mini-stat strong {
    color: #162033;
    font-size: 1.35rem;
    line-height: 1.2;
}

.profile-follow-card {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .85rem;
    border-radius: 18px;
}

.profile-follow-avatar {
    width: 52px;
    height: 52px;
    object-fit: cover;
    border-radius: 50%;
    flex-shrink: 0;
}

.profile-follow-copy strong {
    display: block;
    color: #162033;
    font-size: .98rem;
    line-height: 1.45;
}

.profile-follow-copy span {
    display: block;
    color: #6f7c91;
    font-size: .87rem;
    line-height: 1.45;
    margin-top: .15rem;
}

.profile-note-box {
    padding: .9rem 1rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
    color: #536277;
    font-size: .92rem;
    line-height: 1.65;
    font-family: "Open Sans", sans-serif;
    margin-bottom: .85rem;
}

.profile-recommend-card {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    gap: .8rem;
    align-items: center;
    padding: .82rem;
    border-radius: 18px;
}

.profile-recommend-cover {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 14px;
}

.profile-album-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .9rem;
}

.profile-album-card {
    display: block;
    padding: .8rem;
    border-radius: 20px;
}

.profile-album-cover {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: .75rem;
}

.content-empty-state {
    padding: 2.2rem 1.1rem;
    border-radius: 22px;
    text-align: center;
    border: 1px solid rgba(16, 24, 40, .06);
}

.content-empty-state-compact {
    padding: 1.6rem 1rem;
}

.content-empty-state i {
    margin-bottom: .8rem;
    font-size: 1.6rem;
    color: #0d6efd;
}

.content-empty-state h3 {
    font-size: 1.2rem;
    color: #162033;
    margin-bottom: .45rem;
}

.content-empty-state p {
    max-width: 520px;
    margin: 0 auto;
    font-size: .95rem;
    color: #6f7c91;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

@media (max-width: 1199.98px) {
    .profile-summary-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .profile-album-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 991.98px) {
    .profile-playlist-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .profile-playlist-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 991.98px) {
    .profile-summary-bar {
        margin-top: 0;
    }

    .profile-album-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .profile-hero {
        padding-bottom: 4rem;
    }

    .profile-hero-chips {
        justify-content: flex-start;
    }

    .profile-hero-actions .profile-action-btn {
        width: 100%;
        margin-right: 0 !important;
    }

    .profile-summary-bar {
        grid-template-columns: 1fr;
    }

    .profile-history-card {
        grid-template-columns: 1fr;
        text-align: left;
    }

    .profile-history-cover {
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
    }

    .profile-history-arrow {
        display: none;
    }

    .profile-mini-grid,
    .profile-album-grid {
        grid-template-columns: 1fr;
    }

    .profile-filter-actions {
        justify-content: stretch;
    }

    .profile-filter-actions .btn {
        width: 100%;
    }
}
</style>

<?php include('includes/profile_modals_js.php'); ?>
<?php include('includes/footer.php'); ?>
