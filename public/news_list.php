<?php
include('includes/session.php');
include('includes/database.php');
include('includes/header.php');
include('includes/navbar.php');

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
        'assets/img/post-bg.jpg',
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

$user_id = $_SESSION['user_id'] ?? null;

/* ================== FILTER ================== */
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'followed'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
if ($filter === 'followed' && !$user_id) {
    $filter = 'all';
}

/* ================== PAGINATION ================== */
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

/* ================== BUILD QUERY BASE ================== */
$params = [];
$types = '';

$sqlBase = "
    FROM news n
";

if ($filter === 'followed' && $user_id) {
    $sqlBase .= "
        INNER JOIN artist_follows af
            ON af.artist_id = n.artist_id
            AND af.user_id = ?
    ";
    $params[] = $user_id;
    $types .= 'i';
}

/* ================== COUNT ================== */
$total = 0;
$sqlCount = "SELECT COUNT(*) AS total " . $sqlBase;
if ($stmtCount = $conn->prepare($sqlCount)) {
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $countResult = $stmtCount->get_result()->fetch_assoc();
    $total = (int) ($countResult['total'] ?? 0);
    $stmtCount->close();
}

$totalPages = max(1, (int) ceil($total / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/* ================== LIST ================== */
$newsItems = [];

$sql = "
    SELECT
        n.news_id,
        n.title,
        n.image,
        n.summary,
        n.created_at,
        (n.created_at >= NOW() - INTERVAL 1 DAY) AS is_new,
        COALESCE(ar.artist_name, 'Tin tức hệ thống') AS artist_name
    $sqlBase
    LEFT JOIN artists ar ON ar.artist_id = n.artist_id
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
";

$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;

if ($stmtList = $conn->prepare($sql)) {
    $stmtList->bind_param($listTypes, ...$listParams);
    $stmtList->execute();
    $newsResult = $stmtList->get_result();

    while ($row = $newsResult->fetch_assoc()) {
        $row['image_path'] = nln_public_news_image_path($row['image'] ?? null);
        $row['is_new'] = !empty($row['is_new']);
        $newsItems[] = $row;
    }

    $stmtList->close();
}

$featuredNews = $newsItems[0] ?? null;
$remainingNews = array_slice($newsItems, 1);

$summaryCards = [
    [
        'label' => 'Tổng tin hiện có',
        'value' => number_format($total),
        'note'  => 'Dựa trên bộ lọc hiện tại'
    ],
    [
        'label' => 'Trang hiện tại',
        'value' => number_format($page),
        'note'  => 'Trên ' . number_format($totalPages) . ' trang'
    ],
    [
        'label' => 'Chế độ hiển thị',
        'value' => $filter === 'followed' ? 'Theo dõi' : 'Tất cả',
        'note'  => $filter === 'followed' ? 'Tin từ nghệ sĩ bạn quan tâm' : 'Toàn bộ bài viết mới'
    ]
];
?>

<header class="masthead news-list-hero" style="background-image: url('assets/img/post-bg.jpg')">
    <div class="news-list-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center news-list-heading-shell">
            <span class="news-list-eyebrow">News Hub</span>
            <h1>Tin tức âm nhạc</h1>
            <span class="subheading news-list-subheading">
                Cập nhật nhanh các bài viết mới nhất từ nghệ sĩ và hệ thống nội dung âm nhạc.
            </span>

            <div class="news-list-hero-chips">
                <span class="news-list-chip">
                    <i class="fas fa-newspaper me-2"></i>
                    <?= number_format($total) ?> bài viết
                </span>
                <span class="news-list-chip">
                    <i class="fas fa-filter me-2"></i>
                    <?= $filter === 'followed' ? 'Nghệ sĩ tôi theo dõi' : 'Tất cả tin tức' ?>
                </span>
                <span class="news-list-chip">
                    <i class="fas fa-layer-group me-2"></i>
                    <?= number_format($perPage) ?> tin / trang
                </span>
            </div>
        </div>
    </div>
</header>

<section class="container px-4 px-lg-5 mt-4 mb-5">
    <div class="news-list-summary-bar">
        <?php foreach ($summaryCards as $card): ?>
            <div class="news-list-summary-stat">
                <span><?= h($card['label']) ?></span>
                <strong><?= h((string) $card['value']) ?></strong>
                <small><?= h($card['note']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="news-list-panel">
        <div class="news-list-panel-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <div class="mb-3 mb-lg-0">
                <span class="section-kicker">Latest Stories</span>
                <h4 class="news-list-panel-title mb-1">Danh sách tin tức</h4>
                <p class="news-list-panel-subtitle mb-0">
                    Chọn chế độ hiển thị để xem tất cả tin tức hoặc chỉ các bài viết từ nghệ sĩ bạn đang theo dõi.
                </p>
            </div>

            <div class="news-list-filter-group">
                <a href="news_list.php?filter=all"
                   class="news-filter-pill <?= $filter === 'all' ? 'active' : '' ?>">
                    Tất cả
                </a>

                <?php if ($user_id): ?>
                    <a href="news_list.php?filter=followed"
                       class="news-filter-pill <?= $filter === 'followed' ? 'active' : '' ?>">
                        Nghệ sĩ tôi theo dõi
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($featuredNews)): ?>
            <a href="news.php?id=<?= (int) $featuredNews['news_id'] ?>" class="featured-news-list-card">
                <img
                    src="<?= h($featuredNews['image_path']) ?>"
                    alt="<?= h($featuredNews['title']) ?>"
                    class="featured-news-list-cover"
                >

                <div class="featured-news-list-copy">
                    <div class="featured-news-list-top">
                        <span class="featured-label">Bài viết nổi bật</span>
                        <?php if ($featuredNews['is_new']): ?>
                            <span class="news-status-pill">Tin mới</span>
                        <?php endif; ?>
                    </div>

                    <h2><?= h($featuredNews['title']) ?></h2>

                    <?php if (!empty($featuredNews['summary'])): ?>
                        <p class="featured-news-list-summary"><?= h($featuredNews['summary']) ?></p>
                    <?php endif; ?>

                    <div class="featured-news-list-meta">
                        <span>
                            <i class="fas fa-user-edit me-2"></i>
                            <?= h($featuredNews['artist_name']) ?>
                        </span>
                        <span>
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= h(nln_format_date($featuredNews['created_at'] ?? '')) ?>
                        </span>
                    </div>
                </div>
            </a>

            <div class="news-list-grid">
                <?php foreach ($remainingNews as $n): ?>
                    <a href="news.php?id=<?= (int) $n['news_id'] ?>" class="news-list-card-modern">
                        <img
                            src="<?= h($n['image_path']) ?>"
                            alt="<?= h($n['title']) ?>"
                            class="news-list-card-cover"
                        >

                        <div class="news-list-card-copy">
                            <div class="news-list-card-top">
                                <span class="news-list-artist-tag"><?= h($n['artist_name']) ?></span>
                                <?php if ($n['is_new']): ?>
                                    <span class="news-status-pill">Tin mới</span>
                                <?php endif; ?>
                            </div>

                            <h3><?= h($n['title']) ?></h3>

                            <?php if (!empty($n['summary'])): ?>
                                <p><?= h($n['summary']) ?></p>
                            <?php endif; ?>

                            <div class="news-list-card-meta">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?= h(nln_format_date($n['created_at'] ?? '')) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="content-empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>Chưa có tin tức để hiển thị</h3>
                <p>
                    <?= $filter === 'followed'
                        ? 'Hiện chưa có bài viết mới từ các nghệ sĩ bạn đang theo dõi.'
                        : 'Hệ thống chưa có bài viết nào để hiển thị.' ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="modern-pagination-wrap" aria-label="Phân trang tin tức">
                <ul class="modern-pagination">
                    <?php if ($page > 1): ?>
                        <li>
                            <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page - 1 ?>" class="pagination-pill">
                                &laquo;
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>"
                               class="pagination-pill <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li>
                            <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page + 1 ?>" class="pagination-pill">
                                &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </section>
</section>

<style>
.news-list-hero {
    overflow: visible;
    padding-bottom: 4.8rem;
}

.news-list-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.76) 0%, rgba(16, 24, 40, 0.48) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.news-list-heading-shell {
    position: relative;
    z-index: 1;
}

.news-list-eyebrow {
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

.news-list-heading-shell h1 {
    font-size: clamp(2.3rem, 5.2vw, 4rem);
}

.news-list-subheading {
    max-width: 720px;
    margin: .7rem auto 0;
    font-size: 1.05rem;
    line-height: 1.6;
}

.news-list-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.45rem;
}

.news-list-chip {
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

.news-list-summary-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2.15rem;
    margin-bottom: 1.35rem;
    position: relative;
    z-index: 2;
}

.news-list-summary-stat,
.news-list-panel,
.featured-news-list-card,
.news-list-card-modern,
.content-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.news-list-summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.news-list-summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.news-list-summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.news-list-summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.news-list-panel {
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.news-list-panel-header {
    gap: 1rem;
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

.news-list-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.news-list-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.news-list-filter-group {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
}

.news-filter-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .75rem 1rem;
    border-radius: 999px;
    border: 1px solid rgba(16, 24, 40, .08);
    background: #f8fafc;
    color: #536277;
    text-decoration: none;
    font-weight: 700;
    font-size: .92rem;
    transition: all .2s ease;
}

.news-filter-pill:hover,
.news-filter-pill.active {
    text-decoration: none;
    color: #0d6efd;
    background: rgba(13, 110, 253, .08);
    border-color: rgba(13, 110, 253, .14);
}

.featured-news-list-card {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 1rem;
    align-items: center;
    padding: .95rem;
    border-radius: 22px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: 1rem;
}

.featured-news-list-card:hover,
.news-list-card-modern:hover {
    text-decoration: none;
    color: inherit;
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
}

.featured-news-list-cover {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 18px;
}

.featured-news-list-top,
.news-list-card-top {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .65rem;
}

.featured-label,
.news-status-pill,
.news-list-artist-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .36rem .72rem;
    border-radius: 999px;
    font-family: "Open Sans", sans-serif;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.featured-label,
.news-list-artist-tag {
    color: #0d6efd;
    background: rgba(13, 110, 253, .08);
}

.news-status-pill {
    color: #dc3545;
    background: rgba(220, 53, 69, .1);
}

.featured-news-list-copy h2 {
    margin-bottom: .6rem;
    color: #162033;
    font-size: 1.5rem;
    line-height: 1.35;
    font-weight: 800;
}

.featured-news-list-summary {
    margin-bottom: .8rem;
    color: #6f7c91;
    font-size: .96rem;
    line-height: 1.65;
    font-family: "Open Sans", sans-serif;
}

.featured-news-list-meta,
.news-list-card-meta {
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
    font-family: "Open Sans", sans-serif;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.news-list-grid {
    display: grid;
    gap: .85rem;
}

.news-list-card-modern {
    display: grid;
    grid-template-columns: 140px minmax(0, 1fr);
    gap: .9rem;
    align-items: center;
    padding: .85rem;
    border-radius: 20px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    transition: transform .2s ease, box-shadow .2s ease;
}

.news-list-card-cover {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 16px;
}

.news-list-card-copy h3 {
    margin-bottom: .45rem;
    color: #162033;
    font-size: 1.1rem;
    line-height: 1.4;
    font-weight: 700;
}

.news-list-card-copy p {
    margin-bottom: .55rem;
    color: #6f7c91;
    font-size: .93rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.modern-pagination-wrap {
    margin-top: 1.4rem;
}

.modern-pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .55rem;
    padding-left: 0;
    margin: 0;
    list-style: none;
}

.pagination-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    padding: 0 .9rem;
    border-radius: 999px;
    border: 1px solid rgba(16, 24, 40, .08);
    background: #fff;
    color: #536277;
    text-decoration: none;
    font-weight: 700;
    transition: all .2s ease;
}

.pagination-pill:hover,
.pagination-pill.active {
    text-decoration: none;
    color: #fff;
    background: #0d6efd;
    border-color: #0d6efd;
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
    .news-list-summary-bar {
        grid-template-columns: 1fr;
        margin-top: 0;
    }

    .featured-news-list-card,
    .news-list-card-modern {
        grid-template-columns: 1fr;
    }

    .featured-news-list-cover,
    .news-list-card-cover {
        height: 220px;
    }
}

@media (max-width: 767.98px) {
    .news-list-hero {
        padding-bottom: 5rem;
    }

    .news-list-hero-chips {
        justify-content: flex-start;
    }

    .featured-news-list-copy h2 {
        font-size: 1.3rem;
    }
}
</style>

<?php include('includes/footer.php'); ?>