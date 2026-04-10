<?php
include('includes/session.php');
include('includes/database.php');
include('includes/header.php');
include('includes/navbar.php');
require_once __DIR__ . '/../config/song_helpers.php';
require_once __DIR__ . '/../config/recap_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

$recap = nln_get_user_monthly_recap($conn, $userId, $selectedMonth, $selectedYear);
$period = $recap['period'];
$topSongs = $recap['top_songs'] ?? [];
$topArtists = $recap['top_artists'] ?? [];
$topAlbum = $recap['top_album'] ?? [];
$topGenre = $recap['top_genre'] ?? [];
$insights = $recap['insights'] ?? [];
$metrics = $recap['metrics'] ?? [];
$hasData = (int) ($metrics['total_views'] ?? 0) > 0;

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
        'assets/img/default-avatar.png',
        'assets/img/default.jpg',
        'assets/img/home-bg.jpg',
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
        'assets/img/home-bg.jpg',
    ];

    foreach ($fallbacks as $fallback) {
        if (nln_public_asset_exists($fallback)) {
            return $fallback;
        }
    }

    return 'assets/img/home-bg.jpg';
}

function nln_recap_date_label($date)
{
    if (empty($date)) {
        return 'Chưa xác định';
    }

    $timestamp = strtotime((string) $date);
    if ($timestamp === false) {
        return 'Chưa xác định';
    }

    return date('d/m/Y', $timestamp);
}

function nln_recap_month_options($count = 12)
{
    $items = [];
    $base = new DateTime('first day of this month');

    for ($i = 0; $i < $count; $i++) {
        $date = (clone $base)->modify("-{$i} month");
        $items[] = [
            'month' => (int) $date->format('n'),
            'year' => (int) $date->format('Y'),
            'label' => 'Tháng ' . $date->format('n/Y'),
        ];
    }

    return $items;
}

$periodOptions = nln_recap_month_options(12);
$metricCards = [
    [
        'label' => 'Lượt quan tâm',
        'value' => number_format((int) ($metrics['total_views'] ?? 0)),
        'note' => 'Tổng số lượt xem hoặc tìm kiếm bài hát',
    ],
    [
        'label' => 'Bài hát khác nhau',
        'value' => number_format((int) ($metrics['unique_songs'] ?? 0)),
        'note' => 'Số bài hát bạn đã chạm tới trong tháng',
    ],
    [
        'label' => 'Nghệ sĩ khác nhau',
        'value' => number_format((int) ($metrics['unique_artists'] ?? 0)),
        'note' => 'Số nghệ sĩ xuất hiện trong lịch sử tháng này',
    ],
    [
        'label' => 'Ngày hoạt động',
        'value' => number_format((int) ($metrics['active_days'] ?? 0)),
        'note' => 'Số ngày bạn có tương tác với bài hát trong tháng',
    ],
];
?>

