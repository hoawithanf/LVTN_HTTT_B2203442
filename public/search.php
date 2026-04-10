<?php
include('includes/session.php');
include('includes/header.php');
include('includes/navbar.php');
include('includes/database.php');
require_once __DIR__ . '/../config/song_helpers.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function highlight($text, $keyword)
{
    if (!$keyword) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    return preg_replace(
        '/' . preg_quote($keyword, '/') . '/i',
        '<mark>$0</mark>',
        htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8')
    );
}

$results = [];
$relatedKeywords = [];
$total = 0;
$totalPages = 1;
$topResult = null;

if ($keyword !== '') {
    $term = '%' . $keyword . '%';
    $prefix = $keyword . '%';

    $countSql = "
        SELECT COUNT(*) AS total
        FROM songs s
        JOIN artists a ON s.artist_id = a.artist_id
        LEFT JOIN albums al ON s.album_id = al.album_id
        LEFT JOIN genres g ON g.genre_id = s.genre_id
        WHERE
            s.title LIKE ?
            OR a.artist_name LIKE ?
            OR al.album_name LIKE ?
            OR g.genre_name LIKE ?
    ";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('ssss', $term, $term, $term, $term);
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    $totalPages = max(1, (int) ceil($total / $limit));

    $sql = "
        SELECT
            s.song_id,
            s.title,
            s.release_date,
            a.artist_name,
            al.album_name,
            g.genre_name,
            s.cover_image AS song_cover,
            al.cover_image AS album_cover,
            COUNT(sl.log_id) AS search_count,
            CASE
                WHEN LOWER(s.title) = LOWER(?) THEN 500
                WHEN LOWER(a.artist_name) = LOWER(?) THEN 420
                WHEN s.title LIKE ? THEN 260
                WHEN a.artist_name LIKE ? THEN 220
                WHEN al.album_name LIKE ? THEN 180
                WHEN g.genre_name LIKE ? THEN 140
                ELSE 80
            END AS priority
        FROM songs s
        JOIN artists a ON s.artist_id = a.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        LEFT JOIN genres g ON g.genre_id = s.genre_id
        LEFT JOIN search_logs sl ON sl.song_id = s.song_id
        WHERE
            s.title LIKE ?
            OR a.artist_name LIKE ?
            OR al.album_name LIKE ?
            OR g.genre_name LIKE ?
        GROUP BY
            s.song_id,
            s.title,
            s.release_date,
            a.artist_name,
            al.album_name,
            g.genre_name,
            s.cover_image,
            al.cover_image
        ORDER BY priority DESC, search_count DESC, s.release_date DESC, s.title ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssssssssssii',
        $keyword,
        $keyword,
        $prefix,
        $prefix,
        $prefix,
        $prefix,
        $term,
        $term,
        $term,
        $term,
        $limit,
        $offset
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
        $results[] = $row;
    }
    $stmt->close();

    $topResult = $results[0] ?? null;

    if ($topResult) {
        $keywordPool = [];

        foreach ($results as $row) {
            foreach (['artist_name', 'album_name', 'genre_name'] as $field) {
                if (!empty($row[$field]) && mb_strtolower($row[$field]) !== mb_strtolower($keyword)) {
                    $keywordPool[mb_strtolower($row[$field])] = $row[$field];
                }
            }
        }

        $relatedKeywords = array_slice(array_values($keywordPool), 0, 8);
    }
}
?>

