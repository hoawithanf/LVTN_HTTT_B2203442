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

function nln_public_news_image_path($filename = null)
{
    $filename = trim((string) $filename);

    if ($filename !== '') {
        $safeName = basename($filename);
        $candidate = 'assets/img/news/' . $safeName;

        if (nln_public_asset_exists($candidate)) {
            return $candidate;
        }
    }

    $fallbacks = [
        'assets/img/post-sample-image.jpg',
        'assets/img/home-bg.jpg'
    ];

    foreach ($fallbacks as $fallback) {
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

$hotSongs = [];
$featuredHotSong = null;
$remainingHotSongs = [];
$hotSearchTotal = 0;

$sqlHot = "
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
    WHERE YEARWEEK(l.search_time, 1) = YEARWEEK(CURDATE(), 1)
    GROUP BY
        s.song_id,
        s.title,
        a.artist_name,
        s.cover_image,
        al.cover_image
    ORDER BY total DESC, s.title ASC
    LIMIT 10
";

if ($stmtHot = $conn->prepare($sqlHot)) {
    $stmtHot->execute();
    $resultHot = $stmtHot->get_result();

    while ($row = $resultHot->fetch_assoc()) {
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
        $row['total'] = (int) ($row['total'] ?? 0);
        $hotSearchTotal += $row['total'];
        $hotSongs[] = $row;
    }

    $stmtHot->close();
}

$featuredHotSong = $hotSongs[0] ?? null;
$remainingHotSongs = array_slice($hotSongs, 1);

$newsItems = [];
$featuredNews = null;
$remainingNews = [];
$latestNewsDate = '';

$sqlNews = "
    SELECT news_id, title, image, created_at
    FROM news
    ORDER BY created_at DESC
    LIMIT 6
";

if ($stmtNews = $conn->prepare($sqlNews)) {
    $stmtNews->execute();
    $resultNews = $stmtNews->get_result();

    while ($row = $resultNews->fetch_assoc()) {
        $row['image_path'] = nln_public_news_image_path($row['image'] ?? null);
        $newsItems[] = $row;
    }

    $stmtNews->close();
}

$featuredNews = $newsItems[0] ?? null;
$remainingNews = array_slice($newsItems, 1);
$latestNewsDate = !empty($featuredNews['created_at']) ? nln_format_date($featuredNews['created_at']) : '';

$summaryCards = [
    [
        'label' => 'Bài hát nổi bật tuần',
        'value' => count($hotSongs),
        'note'  => 'Top theo lượt tìm kiếm tuần này'
    ],
    [
        'label' => 'Tổng lượt tìm top tuần',
        'value' => number_format($hotSearchTotal),
        'note'  => 'Tính trên 10 bài hát nổi bật'
    ],
    [
        'label' => 'Tin tức mới nhất',
        'value' => count($newsItems),
        'note'  => $latestNewsDate !== '' ? 'Cập nhật gần nhất ' . $latestNewsDate : 'Chưa có dữ liệu tin tức'
    ]
];
?>

<header class="masthead home-hero" style="background-image: url('assets/img/home-bg.jpg')">
    <div class="home-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center home-heading-shell">
            <span class="home-eyebrow">Music Discovery Platform</span>
            <h1>NLN Lyrics</h1>
            <span class="subheading home-subheading">
                Khám phá lời nhạc, ý nghĩa bài hát và những nội dung âm nhạc đang được quan tâm nhiều nhất.
            </span>

            <div class="home-hero-chips">
                <span class="home-chip">
                    <i class="fas fa-fire me-2"></i>
                    <?= number_format(count($hotSongs)) ?> bài hát hot
                </span>
                <span class="home-chip">
                    <i class="fas fa-newspaper me-2"></i>
                    <?= number_format(count($newsItems)) ?> tin mới
                </span>
                <span class="home-chip">
                    <i class="fas fa-search me-2"></i>
                    <?= number_format($hotSearchTotal) ?> lượt tìm kiếm
                </span>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5">
    <div class="home-summary-bar">
        <?php foreach ($summaryCards as $card): ?>
            <div class="summary-stat">
                <span><?= h($card['label']) ?></span>
                <strong><?= h((string) $card['value']) ?></strong>
                <small><?= h($card['note']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-7 mb-4">
            <section class="home-panel">
                <div class="home-panel-header">
                    <div>
                        <span class="section-kicker">Trending Now</span>
                        <h4 class="home-panel-title mb-1">Top 10 bài hát hot tuần này</h4>
                        <p class="home-panel-subtitle mb-0">
                            Danh sách bài hát được tìm kiếm nhiều nhất trong tuần hiện tại.
                        </p>
                    </div>
                </div>

                <?php if ($featuredHotSong): ?>
                    <a href="post.php?id=<?= (int) $featuredHotSong['song_id'] ?>" class="featured-track-card">
                        <img
                            src="<?= h($featuredHotSong['cover']) ?>"
                            alt="<?= h($featuredHotSong['title']) ?>"
                            class="featured-track-cover"
                        >

                        <div class="featured-track-copy">
                            <span class="featured-label">#1 tuần này</span>
                            <h2><?= h($featuredHotSong['title']) ?></h2>
                            <p class="featured-meta"><?= h($featuredHotSong['artist_name']) ?></p>

                            <div class="featured-badges">
                                <span class="featured-badge">
                                    <?= number_format((int) $featuredHotSong['total']) ?> lượt tìm kiếm
                                </span>
                                <span class="featured-badge featured-badge-soft">Bài hát nổi bật</span>
                            </div>
                        </div>
                    </a>

                    <div class="track-list-modern">
                        <?php foreach ($remainingHotSongs as $index => $row): ?>
                            <a href="post.php?id=<?= (int) $row['song_id'] ?>" class="track-card-modern">
                                <div class="track-rank-badge"><?= $index + 2 ?></div>

                                <img
                                    src="<?= h($row['cover']) ?>"
                                    alt="<?= h($row['title']) ?>"
                                    class="track-cover-modern"
                                >

                                <div class="track-main-copy">
                                    <div class="track-title-modern"><?= h($row['title']) ?></div>
                                    <div class="track-artist-modern"><?= h($row['artist_name']) ?></div>
                                </div>

                                <div class="track-side-meta">
                                    <span>Tuần này</span>
                                    <strong><?= number_format((int) $row['total']) ?> lượt</strong>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-music"></i>
                        <h3>Chưa có dữ liệu bài hát hot</h3>
                        <p>Hệ thống chưa ghi nhận đủ lượt tìm kiếm trong tuần hiện tại để tạo bảng xếp hạng.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="col-lg-5 mb-4">
            <section class="home-panel">
                <div class="home-panel-header">
                    <div>
                        <span class="section-kicker">Latest Updates</span>
                        <h4 class="home-panel-title mb-1">Tin tức mới nhất</h4>
                        <p class="home-panel-subtitle mb-0">
                            Tổng hợp nhanh những bài viết mới được cập nhật trên hệ thống.
                        </p>
                    </div>
                </div>

                <?php if ($featuredNews): ?>
                    <a href="news.php?id=<?= (int) $featuredNews['news_id'] ?>" class="featured-news-card">
                        <img
                            src="<?= h($featuredNews['image_path']) ?>"
                            alt="<?= h($featuredNews['title']) ?>"
                            class="featured-news-cover"
                        >

                        <div class="featured-news-copy">
                            <span class="featured-label">Tin mới nhất</span>
                            <h2><?= h($featuredNews['title']) ?></h2>
                            <?php if (!empty($featuredNews['created_at'])): ?>
                                <p class="featured-meta">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= h(nln_format_date($featuredNews['created_at'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="news-list-modern">
                        <?php foreach ($remainingNews as $n): ?>
                            <a href="news.php?id=<?= (int) $n['news_id'] ?>" class="news-card-modern">
                                <img
                                    src="<?= h($n['image_path']) ?>"
                                    alt="<?= h($n['title']) ?>"
                                    class="news-cover-modern"
                                >

                                <div class="news-main-copy">
                                    <div class="news-title-modern"><?= h($n['title']) ?></div>
                                    <?php if (!empty($n['created_at'])): ?>
                                        <div class="news-date-modern">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= h(nln_format_date($n['created_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state">
                        <i class="fas fa-newspaper"></i>
                        <h3>Chưa có tin tức</h3>
                        <p>Hiện chưa có bài viết mới để hiển thị trên trang chủ.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<style>
.home-hero {
    overflow: visible;
    padding-bottom: 4.8rem;
}

.home-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.74) 0%, rgba(16, 24, 40, 0.48) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.home-heading-shell {
    position: relative;
    z-index: 1;
}

.home-eyebrow {
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

.home-heading-shell h1 {
    font-size: clamp(2.3rem, 5.2vw, 4rem);
}

.home-subheading {
    max-width: 700px;
    margin: .7rem auto 0;
    font-size: 1.05rem;
    line-height: 1.6;
}

.home-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.45rem;
}

.home-chip {
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

.home-summary-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2.15rem;
    margin-bottom: 1.35rem;
    position: relative;
    z-index: 2;
}

.summary-stat,
.home-panel,
.featured-track-card,
.featured-news-card,
.track-card-modern,
.news-card-modern,
.content-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.home-panel {
    height: 100%;
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.home-panel-header {
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

.home-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.home-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.featured-track-card,
.featured-news-card {
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

.featured-track-card:hover,
.featured-news-card:hover,
.track-card-modern:hover,
.news-card-modern:hover {
    text-decoration: none;
    color: inherit;
}

.featured-track-cover,
.featured-news-cover {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    object-fit: cover;
}

.featured-track-copy h2,
.featured-news-copy h2 {
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

.track-list-modern,
.news-list-modern {
    display: grid;
    gap: .78rem;
}

.track-card-modern {
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

.news-card-modern {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: .8rem;
    align-items: center;
    padding: .82rem;
    border-radius: 18px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    transition: transform .2s ease, box-shadow .2s ease;
}

.track-card-modern:hover,
.news-card-modern:hover,
.featured-track-card:hover,
.featured-news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
}

.track-rank-badge {
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

.track-cover-modern,
.news-cover-modern {
    width: 100%;
    height: 68px;
    border-radius: 14px;
    object-fit: cover;
}

.news-cover-modern {
    height: 72px;
}

.track-title-modern,
.news-title-modern {
    color: #162033;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.45;
}

.track-artist-modern,
.news-date-modern {
    margin-top: .18rem;
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
}

.track-side-meta {
    min-width: 88px;
    text-align: right;
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
    color: #6f7c91;
}

.track-side-meta strong {
    display: block;
    margin-top: .24rem;
    color: #162033;
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
    .home-summary-bar {
        grid-template-columns: 1fr;
        margin-top: 0;
    }

    .featured-track-card,
    .featured-news-card {
        grid-template-columns: 1fr;
    }

    .featured-track-cover,
    .featured-news-cover {
        max-height: 260px;
    }
}

@media (max-width: 767.98px) {
    .home-hero {
        padding-bottom: 5rem;
    }

    .home-hero-chips {
        justify-content: flex-start;
    }

    .track-card-modern {
        grid-template-columns: 1fr;
    }

    .track-side-meta {
        text-align: left;
    }
}
</style>

<?php include('includes/footer.php'); ?>