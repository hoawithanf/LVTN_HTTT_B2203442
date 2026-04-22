<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/lyric_request_helpers.php';

/* ================= HELPERS ================= */
function tableExists($conn, $table)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return false;
    }

    $table = $conn->real_escape_string($table);
    $check = $conn->query("SHOW TABLES LIKE '{$table}'");

    return ($check && $check->num_rows > 0);
}

function safeCount($conn, $table)
{
    if (!tableExists($conn, $table)) {
        return 0;
    }

    $q = $conn->query("SELECT COUNT(*) AS total FROM `{$table}`");
    if ($q && $row = $q->fetch_assoc()) {
        return (int) $row['total'];
    }

    return 0;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatAverage($number)
{
    $formatted = number_format((float) $number, 1, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

/* ================= COUNTS ================= */
$totalUsers   = safeCount($conn, 'users');
$totalSongs   = safeCount($conn, 'songs');
$totalArtists = safeCount($conn, 'artists');
$totalGenres  = safeCount($conn, 'genres');
$pendingLyricRequests = nln_lyric_request_pending_count($conn);

/* ================= NOTIFICATIONS ================= */
$unreadNotifications = 0;
if (tableExists($conn, 'notifications')) {
    $q = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
    if ($q && $r = $q->fetch_assoc()) {
        $unreadNotifications = (int) $r['total'];
    }
}

/* ================= SEARCH LOGS (LAST 7 DAYS) ================= */
$searchLabels = [];
$searchData   = [];
$searchMap    = [];

$today = new DateTime('today');
for ($i = 6; $i >= 0; $i--) {
    $dateObj = (clone $today)->modify("-{$i} day");
    $dateKey = $dateObj->format('Y-m-d');

    $searchLabels[]      = $dateObj->format('d/m');
    $searchMap[$dateKey] = 0;
}

if (tableExists($conn, 'search_logs')) {
    $res = $conn->query("
        SELECT DATE(search_time) AS d, COUNT(*) AS c
        FROM search_logs
        WHERE search_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(search_time)
        ORDER BY d ASC
    ");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (isset($searchMap[$r['d']])) {
                $searchMap[$r['d']] = (int) $r['c'];
            }
        }
    }
}

$searchData       = array_values($searchMap);
$searchTotal7Days = array_sum($searchData);
$searchAvgPerDay  = formatAverage($searchTotal7Days / 7);
$hasSearchData    = $searchTotal7Days > 0;

/* ================= SONGS BY GENRE ================= */
$genreLabels = [];
$genreData   = [];

if (tableExists($conn, 'genres') && tableExists($conn, 'songs')) {
    $res = $conn->query("
        SELECT g.genre_name, COUNT(s.song_id) AS total
        FROM genres g
        LEFT JOIN songs s ON s.genre_id = g.genre_id
        GROUP BY g.genre_id, g.genre_name
        ORDER BY total DESC, g.genre_name ASC
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $genreLabels[] = $row['genre_name'];
            $genreData[]   = (int) $row['total'];
        }
    }
}

$hasGenreData = array_sum($genreData) > 0;
$genresWithSongs = 0;
foreach ($genreData as $genreTotal) {
    if ((int) $genreTotal > 0) {
        $genresWithSongs++;
    }
}

$genrePaletteBase = [
    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
    '#6f42c1', '#fd7e14', '#20c997', '#17a2b8', '#858796'
];
$genreChartColors = [];
for ($i = 0; $i < count($genreLabels); $i++) {
    $genreChartColors[] = $genrePaletteBase[$i % count($genrePaletteBase)];
}

/* ================= TOP SONGS ================= */
$topSongs = [];
if (tableExists($conn, 'search_logs') && tableExists($conn, 'songs') && tableExists($conn, 'artists')) {
    $res = $conn->query("
        SELECT s.song_id, s.title, ar.artist_name, COUNT(sl.log_id) AS search_count
        FROM search_logs sl
        INNER JOIN songs s ON s.song_id = sl.song_id
        INNER JOIN artists ar ON ar.artist_id = s.artist_id
        GROUP BY s.song_id, s.title, ar.artist_name
        ORDER BY search_count DESC, s.title ASC
        LIMIT 5
    ");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $topSongs[] = $r;
        }
    }
}