<header class="masthead recap-hero" style="background-image:url('assets/img/home-bg.jpg')">
    <div class="recap-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center recap-heading-shell">
            <span class="recap-eyebrow">Monthly Recap</span>
            <h1><?= h($period['label']) ?></h1>
            <span class="subheading recap-subheading">
                Tổng hợp nhanh những bài hát, nghệ sĩ và xu hướng khám phá âm nhạc nổi bật của bạn trong tháng đã chọn.
            </span>

            <div class="recap-hero-chips">
                <span class="recap-chip">
                    <i class="fas fa-fire me-2"></i>
                    <?= h(number_format((int) ($metrics['total_views'] ?? 0))) ?> lượt quan tâm
                </span>
                <span class="recap-chip">
                    <i class="fas fa-compact-disc me-2"></i>
                    <?= h(number_format((int) ($metrics['unique_songs'] ?? 0))) ?> bài hát
                </span>
                <span class="recap-chip">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= h(number_format((int) ($metrics['unique_artists'] ?? 0))) ?> nghệ sĩ
                </span>
                <span class="recap-chip">
                    <i class="fas fa-calendar-day me-2"></i>
                    <?= h(number_format((int) ($metrics['active_days'] ?? 0))) ?> ngày hoạt động
                </span>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5">
    <section class="recap-toolbar">
        <div>
            <span class="section-kicker">Recap Filter</span>
            <h3 class="recap-toolbar-title">Xem lại hoạt động theo tháng</h3>
            <p class="recap-toolbar-subtitle mb-0">
                Chọn một mốc thời gian gần đây để xem lại xu hướng quan tâm âm nhạc của bạn.
            </p>
        </div>

        <form method="get" class="recap-filter-form">
            <label for="recap_period" class="recap-filter-label">Thời gian</label>
            <select id="recap_period" class="recap-select" onchange="if (this.value) { window.location.href = this.value; }">
                <?php foreach ($periodOptions as $option): ?>
                    <?php
                    $url = 'recap.php?month=' . (int) $option['month'] . '&year=' . (int) $option['year'];
                    $isSelected = (int) $option['month'] === (int) $period['month']
                        && (int) $option['year'] === (int) $period['year'];
                    ?>
                    <option value="<?= h($url) ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= h($option['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </section>

    <?php if ($hasData): ?>
        <div class="recap-summary-bar">
            <?php foreach ($metricCards as $card): ?>
                <div class="recap-summary-stat">
                    <span><?= h($card['label']) ?></span>
                    <strong><?= h($card['value']) ?></strong>
                    <small><?= h($card['note']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="recap-panel">
                    <div class="recap-panel-header">
                        <span class="section-kicker">Top Songs</span>
                        <h4 class="recap-panel-title">Bài hát nổi bật trong tháng</h4>
                        <p class="recap-panel-subtitle mb-0">
                            Những bài hát bạn quay lại hoặc tìm kiếm nhiều nhất trong giai đoạn đã chọn.
                        </p>
                    </div>

                    <div class="recap-song-list">
                        <?php foreach ($topSongs as $index => $song): ?>
                            <?php
                            $cover = nln_public_song_cover_path($song['song_cover'] ?? null, $song['album_cover'] ?? null);
                            ?>
                            <a href="post.php?id=<?= (int) $song['song_id'] ?>" class="recap-song-item">
                                <span class="recap-rank"><?= (int) ($index + 1) ?></span>
                                <img src="<?= h($cover) ?>" alt="<?= h($song['title']) ?>" class="recap-song-cover">
                                <div class="recap-song-copy">
                                    <strong><?= h($song['title']) ?></strong>
                                    <span>
                                        <?= h($song['artist_name']) ?>
                                        <?php if (!empty($song['album_name'])): ?>
                                            · <?= h($song['album_name']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <span class="recap-song-metric"><?= h(number_format((int) $song['total_views'])) ?> lượt</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="recap-panel mb-4">
                    <div class="recap-panel-header">
                        <span class="section-kicker">Narrative</span>
                        <h4 class="recap-panel-title">Tóm tắt tháng của bạn</h4>
                    </div>
                    <div class="recap-highlight-copy">
                        <?= h($recap['summary_text'] ?? '') ?>
                    </div>
                </section>

                <section class="recap-panel mb-4">
                    <div class="recap-panel-header">
                        <span class="section-kicker">Top Artists</span>
                        <h4 class="recap-panel-title">Nghệ sĩ nổi bật</h4>
                    </div>

                    <div class="recap-artist-list">
                        <?php foreach ($topArtists as $artist): ?>
                            <a href="artist.php?id=<?= (int) $artist['artist_id'] ?>" class="recap-artist-item">
                                <img
                                    src="<?= h(nln_public_artist_avatar_path($artist['avatar'] ?? null)) ?>"
                                    alt="<?= h($artist['artist_name']) ?>"
                                    class="recap-artist-avatar"
                                >
                                <div class="recap-artist-copy">
                                    <strong><?= h($artist['artist_name']) ?></strong>
                                    <span><?= h(number_format((int) $artist['total_views'])) ?> lượt liên quan</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if (!empty($topAlbum)): ?>
                    <section class="recap-panel mb-4">
                        <div class="recap-panel-header">
                            <span class="section-kicker">Top Album</span>
                            <h4 class="recap-panel-title">Album được quay lại nhiều nhất</h4>
                        </div>

                        <a href="album.php?id=<?= (int) $topAlbum['album_id'] ?>" class="recap-top-album">
                            <img
                                src="<?= h(nln_public_album_cover_path($topAlbum['cover_image'] ?? null)) ?>"
                                alt="<?= h($topAlbum['album_name']) ?>"
                                class="recap-top-album-cover"
                            >
                            <div class="recap-top-album-copy">
                                <strong><?= h($topAlbum['album_name']) ?></strong>
                                <span><?= h($topAlbum['artist_name']) ?></span>
                                <small><?= h(number_format((int) $topAlbum['total_views'])) ?> lượt liên quan</small>
                            </div>
                        </a>
                    </section>
                <?php endif; ?>

                <section class="recap-panel">
                    <div class="recap-panel-header">
                        <span class="section-kicker">Listening Style</span>
                        <h4 class="recap-panel-title">Xu hướng khám phá</h4>
                    </div>

                    <div class="recap-insight-grid">
                        <div class="recap-insight-card">
                            <span>Bài quay lại</span>
                            <strong><?= h(number_format((int) ($insights['repeat_songs'] ?? 0))) ?></strong>
                        </div>
                        <div class="recap-insight-card">
                            <span>Bài khám phá</span>
                            <strong><?= h(number_format((int) ($insights['discovery_songs'] ?? 0))) ?></strong>
                        </div>
                        <div class="recap-insight-card recap-insight-card-wide">
                            <span>Thể loại nổi bật</span>
                            <strong>
                                <?= h($topGenre['genre_name'] ?? 'Chưa xác định') ?>
                                <?php if (!empty($insights['top_genre_share_percent'])): ?>
                                    · <?= h((string) (int) $insights['top_genre_share_percent']) ?>%
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="recap-insight-card recap-insight-card-wide">
                            <span>Xu hướng tháng này</span>
                            <strong>
                                <?php
                                $style = $insights['listening_style'] ?? 'balanced';
                                if ($style === 'repeat') {
                                    echo 'Thiên về nghe lại';
                                } elseif ($style === 'discovery') {
                                    echo 'Thiên về khám phá';
                                } else {
                                    echo 'Cân bằng';
                                }
                                ?>
                            </strong>
                        </div>
                        <div class="recap-insight-card recap-insight-card-wide">
                            <span>Ngày hoạt động cao nhất</span>
                            <strong>
                                <?= h(nln_recap_date_label($insights['peak_day'] ?? '')) ?>
                                <?php if (!empty($insights['peak_day_total_views'])): ?>
                                    · <?= h(number_format((int) $insights['peak_day_total_views'])) ?> lượt
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="recap-insight-card recap-insight-card-wide">
                            <span>Album đã chạm tới</span>
                            <strong><?= h(number_format((int) ($metrics['unique_albums'] ?? 0))) ?> album</strong>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="recap-cta-row">
            <a href="profile.php" class="recap-cta recap-cta-primary">Xem gợi ý dành cho bạn</a>
            <a href="charts.php" class="recap-cta recap-cta-secondary">Khám phá bảng xếp hạng</a>
        </section>
    <?php else: ?>
        <section class="recap-empty-state">
            <span class="section-kicker">Not Enough Data</span>
            <h3>Chưa có đủ hoạt động để tạo recap</h3>
            <p>
                Bạn chưa có đủ dữ liệu trong <?= h($period['label']) ?> để tạo bản tổng hợp âm nhạc.
                Hãy khám phá thêm bài hát, nghệ sĩ hoặc bảng xếp hạng rồi quay lại sau.
            </p>
            <div class="recap-empty-actions">
                <a href="search.php" class="recap-cta recap-cta-primary">Tìm bài hát</a>
                <a href="charts.php" class="recap-cta recap-cta-secondary">Xem charts</a>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>

<style>
.recap-hero {
    position: relative;
}

.recap-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(20, 34, 58, 0.78), rgba(45, 69, 108, 0.58)),
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 30%);
}

