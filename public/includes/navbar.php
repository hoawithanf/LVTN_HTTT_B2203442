<?php
include_once(__DIR__ . '/session.php');
include_once(__DIR__ . '/database.php');

$user_id = $_SESSION['user_id'] ?? null;

$notifications = [];
$unreadCount = 0;
$recentSearches = [];

if ($user_id) {
    $sql = "
        SELECT
            n.notification_id,
            n.news_id,
            n.is_read,
            n.created_at,
            ne.title
        FROM notifications n
        INNER JOIN news ne ON ne.news_id = n.news_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 5
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
        if ((int) $row['is_read'] === 0) {
            $unreadCount++;
        }
    }

    $stmt->close();

    $recentSql = "
        SELECT
            s.song_id,
            s.title,
            a.artist_name,
            al.album_name,
            s.cover_image AS song_cover,
            al.cover_image AS album_cover,
            MAX(sl.search_time) AS recent_at
        FROM search_logs sl
        INNER JOIN songs s ON s.song_id = sl.song_id
        INNER JOIN artists a ON a.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        WHERE sl.user_id = ?
        GROUP BY
            s.song_id,
            s.title,
            a.artist_name,
            al.album_name,
            s.cover_image,
            al.cover_image
        ORDER BY recent_at DESC, s.song_id DESC
        LIMIT 5
    ";

    $recentStmt = $conn->prepare($recentSql);
    $recentStmt->bind_param("i", $user_id);
    $recentStmt->execute();
    $recentRes = $recentStmt->get_result();

    while ($row = $recentRes->fetch_assoc()) {
        $cover = trim((string) ($row['song_cover'] ?? ''));
        if ($cover === '') {
            $cover = trim((string) ($row['album_cover'] ?? ''));
        }

        if ($cover !== '') {
            $cover = 'assets/img/albums/' . basename($cover);
        } else {
            $cover = 'assets/img/default.jpg';
        }

        $subtitleParts = [(string) ($row['artist_name'] ?? '')];
        if (!empty($row['album_name'])) {
            $subtitleParts[] = (string) $row['album_name'];
        }

        $recentSearches[] = [
            'song_id' => (int) $row['song_id'],
            'title' => htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'),
            'subtitle' => htmlspecialchars(implode(' · ', array_filter($subtitleParts)), ENT_QUOTES, 'UTF-8'),
            'cover' => htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'),
        ];
    }

    $recentStmt->close();
}
?>

