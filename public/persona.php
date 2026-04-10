<?php
include('includes/session.php');
include('includes/database.php');
include('includes/header.php');
include('includes/navbar.php');
require_once __DIR__ . '/../config/song_helpers.php';
require_once __DIR__ . '/../config/persona_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$personaData = nln_get_user_persona($conn, $userId, $refresh);

$profile = $personaData['profile'] ?? [];
$persona = $personaData['persona'] ?? [];
$insightCards = $personaData['insight_cards'] ?? [];
$whyThisPersona = $personaData['why_this_persona'] ?? [];
$recommendations = $personaData['recommendations'] ?? ['songs' => [], 'artists' => [], 'albums' => []];
$hasEnoughData = !empty($personaData['has_enough_data']);
$copySource = (string) ($personaData['copy_source'] ?? 'local');

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

    foreach (['assets/img/default-artist.jpg', 'assets/img/default-avatar.png', 'assets/img/default.jpg', 'assets/img/home-bg.jpg'] as $fallback) {
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

    foreach (['assets/img/default.jpg', 'assets/img/home-bg.jpg'] as $fallback) {
        if (nln_public_asset_exists($fallback)) {
            return $fallback;
        }
    }

    return 'assets/img/home-bg.jpg';
}

$topGenreName = (string) ($profile['top_genre']['genre_name'] ?? 'Chưa xác định');
$topArtistName = (string) ($profile['top_artist']['artist_name'] ?? 'Chưa xác định');
$topAlbumName = (string) ($profile['top_album']['album_name'] ?? 'Chưa xác định');
$behavior = $profile['behavior'] ?? [];
$copySourceLabel = $copySource === 'openai' ? 'AI diễn giải' : 'Phân loại cục bộ';
$copySourceNote = $copySource === 'openai'
    ? 'Phần mô tả đang được AI viết lại từ dữ liệu thật của bạn để tự nhiên và dễ đọc hơn.'
    : 'Trang hiện đang dùng mô tả nội bộ để đảm bảo luôn ổn định ngay cả khi AI không sẵn sàng.';
$focusPercent = number_format(((float) ($behavior['artist_focus_ratio'] ?? 0)) * 100, 0) . '%';
$listeningLean = ((int) ($behavior['repeat_songs'] ?? 0) > (int) ($behavior['discovery_songs'] ?? 0))
    ? 'quay lại những bài đã thích'
    : (((int) ($behavior['discovery_songs'] ?? 0) > (int) ($behavior['repeat_songs'] ?? 0))
        ? 'khám phá thêm bài hát mới'
        : 'giữ gu nghe khá cân bằng');
?>