.recap-heading-shell {
    max-width: 860px;
    margin: 0 auto;
}

.recap-eyebrow,
.section-kicker {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.45rem 0.95rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-size: 0.78rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-weight: 800;
}

.section-kicker {
    background: #eaf1ff;
    color: #2962ff;
}

.recap-subheading {
    max-width: 760px;
    margin: 1rem auto 0;
    color: rgba(255, 255, 255, 0.92);
}

.recap-hero-chips {
    margin-top: 2rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.9rem;
}

.recap-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.9rem 1.2rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #fff;
    font-weight: 700;
}

.recap-toolbar,
.recap-panel,
.recap-empty-state {
    background: #fff;
    border: 1px solid rgba(20, 34, 58, 0.08);
    border-radius: 28px;
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
}

.recap-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1.5rem;
    padding: 1.6rem 1.7rem;
    margin-bottom: 1.5rem;
}

.recap-toolbar-title,
.recap-panel-title {
    margin: 0.5rem 0 0.35rem;
    font-size: 1.85rem;
    font-weight: 800;
    color: #112347;
}

.recap-toolbar-subtitle,
.recap-panel-subtitle {
    color: #677a9d;
}

.recap-filter-form {
    min-width: 220px;
}

.recap-filter-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6a7ea5;
    font-size: 0.92rem;
    font-weight: 700;
}

