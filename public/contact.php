<?php
include('includes/session.php');
include('includes/database.php');
require_once __DIR__ . '/../config/lyric_request_helpers.php';

nln_lyric_request_ensure_schema($conn);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$successMessage = isset($_GET['submitted']) ? 'Yêu cầu của bạn đã được gửi tới admin. Bên mình sẽ kiểm tra và cập nhật lyrics sớm.' : '';
$errorMessage = '';
$songQueryValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUserId <= 0) {
        $errorMessage = 'Bạn cần đăng nhập để gửi yêu cầu chỉnh sửa lyrics.';
    } else {
        $songId = (int) ($_POST['song_id'] ?? 0);
        $songQueryValue = trim((string) ($_POST['song_query'] ?? ''));
        $requestMessage = trim((string) ($_POST['request_message'] ?? ''));

        if ($songId <= 0 || $requestMessage === '') {
            $errorMessage = 'Vui lòng chọn bài hát từ gợi ý và mô tả rõ nội dung cần chỉnh sửa.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO lyric_correction_requests (user_id, song_id, request_message, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");

            if ($stmt) {
                $stmt->bind_param("iis", $currentUserId, $songId, $requestMessage);
                $stmt->execute();
                $stmt->close();
                header('Location: contact.php?submitted=1');
                exit;
            }

            $errorMessage = 'Hiện chưa thể tạo yêu cầu. Vui lòng thử lại sau.';
        }
    }
}

include('includes/header.php');
include('includes/navbar.php');
?>

<header class="masthead" style="background-image: url('assets/img/home-bg.jpg');">
    <div class="overlay"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading">
            <span class="subheading">Support & Feedback</span>
            <h1>Liên hệ</h1>
            <span class="subheading">Gửi yêu cầu chỉnh sửa lyrics chưa chính xác để admin cập nhật lại dữ liệu.</span>
        </div>
    </div>
</header>

<main class="container px-4 px-lg-5 my-5">
    <section class="contact-shell">
        <div class="contact-card">
            <div class="contact-copy">
                <span class="contact-kicker">Lyrics Feedback</span>
                <h2>Gửi yêu cầu chỉnh sửa sai sót lyrics</h2>
                <p>Nhập tên bài hát để tìm nhanh đúng track, sau đó mô tả rõ dòng lyrics bị sai hoặc phần cần điều chỉnh. Admin sẽ xử lý trực tiếp ở trang chỉnh sửa bài hát.</p>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success rounded-4"><?= h($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger rounded-4"><?= h($errorMessage) ?></div>
            <?php endif; ?>

            <?php if ($currentUserId <= 0): ?>
                <div class="contact-login-box">
                    <p class="mb-3">Bạn cần đăng nhập trước khi gửi yêu cầu chỉnh sửa lyrics.</p>
                    <a class="btn btn-primary rounded-pill px-4" href="login.php">Đăng nhập</a>
                </div>
            <?php else: ?>
                <form method="POST" class="contact-form" autocomplete="off">
                    <div class="mb-3 position-relative">
                        <label for="song-query" class="form-label">Bài hát cần chỉnh sửa</label>
                        <input
                            id="song-query"
                            name="song_query"
                            type="text"
                            class="form-control contact-input"
                            placeholder="Nhập tên bài hát để tìm gợi ý..."
                            value="<?= h($songQueryValue) ?>"
                            required
                        >
                        <input id="song-id" name="song_id" type="hidden" value="">

                        <div id="contactSuggest" class="contact-suggest" hidden>
                            <div class="contact-suggest-head">
                                <span class="contact-suggest-kicker">Gợi ý</span>
                                <strong id="contactSuggestHeading">Bài hát phù hợp</strong>
                            </div>
                            <div id="contactSuggestList" class="contact-suggest-list"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="request_message" class="form-label">Nội dung cần chỉnh sửa</label>
                        <textarea
                            id="request_message"
                            name="request_message"
                            class="form-control contact-input"
                            rows="7"
                            placeholder="Ví dụ: Ở verse 2, dòng thứ 3 đang thiếu một câu. Hoặc phần chorus bị đảo vị trí hai dòng..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.contact-shell {
    max-width: 880px;
    margin: 0 auto;
}

.contact-card {
    padding: 2rem;
    border-radius: 28px;
    background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,255,.98));
    border: 1px solid rgba(15, 23, 42, .06);
    box-shadow: 0 22px 48px rgba(15, 23, 42, .08);
}