/* ================= TOP ARTISTS ================= */
$topArtists = [];
if (tableExists($conn, 'search_logs') && tableExists($conn, 'songs') && tableExists($conn, 'artists')) {
    $res = $conn->query("
        SELECT ar.artist_id, ar.artist_name, COUNT(sl.log_id) AS search_count
        FROM search_logs sl
        INNER JOIN songs s ON s.song_id = sl.song_id
        INNER JOIN artists ar ON ar.artist_id = s.artist_id
        GROUP BY ar.artist_id, ar.artist_name
        ORDER BY search_count DESC, ar.artist_name ASC
        LIMIT 5
    ");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $topArtists[] = $r;
        }
    }
}

/* ================= PROGRESS ================= */
$songsWithLyrics = 0;
if (tableExists($conn, 'songs')) {
    $q = $conn->query("SELECT COUNT(*) AS total FROM songs WHERE lyrics IS NOT NULL AND lyrics <> ''");
    if ($q && $r = $q->fetch_assoc()) {
        $songsWithLyrics = (int) $r['total'];
    }
}

$songProgress      = $totalSongs ? (int) round(($songsWithLyrics / $totalSongs) * 100) : 0;
$songsWithoutLyrics = max($totalSongs - $songsWithLyrics, 0);

$topSongName   = !empty($topSongs) ? $topSongs[0]['title'] : 'Chưa có dữ liệu';
$topArtistName = !empty($topArtists) ? $topArtists[0]['artist_name'] : 'Chưa có dữ liệu';

