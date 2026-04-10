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

function nln_estimated_read_time($content)
{
    $text = trim(strip_tags((string) $content));
    if ($text === '') {
        return 1;
    }

    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $count = is_array($words) ? count($words) : 0;

    return max(1, (int) ceil($count / 220));
}

// Validate ID
$news_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($news_id <= 0) {
    header("Location: index.php");
    exit;
}

// Load news
$stmt = $conn->prepare("
    SELECT title, image, content, created_at
    FROM news
    WHERE news_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    ?>
    <div class="container px-4 px-lg-5 py-5">
        <div class="news-empty-state">
            <i class="fas fa-newspaper"></i>
            <h3>Tin tức không tồn tại</h3>
            <p>Bài viết bạn đang tìm không còn tồn tại hoặc đã bị gỡ khỏi hệ thống.</p>
            <a href="index.php" class="btn btn-primary mt-2">Quay về trang chủ</a>
        </div>
    </div>
    <?php
    include('includes/footer.php');
    exit;
}

$news = $result->fetch_assoc();
$stmt->close();

$bgImage = nln_public_news_image_path($news['image'] ?? null);
$newsTitle = $news['title'] ?? '';
$newsDate = nln_format_date($news['created_at'] ?? '');
$readTime = nln_estimated_read_time($news['content'] ?? '');
$newsContent = nl2br(h($news['content'] ?? ''));
?>

<header class="masthead news-hero" style="background-image: url('<?= h($bgImage) ?>')">
    <div class="overlay news-hero-overlay"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <div class="post-heading text-white news-heading-card">
                    <span class="news-eyebrow">News & Updates</span>
                    <h1><?= h($newsTitle) ?></h1>

                    <div class="news-meta-group">
                        <?php if ($newsDate !== ''): ?>
                            <span class="news-meta-pill">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Đăng ngày <?= h($newsDate) ?>
                            </span>
                        <?php endif; ?>

                        <span class="news-meta-pill">
                            <i class="fas fa-book-open me-2"></i>
                            <?= $readTime ?> phút đọc
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<article class="news-article-wrap mb-5">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-xl-10 col-lg-11">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="news-article-card">
                            <div class="news-article-head">
                                <span class="section-kicker">Bài viết chi tiết</span>
                                <h2><?= h($newsTitle) ?></h2>
                                <?php if ($newsDate !== ''): ?>
                                    <p class="news-article-date mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        Cập nhật ngày <?= h($newsDate) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="news-article-body">
                                <?= $newsContent ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <aside class="news-side-card">
                            <span class="section-kicker">Thông tin nhanh</span>
                            <h3>Tóm tắt bài viết</h3>

                            <div class="news-side-info">
                                <div class="news-side-item">
                                    <span>Ngày đăng</span>
                                    <strong><?= $newsDate !== '' ? h($newsDate) : 'Đang cập nhật' ?></strong>
                                </div>

                                <div class="news-side-item">
                                    <span>Thời gian đọc</span>
                                    <strong><?= $readTime ?> phút</strong>
                                </div>

                                <div class="news-side-item">
                                    <span>Chuyên mục</span>
                                    <strong>Tin tức âm nhạc</strong>
                                </div>
                            </div>

                            <div class="news-side-actions">
                                <a href="javascript:history.back()" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại
                                </a>
                                <a href="index.php" class="btn btn-primary w-100">
                                    <i class="fas fa-home me-2"></i>
                                    Về trang chủ
                                </a>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<style>
.news-hero {
    overflow: visible;
    padding-bottom: 5rem;
}

.news-hero-overlay {
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.76) 0%, rgba(16, 24, 40, 0.44) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.news-heading-card {
    position: relative;
    z-index: 1;
    padding: 1.4rem 1.5rem;
    border-radius: 26px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.14);
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 44px rgba(10, 20, 40, .16);
}

.news-eyebrow {
    display: inline-block;
    padding: .42rem .8rem;
    margin-bottom: .95rem;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.news-heading-card h1 {
    margin-bottom: .95rem;
    font-size: clamp(2rem, 4.8vw, 3.4rem);
    line-height: 1.2;
}

.news-meta-group {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
}

.news-meta-pill {
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

.news-article-wrap {
    margin-top: -2rem;
    position: relative;
    z-index: 2;
}

.news-article-card,
.news-side-card,
.news-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
    border: 1px solid rgba(16, 24, 40, .06);
}

.news-article-card {
    padding: 1.25rem 1.25rem 1.45rem;
    border-radius: 26px;
}

.news-article-head {
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(16, 24, 40, .08);
}

.section-kicker {
    display: inline-flex;
    margin-bottom: .5rem;
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

.news-article-head h2 {
    color: #162033;
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1.35;
    margin-bottom: .45rem;
}

.news-article-date {
    color: #6f7c91;
    font-size: .95rem;
    font-family: "Open Sans", sans-serif;
}

.news-article-body {
    color: #273244;
    font-size: 1rem;
    line-height: 1.9;
    font-family: "Open Sans", sans-serif;
    word-break: break-word;
}

.news-article-body p {
    margin-bottom: 1rem;
}

.news-article-body img {
    max-width: 100%;
    height: auto;
    border-radius: 16px;
}

.news-side-card {
    padding: 1.15rem;
    border-radius: 24px;
    position: sticky;
    top: 110px;
}

.news-side-card h3 {
    color: #162033;
    font-size: 1.3rem;
    font-weight: 800;
    margin-bottom: .9rem;
}

.news-side-info {
    display: grid;
    gap: .75rem;
    margin-bottom: 1rem;
}

.news-side-item {
    padding: .9rem 1rem;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid rgba(16, 24, 40, .06);
}

.news-side-item span {
    display: block;
    margin-bottom: .22rem;
    color: #6f7c91;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-family: "Open Sans", sans-serif;
}

.news-side-item strong {
    color: #162033;
    font-size: 1rem;
    line-height: 1.45;
}

.news-side-actions .btn {
    border-radius: 14px;
    padding: .8rem 1rem;
    font-weight: 700;
}

.news-empty-state {
    padding: 2.4rem 1.2rem;
    border-radius: 24px;
    text-align: center;
    margin-top: 2rem;
}

.news-empty-state i {
    margin-bottom: .8rem;
    font-size: 1.8rem;
    color: #0d6efd;
}

.news-empty-state h3 {
    color: #162033;
    font-size: 1.4rem;
    margin-bottom: .5rem;
}

.news-empty-state p {
    max-width: 560px;
    margin: 0 auto;
    color: #6f7c91;
    font-size: .98rem;
    line-height: 1.7;
    font-family: "Open Sans", sans-serif;
}

@media (max-width: 991.98px) {
    .news-side-card {
        position: static;
    }
}

@media (max-width: 767.98px) {
    .news-heading-card {
        padding: 1.1rem;
    }

    .news-meta-group {
        justify-content: flex-start;
    }

    .news-article-wrap {
        margin-top: 0;
    }

    .news-article-card,
    .news-side-card {
        border-radius: 22px;
    }

    .news-article-head h2 {
        font-size: 1.45rem;
    }
}
</style>

<?php include('includes/footer.php'); ?>