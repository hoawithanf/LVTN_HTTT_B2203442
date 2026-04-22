<?php
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

function nln_charts_range_label($range)
{
    switch ($range) {
        case 'week':
            return '7 ngày gần nhất';
        case 'month':
            return '30 ngày gần nhất';
        default:
            return 'Toàn thời gian';
    }
}

/* ================= FILTER ================= */
$allowedRanges = ['all', 'month', 'week'];
$range = isset($_GET['range']) && in_array($_GET['range'], $allowedRanges, true)
    ? $_GET['range']
    : 'all';

$timeFilter = null;
if ($range === 'week') {
    $timeFilter = date('Y-m-d H:i:s', strtotime('-7 days'));
} elseif ($range === 'month') {
    $timeFilter = date('Y-m-d H:i:s', strtotime('-30 days'));
}

/* ================= TOP SONGS ================= */
$topSongs = [];
$topArtists = [];
$totalSongSearches = 0;
$totalArtistSearches = 0;

$sqlSongs = "
    SELECT
        s.song_id,
        s.title,
        a.artist_name,
        s.cover_image AS song_cover,
        al.cover_image AS album_cover,
        COUNT(l.song_id) AS total
    FROM search_logs l
    INNER JOIN songs s ON s.song_id = l.song_id
    INNER JOIN artists a ON a.artist_id = s.artist_id
    LEFT JOIN albums al ON al.album_id = s.album_id
";

if ($timeFilter !== null) {
    $sqlSongs .= " WHERE l.search_time >= ? ";
}

$sqlSongs .= "
    GROUP BY
        s.song_id,
        s.title,
        a.artist_name,
        s.cover_image,
        al.cover_image
    ORDER BY total DESC, s.title ASC
    LIMIT 20
";

if ($stmtSongs = $conn->prepare($sqlSongs)) {
    if ($timeFilter !== null) {
        $stmtSongs->bind_param("s", $timeFilter);
    }

    $stmtSongs->execute();
    $songsResult = $stmtSongs->get_result();

    while ($row = $songsResult->fetch_assoc()) {
        $row['total'] = (int) ($row['total'] ?? 0);
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
        $totalSongSearches += $row['total'];
        $topSongs[] = $row;
    }

    $stmtSongs->close();
}

/* ================= TOP ARTISTS ================= */
$sqlArtists = "
    SELECT
        a.artist_id,
        a.artist_name,
        a.avatar,
        COUNT(l.song_id) AS total
    FROM search_logs l
    INNER JOIN songs s ON s.song_id = l.song_id
    INNER JOIN artists a ON a.artist_id = s.artist_id
";

if ($timeFilter !== null) {
    $sqlArtists .= " WHERE l.search_time >= ? ";
}

$sqlArtists .= "
    GROUP BY
        a.artist_id,
        a.artist_name,
        a.avatar
    ORDER BY total DESC, a.artist_name ASC
    LIMIT 10
";

if ($stmtArtists = $conn->prepare($sqlArtists)) {
    if ($timeFilter !== null) {
        $stmtArtists->bind_param("s", $timeFilter);
    }

    $stmtArtists->execute();
    $artistsResult = $stmtArtists->get_result();

    while ($row = $artistsResult->fetch_assoc()) {
        $row['total'] = (int) ($row['total'] ?? 0);
        $row['avatar_path'] = nln_public_artist_avatar_path($row['avatar'] ?? null);
        $totalArtistSearches += $row['total'];
        $topArtists[] = $row;
    }

    $stmtArtists->close();
}

$featuredSong = $topSongs[0] ?? null;
$remainingSongs = array_slice($topSongs, 1);

$featuredArtist = $topArtists[0] ?? null;
$remainingArtists = array_slice($topArtists, 1);

$summaryCards = [
    [
        'label' => 'Bài hát trên BXH',
        'value' => count($topSongs),
        'note'  => 'Hiển thị tối đa 20 bài hát'
    ],
    [
        'label' => 'Lượt tìm kiếm bài hát',
        'value' => number_format($totalSongSearches),
        'note'  => nln_charts_range_label($range)
    ],
    [
        'label' => 'Nghệ sĩ nổi bật',
        'value' => count($topArtists),
        'note'  => 'Hiển thị tối đa 10 nghệ sĩ'
    ]
];
?>

