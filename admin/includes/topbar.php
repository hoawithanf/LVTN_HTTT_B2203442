<?php
require_once __DIR__ . '/../../config/lyric_request_helpers.php';

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$notiCount = $unreadNotifications ?? 0;

if (isset($conn) && $conn instanceof mysqli) {
    $notiCount = nln_lyric_request_pending_count($conn);
}
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <form
        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search"
        method="GET"
        action="search_admin.php"
    >
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control bg-light border-0 small"
                placeholder="Tìm user / bài hát / nghệ sĩ..."
                aria-label="Search"
                required
            >
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
    </form>

    <ul class="navbar-nav ml-auto">

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <i class="fas fa-bell fa-fw"></i>

                <?php if ($notiCount > 0): ?>
                    <span class="badge badge-danger badge-counter">
                        <?= $notiCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                <h6 class="dropdown-header">Notifications</h6>

                <a class="dropdown-item text-center small text-gray-500" href="lyric_requests.php">
                    Xem nguồn thông báo
                </a>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <i class="fas fa-envelope fa-fw"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                <h6 class="dropdown-header">Message Center</h6>
                <a class="dropdown-item text-center small text-gray-500" href="#">
                    Chưa có tin nhắn
                </a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?= htmlspecialchars($adminName) ?>
                </span>
                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" alt="Admin profile">
            </a>

            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">

                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>

                <a class="dropdown-item" href="change_password.php">
                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                    Đổi mật khẩu
                </a>

                <div class="dropdown-divider"></div>

                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>
</nav>