<header class="masthead persona-hero" style="background-image:url('assets/img/home-bg.jpg')">
    <div class="persona-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center persona-heading-shell">
            <span class="persona-eyebrow">AI Music Persona</span>
            <h1><?= h($persona['persona_title'] ?? 'Music Persona') ?></h1>
            <span class="subheading persona-subheading">
                <?php if ($hasEnoughData): ?>
                    <?= h($persona['persona_description'] ?? '') ?>
                <?php else: ?>
                    Bạn chưa có đủ dữ liệu để hệ thống tạo chân dung âm nhạc cá nhân thật sự đáng tin cậy.
                <?php endif; ?>
            </span>

            <div class="persona-hero-chips">
                <span class="persona-chip">
                    <i class="fas fa-headphones me-2"></i>
                    <?= h(number_format((int) ($behavior['total_searches'] ?? 0))) ?> lượt quan tâm
                </span>
                <span class="persona-chip">
                    <i class="fas fa-music me-2"></i>
                    <?= h($topGenreName) ?>
                </span>
                <span class="persona-chip">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= h($topArtistName) ?>
                </span>
                <span class="persona-chip">
                    <i class="fas fa-wand-magic-sparkles me-2"></i>
                    <?= h($copySourceLabel) ?>
                </span>
            </div>

            <div class="persona-hero-actions">
                <a href="persona.php?refresh=1" class="persona-cta persona-cta-primary">
                    <i class="fas fa-sparkles me-2"></i>Phân tích lại
                </a>
                <a href="recap.php" class="persona-cta persona-cta-secondary">
                    <i class="fas fa-chart-pie me-2"></i>Xem recap
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 mt-4 mb-5">
    <?php if ($hasEnoughData): ?>
        <div class="persona-summary-bar">
            <div class="persona-summary-stat">
                <span>Bài hát khác nhau</span>
                <strong><?= h(number_format((int) ($behavior['unique_songs'] ?? 0))) ?></strong>
                <small>Số bài hát khác nhau bạn từng chạm tới.</small>
            </div>
            <div class="persona-summary-stat">
                <span>Nghệ sĩ theo dõi</span>
                <strong><?= h(number_format((int) ($behavior['followed_artists'] ?? 0))) ?></strong>
                <small>Tín hiệu quan tâm dài hạn từ tài khoản của bạn.</small>
            </div>
            <div class="persona-summary-stat">
                <span>Album yêu thích</span>
                <strong><?= h(number_format((int) ($behavior['favorite_albums'] ?? 0))) ?></strong>
                <small>Số album đã được bạn đánh dấu để quay lại.</small>
            </div>
            <div class="persona-summary-stat">
                <span>Album nổi bật</span>
                <strong><?= h($topAlbumName) ?></strong>
                <small>Album xuất hiện đậm nét nhất trong dữ liệu hiện có.</small>
            </div>
        </div>

        <section class="persona-panel persona-story-panel mb-4">
            <div class="persona-panel-header">
                <span class="section-kicker">Persona Story</span>
                <h4 class="persona-panel-title">Gu nghe của bạn đang nghiêng về đâu?</h4>
                <p class="persona-panel-subtitle mb-0">
                    <?= h($copySourceNote) ?>
                </p>
            </div>

            <div class="persona-story-grid">
                <div class="persona-story-lead">
                    <strong><?= h($persona['persona_title'] ?? 'Music Persona') ?></strong>
                    <p><?= h($persona['persona_description'] ?? '') ?></p>
                </div>

                <div class="persona-story-facts">
                    <div class="persona-story-fact">
                        <span>Thể loại dẫn dắt</span>
                        <strong><?= h($topGenreName) ?></strong>
                    </div>
                    <div class="persona-story-fact">
                        <span>Nghệ sĩ ảnh hưởng nhất</span>
                        <strong><?= h($topArtistName) ?></strong>
                    </div>
                    <div class="persona-story-fact">
                        <span>Mức tập trung nghệ sĩ</span>
                        <strong><?= h($focusPercent) ?></strong>
                    </div>
                    <div class="persona-story-fact">
                        <span>Xu hướng hiện tại</span>
                        <strong><?= h($listeningLean) ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="persona-panel mb-4">
                    <div class="persona-panel-header">
                        <span class="section-kicker">Persona Insight</span>
                        <h4 class="persona-panel-title">Dấu hiệu chính trong gu nhạc</h4>
                        <p class="persona-panel-subtitle mb-0">
                            Những tín hiệu mạnh nhất hệ thống đang dùng để mô tả chân dung âm nhạc của bạn.
                        </p>
                    </div>

                    <div class="persona-insight-grid">
                        <?php foreach ($insightCards as $card): ?>
                            <div class="persona-insight-card">
                                <span><?= h($card['label'] ?? '') ?></span>
                                <strong><?= h($card['value'] ?? '') ?></strong>
                                <small><?= h($card['note'] ?? '') ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="persona-panel mb-4">
                    <div class="persona-panel-header">
                        <span class="section-kicker">Recommended Songs</span>
                        <h4 class="persona-panel-title">Bài hát hợp gu hiện tại</h4>
                        <p class="persona-panel-subtitle mb-0">
                            Những ca khúc có khả năng kết nối tốt với persona hiện tại của bạn.
                        </p>
                    </div>

                    <div class="persona-song-list">
                        <?php foreach (($recommendations['songs'] ?? []) as $song): ?>
                            <a href="post.php?id=<?= (int) $song['song_id'] ?>" class="persona-song-item">
                                <img
                                    src="<?= h($song['cover'] ?? nln_public_song_cover_path($song['song_cover'] ?? null, $song['album_cover'] ?? null)) ?>"
                                    alt="<?= h($song['title']) ?>"
                                    class="persona-song-cover"
                                >
                                <div class="persona-song-copy">
                                    <strong><?= h($song['title']) ?></strong>
                                    <span>
                                        <?= h($song['artist_name']) ?>
                                        <?php if (!empty($song['album_name'])): ?>
                                            · <?= h($song['album_name']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="persona-panel mb-4">
                    <div class="persona-panel-header">
                        <span class="section-kicker">Why This Persona</span>
                        <h4 class="persona-panel-title">Vì sao hệ thống kết luận như vậy?</h4>
                    </div>

                    <div class="persona-reason-list">
                        <?php foreach ($whyThisPersona as $reason): ?>
                            <div class="persona-reason-item">
                                <i class="fas fa-check-circle"></i>
                                <span><?= h($reason) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="persona-panel mb-4">
                    <div class="persona-panel-header">
                        <span class="section-kicker">Recommended Artists</span>
                        <h4 class="persona-panel-title">Nghệ sĩ nên khám phá tiếp</h4>
                        <p class="persona-panel-subtitle mb-0">
                            Các nghệ sĩ có tín hiệu phù hợp với gu hiện tại nhưng chưa được bạn theo dõi.
                        </p>
                    </div>

                    <div class="persona-artist-list">
                        <?php foreach (($recommendations['artists'] ?? []) as $artist): ?>
                            <a href="artist.php?id=<?= (int) $artist['artist_id'] ?>" class="persona-artist-item">
                                <img
                                    src="<?= h(nln_public_artist_avatar_path($artist['avatar'] ?? null)) ?>"
                                    alt="<?= h($artist['artist_name']) ?>"
                                    class="persona-artist-avatar"
                                >
                                <div class="persona-artist-copy">
                                    <strong><?= h($artist['artist_name']) ?></strong>
                                    <span><?= h(number_format((int) ($artist['popularity'] ?? 0))) ?> tín hiệu phổ biến</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="persona-panel">
                    <div class="persona-panel-header">
                        <span class="section-kicker">Recommended Albums</span>
                        <h4 class="persona-panel-title">Album nên mở rộng tiếp</h4>
                        <p class="persona-panel-subtitle mb-0">
                            Những album đáng thử nếu bạn muốn đi sâu hơn thay vì chỉ nghe single.
                        </p>
                    </div>

                    <div class="persona-album-list">
                        <?php foreach (($recommendations['albums'] ?? []) as $album): ?>
                            <a href="album.php?id=<?= (int) $album['album_id'] ?>" class="persona-album-item">
                                <img
                                    src="<?= h(nln_public_album_cover_path($album['cover_image'] ?? null)) ?>"
                                    alt="<?= h($album['album_name']) ?>"
                                    class="persona-album-cover"
                                >
                                <div class="persona-album-copy">
                                    <strong><?= h($album['album_name']) ?></strong>
                                    <span><?= h($album['artist_name']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>

        <section class="persona-cta-row">
            <a href="profile.php" class="persona-cta persona-cta-primary">Quay về hồ sơ</a>
            <a href="charts.php" class="persona-cta persona-cta-secondary">Khám phá charts</a>
        </section>
    <?php else: ?>
        <section class="persona-empty-state">
            <span class="section-kicker">Not Enough Data</span>
            <h3>Chưa có đủ dữ liệu để tạo chân dung âm nhạc</h3>
            <p>
                Hãy tiếp tục tìm kiếm bài hát, theo dõi nghệ sĩ hoặc lưu album yêu thích.
                Khi dữ liệu hành vi đủ mạnh hơn, hệ thống sẽ tạo một persona đáng tin cậy hơn cho bạn.
            </p>
            <div class="persona-empty-actions">
                <a href="search.php" class="persona-cta persona-cta-primary">Tìm bài hát</a>
                <a href="recap.php" class="persona-cta persona-cta-secondary">Xem recap</a>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>

<style>
.persona-hero {
    position: relative;
}

.persona-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(18, 28, 50, 0.8), rgba(41, 66, 108, 0.58)),
        radial-gradient(circle at top left, rgba(52, 211, 153, 0.14), transparent 30%);
}