.recap-select {
    width: 100%;
    min-height: 52px;
    border-radius: 18px;
    border: 1px solid rgba(41, 98, 255, 0.16);
    background: #f4f8ff;
    color: #112347;
    font-weight: 700;
    padding: 0 1rem;
    outline: none;
}

.recap-summary-bar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.recap-summary-stat {
    background: #fff;
    border: 1px solid rgba(20, 34, 58, 0.08);
    border-radius: 24px;
    padding: 1.25rem 1.3rem;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
}

.recap-summary-stat span,
.recap-insight-card span {
    display: block;
    color: #7588a8;
    font-size: 0.86rem;
    font-weight: 700;
}

.recap-summary-stat strong,
.recap-insight-card strong {
    display: block;
    margin-top: 0.45rem;
    color: #112347;
    font-size: 1.75rem;
    font-weight: 800;
}

.recap-summary-stat small {
    display: block;
    margin-top: 0.45rem;
    color: #8493ae;
}

.recap-panel {
    padding: 1.55rem;
}

.recap-song-list,
.recap-artist-list {
    display: grid;
    gap: 0.9rem;
}

.recap-song-item,
.recap-artist-item,
.recap-top-album {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
    background: #f8fbff;
    border: 1px solid rgba(41, 98, 255, 0.08);
    border-radius: 22px;
    padding: 0.95rem 1rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.recap-song-item:hover,
.recap-artist-item:hover,
.recap-top-album:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(41, 98, 255, 0.09);
    border-color: rgba(41, 98, 255, 0.16);
}

.recap-rank {
    width: 42px;
    height: 42px;
    border-radius: 16px;
    background: #eaf1ff;
    color: #2962ff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    flex: 0 0 auto;
}

.recap-song-cover,
.recap-top-album-cover {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    object-fit: cover;
    flex: 0 0 auto;
}

.recap-artist-avatar {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    object-fit: cover;
    flex: 0 0 auto;
}

.recap-song-copy,
.recap-artist-copy,
.recap-top-album-copy {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.recap-song-copy strong,
.recap-artist-copy strong,
.recap-top-album-copy strong {
    color: #112347;
    font-size: 1.05rem;
    font-weight: 800;
}

.recap-song-copy span,
.recap-artist-copy span,
.recap-top-album-copy span,
.recap-top-album-copy small,
.recap-song-metric {
    color: #6f82a3;
    font-weight: 600;
}

.recap-song-metric {
    margin-left: auto;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 0 0.9rem;
    border-radius: 999px;
    background: #eef5ff;
    color: #2962ff;
    font-weight: 800;
}

.recap-highlight-copy {
    color: #304669;
    font-size: 1.03rem;
    line-height: 1.8;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    border: 1px solid rgba(41, 98, 255, 0.08);
    border-radius: 22px;
    padding: 1rem 1.05rem;
}

.recap-insight-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.recap-insight-card {
    background: #f8fbff;
    border: 1px solid rgba(41, 98, 255, 0.08);
    border-radius: 22px;
    padding: 1rem 1.05rem;
}

.recap-insight-card strong {
    font-size: 1.2rem;
}

.recap-insight-card-wide {
    grid-column: 1 / -1;
}

.recap-cta-row,
.recap-empty-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
}

.recap-cta-row {
    margin-top: 1.5rem;
}

.recap-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0 1.25rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 800;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.recap-cta:hover {
    transform: translateY(-1px);
}

.recap-cta-primary {
    background: linear-gradient(135deg, #1f98c2, #2a7eaa);
    color: #fff;
    box-shadow: 0 14px 26px rgba(31, 152, 194, 0.2);
}

.recap-cta-secondary {
    background: #fff;
    color: #205b8d;
    border: 1px solid rgba(32, 91, 141, 0.18);
}

.recap-empty-state {
    padding: 2rem;
}

.recap-empty-state h3 {
    margin: 0.7rem 0;
    color: #112347;
    font-weight: 800;
}

.recap-empty-state p {
    max-width: 620px;
    color: #63789a;
    line-height: 1.8;
}

@media (max-width: 991.98px) {
    .recap-summary-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .recap-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 767.98px) {
    .recap-summary-bar,
    .recap-insight-grid {
        grid-template-columns: 1fr;
    }

    .recap-song-item,
    .recap-artist-item,
    .recap-top-album {
        align-items: flex-start;
    }

    .recap-song-item {
        flex-wrap: wrap;
    }

    .recap-song-metric {
        margin-left: 0;
        width: 100%;
        padding-left: calc(42px + 1rem);
    }
}
</style>
