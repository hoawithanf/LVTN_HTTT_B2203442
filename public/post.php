<?php
include('includes/session.php');
include('includes/header.php');
include('includes/navbar.php');
include('includes/database.php');
include('includes/lyrics_api.php');
include('includes/env_loader.php');
require_once __DIR__ . '/../config/song_helpers.php';
require_once __DIR__ . '/../config/youtube_helpers.php';
require_once __DIR__ . '/../config/related_song_helpers.php';
require_once __DIR__ . '/../config/playlist_helpers.php';

loadEnv(__DIR__ . '/../.env');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nln_format_date($dateTime)
{
    if (empty($dateTime)) {
        return '';
    }

    $timestamp = strtotime((string) $dateTime);
    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

$song_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$query = $conn->prepare("
    SELECT s.*, a.artist_name, al.album_name, al.cover_image AS album_cover
    FROM songs s
    JOIN artists a ON s.artist_id = a.artist_id
    LEFT JOIN albums al ON s.album_id = al.album_id
    WHERE s.song_id = ?
    LIMIT 1
");
$query->bind_param("i", $song_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    ?>
    <div class="container px-4 px-lg-5 py-5">
        <div class="content-empty-state">
            <i class="fas fa-music"></i>
            <h3>Bài hát không tồn tại</h3>
            <p>Bài hát bạn đang tìm không tồn tại hoặc đã bị gỡ khỏi hệ thống.</p>
            <a href="index.php" class="btn btn-primary mt-2">Quay về trang chủ</a>
        </div>
    </div>
    <?php
    include('includes/footer.php');
    exit;
}

$song = $result->fetch_assoc();
$query->close();

$resolvedCover = nln_song_cover_filename($song['cover_image'] ?? null, $song['album_cover'] ?? null) ?? 'default.jpg';
$background = nln_public_song_cover_path($song['cover_image'] ?? null, $song['album_cover'] ?? null);

if (isset($_SESSION['user_id'])) {
    $insert_log = $conn->prepare("
        INSERT INTO search_logs (user_id, song_id, song_title, artist_name, cover_image, search_time)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert_log->bind_param(
        "iisss",
        $_SESSION['user_id'],
        $song['song_id'],
        $song['title'],
        $song['artist_name'],
        $resolvedCover
    );
    $insert_log->execute();
    $insert_log->close();
}

$commentCount = 0;
$stmtCommentCount = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM comments
    WHERE song_id = ?
");
$stmtCommentCount->bind_param("i", $song_id);
$stmtCommentCount->execute();
$commentCount = (int) ($stmtCommentCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCommentCount->close();

$needAjaxFetch = empty(trim((string) ($song['lyrics'] ?? '')));

$youtubeVideoId = $song['youtube_video_id'] ?? null;
$youtubeQuery = $song['title'] . ' ' . $song['artist_name'];
$youtubeVideoId = nln_youtube_resolve_video_id(
    $conn,
    $song_id,
    (string) $song['title'],
    (string) $song['artist_name'],
    $youtubeVideoId
);

$releaseDate = nln_format_date($song['release_date'] ?? '');
$albumName = trim((string) ($song['album_name'] ?? '')) !== '' ? $song['album_name'] : 'Single';
$language = trim((string) ($song['language'] ?? '')) !== '' ? $song['language'] : 'Đang cập nhật';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$isSimpleAdmin = $currentUserId === 1;
$artistUrl = 'artist.php?id=' . (int) $song['artist_id'];
$albumUrl = !empty($song['album_id']) ? 'album.php?id=' . (int) $song['album_id'] : '';
$relatedSongs = nln_fetch_related_songs(
    $conn,
    (int) $song['song_id'],
    (int) $song['artist_id'],
    !empty($song['album_id']) ? (int) $song['album_id'] : null,
    !empty($song['genre_id']) ? (int) $song['genre_id'] : null,
    6
);
$userPlaylists = $currentUserId > 0 ? nln_playlist_fetch_user_playlists($conn, $currentUserId, 20) : [];
?>

<header class="masthead post-hero" style="background-image: url('<?= h($background) ?>');">
    <div class="overlay post-hero-overlay"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="post-heading text-white post-heading-shell">
            <span class="post-eyebrow">Song Detail</span>
            <h1 class="fw-bold"><?= h($song['title']) ?></h1>
            <h2 class="subheading post-subheading"><?= h($albumName) ?></h2>

            <div class="post-hero-chips">
                <a class="post-chip post-chip-link" href="<?= h($artistUrl) ?>">
                    <i class="fas fa-microphone-alt me-2"></i>
                    <?= h($song['artist_name']) ?>
                </a>

                <?php if ($albumUrl !== ''): ?>
                    <a class="post-chip post-chip-link" href="<?= h($albumUrl) ?>">
                        <i class="fas fa-compact-disc me-2"></i>
                        <?= h($albumName) ?>
                    </a>
                <?php endif; ?>

                <?php if ($releaseDate !== ''): ?>
                    <span class="post-chip">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?= h($releaseDate) ?>
                    </span>
                <?php endif; ?>

                <span class="post-chip">
                    <i class="fas fa-language me-2"></i>
                    <?= h($language) ?>
                </span>

                <span class="post-chip">
                    <i class="fas fa-comments me-2"></i>
                    <?= number_format($commentCount) ?> bình luận
                </span>
            </div>
        </div>
    </div>
</header>

<div class="container px-4 px-lg-5 my-4 post-page">
    <div class="post-summary-bar">
        <div class="post-summary-stat">
            <span>Nghệ sĩ</span>
            <strong><a class="post-summary-link" href="<?= h($artistUrl) ?>"><?= h($song['artist_name']) ?></a></strong>
            <small>Người thể hiện bài hát</small>
        </div>

        <div class="post-summary-stat">
            <span>Album</span>
            <strong>
                <?php if ($albumUrl !== ''): ?>
                    <a class="post-summary-link" href="<?= h($albumUrl) ?>"><?= h($albumName) ?></a>
                <?php else: ?>
                    <?= h($albumName) ?>
                <?php endif; ?>
            </strong>
            <small>Thông tin phát hành liên quan</small>
        </div>

        <div class="post-summary-stat">
            <span>Ngôn ngữ</span>
            <strong><?= h($language) ?></strong>
            <small>Ngôn ngữ bài hát hiện có</small>
        </div>

        <div class="post-summary-stat">
            <span>Bình luận</span>
            <strong><a class="post-summary-link" href="#comments-panel"><?= number_format($commentCount) ?></a></strong>
            <small>Tương tác của người dùng</small>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7 mb-4">
            <section class="post-panel">
                <div class="post-panel-header">
                    <span class="section-kicker">Lyrics</span>
                    <h4 class="post-panel-title mb-1">Lời bài hát</h4>
                    <p class="post-panel-subtitle mb-0">
                        Hiển thị lyrics từ database hoặc tự động tải khi dữ liệu chưa có sẵn.
                    </p>
                </div>

                <div id="lyrics-box" class="lyrics-box text-dark">
                    <?php if (!$needAjaxFetch): ?>
                        <div class="lyrics-source">
                            <span class="lyrics-source-pill">(Nguồn: database)</span>
                        </div>
                        <?php
                        $lyrics = htmlspecialchars((string) $song['lyrics'], ENT_QUOTES, 'UTF-8');
                        $lyrics = preg_replace("/\n{2,}/", "</p><p>", "<p>" . nl2br($lyrics) . "</p>");
                        echo "<div class='lyrics-text'>{$lyrics}</div>";
                        ?>
                    <?php else: ?>
                        <p class="lyrics-loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>Đang tải lời bài hát...
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="post-panel mt-4 post-quick-info-panel">
                <div class="post-panel-header">
                    <span class="section-kicker">Music Video</span>
                    <h4 class="post-panel-title mb-1">Video bài hát</h4>
                    <p class="post-panel-subtitle mb-0">
                        Xem nhanh video liên quan hoặc mở tìm kiếm trên YouTube.
                    </p>
                </div>

                <?php if ($youtubeVideoId): ?>
                    <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm post-video-frame">
                        <iframe
                            src="https://www.youtube-nocookie.com/embed/<?= h($youtubeVideoId) ?>?rel=0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy">
                        </iframe>
                    </div>
                <?php else: ?>
                    <div class="content-empty-state content-empty-state-compact">
                        <i class="fab fa-youtube"></i>
                        <h3>Không tìm thấy video phù hợp</h3>
                        <p>Hiện chưa có video phù hợp để nhúng trực tiếp cho bài hát này.</p>
                    </div>
                <?php endif; ?>

                <a href="https://www.youtube.com/results?search_query=<?= urlencode($youtubeQuery) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-outline-danger btn-sm mt-3 rounded-pill px-3 py-2 fw-bold">
                    <i class="fab fa-youtube me-1"></i> Xem trên YouTube
                </a>
            </section>

            <?php if ($currentUserId > 0): ?>
                <section class="post-panel mt-4">
                    <div class="post-panel-header">
                        <span class="section-kicker">Playlist</span>
                        <h4 class="post-panel-title mb-1">Thêm vào playlist</h4>
                        <p class="post-panel-subtitle mb-0">
                            Lưu nhanh bài hát này vào một playlist có sẵn hoặc tạo playlist mới của riêng bạn.
                        </p>
                    </div>

                    <div id="playlist-status" class="mb-3"></div>

                    <div class="playlist-form-grid">
                        <div>
                            <label class="post-form-label" for="playlist-select">Playlist có sẵn</label>
                            <select id="playlist-select" class="form-control post-form-input">
                                <option value="">Chọn playlist của bạn</option>
                                <?php foreach ($userPlaylists as $playlist): ?>
                                    <option value="<?= (int) $playlist['playlist_id'] ?>">
                                        <?= h($playlist['playlist_name']) ?> (<?= (int) $playlist['song_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="post-form-label" for="playlist-name">Hoặc tạo playlist mới</label>
                            <input
                                id="playlist-name"
                                class="form-control post-form-input"
                                type="text"
                                maxlength="150"
                                placeholder="Ví dụ: Chill tối nay"
                            >
                        </div>
                    </div>

                    <button id="btn-add-to-playlist" type="button" class="btn btn-primary rounded-pill px-3 mt-3">
                        <i class="fas fa-plus me-2"></i>Thêm bài hát vào playlist
                    </button>
                </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-5 mb-4">
            <section class="post-panel">
                <div class="post-panel-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <span class="section-kicker">Meaning Analysis</span>
                        <h4 class="post-panel-title mb-1">Ý nghĩa bài hát</h4>
                        <p class="post-panel-subtitle mb-0">
                            Phân tích nội dung bài hát bằng AI và lưu lại vào database khi cần.
                        </p>
                    </div>

                    <button id="btn-reanalyze" type="button" class="btn btn-sm btn-outline-primary px-3 py-2 rounded-pill">
                        <i class="fas fa-rotate-right me-1"></i>Phân tích lại
                    </button>
                </div>

                <div id="analysis-status" class="mb-3"></div>

                <div id="analysis-box" class="meaning-box">
                    <div class="skeleton skeleton-title"></div>
                    <div class="skeleton skeleton-line"></div>
                    <div class="skeleton skeleton-line"></div>
                    <div class="skeleton skeleton-line" style="width:60%"></div>
                </div>
            </section>

            <section class="post-panel mt-4 post-right-song-info-panel">
                <div class="post-panel-header">
                    <span class="section-kicker">Song Info</span>
                    <h4 class="post-panel-title mb-1">Thông tin nhanh</h4>
                    <p class="post-panel-subtitle mb-0">
                        Tóm tắt một số thông tin cơ bản của bài hát đang xem.
                    </p>
                </div>

                <div class="post-info-list">
                    <div class="post-info-item">
                        <span>Tên bài hát</span>
                        <strong><?= h($song['title']) ?></strong>
                    </div>

                    <div class="post-info-item">
                        <span>Nghệ sĩ</span>
                        <strong><?= h($song['artist_name']) ?></strong>
                    </div>

                    <div class="post-info-item">
                        <span>Album</span>
                        <strong><?= h($albumName) ?></strong>
                    </div>

                    <div class="post-info-item">
                        <span>Ngày phát hành</span>
                        <strong><?= $releaseDate !== '' ? h($releaseDate) : 'Đang cập nhật' ?></strong>
                    </div>

                    <div class="post-info-item">
                        <span>Ngôn ngữ</span>
                        <strong><?= h($language) ?></strong>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php if (!empty($relatedSongs)): ?>
        <section class="post-panel mt-2">
            <div class="post-panel-header">
                <span class="section-kicker">Related Songs</span>
                <h4 class="post-panel-title mb-1">Bài hát liên quan</h4>
                <p class="post-panel-subtitle mb-0">
                    Gợi ý thêm những bài hát gần với bài bạn đang xem theo nghệ sĩ, album và thể loại.
                </p>
            </div>

            <div class="related-song-grid">
                <?php foreach ($relatedSongs as $relatedSong): ?>
                    <a href="post.php?id=<?= (int) $relatedSong['song_id'] ?>" class="related-song-card">
                        <img
                            src="<?= h($relatedSong['cover']) ?>"
                            alt="<?= h($relatedSong['title']) ?>"
                            class="related-song-cover"
                        >

                        <div class="related-song-copy">
                            <span class="related-song-badge"><?= h($relatedSong['relation_label']) ?></span>
                            <strong><?= h($relatedSong['title']) ?></strong>
                            <span class="related-song-meta">
                                <?= h($relatedSong['artist_name']) ?>
                                <?php if (!empty($relatedSong['album_name'])): ?>
                                    · <?= h($relatedSong['album_name']) ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <span class="related-song-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section id="comments-panel" class="post-panel mt-2">
        <div class="post-panel-header">
            <span class="section-kicker">Comments</span>
            <h4 class="post-panel-title mb-1">Bình luận</h4>
            <p class="post-panel-subtitle mb-0">
                Thảo luận, chia sẻ cảm nhận và đọc ý kiến từ người dùng khác.
            </p>
        </div>

        <div id="comments-section">
            <?php if (!isset($_SESSION["user_id"])): ?>
                <div class="comment-login-box">
                    <p><em>Bạn cần đăng nhập để bình luận.</em></p>
                    <a href="login.php" class="btn btn-primary rounded-pill px-3">Đăng nhập</a>
                </div>
            <?php else: ?>
                <div class="comment-form-box">
                    <textarea id="comment-input" class="form-control mb-2 comment-input" placeholder="Nhập bình luận..."></textarea>
                    <button id="btn-comment" class="btn btn-primary rounded-pill px-3">Gửi bình luận</button>
                </div>
            <?php endif; ?>

            <div id="comment-list" class="comment-list">
                <p class="text-muted">Đang tải bình luận...</p>
            </div>
        </div>
    </section>
</div>

<script>
const CURRENT_USER_ID = <?= $currentUserId ?>;
const SIMPLE_ADMIN_CAN_DELETE = <?= $isSimpleAdmin ? 'true' : 'false' ?>;
const SONG_ID = <?= (int) $song['song_id'] ?>;

function loadLyrics() {
    const lyricsBox = document.getElementById('lyrics-box');

    <?php if (!$needAjaxFetch): ?>
        lyricsBox.classList.add("show");
        loadMeaning();
        return;
    <?php endif; ?>

    fetch("includes/ajax_fetch_lyrics.php?song_id=<?= (int) $song['song_id'] ?>")
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                lyricsBox.innerHTML =
                    `<div class="lyrics-source"><span class="lyrics-source-pill">(Nguồn: ${data.source})</span></div>
                     <div class="lyrics-text">${data.lyrics}</div>`;
            } else {
                lyricsBox.innerHTML =
                    `<p class="lyrics-loading">${data.error || 'Lyrics hiện chưa sẵn sàng trong dữ liệu demo.'}</p>`;
            }
        })
        .finally(() => {
            lyricsBox.classList.add("show");
            setTimeout(loadMeaning, 200);
        });
}

function renderMeaningLoading() {
    const box = document.getElementById("analysis-box");
    box.innerHTML = `
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line" style="width:60%"></div>
    `;
}

function loadMeaning(force = false) {
    const box = document.getElementById("analysis-box");
    const status = document.getElementById("analysis-status");

    if (force) {
        renderMeaningLoading();
        status.innerHTML = `<span class="badge-status badge-yellow">AI đang phân tích lại và lưu vào database...</span>`;
    } else {
        status.innerHTML = "";
    }

    fetch(`includes/ajax_analyze_lyrics.php?song_id=<?= (int) $song['song_id'] ?>&force=${force ? '1' : '0'}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                box.innerHTML = `<p class="text-muted mb-0">` + (data.error || 'Chưa thể phân tích ý nghĩa bài hát.') + `</p>`;
                status.innerHTML = "";
                loadComments();
                return;
            }

            box.innerHTML = data.meaning;
            status.innerHTML = data.cached && !force
                ? `<span class="badge-status badge-green">Đã dùng meaning lưu trong database</span>`
                : `<span class="badge-status badge-green">Đã phân tích lại và lưu meaning vào database</span>`;

            loadComments();
        })
        .catch(() => {
            box.innerHTML = `<p class="text-muted mb-0">Không thể tải phần phân tích lúc này.</p>`;
            status.innerHTML = "";
            loadComments();
        });
}

function loadComments() {
    fetch("includes/api_get_comments.php?song_id=<?= (int) $song['song_id'] ?>")
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById("comment-list");

            if (!data.success || !data.comments.length) {
                box.innerHTML = "<div class='comment-empty'>Chưa có bình luận.</div>";
                return;
            }

            box.innerHTML = data.comments.map(c => `
                <div class="comment-item">
                    <div class="comment-head">
                        <div class="comment-user">
                            <strong>${c.username_html}</strong>
                            <span>${c.created_at_html}</span>
                        </div>
                        ${(CURRENT_USER_ID == c.user_id || SIMPLE_ADMIN_CAN_DELETE)
                            ? `<button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="deleteComment(${c.comment_id})">Xóa</button>`
                            : ``}
                    </div>
                    <div class="comment-body">${c.content_html}</div>
                </div>
            `).join("");
        })
        .catch(() => {
            document.getElementById("comment-list").innerHTML =
                "<div class='comment-empty'>Không thể tải bình luận.</div>";
        });
}

function deleteComment(id) {
    if (!confirm("Xóa bình luận?")) return;

    const fd = new FormData();
    fd.append("comment_id", id);

    fetch("includes/api_delete_comment.php", { method: "POST", body: fd })
        .then(() => loadComments());
}

document.getElementById("btn-comment")?.addEventListener("click", () => {
    const input = document.getElementById("comment-input");
    const text = input.value.trim();
    if (!text) return;

    const fd = new FormData();
    fd.append("song_id", <?= (int) $song['song_id'] ?>);
    fd.append("comment", text);

    fetch("includes/api_add_comment.php", { method: "POST", body: fd })
        .then(() => {
            input.value = "";
            loadComments();
        });
});

document.getElementById("btn-reanalyze")?.addEventListener("click", () => {
    loadMeaning(true);
});

document.getElementById("btn-add-to-playlist")?.addEventListener("click", () => {
    const playlistSelect = document.getElementById("playlist-select");
    const playlistName = document.getElementById("playlist-name");
    const statusBox = document.getElementById("playlist-status");

    const selectedPlaylistId = (playlistSelect?.value || "").trim();
    const newPlaylistName = (playlistName?.value || "").trim();

    if (!selectedPlaylistId && !newPlaylistName) {
        statusBox.innerHTML = `<span class="badge-status badge-yellow">Hãy chọn playlist có sẵn hoặc nhập tên playlist mới.</span>`;
        return;
    }

    const fd = new FormData();
    fd.append("song_id", SONG_ID);
    if (selectedPlaylistId) {
        fd.append("playlist_id", selectedPlaylistId);
    }
    if (newPlaylistName) {
        fd.append("playlist_name", newPlaylistName);
    }

    statusBox.innerHTML = `<span class="badge-status badge-yellow">Đang thêm bài hát vào playlist...</span>`;

    fetch("includes/api_add_to_playlist.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                statusBox.innerHTML = `<span class="badge-status badge-red">${data.message || data.error || 'Không thể thêm vào playlist lúc này.'}</span>`;
                return;
            }

            statusBox.innerHTML = `<span class="badge-status badge-green">Đã thêm vào playlist: ${data.playlist_name}</span>`;
            if (playlistSelect && data.playlist_id && data.playlist_name) {
                let existingOption = Array.from(playlistSelect.options).find((option) => option.value === String(data.playlist_id));
                if (!existingOption) {
                    existingOption = document.createElement("option");
                    existingOption.value = String(data.playlist_id);
                    playlistSelect.appendChild(existingOption);
                }
                existingOption.textContent = `${data.playlist_name}`;
                playlistSelect.value = String(data.playlist_id);
            }
            if (playlistName) {
                playlistName.value = "";
            }
        })
        .catch(() => {
            statusBox.innerHTML = `<span class="badge-status badge-red">Không thể kết nối để thêm bài hát vào playlist.</span>`;
        });
});

document.addEventListener("DOMContentLoaded", loadLyrics);
</script>

<script>
function commentActionButton(label, extraClass, onClick) {
    return `<button type="button" class="comment-action ${extraClass}" onclick="${onClick}">${label}</button>`;
}

function renderCommentCard(comment, isReply = false) {
    const editedBadge = comment.is_edited ? `<span class="comment-edited">(đã sửa)</span>` : '';
    const repliesHtml = (comment.replies || []).map((reply) => renderCommentCard(reply, true)).join('');

    const replyForm = window.activeReplyParentId === comment.comment_id ? `
        <div class="comment-inline-form">
            <textarea id="reply-input-${comment.comment_id}" class="form-control comment-input" placeholder="Viết phản hồi..."></textarea>
            <div class="comment-inline-actions">
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="submitReply(${comment.comment_id})">Gửi phản hồi</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="cancelReply()">Hủy</button>
            </div>
        </div>
    ` : '';

    const editForm = window.activeEditCommentId === comment.comment_id ? `
        <div class="comment-inline-form">
            <textarea id="edit-input-${comment.comment_id}" class="form-control comment-input">${comment.content_input_html}</textarea>
            <div class="comment-inline-actions">
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="submitEdit(${comment.comment_id})">Lưu chỉnh sửa</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="cancelEdit()">Hủy</button>
            </div>
        </div>
    ` : '';

    return `
        <article class="comment-item ${isReply ? 'comment-reply' : ''}">
            <div class="comment-head">
                <div class="comment-user">
                    <strong>${comment.username_html}</strong>
                    <span>${comment.created_at_html} ${editedBadge}</span>
                </div>
                <div class="comment-actions-top">
                    ${comment.can_delete ? commentActionButton('Xóa', 'is-danger', `deleteComment(${comment.comment_id})`) : ''}
                </div>
            </div>

            <div class="comment-body">${window.activeEditCommentId === comment.comment_id ? '' : comment.content_html}</div>
            ${editForm}

            <div class="comment-actions-row">
                ${comment.can_reply ? commentActionButton('Phản hồi', '', `toggleReply(${comment.comment_id})`) : ''}
                ${comment.can_edit ? commentActionButton('Sửa', '', `toggleEdit(${comment.comment_id})`) : ''}
                ${CURRENT_USER_ID > 0 && CURRENT_USER_ID !== comment.user_id
                    ? `<button type="button" class="comment-action ${comment.liked_by_me ? 'is-liked' : ''}" onclick="toggleLike(${comment.comment_id})"><i class="fas fa-heart me-1"></i>${comment.liked_by_me ? 'Đã thích' : 'Thích'} <span>(${comment.like_count})</span></button>`
                    : `<span class="comment-like-counter"><i class="fas fa-heart me-1"></i>${comment.like_count}</span>`}
            </div>

            ${replyForm}
            ${repliesHtml ? `<div class="comment-replies">${repliesHtml}</div>` : ''}
        </article>
    `;
}

window.activeReplyParentId = null;
window.activeEditCommentId = null;

window.loadComments = function () {
    fetch(`includes/api_get_comments.php?song_id=${SONG_ID}`)
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById("comment-list");
            if (!data.success || !data.comments.length) {
                box.innerHTML = "<div class='comment-empty'>Chưa có bình luận.</div>";
                return;
            }

            box.innerHTML = data.comments.map((comment) => renderCommentCard(comment)).join("");
        })
        .catch(() => {
            document.getElementById("comment-list").innerHTML = "<div class='comment-empty'>Không thể tải bình luận.</div>";
        });
};

window.submitComment = function (text, parentCommentId = null) {
    const content = (text || "").trim();
    if (!content) {
        return;
    }

    const fd = new FormData();
    fd.append("song_id", SONG_ID);
    fd.append("comment", content);
    if (parentCommentId !== null) {
        fd.append("parent_comment_id", parentCommentId);
    }

    fetch("includes/api_add_comment.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then((data) => {
            if (!data.success) {
                const message = data.message || data.error || "Không thể lưu bình luận lúc này.";
                alert(message);
                return;
            }

            window.activeReplyParentId = null;
            window.loadComments();
        })
        .catch(() => {
            alert("Không thể kết nối để lưu bình luận lúc này.");
        });
};

window.toggleReply = function (commentId) {
    window.activeEditCommentId = null;
    window.activeReplyParentId = window.activeReplyParentId === commentId ? null : commentId;
    window.loadComments();
};

window.cancelReply = function () {
    window.activeReplyParentId = null;
    window.loadComments();
};

window.submitReply = function (commentId) {
    const input = document.getElementById(`reply-input-${commentId}`);
    if (!input) return;
    window.submitComment(input.value, commentId);
};

window.toggleEdit = function (commentId) {
    window.activeReplyParentId = null;
    window.activeEditCommentId = window.activeEditCommentId === commentId ? null : commentId;
    window.loadComments();
};

window.cancelEdit = function () {
    window.activeEditCommentId = null;
    window.loadComments();
};

window.submitEdit = function (commentId) {
    const input = document.getElementById(`edit-input-${commentId}`);
    if (!input || !input.value.trim()) return;

    const fd = new FormData();
    fd.append("comment_id", commentId);
    fd.append("comment", input.value.trim());

    fetch("includes/api_edit_comment.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(() => {
            window.activeEditCommentId = null;
            window.loadComments();
        });
};

window.toggleLike = function (commentId) {
    const fd = new FormData();
    fd.append("comment_id", commentId);

    fetch("includes/api_like_comment.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(() => window.loadComments());
};

window.deleteComment = function (commentId) {
    if (!confirm("Xóa bình luận?")) return;

    const fd = new FormData();
    fd.append("comment_id", commentId);
    fetch("includes/api_delete_comment.php", { method: "POST", body: fd })
        .then(() => window.loadComments());
};

document.getElementById("btn-comment")?.addEventListener("click", () => {
    const input = document.getElementById("comment-input");
    window.submitComment(input.value);
    input.value = "";
});

const originalCommentButton = document.getElementById("btn-comment");
if (originalCommentButton) {
    const freshCommentButton = originalCommentButton.cloneNode(true);
    originalCommentButton.parentNode.replaceChild(freshCommentButton, originalCommentButton);
    freshCommentButton.addEventListener("click", () => {
        const input = document.getElementById("comment-input");
        window.submitComment(input.value);
        input.value = "";
    });
}
</script>

<style>
.post-page,
.post-page * {
    box-sizing: border-box;
}

.post-page p,
.post-page .post-panel-subtitle,
.post-page .content-empty-state p,
.post-page .comment-login-box p,
.post-page .lyrics-loading,
.post-page .post-info-item span {
    margin: 0;
}

.post-hero {
    overflow: visible;
    padding-bottom: 4.8rem;
}

.post-hero-overlay {
    background:
        linear-gradient(135deg, rgba(16, 24, 40, 0.78) 0%, rgba(16, 24, 40, 0.50) 100%),
        radial-gradient(circle at top left, rgba(12, 202, 240, 0.16), transparent 28%);
}

.post-heading-shell {
    position: relative;
    z-index: 1;
    max-width: 980px;
    margin: 0 auto;
    text-align: center;
}

.post-eyebrow {
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

.post-heading-shell h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem);
}

.post-subheading {
    display: block;
    margin-top: .6rem;
    font-size: 1.08rem;
    line-height: 1.6;
}

.post-hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .65rem;
    margin-top: 1.35rem;
}

.post-chip {
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

.post-chip-link {
    text-decoration: none;
    transition: transform .18s ease, background-color .18s ease, border-color .18s ease;
}

.post-chip-link:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.18);
    border-color: rgba(255,255,255,.3);
    text-decoration: none;
}

.post-summary-bar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .8rem;
    margin-top: -2rem;
    margin-bottom: 1.4rem;
    position: relative;
    z-index: 2;
}

.post-summary-bar {
    display: grid;
}

.post-quick-info-panel {
    display: block;
}

.post-right-song-info-panel {
    display: none;
}

.post-summary-stat,
.post-panel,
.content-empty-state,
.lyrics-box,
.meaning-box,
.comment-item,
.comment-login-box,
.comment-form-box,
.post-info-item {
    background: #fff;
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.post-summary-stat {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.post-summary-stat span {
    display: block;
    margin-bottom: .22rem;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7d8795;
}

.post-summary-stat strong {
    display: block;
    color: #162033;
    font-size: 1.2rem;
    line-height: 1.2;
}

.post-summary-stat small {
    display: block;
    margin-top: .3rem;
    color: #6f7c91;
    font-size: .86rem;
    line-height: 1.5;
}

.post-summary-link {
    color: #162033;
    text-decoration: none;
}

.post-summary-link:hover {
    color: #0d6efd;
    text-decoration: none;
}

.post-panel {
    height: 100%;
    padding: 1.15rem;
    border-radius: 24px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.post-panel-header {
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

.post-panel-title {
    color: #162033;
    font-size: 1.45rem;
    font-weight: 800;
}

.post-panel-subtitle {
    color: #6f7c91;
    font-size: .95rem;
    line-height: 1.6;
    font-family: "Open Sans", sans-serif;
}

.lyrics-box,
.meaning-box {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
    min-height: 170px;
}

.lyrics-box {
    opacity: 0;
    transform: translateY(8px);
    transition: all .25s ease;
}

.lyrics-box.show {
    opacity: 1;
    transform: translateY(0);
}

.lyrics-source {
    margin-bottom: .9rem;
}

.lyrics-source-pill {
    display: inline-flex;
    align-items: center;
    padding: .4rem .75rem;
    border-radius: 999px;
    background: rgba(25, 135, 84, .10);
    color: #198754;
    font-size: .82rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.lyrics-loading {
    color: #6f7c91;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.lyrics-text {
    color: #273244;
    font-size: 1rem;
    line-height: 1.9;
    font-family: "Open Sans", sans-serif;
}

.lyrics-text p {
    margin: 0 0 1rem 0;
}

.post-video-frame {
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
    box-shadow: 0 18px 44px rgba(10, 20, 40, .08);
}

.related-song-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.related-song-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 22px;
    border: 1px solid rgba(16, 24, 40, .06);
    background: #f8fbff;
    text-decoration: none;
    color: inherit;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.related-song-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(15, 23, 42, .08);
    border-color: rgba(13, 110, 253, .16);
    text-decoration: none;
}

.related-song-cover {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    object-fit: cover;
    flex: 0 0 auto;
    background: #e5e7eb;
}

.related-song-copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .24rem;
}

.related-song-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: .28rem .6rem;
    border-radius: 999px;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.related-song-copy strong {
    color: #162033;
    font-size: 1.02rem;
    line-height: 1.35;
}

.related-song-meta {
    color: #6f7c91;
    font-size: .9rem;
    line-height: 1.5;
}

.related-song-arrow {
    margin-left: auto;
    color: #8ca0bd;
    flex: 0 0 auto;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    padding: .45rem .8rem;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.badge-green {
    background: rgba(25, 135, 84, .10);
    color: #198754;
}

.badge-yellow {
    background: rgba(255, 193, 7, .14);
    color: #9a6b00;
}

.badge-red {
    background: rgba(220, 53, 69, .12);
    color: #b42334;
}

.skeleton {
    border-radius: 12px;
    background: linear-gradient(90deg, #eef2f7 25%, #f7f9fc 50%, #eef2f7 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite linear;
}

.skeleton-title {
    height: 20px;
    width: 48%;
    margin-bottom: 1rem;
}

.skeleton-line {
    height: 14px;
    width: 100%;
    margin-bottom: .75rem;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.post-info-list {
    display: grid;
    gap: .75rem;
}

.post-info-item {
    padding: .95rem 1rem;
    border-radius: 18px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.post-info-item span {
    display: block;
    margin-bottom: .22rem;
    color: #6f7c91;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-family: "Open Sans", sans-serif;
}

.post-info-item strong {
    color: #162033;
    font-size: 1rem;
    line-height: 1.45;
}

.comment-form-box {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: 1rem;
}

.comment-login-box {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
    margin-bottom: 1rem;
    text-align: center;
}

.comment-input {
    min-height: 110px;
    border-radius: 16px;
    border: 1px solid rgba(16, 24, 40, .10);
    box-shadow: none;
}

.comment-input:focus {
    border-color: rgba(13, 110, 253, .35);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .10);
}

.comment-list {
    display: grid;
    gap: .8rem;
}

.comment-item {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(16, 24, 40, .06);
}

.comment-head {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .65rem;
}

.comment-user strong {
    display: block;
    color: #162033;
    font-size: .98rem;
    line-height: 1.45;
}

.comment-user span {
    display: block;
    margin-top: .12rem;
    color: #6f7c91;
    font-size: .85rem;
    line-height: 1.45;
}

.comment-body {
    color: #273244;
    font-size: .95rem;
    line-height: 1.75;
    font-family: "Open Sans", sans-serif;
    word-break: break-word;
}

.comment-body p {
    margin: 0;
}

.comment-empty {
    padding: 1rem;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid rgba(16, 24, 40, .06);
    color: #6f7c91;
    font-size: .95rem;
    font-family: "Open Sans", sans-serif;
    text-align: center;
}

.comment-reply {
    margin-top: .8rem;
    background: linear-gradient(180deg, rgba(13, 110, 253, .04), rgba(13, 110, 253, .01));
}

.comment-replies {
    margin-top: .85rem;
    padding-left: 1rem;
    border-left: 2px solid rgba(13, 110, 253, .12);
}

.comment-actions-top,
.comment-actions-row,
.comment-inline-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.comment-actions-row,
.comment-inline-actions {
    margin-top: .8rem;
}

.comment-action {
    border: 0;
    border-radius: 999px;
    padding: .45rem .9rem;
    background: rgba(15, 23, 42, .06);
    color: #324155;
    font-family: "Open Sans", sans-serif;
    font-size: .82rem;
    font-weight: 700;
    transition: background-color .18s ease, color .18s ease, transform .18s ease;
}

.comment-action:hover {
    background: rgba(13, 110, 253, .12);
    color: #0d6efd;
    transform: translateY(-1px);
}

.comment-action.is-danger:hover {
    background: rgba(220, 53, 69, .12);
    color: #dc3545;
}

.comment-action.is-liked {
    background: rgba(220, 53, 69, .1);
    color: #c43b4d;
}

.comment-like-counter,
.comment-edited {
    color: #6f7c91;
    font-family: "Open Sans", sans-serif;
    font-size: .84rem;
}

.comment-inline-form {
    margin-top: .9rem;
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

.playlist-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.post-form-label {
    display: block;
    margin-bottom: .45rem;
    color: #6f7c91;
    font-size: .86rem;
    font-weight: 700;
    font-family: "Open Sans", sans-serif;
}

.post-form-input {
    min-height: 46px;
    border-radius: 16px;
    border: 1px solid rgba(16, 24, 40, .08);
    background: #f8fbff;
    box-shadow: none !important;
}

@media (max-width: 1199.98px) {
    .post-summary-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .related-song-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 991.98px) {
    .post-summary-bar {
        margin-top: 0;
    }
}

@media (max-width: 767.98px) {
    .post-hero {
        padding-bottom: 4rem;
    }

    .post-hero-chips {
        justify-content: flex-start;
    }

    .post-summary-bar {
        grid-template-columns: 1fr;
    }

    .playlist-form-grid {
        grid-template-columns: 1fr;
    }

    .comment-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .comment-replies {
        padding-left: .75rem;
    }
}
</style>

<?php include('includes/footer.php'); ?>