$cards = [
    [
        'title'   => 'Người dùng',
        'value'   => $totalUsers,
        'color'   => 'primary',
        'link'    => 'users.php',
        'icon'    => 'fa-users',
        'caption' => 'Tài khoản trong hệ thống'
    ],
    [
        'title'   => 'Bài hát',
        'value'   => $totalSongs,
        'color'   => 'success',
        'link'    => 'songs.php',
        'icon'    => 'fa-music',
        'caption' => 'Kho lyrics và nội dung'
    ],
    [
        'title'   => 'Nghệ sĩ',
        'value'   => $totalArtists,
        'color'   => 'info',
        'link'    => 'artists.php',
        'icon'    => 'fa-microphone',
        'caption' => 'Hồ sơ nghệ sĩ đang quản lý'
    ],
    [
        'title'   => 'Yêu cầu lyrics',
        'value'   => $pendingLyricRequests,
        'color'   => 'warning',
        'link'    => 'lyric_requests.php',
        'icon'    => 'fa-file-signature',
        'caption' => 'Yêu cầu người dùng đang chờ xử lý'
    ]
];
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .dashboard-page {
        color: #5a5c69;
    }

    .dashboard-hero {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
        box-shadow: 0 16px 40px rgba(58, 59, 69, 0.08);
    }

    .dashboard-eyebrow {
        display: inline-block;
        margin-bottom: .75rem;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #4e73df;
    }

    .dashboard-title {
        margin-bottom: .5rem;
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
    }

    .dashboard-subtitle {
        max-width: 760px;
        margin-bottom: 1rem;
        font-size: .98rem;
        line-height: 1.65;
        color: #6b7280;
    }

    .meta-pill {
        display: inline-flex;
        align-items: center;
        padding: .5rem .9rem;
        margin-right: .6rem;
        margin-bottom: .5rem;
        border: 1px solid #e8edf7;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(78, 115, 223, 0.08);
        font-size: .88rem;
        color: #5a5c69;
    }

    .meta-pill i {
        margin-right: .45rem;
        color: #4e73df;
    }

    .hero-actions .btn {
        min-width: 122px;
        padding: .72rem 1rem;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 10px 24px rgba(58, 59, 69, 0.08);
    }

    .dashboard-link-card {
        display: block;
        color: inherit;
        text-decoration: none !important;
    }

    .metric-card {
        position: relative;
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 14px 34px rgba(58, 59, 69, 0.08);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .metric-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 42px rgba(58, 59, 69, 0.12);
    }

    .metric-card-primary::before { background: #4e73df; }
    .metric-card-success::before { background: #1cc88a; }
    .metric-card-info::before    { background: #36b9cc; }
    .metric-card-warning::before { background: #f6c23e; }

    .metric-label {
        margin-bottom: .65rem;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #858796;
    }

    .metric-value {
        font-size: 1.9rem;
        font-weight: 700;
        line-height: 1.1;
        color: #1f2937;
    }

    .metric-caption {
        margin-top: .45rem;
        font-size: .92rem;
        color: #858796;
    }

    .metric-icon {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #fff;
        font-size: 1.3rem;
        box-shadow: 0 14px 28px rgba(58, 59, 69, 0.12);
    }

    .metric-icon-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
    .metric-icon-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .metric-icon-info    { background: linear-gradient(135deg, #36b9cc, #258391); }
    .metric-icon-warning { background: linear-gradient(135deg, #f6c23e, #dda20a); }

    .section-card,
    .chart-card {
        border: 0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(58, 59, 69, 0.08);
        overflow: hidden;
    }

    .section-card .card-header,
    .chart-card .card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #edf1f7;
        background: #fff;
    }

    .section-title {
        margin-bottom: .2rem;
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
    }

    .section-subtitle {
        margin-bottom: 0;
        font-size: .87rem;
        color: #858796;
    }

    .overview-block {
        padding: 1rem;
        border: 1px solid #e9eef9;
        border-radius: 16px;
        background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
    }

    .progress-modern {
        height: .8rem;
        border-radius: 999px;
        background: #e7edf8;
        overflow: hidden;
    }

    .progress-modern .progress-bar {
        border-radius: 999px;
        background: linear-gradient(90deg, #4e73df 0%, #36b9cc 100%);
    }

    .badge-soft {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .42rem .75rem;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 700;
    }

    .badge-soft-primary {
        background: rgba(78, 115, 223, .12);
        color: #2e59d9;
    }

    .overview-list,
    .insight-grid {
        display: grid;
        gap: 1rem;
    }

    .overview-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 1rem;
    }

    .insight-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mini-stat {
        padding: 1rem;
        border: 1px solid #edf1f7;
        border-radius: 16px;
        background: #fff;
    }

    .mini-stat-label {
        font-size: .77rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #858796;
    }

    .mini-stat-value {
        margin-top: .35rem;
        font-size: 1.35rem;
        font-weight: 700;
        color: #1f2937;
    }

    .mini-stat-note {
        margin-top: .35rem;
        font-size: .87rem;
        color: #858796;
    }

    .insight-callout {
        height: 100%;
        padding: 1rem 1.1rem;
        border: 1px solid #e7edf8;
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #f7f9fe 100%);
    }

    .insight-callout-title {
        margin-bottom: .45rem;
        font-size: .95rem;
        font-weight: 700;
        color: #1f2937;
    }

    .insight-callout p {
        margin-bottom: 0;
        font-size: .92rem;
        line-height: 1.65;
        color: #6b7280;
    }

    .rank-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .rank-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #edf1f7;
        transition: background .2s ease;
    }

    .rank-item:last-child {
        border-bottom: 0;
    }

    .rank-item:hover {
        background: #f8faff;
    }

    .rank-main {
        display: flex;
        align-items: center;
        min-width: 0;
        padding-right: 1rem;
    }

    .rank-number {
        width: 34px;
        height: 34px;
        margin-right: .85rem;
        border-radius: 10px;
        background: #eef3ff;
        color: #4e73df;
        font-size: .9rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .rank-name {
        font-size: .95rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.4;
        word-break: break-word;
    }

    .rank-subtext {
        margin-top: .15rem;
        font-size: .86rem;
        color: #858796;
    }

    .badge-soft-info {
        background: rgba(54, 185, 204, .12);
        color: #258391;
    }

    .badge-soft-warning {
        background: rgba(246, 194, 62, .18);
        color: #b07d06;
    }

    .chart-card .card-body {
        padding: 1rem 1.25rem 1.25rem;
    }

    .chart-container {
        position: relative;
        height: 320px;
    }

    .empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: #858796;
    }

    .empty-state i {
        display: block;
        margin-bottom: .75rem;
        font-size: 1.4rem;
        color: #c7cde2;
    }

    @media (max-width: 1199.98px) {
        .insight-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .dashboard-title {
            font-size: 1.6rem;
        }

        .overview-list,
        .insight-grid {
            grid-template-columns: 1fr;
        }

        .metric-value {
            font-size: 1.7rem;
        }

        .hero-actions .btn {
            width: 100%;
        }
    }
</style>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid dashboard-page">

            <div class="card dashboard-hero mb-4">
                <div class="card-body py-4 px-lg-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8 mb-4 mb-lg-0">
                            <span class="dashboard-eyebrow">Tổng quan hệ thống</span>
                            <h1 class="dashboard-title">Dashboard quản trị</h1>
                            <p class="dashboard-subtitle">
                                Theo dõi nhanh các chỉ số quan trọng, xu hướng tìm kiếm và mức độ hoàn thiện nội dung
                                để vận hành hệ thống hiệu quả hơn mà không làm thay đổi chức năng hiện có.
                            </p>
                            <div>
                                <span class="meta-pill">
                                    <i class="fas fa-sync-alt"></i>
                                    Cập nhật: <?= date('d/m/Y H:i') ?>
                                </span>
                                <span class="meta-pill">
                                    <i class="fas fa-file-alt"></i>
                                    Lyrics hoàn chỉnh: <?= $songProgress ?>%
                                </span>
                                <span class="meta-pill">
                                    <i class="fas fa-search"></i>
                                    Tìm kiếm 7 ngày: <?= number_format($searchTotal7Days) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex flex-wrap justify-content-lg-end hero-actions">
                                <a href="export_report.php?type=pdf" class="btn btn-primary mr-2 mb-2">
                                    <i class="fas fa-file-pdf mr-2"></i> Xuất PDF
                                </a>
                                <a href="export_report.php?type=csv" class="btn btn-success mb-2">
                                    <i class="fas fa-file-csv mr-2"></i> Xuất CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php foreach ($cards as $card): ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <a href="<?= h($card['link']) ?>" class="dashboard-link-card">
                            <div class="card metric-card metric-card-<?= h($card['color']) ?> h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="pr-3">
                                            <div class="metric-label"><?= h($card['title']) ?></div>
                                            <div class="metric-value"><?= number_format((int) $card['value']) ?></div>
                                            <div class="metric-caption"><?= h($card['caption']) ?></div>
                                        </div>
                                        <div class="metric-icon metric-icon-<?= h($card['color']) ?>">
                                            <i class="fas <?= h($card['icon']) ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row">
                <div class="col-xl-5 col-lg-6 mb-4">
                    <div class="card section-card h-100">
                        <div class="card-header">
                            <div class="section-title">Tiến độ nội dung</div>
                            <p class="section-subtitle">Tỷ lệ bài hát đã có lyrics và các chỉ số nền của hệ thống.</p>
                        </div>
                        <div class="card-body">
                            <div class="overview-block">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark">Bài hát đã có lyrics</strong>
                                    <span class="badge-soft badge-soft-primary"><?= $songProgress ?>%</span>
                                </div>
                                <div class="progress progress-modern mb-3">
                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        style="width: <?= $songProgress ?>%;"
                                        aria-valuenow="<?= $songProgress ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                                <div class="d-flex flex-wrap text-muted small">
                                    <div class="mr-4 mb-2">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        <?= number_format($songsWithLyrics) ?> bài có lyrics
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-exclamation-circle text-warning mr-1"></i>
                                        <?= number_format($songsWithoutLyrics) ?> bài cần bổ sung
                                    </div>
                                </div>
                            </div>

                            <div class="overview-list">
                                <div class="mini-stat">
                                    <div class="mini-stat-label">Thể loại</div>
                                    <div class="mini-stat-value"><?= number_format($totalGenres) ?></div>
                                    <div class="mini-stat-note"><?= number_format($genresWithSongs) ?> thể loại đã có bài hát</div>
                                </div>

                                <div class="mini-stat">
                                    <div class="mini-stat-label">TB tìm kiếm/ngày</div>
                                    <div class="mini-stat-value"><?= h($searchAvgPerDay) ?></div>
                                    <div class="mini-stat-note">Dựa trên 7 ngày gần nhất</div>
                                </div>

                                <div class="mini-stat">
                                    <div class="mini-stat-label">Top bài hát</div>
                                    <div class="mini-stat-value" style="font-size: 1.05rem;"><?= h($topSongName) ?></div>
                                    <div class="mini-stat-note">Bài được tìm nhiều nhất hiện tại</div>
                                </div>

                                <div class="mini-stat">
                                    <div class="mini-stat-label">Top nghệ sĩ</div>
                                    <div class="mini-stat-value" style="font-size: 1.05rem;"><?= h($topArtistName) ?></div>
                                    <div class="mini-stat-note">Nghệ sĩ được quan tâm nhiều nhất</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7 col-lg-6 mb-4">
                    <div class="card section-card h-100">
                        <div class="card-header">
                            <div class="section-title">Điểm nổi bật vận hành</div>
                            <p class="section-subtitle">Tóm tắt nhanh để admin nắm trạng thái hệ thống ngay khi truy cập.</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-8 mb-3 mb-lg-0">
                                    <div class="insight-grid">
                                        <div class="mini-stat">
                                            <div class="mini-stat-label">Tìm kiếm 7 ngày</div>
                                            <div class="mini-stat-value"><?= number_format($searchTotal7Days) ?></div>
                                            <div class="mini-stat-note">Tổng lượt tìm kiếm gần đây</div>
                                        </div>

                                        <div class="mini-stat">
                                            <div class="mini-stat-label">Thông báo chưa đọc</div>
                                            <div class="mini-stat-value"><?= number_format($unreadNotifications) ?></div>
                                            <div class="mini-stat-note">Cần xử lý hoặc kiểm tra</div>
                                        </div>

                                        <div class="mini-stat">
                                            <div class="mini-stat-label">Kho nghệ sĩ</div>
                                            <div class="mini-stat-value"><?= number_format($totalArtists) ?></div>
                                            <div class="mini-stat-note">Hồ sơ nghệ sĩ đang quản lý</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="insight-callout">
                                        <div class="insight-callout-title">Nhận định nhanh</div>
                                        <p>
                                            <?php if ($hasSearchData): ?>
                                                Trong 7 ngày gần nhất hệ thống ghi nhận
                                                <strong><?= number_format($searchTotal7Days) ?></strong> lượt tìm kiếm,
                                                trung bình <strong><?= h($searchAvgPerDay) ?></strong> lượt/ngày.
                                                Tỷ lệ bài hát đã có lyrics hiện đạt <strong><?= $songProgress ?>%</strong>.
                                            <?php else: ?>
                                                Hiện chưa có đủ dữ liệu tìm kiếm trong 7 ngày gần nhất.
                                                Dashboard vẫn hiển thị đầy đủ các chỉ số nền để bạn tiếp tục quản trị nội dung.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card section-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <div class="section-title">Top bài hát</div>
                                <p class="section-subtitle">Các bài hát được tìm kiếm nhiều nhất.</p>
                            </div>
                            <a href="top_songs.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                        </div>

                        <?php if (!empty($topSongs)): ?>
                            <ul class="rank-list">
                                <?php foreach ($topSongs as $index => $song): ?>
                                    <li class="rank-item">
                                        <div class="rank-main">
                                            <span class="rank-number"><?= $index + 1 ?></span>
                                            <div>
                                                <div class="rank-name"><?= h($song['title']) ?></div>
                                                <div class="rank-subtext"><?= h($song['artist_name']) ?></div>
                                            </div>
                                        </div>
                                        <span class="badge-soft badge-soft-primary">
                                            <?= number_format((int) $song['search_count']) ?> lượt
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-music"></i>
                                Chưa có dữ liệu top bài hát để hiển thị.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card section-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <div class="section-title">Top nghệ sĩ</div>
                                <p class="section-subtitle">Các nghệ sĩ được quan tâm nhiều nhất.</p>
                            </div>
                            <a href="top_artists.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                        </div>

                        <?php if (!empty($topArtists)): ?>
                            <ul class="rank-list">
                                <?php foreach ($topArtists as $index => $artist): ?>
                                    <li class="rank-item">
                                        <div class="rank-main">
                                            <span class="rank-number"><?= $index + 1 ?></span>
                                            <div>
                                                <div class="rank-name"><?= h($artist['artist_name']) ?></div>
                                                <div class="rank-subtext">Hiệu suất tìm kiếm nghệ sĩ</div>
                                            </div>
                                        </div>
                                        <span class="badge-soft badge-soft-info">
                                            <?= number_format((int) $artist['search_count']) ?> lượt
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-microphone"></i>
                                Chưa có dữ liệu top nghệ sĩ để hiển thị.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8 mb-4">
                    <div class="card chart-card h-100">
                        <div class="card-header">
                            <div class="section-title">Search Activity</div>
                            <p class="section-subtitle">Biến động tìm kiếm trong 7 ngày gần nhất.</p>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="searchChart"></canvas>
                            </div>
                            <?php if (!$hasSearchData): ?>
                                <div class="empty-state pt-4 pb-0">
                                    <i class="fas fa-chart-line"></i>
                                    Chưa có đủ dữ liệu tìm kiếm trong 7 ngày để biểu đồ thể hiện rõ xu hướng.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 mb-4">
                    <div class="card chart-card h-100">
                        <div class="card-header">
                            <div class="section-title">Songs by Genre</div>
                            <p class="section-subtitle">Phân bố bài hát theo thể loại hiện có.</p>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="genreChart"></canvas>
                            </div>
                            <?php if (!$hasGenreData): ?>
                                <div class="empty-state pt-4 pb-0">
                                    <i class="fas fa-compact-disc"></i>
                                    Chưa có dữ liệu thể loại để hiển thị biểu đồ.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<?php include 'includes/scripts.php'; ?>

<script>
    var searchCtx = document.getElementById('searchChart');
    if (searchCtx) {
        new Chart(searchCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($searchLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Lượt tìm kiếm',
                    data: <?= json_encode($searchData) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.12)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4e73df',
                    pointBorderWidth: 2,
                    lineTension: 0.35,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                tooltips: {
                    intersect: false,
                    mode: 'index',
                    backgroundColor: '#1f2937',
                    titleFontSize: 12,
                    bodyFontSize: 12,
                    padding: 12
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            fontColor: '#858796'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0,
                            fontColor: '#858796',
                            padding: 10
                        },
                        gridLines: {
                            color: 'rgba(234, 236, 244, 0.9)',
                            zeroLineColor: 'rgba(234, 236, 244, 1)',
                            drawBorder: false
                        }
                    }]
                }
            }
        });
    }

    var genreCtx = document.getElementById('genreChart');
    if (genreCtx) {
        new Chart(genreCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($genreLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($genreData) ?>,
                    backgroundColor: <?= json_encode($genreChartColors) ?>,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverBorderColor: '#ffffff'
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 16,
                        fontColor: '#858796'
                    }
                },
                cutoutPercentage: 68,
                tooltips: {
                    backgroundColor: '#1f2937',
                    bodyFontSize: 12,
                    padding: 12
                }
            }
        });
    }
</script>