<nav class="navbar navbar-expand-lg navbar-light" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand" href="index.php">NLN Lyrics</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive">
            Menu <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto py-4 py-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-lg-3 py-3 py-lg-4" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3 py-3 py-lg-4" href="charts.php">Charts</a>
                </li>
                <?php if ($user_id): ?>
                    <li class="nav-item">
                        <a class="nav-link px-lg-3 py-3 py-lg-4" href="recap.php">Recap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-lg-3 py-3 py-lg-4" href="persona.php">Persona</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link px-lg-3 py-3 py-lg-4" href="news_list.php">Tin tức</a>
                </li>
            </ul>

            <form class="search-box navbar-search-box ms-lg-3 mt-3 mt-lg-0" method="GET" action="search.php" autocomplete="off">
                <div class="position-relative navbar-search-shell">
                    <input
                        id="navbarSearchInput"
                        class="form-control form-control-sm ps-4 pe-5 rounded-pill navbar-search-input"
                        type="search"
                        name="q"
                        placeholder="Tìm bài hát hoặc nghệ sĩ"
                        aria-label="Tìm bài hát hoặc nghệ sĩ"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <button class="btn position-absolute top-50 end-0 translate-middle-y pe-3 border-0 bg-transparent text-secondary" type="submit" aria-label="Tìm kiếm">
                        <i class="fas fa-search"></i>
                    </button>

                    <div id="navbarSearchSuggest" class="navbar-search-dropdown" hidden>
                        <div class="navbar-popover-head">
                            <span id="navbarSearchKicker" class="navbar-popover-kicker">Recent</span>
                            <strong id="navbarSearchHeading">Tìm kiếm gần đây</strong>
                        </div>
                        <div id="navbarSearchSuggestList" class="navbar-search-list"></div>
                    </div>
                </div>
            </form>

            <ul class="navbar-nav ms-3 mt-3 mt-lg-0 align-items-center navbar-utility-nav">
                <?php if ($user_id): ?>
                    <li class="nav-item dropdown position-relative">
                        <a class="nav-link position-relative px-lg-3 py-3 py-lg-4 navbar-icon-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="navbar-badge"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow navbar-dropdown navbar-notification-menu">
                            <li class="navbar-dropdown-head">
                                <span class="navbar-popover-kicker">Inbox</span>
                                <strong>Thông báo</strong>
                            </li>
                            <?php if ($notifications): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <a
                                            class="dropdown-item navbar-noti-item <?= $notification['is_read'] ? '' : 'is-unread' ?>"
                                            data-id="<?= (int) $notification['notification_id'] ?>"
                                            href="news.php?id=<?= (int) $notification['news_id'] ?>"
                                        >
                                            <span class="navbar-noti-title"><?= htmlspecialchars((string) $notification['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="navbar-noti-time"><?= date('d/m/Y H:i', strtotime((string) $notification['created_at'])) ?></small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="navbar-empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <span>Chưa có thông báo nào.</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-lg-3 py-3 py-lg-4 navbar-icon-link navbar-profile-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow navbar-dropdown navbar-profile-menu">
                            <li class="navbar-dropdown-head">
                                <span class="navbar-popover-kicker">Account</span>
                                <strong>Tài khoản</strong>
                            </li>
                            <li>
                                <a class="dropdown-item navbar-profile-item" href="recap.php">
                                    <i class="fas fa-chart-pie"></i>
                                    <span>Recap cá nhân</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item navbar-profile-item" href="persona.php">
                                    <i class="fas fa-compact-disc"></i>
                                    <span>Music Persona</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item navbar-profile-item" href="profile.php">
                                    <i class="fas fa-id-badge"></i>
                                    <span>Hồ sơ cá nhân</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item navbar-profile-item is-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Đăng xuất</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-lg-3 py-3 py-lg-4 text-success" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-search-box {
    width: min(248px, 100%);
}

.navbar-search-shell {
    width: 100%;
}

.navbar-search-input {
    height: 40px;
    border: 1px solid rgba(148, 163, 184, .24);
    background: rgba(255, 255, 255, .96) !important;
    box-shadow: 0 10px 22px rgba(15, 23, 42, .08);
    font-family: "Open Sans", sans-serif;
    font-size: .86rem;
}

.navbar-search-input:focus {
    border-color: rgba(13, 110, 253, .24);
    box-shadow: 0 14px 28px rgba(15, 23, 42, .12), 0 0 0 .18rem rgba(13, 110, 253, .08);
}

.navbar-icon-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.navbar-badge {
    position: absolute;
    top: 10px;
    right: 2px;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: .66rem;
    font-weight: 800;
    line-height: 1;
    box-shadow: 0 8px 16px rgba(239, 68, 68, .24);
}

.navbar-dropdown {
    min-width: 240px;
    margin-top: .5rem;
    padding: .45rem;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: 20px;
    background: rgba(255, 255, 255, .985);
    box-shadow: 0 24px 42px rgba(15, 23, 42, .14);
    backdrop-filter: blur(12px);
}

.navbar-notification-menu {
    width: min(320px, calc(100vw - 24px));
}

.navbar-profile-menu {
    width: 220px;
}

.navbar-dropdown-head,
.navbar-popover-head {
    display: grid;
    gap: .12rem;
    padding: .7rem .85rem .65rem;
}

.navbar-popover-kicker {
    color: #2563eb;
    font-family: "Open Sans", sans-serif;
    font-size: .64rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.navbar-dropdown-head strong,
.navbar-popover-head strong {
    color: #162033;
    font-size: .95rem;
    line-height: 1.28;
}

.navbar-noti-item,
.navbar-profile-item,
.navbar-search-item {
    display: flex;
    align-items: flex-start;
    gap: .78rem;
    padding: .76rem .85rem;
    border-radius: 16px;
    transition: background-color .16s ease, transform .16s ease;
}

.navbar-noti-item:hover,
.navbar-noti-item:focus,
.navbar-profile-item:hover,
.navbar-profile-item:focus,
.navbar-search-item:hover,
.navbar-search-item:focus {
    color: inherit;
    background: #f8fbff;
    transform: translateY(-1px);
}

.navbar-noti-item.is-unread {
    background: linear-gradient(180deg, rgba(37, 99, 235, .075), rgba(37, 99, 235, .025));
}

.navbar-noti-title {
    display: block;
    color: #1f2937;
    font-size: .92rem;
    font-weight: 700;
    line-height: 1.42;
}

.navbar-noti-time {
    display: block;
    margin-top: .16rem;
    color: #7b8794;
    font-size: .76rem;
    line-height: 1.42;
}

.navbar-profile-item {
    align-items: center;
    color: #162033;
    font-size: .94rem;
    font-weight: 700;
}

.navbar-profile-item i {
    width: 18px;
    text-align: center;
    color: #334155;
}

.navbar-profile-item.is-danger,
.navbar-profile-item.is-danger i {
    color: #ef4444;
}

.navbar-empty-state {
    display: grid;
    justify-items: center;
    gap: .45rem;
    padding: 1rem .85rem;
    color: #7b8794;
    text-align: center;
}

.navbar-empty-state i {
    color: #94a3b8;
    font-size: 1.05rem;
}

.navbar-search-dropdown {
    position: absolute;
    top: calc(100% + .5rem);
    left: 0;
    right: 0;
    z-index: 1035;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: 20px;
    background: rgba(255, 255, 255, .985);
    box-shadow: 0 24px 42px rgba(15, 23, 42, .14);
    backdrop-filter: blur(12px);
}

.navbar-search-list {
    max-height: 332px;
    overflow-y: auto;
    padding: 0 .45rem .45rem;
}

.navbar-search-item {
    text-decoration: none;
    color: inherit;
}

.navbar-search-thumb {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 12px;
    object-fit: cover;
    background: #e5e7eb;
}

.navbar-search-copy {
    min-width: 0;
}

.navbar-search-title {
    color: #162033;
    font-size: .92rem;
    font-weight: 700;
    line-height: 1.35;
    word-break: break-word;
}

.navbar-search-subtitle,
.navbar-search-meta {
    display: block;
    color: #7b8794;
    font-size: .76rem;
    line-height: 1.42;
}

.navbar-search-meta {
    margin-top: .12rem;
}

@media (max-width: 991.98px) {
    .navbar-utility-nav {
        align-items: flex-start !important;
        gap: .15rem;
    }

    .navbar-dropdown,
    .navbar-search-dropdown {
        width: min(100%, calc(100vw - 24px));
    }

    .navbar-search-box {
        width: 100%;
    }
}
</style>

<script>
(function () {
    const searchInput = document.getElementById('navbarSearchInput');
    const suggestBox = document.getElementById('navbarSearchSuggest');
    const suggestList = document.getElementById('navbarSearchSuggestList');
    const searchHeading = document.getElementById('navbarSearchHeading');
    const searchKicker = document.getElementById('navbarSearchKicker');
    const recentItems = <?= json_encode($recentSearches, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let controller = null;
    let debounceTimer = null;

    function hideSuggestBox() {
        if (!suggestBox) {
            return;
        }
        suggestBox.hidden = true;
    }

    function renderEmptyState(message) {
        suggestList.innerHTML = `<div class="navbar-empty-state"><i class="fas fa-search"></i><span>${message}</span></div>`;
        suggestBox.hidden = false;
    }

    function renderSearchItems(items) {
        if (!suggestList || !suggestBox) {
            return;
        }

        suggestList.innerHTML = items.map((item) => {
            const metaParts = [];
            if (item.search_count) {
                metaParts.push(`${new Intl.NumberFormat('vi-VN').format(item.search_count)} lượt tìm`);
            }
            if (item.release_date) {
                metaParts.push(item.release_date);
            }

            return `
                <a class="navbar-search-item" href="post.php?id=${item.song_id}">
                    <img class="navbar-search-thumb" src="${item.cover || item.cover_image}" alt="${item.title}">
                    <div class="navbar-search-copy">
                        <span class="navbar-search-title">${item.title}</span>
                        <span class="navbar-search-subtitle">${item.subtitle || item.artist_name || ''}</span>
                        ${metaParts.length ? `<span class="navbar-search-meta">${metaParts.join(' · ')}</span>` : ''}
                    </div>
                </a>
            `;
        }).join('');

        suggestBox.hidden = false;
    }

    function showRecentItems() {
        if (!searchKicker || !searchHeading) {
            return;
        }

        searchKicker.textContent = 'Recent';
        searchHeading.textContent = 'Tìm kiếm gần đây';

        if (!recentItems.length) {
            renderEmptyState('Chưa có lượt tìm kiếm gần đây.');
            return;
        }

        renderSearchItems(recentItems);
    }

    function showKeywordItems(items) {
        if (!searchKicker || !searchHeading) {
            return;
        }

        searchKicker.textContent = 'Search';
        searchHeading.textContent = 'Gợi ý theo từ khóa';

        if (!items.length) {
            renderEmptyState('Không có gợi ý phù hợp.');
            return;
        }

        renderSearchItems(items);
    }

    if (searchInput && suggestBox && suggestList) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value.trim();

            clearTimeout(debounceTimer);

            if (controller) {
                controller.abort();
            }

            if (keyword === '') {
                showRecentItems();
                return;
            }

            debounceTimer = setTimeout(() => {
                controller = new AbortController();

                fetch(`includes/api_search_suggest.php?q=${encodeURIComponent(keyword)}`, {
                    signal: controller.signal
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data || !Array.isArray(data.items)) {
                            renderEmptyState('Không có gợi ý phù hợp.');
                            return;
                        }

                        showKeywordItems(data.items);
                    })
                    .catch((error) => {
                        if (error.name !== 'AbortError') {
                            renderEmptyState('Không thể tải gợi ý lúc này.');
                        }
                    });
            }, 150);
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim() === '') {
                showRecentItems();
            } else if (suggestList.innerHTML.trim() !== '') {
                suggestBox.hidden = false;
            }
        });

        document.addEventListener('click', function (event) {
            if (!suggestBox.contains(event.target) && event.target !== searchInput) {
                hideSuggestBox();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideSuggestBox();
            }
        });
    }

    document.querySelectorAll('.navbar-noti-item').forEach((item) => {
        item.addEventListener('click', function () {
            fetch('includes/api_mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: 'notification_id=' + this.dataset.id
            });
        });
    });
})();
</script>