.contact-kicker {
    display: inline-block;
    margin-bottom: .75rem;
    color: #2563eb;
    font-size: .75rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.contact-copy h2 {
    margin-bottom: .75rem;
    color: #162033;
    font-size: 2rem;
    font-weight: 800;
}

.contact-copy p,
.contact-login-box p {
    color: #607086;
    font-size: 1rem;
    line-height: 1.75;
    font-family: "Open Sans", sans-serif;
}

.contact-form {
    margin-top: 1.5rem;
}

.contact-input {
    min-height: 52px;
    border-radius: 18px;
    border: 1px solid rgba(15, 23, 42, .08);
    background: #f8fbff;
    box-shadow: none !important;
}

.contact-login-box {
    margin-top: 1.5rem;
    padding: 1.25rem;
    border-radius: 20px;
    background: rgba(37, 99, 235, .05);
    border: 1px solid rgba(37, 99, 235, .08);
}

.contact-suggest {
    position: absolute;
    top: calc(100% + .55rem);
    left: 0;
    right: 0;
    z-index: 40;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: 20px;
    background: rgba(255,255,255,.985);
    box-shadow: 0 24px 42px rgba(15,23,42,.14);
    backdrop-filter: blur(12px);
}

.contact-suggest-head {
    display: grid;
    gap: .12rem;
    padding: .8rem .95rem .65rem;
}

.contact-suggest-kicker {
    color: #2563eb;
    font-size: .64rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.contact-suggest-list {
    max-height: 320px;
    overflow-y: auto;
    padding: 0 .45rem .45rem;
}

.contact-suggest-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    width: 100%;
    padding: .78rem .9rem;
    border: 0;
    border-radius: 16px;
    background: transparent;
    text-align: left;
    transition: background-color .16s ease, transform .16s ease;
}

.contact-suggest-item:hover {
    background: #f8fbff;
    transform: translateY(-1px);
}

.contact-suggest-thumb {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    object-fit: cover;
    flex-shrink: 0;
}

.contact-suggest-title {
    display: block;
    color: #162033;
    font-weight: 700;
}

.contact-suggest-subtitle {
    display: block;
    margin-top: .12rem;
    color: #607086;
    font-size: .9rem;
}

.contact-suggest-empty {
    padding: 1rem;
    color: #607086;
    text-align: center;
}
</style>

<script>
(() => {
    const input = document.getElementById('song-query');
    const hiddenSongId = document.getElementById('song-id');
    const suggestBox = document.getElementById('contactSuggest');
    const suggestList = document.getElementById('contactSuggestList');
    let debounceTimer = null;
    let controller = null;

    if (!input || !hiddenSongId || !suggestBox || !suggestList) {
        return;
    }

    function hideSuggest() {
        suggestBox.hidden = true;
    }

    function renderEmptyState(message) {
        suggestList.innerHTML = `<div class="contact-suggest-empty">${message}</div>`;
        suggestBox.hidden = false;
    }

    function renderItems(items) {
        suggestList.innerHTML = items.map((item) => `
            <button type="button" class="contact-suggest-item" data-song-id="${item.song_id}" data-song-label="${item.title} - ${item.artist_name}">
                <img class="contact-suggest-thumb" src="${item.cover}" alt="${item.title}">
                <span>
                    <span class="contact-suggest-title">${item.title}</span>
                    <span class="contact-suggest-subtitle">${item.subtitle || item.artist_name}</span>
                </span>
            </button>
        `).join('');

        suggestList.querySelectorAll('.contact-suggest-item').forEach((button) => {
            button.addEventListener('click', () => {
                hiddenSongId.value = button.dataset.songId || '';
                input.value = button.dataset.songLabel || '';
                hideSuggest();
            });
        });

        suggestBox.hidden = false;
    }

    input.addEventListener('input', function () {
        const keyword = this.value.trim();
        hiddenSongId.value = '';

        clearTimeout(debounceTimer);

        if (controller) {
            controller.abort();
        }

        if (keyword === '') {
            hideSuggest();
            return;
        }

        debounceTimer = setTimeout(() => {
            controller = new AbortController();

            fetch(`includes/api_search_suggest.php?q=${encodeURIComponent(keyword)}`, {
                signal: controller.signal
            })
                .then((response) => response.json())
                .then((data) => {
                    const items = Array.isArray(data.items) ? data.items : [];
                    if (!items.length) {
                        renderEmptyState('Không tìm thấy bài hát phù hợp.');
                        return;
                    }

                    renderItems(items);
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        renderEmptyState('Không thể tải gợi ý lúc này.');
                    }
                });
        }, 140);
    });

    document.addEventListener('click', (event) => {
        if (!suggestBox.contains(event.target) && event.target !== input) {
            hideSuggest();
        }
    });
})();
</script>

<?php include('includes/footer.php'); ?>