.persona-heading-shell {
    max-width: 860px;
    margin: 0 auto;
}

.persona-eyebrow,
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
    background: #eaf7ff;
    color: #1178c2;
}

.persona-subheading {
    max-width: 760px;
    margin: 1rem auto 0;
    color: rgba(255,255,255,0.92);
}

.persona-hero-chips,
.persona-hero-actions,
.persona-cta-row,
.persona-empty-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.9rem;
}

.persona-hero-chips {
    margin-top: 1.8rem;
}

.persona-hero-actions {
    margin-top: 1.25rem;
}

.persona-chip,
.persona-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0 1.2rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 800;
}

.persona-chip {
    color: #fff;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255,255,255,0.18);
}

.persona-cta {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.persona-cta:hover {
    transform: translateY(-1px);
}

.persona-cta-primary {
    color: #fff;
    background: linear-gradient(135deg, #1f98c2, #2a7eaa);
    box-shadow: 0 14px 26px rgba(31, 152, 194, 0.2);
}

.persona-cta-secondary {
    color: #1a4f7d;
    background: #fff;
    border: 1px solid rgba(26, 79, 125, 0.18);
}

.persona-summary-bar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.persona-story-grid {
    display: grid;
    grid-template-columns: 1.2fr .9fr;
    gap: 1rem;
}

.persona-story-lead,
.persona-story-fact {
    background: #f8fbff;
    border: 1px solid rgba(41, 98, 255, 0.08);
    border-radius: 22px;
}

.persona-story-lead {
    padding: 1.2rem 1.25rem;
}

.persona-story-lead strong {
    display: block;
    color: #112347;
    font-size: 1.4rem;
    font-weight: 800;
}

.persona-story-lead p {
    margin: 0.7rem 0 0;
    color: #44597a;
    line-height: 1.8;
}

.persona-story-facts {
    display: grid;
    gap: 0.85rem;
}

.persona-story-fact {
    padding: 0.95rem 1rem;
}

.persona-story-fact span {
    display: block;
    color: #7588a8;
    font-size: 0.84rem;
    font-weight: 700;
}

.persona-story-fact strong {
    display: block;
    margin-top: 0.35rem;
    color: #112347;
    font-size: 1rem;
    font-weight: 800;
}

.persona-summary-stat,
.persona-panel,
.persona-empty-state {
    background: #fff;
    border: 1px solid rgba(20, 34, 58, 0.08);
    border-radius: 28px;
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
}

.persona-summary-stat {
    padding: 1.25rem 1.3rem;
}

.persona-summary-stat span,
.persona-insight-card span {
    display: block;
    color: #7588a8;
    font-size: 0.86rem;
    font-weight: 700;
}

.persona-summary-stat strong,
.persona-insight-card strong {
    display: block;
    margin-top: 0.45rem;
    color: #112347;
    font-size: 1.55rem;
    font-weight: 800;
}

.persona-summary-stat small,
.persona-insight-card small {
    display: block;
    margin-top: 0.45rem;
    color: #8493ae;
}

.persona-panel {
    padding: 1.55rem;
}

.persona-panel-title {
    margin: 0.5rem 0 0.35rem;
    font-size: 1.8rem;
    font-weight: 800;
    color: #112347;
}

.persona-panel-subtitle {
    color: #677a9d;
}

.persona-insight-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.95rem;
}

.persona-insight-card,
.persona-reason-item,
.persona-song-item,
.persona-artist-item,
.persona-album-item {
    background: #f8fbff;
    border: 1px solid rgba(41, 98, 255, 0.08);
    border-radius: 22px;
}

.persona-insight-card {
    padding: 1rem 1.05rem;
}

.persona-reason-list,
.persona-song-list,
.persona-artist-list,
.persona-album-list {
    display: grid;
    gap: 0.9rem;
}

.persona-reason-item {
    display: flex;
    align-items: flex-start;
    gap: 0.8rem;
    padding: 1rem 1.05rem;
    color: #314866;
}

.persona-reason-item i {
    color: #1f98c2;
    margin-top: 0.1rem;
}

.persona-song-item,
.persona-artist-item,
.persona-album-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.95rem 1rem;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.persona-song-item:hover,
.persona-artist-item:hover,
.persona-album-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(41, 98, 255, 0.09);
    border-color: rgba(41, 98, 255, 0.16);
}

.persona-song-cover,
.persona-album-cover {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    object-fit: cover;
    flex: 0 0 auto;
}

.persona-artist-avatar {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    object-fit: cover;
    flex: 0 0 auto;
}

.persona-song-copy,
.persona-artist-copy,
.persona-album-copy {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.persona-song-copy strong,
.persona-artist-copy strong,
.persona-album-copy strong {
    color: #112347;
    font-size: 1.05rem;
    font-weight: 800;
}

.persona-song-copy span,
.persona-artist-copy span,
.persona-album-copy span {
    color: #6f82a3;
    font-weight: 600;
}

.persona-empty-state {
    padding: 2rem;
}

.persona-empty-state h3 {
    margin: 0.7rem 0;
    color: #112347;
    font-weight: 800;
}

.persona-empty-state p {
    max-width: 620px;
    color: #63789a;
    line-height: 1.8;
}

.persona-cta-row {
    margin-top: 1.5rem;
}

@media (max-width: 991.98px) {
    .persona-summary-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .persona-story-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .persona-summary-bar,
    .persona-insight-grid {
        grid-template-columns: 1fr;
    }

    .persona-song-item,
    .persona-artist-item,
    .persona-album-item {
        align-items: flex-start;
    }
}
</style>