<header class="masthead charts-hero" style="background-image:url('assets/img/home-bg.jpg')">
    <div class="charts-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center charts-heading-shell">
            <span class="charts-eyebrow">Music Ranking</span>
            <h1>BXH</h1>
            <span class="subheading charts-subheading">
                Bảng xếp hạng âm nhạc dựa trên mức độ quan tâm và lượt tìm kiếm của người dùng.
            </span>

            <div class="charts-hero-chips">
                <span class="charts-chip">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= h(nln_charts_range_label($range)) ?>
                </span>
                <span class="charts-chip">
                    <i class="fas fa-fire me-2"></i>
                    <?= number_format(count($topSongs)) ?> bài hát
                </span>
                <span class="charts-chip">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= number_format(count($topArtists)) ?> nghệ sĩ
                </span>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5">
    <div class="charts-summary-bar">
        <?php foreach ($summaryCards as $card): ?>
            <div class="charts-summary-stat">
                <span><?= h($card['label']) ?></span>
                <strong><?= h((string) $card['value']) ?></strong>
                <small><?= h($card['note']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-7 mb-4">
            <section class="charts-panel">
                <div class="charts-panel-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <span class="section-kicker">Song Ranking</span>
                        <h4 class="charts-panel-title mb-1">Top 20 bài hát</h4>
                        <p class="charts-panel-subtitle mb-0">
                            Xếp hạng bài hát theo lượt tìm kiếm trong khoảng thời gian đã chọn.
                        </p>
                    </div>

                    <form method="get" class="charts-filter-form">
                        <label for="range" class="charts-filter-label">Khoảng thời gian</label>
                        <select name="range" id="range" class="charts-select" onchange="this.form.submit()">
                            <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All-time</option>
                            <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>Tháng</option>
                            <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>Tuần</option>
                        </select>
                    </form>
                </div>

                <?php if ($featuredSong): ?>
                    <a href="post.php?id=<?= (int) $featuredSong['song_id'] ?>" class="featured-song-card">
                        <img
                            src="<?= h($featuredSong['cover']) ?>"
                            alt="<?= h($featuredSong['title']) ?>"
                            class="featured-song-cover"
                        >

                        <div class="featured-song-copy">
                            <span class="featured-label">#1 bảng xếp hạng</span>
                            <h2><?= h($featuredSong['title']) ?></h2>
                            <p class="featured-meta"><?= h($featuredSong['artist_name']) ?></p>

                            <div class="featured-badges">
                                <span class="featured-badge">
                                    <?= number_format((int) $featuredSong['total']) ?> lượt tìm kiếm
                                </span>
                                <span class="featured-badge featured-badge-soft">
                                    <?= h(nln_charts_range_label($range)) ?>
                                </span>
                            </div>
                        </div>
                    </a>

                    <div class="charts-song-list">
                        <?php foreach ($remainingSongs as $index => $song): ?>
                            <a href="post.php?id=<?= (int) $song['song_id'] ?>" class="chart-song-card">
                                <div class="chart-rank-badge"><?= $index + 2 ?></div>

                                <img
                                    src="<?= h($song['cover']) ?>"
                                    alt="<?= h($song['title']) ?>"
                                    class="chart-song-cover"
                                >

                                <div class="chart-main-copy">
                                    <div class="chart-song-title"><?= h($song['title']) ?></div>
                                    <div class="chart-song-artist"><?= h($song['artist_name']) ?></div>
                                </div>

                                <div class="chart-side-meta">
                                    <span><?= h(nln_charts_range_label($range)) ?></span>
                                    <strong><?= number_format((int) $song['total']) ?> lượt</strong>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-chart-line"></i>
                        <h3>Chưa có dữ liệu xếp hạng bài hát</h3>
                        <p>Hiện chưa đủ dữ liệu tìm kiếm để tạo bảng xếp hạng trong khoảng thời gian này.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="col-lg-5 mb-4">
            <section class="charts-panel">
                <div class="charts-panel-header">
                    <span class="section-kicker">Artist Ranking</span>
                    <h4 class="charts-panel-title mb-1">Top nghệ sĩ</h4>
                    <p class="charts-panel-subtitle mb-0">
                        Những nghệ sĩ được quan tâm nhiều nhất dựa trên lượt tìm kiếm bài hát.
                    </p>
                </div>

                <?php if ($featuredArtist): ?>
                    <a href="artist.php?id=<?= (int) $featuredArtist['artist_id'] ?>" class="featured-artist-card">
                        <img
                            src="<?= h($featuredArtist['avatar_path']) ?>"
                            alt="<?= h($featuredArtist['artist_name']) ?>"
                            class="featured-artist-avatar"
                        >

                        <div class="featured-artist-copy">
                            <span class="featured-label">Nghệ sĩ #1</span>
                            <h2><?= h($featuredArtist['artist_name']) ?></h2>
                            <p class="featured-meta">
                                <?= number_format((int) $featuredArtist['total']) ?> lượt tìm kiếm
                            </p>
                        </div>
                    </a>

                    <div class="charts-artist-list">
                        <?php foreach ($remainingArtists as $index => $artist): ?>
                            <a href="artist.php?id=<?= (int) $artist['artist_id'] ?>" class="chart-artist-card">
                                <div class="chart-rank-badge"><?= $index + 2 ?></div>

                                <img
                                    src="<?= h($artist['avatar_path']) ?>"
                                    alt="<?= h($artist['artist_name']) ?>"
                                    class="chart-artist-avatar"
                                >

                                <div class="chart-main-copy">
                                    <div class="chart-song-title"><?= h($artist['artist_name']) ?></div>
                                    <div class="chart-song-artist">Mức độ quan tâm hiện tại</div>
                                </div>

                                <div class="chart-side-meta chart-side-meta-artist">
                                    <strong><?= number_format((int) $artist['total']) ?> lượt</strong>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-microphone-alt"></i>
                        <h3>Chưa có nghệ sĩ nổi bật</h3>
                        <p>Hiện chưa có đủ dữ liệu để tạo bảng xếp hạng nghệ sĩ.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<style>
.charts-hero {
    overflow: visible;
    padding-bottom: 4.8rem;
}

.charts-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.74) 0%, rgba(16, 24, 40, 0.48) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.charts-heading-shell {
    position: relative;
    z-index: 1;
}