<header class="masthead search-hero" style="background-image: url('assets/img/search-bg.jpg')">
    <div class="search-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center search-heading-shell">
            <span class="search-eyebrow">Smart Song Search</span>
            <h1>Tìm kiếm bài hát</h1>

            <?php if ($keyword): ?>
                <span class="subheading search-subheading">
                    <?= number_format($total) ?> kết quả cho "<strong><?= h($keyword) ?></strong>"
                </span>
            <?php else: ?>
                <span class="subheading search-subheading">
                    Tìm theo tên bài hát, nghệ sĩ, album hoặc thể loại
                </span>
            <?php endif; ?>

            <form method="GET" action="search.php" class="search-form-hero">
                <div class="search-input-shell">
                    <i class="fas fa-search"></i>
                    <input
                        type="text"
                        id="searchInput"
                        name="q"
                        class="form-control search-input"
                        placeholder="Tìm bài hát, nghệ sĩ, album..."
                        value="<?= h($keyword) ?>"
                        autocomplete="off"
                    >
                    <button type="submit">Tìm ngay</button>
                </div>
                <div id="suggestBox" class="search-suggest-box"></div>
            </form>

            <?php if ($relatedKeywords): ?>
                <div class="related-keywords-strip">
                    <span class="related-label">Từ khóa liên quan</span>
                    <?php foreach ($relatedKeywords as $tag): ?>
                        <a href="search.php?q=<?= urlencode($tag) ?>" class="related-chip"><?= h($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5">
    <?php if ($keyword !== ''): ?>
        <div class="search-summary-bar">
            <div class="summary-stat">
                <span>Kết quả</span>
                <strong><?= number_format($total) ?></strong>
            </div>
            <div class="summary-stat">
                <span>Trang</span>
                <strong><?= number_format($page) ?>/<?= number_format($totalPages) ?></strong>
            </div>
            <div class="summary-stat">
                <span>Ưu tiên</span>
                <strong>Khớp tên + độ phổ biến</strong>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($topResult): ?>
        <section class="featured-result-card">
            <div class="featured-result-copy">
                <span class="featured-label">Kết quả nổi bật</span>
                <h2><?= highlight($topResult['title'], $keyword) ?></h2>
                <p class="featured-meta">
                    <?= highlight($topResult['artist_name'], $keyword) ?>
                    <?php if (!empty($topResult['album_name'])): ?>
                        <span>• <?= h($topResult['album_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($topResult['genre_name'])): ?>
                        <span>• <?= h($topResult['genre_name']) ?></span>
                    <?php endif; ?>
                </p>
                <div class="featured-badges">
                    <span class="featured-badge"><?= number_format((int) $topResult['search_count']) ?> lượt tìm</span>
                    <?php if (!empty($topResult['release_date'])): ?>
                        <span class="featured-badge featured-badge-soft"><?= h($topResult['release_date']) ?></span>
                    <?php endif; ?>
                </div>
                <a href="post.php?id=<?= (int) $topResult['song_id'] ?>" class="featured-link">Mở bài hát</a>
            </div>
            <img src="<?= h($topResult['cover']) ?>" alt="<?= h($topResult['title']) ?>" class="featured-result-cover">
        </section>
    <?php elseif ($keyword !== ''): ?>
        <div class="search-empty-state">
            <i class="fas fa-compact-disc"></i>
            <h3>Không tìm thấy kết quả phù hợp</h3>
            <p>Thử rút gọn từ khóa, đổi sang tên nghệ sĩ hoặc chọn một từ khóa liên quan bên trên.</p>
        </div>
    <?php else: ?>
        <div class="search-empty-state">
            <i class="fas fa-search"></i>
            <h3>Nhập từ khóa để bắt đầu</h3>
            <p>Gợi ý sẽ xuất hiện khi bạn gõ từ 2 ký tự trở lên.</p>
        </div>
    <?php endif; ?>

    <?php if ($results): ?>
        <section class="search-results-grid">
            <?php foreach ($results as $row): ?>
                <a href="post.php?id=<?= (int) $row['song_id'] ?>" class="track-card-modern">
                    <img src="<?= h($row['cover']) ?>" alt="<?= h($row['title']) ?>" class="track-cover-modern">

                    <div class="track-main-copy">
                        <div class="track-title-modern"><?= highlight($row['title'], $keyword) ?></div>
                        <div class="track-artist-modern"><?= highlight($row['artist_name'], $keyword) ?></div>

                        <div class="track-tags">
                            <?php if (!empty($row['album_name'])): ?>
                                <span class="track-tag"><?= h($row['album_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row['genre_name'])): ?>
                                <span class="track-tag track-tag-soft"><?= h($row['genre_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="track-side-meta">
                        <?php if (!empty($row['release_date'])): ?>
                            <span><?= h($row['release_date']) ?></span>
                        <?php endif; ?>
                        <strong><?= number_format((int) $row['search_count']) ?> lượt tìm</strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center search-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?q=<?= urlencode($keyword) ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.search-hero {
    overflow: visible;
    padding-bottom: 4.75rem;
}

.search-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.76) 0%, rgba(16, 24, 40, 0.46) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.18), transparent 28%);
}

.search-heading-shell {
    position: relative;
    z-index: 1;
}

.search-eyebrow {
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

.search-heading-shell h1 {
    font-size: clamp(2.3rem, 5.2vw, 4rem);
}

.search-subheading {
    max-width: 660px;
    margin: .65rem auto 0;
    font-size: 1.05rem;
    line-height: 1.6;
}

.search-form-hero {
    position: relative;
    max-width: 760px;
    margin: 1.5rem auto 0;
}

.search-input-shell {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: .75rem;
    padding: .7rem .75rem .7rem 1rem;
    border-radius: 22px;
    background: rgba(255,255,255,.96);
    box-shadow: 0 24px 60px rgba(7, 16, 31, .18);
}

.search-input-shell i {
    color: #7d8795;
}

.search-input-shell .search-input {
    border: 0;
    padding: 0;
    box-shadow: none;
    background: transparent;
    font-size: .95rem;
}

.search-input-shell .search-input:focus {
    box-shadow: none;
}

.search-input-shell button {
    border: 0;
    border-radius: 16px;
    padding: .82rem 1.05rem;
    color: #fff;
    font-family: "Open Sans", sans-serif;
    font-size: .9rem;
    font-weight: 700;
    background: linear-gradient(135deg, #0d6efd, #224abe);
    box-shadow: 0 14px 30px rgba(13, 110, 253, .22);
}

.search-suggest-box {
    position: absolute;
    top: calc(100% + 12px);
    left: 0;
    right: 0;
    z-index: 30;
    display: none;
    border-radius: 24px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 26px 48px rgba(8, 18, 37, .14);
    text-align: left;
}

.search-suggest-box.is-open {
    display: block;
}

.suggest-panel {
    padding: .65rem;
}

.suggest-heading {
    padding: .65rem .75rem .45rem;
    font-family: "Open Sans", sans-serif;
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #7d8795;
}

.suggest-item {
    display: grid;
    grid-template-columns: 54px 1fr auto;
    gap: .9rem;
    align-items: center;
    padding: .8rem;
    border-radius: 18px;
    text-decoration: none;
    color: inherit;
}

.suggest-item:hover {
    background: #f6f9ff;
}

.suggest-cover {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    object-fit: cover;
}

.suggest-title {
    font-weight: 700;
    color: #162033;
}

.suggest-subtitle {
    margin-top: .15rem;
    font-size: .88rem;
    color: #6f7c91;
}

.suggest-meta {
    text-align: right;
    font-size: .82rem;
    color: #6f7c91;
}

.suggest-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 0 .75rem .75rem;
}

.suggest-keyword-chip,
.related-chip,
.track-tag,
.featured-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    font-family: "Open Sans", sans-serif;
    font-weight: 700;
}

.suggest-keyword-chip,
.related-chip {
    padding: .45rem .8rem;
    text-decoration: none;
    color: #1b4db1;
    background: rgba(13, 110, 253, .08);
}

.related-keywords-strip {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .55rem;
    margin-top: 1.15rem;
}

.related-label {
    display: inline-flex;
    align-items: center;
    color: rgba(255,255,255,.76);
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
}

.related-chip {
    color: #fff;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.14);
}

.search-summary-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2.1rem;
    margin-bottom: 1.35rem;
    position: relative;
    z-index: 2;
}

.summary-stat,
.featured-result-card,
.track-card-modern,
.search-empty-state {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.summary-stat {
    padding: .9rem 1rem;
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
    color: #162033;
    font-size: 1rem;
}

.featured-result-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 190px;
    gap: 1rem;
    align-items: center;
    padding: 1.05rem;
    border-radius: 22px;
    margin-bottom: 1.1rem;
    border: 1px solid rgba(16, 24, 40, .06);
}

.featured-label {
    display: inline-flex;
    margin-bottom: .65rem;
    padding: .38rem .7rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.featured-result-card h2 {
    margin-bottom: .4rem;
    font-size: 1.65rem;
    color: #162033;
}

.featured-meta {
    color: #6f7c91;
    font-family: "Open Sans", sans-serif;
    font-size: .95rem;
    line-height: 1.55;
}

.featured-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin: .8rem 0 .9rem;
}

.featured-badge {
    padding: .38rem .7rem;
    font-size: .8rem;
    color: #0d6efd;
    background: rgba(13, 110, 253, .08);
}

.featured-badge-soft {
    color: #6f7c91;
    background: #eef3fb;
}

.featured-link {
    display: inline-flex;
    padding: .8rem 1rem;
    border-radius: 15px;
    text-decoration: none;
    color: #fff;
    font-family: "Open Sans", sans-serif;
    font-size: .9rem;
    font-weight: 700;
    background: linear-gradient(135deg, #0d6efd, #224abe);
}

.featured-result-cover {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 22px;
    object-fit: cover;
}

.search-results-grid {
    display: grid;
    gap: .8rem;
}

.track-card-modern {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr) auto;
    gap: .85rem;
    align-items: center;
    padding: .85rem;
    border-radius: 18px;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(16, 24, 40, .06);
    transition: transform .2s ease, box-shadow .2s ease;
}

.track-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(10, 20, 40, .1);
}

.track-cover-modern {
    width: 72px;
    height: 72px;
    border-radius: 14px;
    object-fit: cover;
}

.track-title-modern {
    font-size: 1rem;
    font-weight: 700;
    color: #162033;
}

.track-artist-modern {
    margin-top: .15rem;
    font-size: .9rem;
    color: #6f7c91;
}

.track-tags {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin-top: .6rem;
}

.track-tag {
    padding: .34rem .62rem;
    font-size: .75rem;
    color: #0d6efd;
    background: rgba(13, 110, 253, .08);
}

.track-tag-soft {
    color: #536277;
    background: #eef3fb;
}

.track-side-meta {
    min-width: 94px;
    text-align: right;
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
    color: #6f7c91;
}

.track-side-meta strong {
    display: block;
    margin-top: .25rem;
    color: #162033;
}

.search-empty-state {
    padding: 2.2rem 1.1rem;
    border-radius: 22px;
    text-align: center;
    border: 1px solid rgba(16, 24, 40, .06);
}

.search-empty-state i {
    margin-bottom: .8rem;
    font-size: 1.6rem;
    color: #0d6efd;
}

.search-empty-state h3 {
    font-size: 1.35rem;
    color: #162033;
}

.search-empty-state p {
    max-width: 520px;
    margin: 0 auto;
    font-size: .95rem;
    color: #6f7c91;
    font-family: "Open Sans", sans-serif;
}

.search-pagination .page-link {
    border-radius: 12px;
    margin: 0 .18rem;
}

mark {
    background: rgba(13, 110, 253, .12);
    color: #0d6efd;
    padding: 0 .18rem;
    border-radius: 4px;
}

@media (max-width: 991.98px) {
    .search-summary-bar {
        grid-template-columns: 1fr;
        margin-top: 0;
    }

    .featured-result-card {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .search-hero {
        padding-bottom: 5rem;
    }

    .search-input-shell {
        grid-template-columns: 1fr;
        padding: 1rem;
    }

    .search-input-shell i {
        display: none;
    }

    .search-input-shell button {
        width: 100%;
    }

    .track-card-modern {
        grid-template-columns: 1fr;
    }

    .track-side-meta {
        text-align: left;
    }

    .related-keywords-strip {
        justify-content: flex-start;
    }
}
</style>

<script>
const input = document.getElementById('searchInput');
const box = document.getElementById('suggestBox');

function closeSuggestBox() {
    box.classList.remove('is-open');
    box.innerHTML = '';
}

function renderSuggest(data, q) {
    const items = data.items || [];
    const related = data.related_keywords || [];

    if (!items.length && !related.length) {
        closeSuggestBox();
        return;
    }

    let html = `<div class="suggest-panel">`;

    if (items.length) {
        html += `<div class="suggest-heading">Gợi ý bài hát</div>`;
        html += items.map(item => `
            <a href="post.php?id=${item.song_id}" class="suggest-item">
                <img src="${item.cover}" alt="${item.title}" class="suggest-cover">
                <div>
                    <div class="suggest-title">${item.title}</div>
                    <div class="suggest-subtitle">${item.subtitle || item.artist_name}</div>
                </div>
                <div class="suggest-meta">
                    ${item.search_count ? `<strong>${item.search_count}</strong><br>` : ''}
                    ${item.release_date ? `<span>${item.release_date}</span>` : ''}
                </div>
            </a>
        `).join('');
    }

    if (related.length) {
        html += `<div class="suggest-heading">Từ khóa liên quan</div>`;
        html += `<div class="suggest-keywords">` + related.map(tag => `
            <a href="search.php?q=${encodeURIComponent(tag)}" class="suggest-keyword-chip">${tag}</a>
        `).join('') + `</div>`;
    }

    html += `</div>`;
    box.innerHTML = html;
    box.classList.add('is-open');
}

input.addEventListener('input', () => {
    const q = input.value.trim();
    if (q.length < 2) {
        closeSuggestBox();
        return;
    }

    fetch(`includes/api_search_suggest.php?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => renderSuggest(data, q))
        .catch(() => closeSuggestBox());
});

document.addEventListener('click', (event) => {
    if (!box.contains(event.target) && event.target !== input) {
        closeSuggestBox();
    }
});

input.addEventListener('focus', () => {
    if (box.innerHTML.trim() !== '') {
        box.classList.add('is-open');
    }
});
</script>

<?php include('includes/footer.php'); ?>