.charts-eyebrow {
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

.charts-heading-shell h1 {
    font-size: clamp(2.3rem, 5.2vw, 4rem);
}

.charts-subheading {
    max-width: 700px;
    margin: .7rem auto 0;
    font-size: 1.05rem;
    line-height: 1.6;
}

.charts-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.45rem;
}

.charts-chip {
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

.charts-summary-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2.15rem;
    margin-bottom: 1.35rem;
    position: relative;
    z-index: 2;
}

.charts-summary-stat,
.charts-panel,
.featured-song-card,
.featured-artist-card,
.chart-song-card,
.chart-artist-card,
.content-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.charts-summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.charts-summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.charts-summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.charts-summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.charts-panel {
    height: 100%;
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.charts-panel-header {
    margin-bottom: 1rem;
    gap: 1rem;
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

.charts-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.charts-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.charts-filter-form {
    min-width: 180px;
}

.charts-filter-label {
    display: block;
    margin-bottom: .35rem;
    color: #6f7c91;
    font-size: .82rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.charts-select {
    width: 100%;
    padding: .78rem .95rem;
    border: 1px solid rgba(16, 24, 40, .1);
    border-radius: 14px;
    background: #f8fafc;
    color: #162033;
    font-size: .95rem;
    font-weight: 600;
    outline: none;
    box-shadow: none;
}

.charts-select:focus {
    border-color: rgba(13, 110, 253, .42);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
}

.featured-song-card,
.featured-artist-card {
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

.featured-song-card:hover,
.featured-artist-card:hover,
.chart-song-card:hover,
.chart-artist-card:hover {
    text-decoration: none;
    color: inherit;
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
}

.featured-song-cover,
.featured-artist-avatar {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    object-fit: cover;
}

.featured-artist-avatar {
    border-radius: 50%;
}

.featured-song-copy h2,
.featured-artist-copy h2 {
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
    margin-bottom: 0;
}

.featured-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .8rem;
}

.featured-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .38rem .7rem;
    border-radius: 999px;
    font-family: "Open Sans", sans-serif;
    font-size: .8rem;
    font-weight: 700;
    color: #0d6efd;
    background: rgba(13, 110, 253, .08);
}

.featured-badge-soft {
    color: #536277;
    background: #eef3fb;
}

.charts-song-list,
.charts-artist-list {
    display: grid;
    gap: .78rem;
}

.chart-song-card {
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

.chart-artist-card {
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

.chart-rank-badge {
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

.chart-song-cover,
.chart-artist-avatar {
    width: 100%;
    height: 68px;
    border-radius: 14px;
    object-fit: cover;
}

.chart-artist-avatar {
    border-radius: 50%;
}

.chart-song-title {
    color: #162033;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.45;
}

.chart-song-artist {
    margin-top: .18rem;
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
}

.chart-side-meta {
    min-width: 98px;
    text-align: right;
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
    color: #6f7c91;
}

.chart-side-meta strong {
    display: block;
    margin-top: .24rem;
    color: #162033;
}

.chart-side-meta-artist {
    min-width: 88px;
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

@media (max-width: 991.98px) {
    .charts-summary-bar {
        grid-template-columns: 1fr;
        margin-top: 0;
    }

    .featured-song-card,
    .featured-artist-card {
        grid-template-columns: 1fr;
    }

    .featured-song-cover,
    .featured-artist-avatar {
        max-height: 260px;
    }

    .featured-artist-avatar {
        border-radius: 18px;
    }
}

@media (max-width: 767.98px) {
    .charts-hero {
        padding-bottom: 5rem;
    }

    .charts-hero-chips {
        justify-content: flex-start;
    }

    .chart-song-card,
    .chart-artist-card {
        grid-template-columns: 1fr;
    }

    .chart-side-meta {
        text-align: left;
    }
}
</style>

<?php include('includes/footer.php'); ?>
