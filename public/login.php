<?php
include('includes/session.php');
include('includes/database.php');
require_once __DIR__ . '/../config/auth_helpers.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (nln_verify_password($password, $user['password_hash'])) {
            if (nln_password_needs_rehash($user['password_hash'])) {
                nln_upgrade_password_hash($conn, (int) $user['user_id'], $password);
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit;
        }
    }

    $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
}
?>

<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

<header class="masthead auth-hero" style="background-image: url('assets/img/login-bg.jpg')">
    <div class="auth-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center auth-heading-shell">
            <span class="auth-kicker">Welcome Back</span>
            <h1>Đăng nhập</h1>
            <span class="subheading auth-subheading">
                Tiếp tục khám phá lời nhạc, gợi ý cá nhân và các nội dung bạn đã lưu trên NLN Lyrics.
            </span>
        </div>
    </div>
</header>

<main class="auth-page py-5">
    <div class="container px-4 px-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <section class="auth-shell">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-lg-5">
                            <div class="auth-aside">
                                <span class="auth-panel-kicker">Account Access</span>
                                <h2 class="auth-panel-title">Chào mừng bạn quay trở lại</h2>
                                <p class="auth-panel-copy">
                                    Đăng nhập để tiếp tục theo dõi nghệ sĩ, xem gợi ý dành riêng cho bạn và truy cập nhanh vào những album yêu thích.
                                </p>

                                <div class="auth-feature-list">
                                    <div class="auth-feature-item">
                                        <i class="fas fa-headphones-alt"></i>
                                        <div>
                                            <strong>Nghe theo gu riêng</strong>
                                            <span>Nhận các gợi ý bài hát và album gần hơn với lịch sử tương tác của bạn.</span>
                                        </div>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="fas fa-heart"></i>
                                        <div>
                                            <strong>Lưu nội dung yêu thích</strong>
                                            <span>Quản lý album đã thích và quay lại những bài hát bạn quan tâm gần đây.</span>
                                        </div>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="fas fa-comments"></i>
                                        <div>
                                            <strong>Tương tác cộng đồng</strong>
                                            <span>Bình luận, phản hồi và theo dõi nghệ sĩ bạn yêu thích trên hệ thống.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="auth-form-panel">
                                <div class="auth-form-head">
                                    <span class="auth-form-kicker">Sign In</span>
                                    <h2>Truy cập tài khoản</h2>
                                    <p>Nhập thông tin đăng nhập để tiếp tục sử dụng hệ thống.</p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="auth-alert is-error" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" class="auth-form" novalidate>
                                    <div class="auth-field">
                                        <label for="username" class="auth-label">
                                            <i class="fas fa-user"></i>
                                            <span>Tên đăng nhập</span>
                                        </label>
                                        <input
                                            id="username"
                                            type="text"
                                            name="username"
                                            class="form-control auth-input"
                                            required
                                            autocomplete="username"
                                            placeholder="Nhập tên đăng nhập"
                                        >
                                    </div>

                                    <div class="auth-field">
                                        <label for="password" class="auth-label">
                                            <i class="fas fa-lock"></i>
                                            <span>Mật khẩu</span>
                                        </label>
                                        <input
                                            id="password"
                                            type="password"
                                            name="password"
                                            class="form-control auth-input"
                                            required
                                            autocomplete="current-password"
                                            placeholder="Nhập mật khẩu"
                                        >
                                    </div>

                                    <button type="submit" class="btn btn-primary auth-submit-btn">
                                        Đăng nhập
                                    </button>
                                </form>

                                <p class="auth-helper-text">
                                    Chưa có tài khoản?
                                    <a href="signup.php">Đăng ký ngay</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
.auth-page,
.auth-page * {
    box-sizing: border-box;
}

.auth-page {
    background:
        radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 28%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.auth-hero {
    overflow: visible;
    padding-bottom: 5.5rem;
}

.auth-hero-layer {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(15, 23, 42, .78) 0%, rgba(15, 23, 42, .46) 100%),
        radial-gradient(circle at top left, rgba(37, 99, 235, .22), transparent 30%);
}

.auth-heading-shell {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0 auto;
}

.auth-kicker,
.auth-panel-kicker,
.auth-form-kicker {
    display: inline-flex;
    padding: .4rem .78rem;
    border-radius: 999px;
    font-family: "Open Sans", sans-serif;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.auth-kicker {
    margin-bottom: .8rem;
    color: #fff;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .16);
}

.auth-subheading {
    display: block;
    max-width: 680px;
    margin: .8rem auto 0;
    font-size: 1.03rem;
    line-height: 1.75;
}

.auth-shell {
    margin-top: -3.1rem;
    position: relative;
    z-index: 2;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: 30px;
    background: rgba(255, 255, 255, .98);
    box-shadow: 0 28px 70px rgba(15, 23, 42, .12);
}

.auth-aside {
    height: 100%;
    padding: 2rem 1.8rem;
    background:
        radial-gradient(circle at top left, rgba(37, 99, 235, .16), transparent 34%),
        linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
    border-right: 1px solid rgba(148, 163, 184, .14);
}

.auth-panel-kicker,
.auth-form-kicker {
    margin-bottom: .75rem;
    color: #2563eb;
    background: rgba(37, 99, 235, .08);
}

.auth-panel-title,
.auth-form-head h2 {
    margin: 0 0 .75rem;
    color: #162033;
    font-size: 1.9rem;
    line-height: 1.2;
    font-weight: 800;
}

.auth-panel-copy,
.auth-form-head p,
.auth-feature-item span,
.auth-helper-text {
    color: #64748b;
    font-family: "Open Sans", sans-serif;
    font-size: .96rem;
    line-height: 1.7;
}

.auth-feature-list {
    display: grid;
    gap: .95rem;
    margin-top: 1.4rem;
}

.auth-feature-item {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: .85rem;
    align-items: start;
    padding: 1rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, .78);
    border: 1px solid rgba(148, 163, 184, .14);
    box-shadow: 0 12px 28px rgba(15, 23, 42, .05);
}

.auth-feature-item i {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(37, 99, 235, .1);
    color: #2563eb;
}

.auth-feature-item strong {
    display: block;
    margin-bottom: .18rem;
    color: #162033;
    font-size: .98rem;
    line-height: 1.4;
}

.auth-form-panel {
    height: 100%;
    padding: 2rem 1.8rem;
}

.auth-form-head {
    margin-bottom: 1.3rem;
}

.auth-alert {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    margin-bottom: 1rem;
    padding: .9rem 1rem;
    border-radius: 18px;
    font-family: "Open Sans", sans-serif;
    font-size: .92rem;
    line-height: 1.6;
}

.auth-alert i {
    margin-top: .12rem;
}

.auth-alert.is-error {
    border: 1px solid rgba(239, 68, 68, .16);
    background: rgba(254, 242, 242, .96);
    color: #b91c1c;
}

.auth-form {
    display: grid;
    gap: 1rem;
}

.auth-field {
    display: grid;
    gap: .48rem;
}

.auth-label {
    display: inline-flex;
    align-items: center;
    gap: .58rem;
    margin: 0;
    color: #334155;
    font-size: .92rem;
    font-weight: 700;
}

.auth-label i {
    width: 18px;
    color: #64748b;
    text-align: center;
}

.auth-input {
    min-height: 52px;
    border: 1px solid rgba(148, 163, 184, .2);
    border-radius: 18px;
    background: #f8fbff;
    box-shadow: none;
    font-size: .96rem;
}

.auth-input:focus {
    border-color: rgba(37, 99, 235, .34);
    background: #fff;
    box-shadow: 0 0 0 .18rem rgba(37, 99, 235, .08);
}

.auth-submit-btn {
    width: 100%;
    min-height: 52px;
    margin-top: .25rem;
    border-radius: 18px;
    font-size: .98rem;
    font-weight: 800;
    letter-spacing: .01em;
    box-shadow: 0 16px 30px rgba(37, 99, 235, .22);
}

.auth-helper-text {
    margin: 1rem 0 0;
}

.auth-helper-text a {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.auth-helper-text a:hover {
    text-decoration: underline;
}

@media (max-width: 991.98px) {
    .auth-shell {
        margin-top: -2.4rem;
    }

    .auth-aside {
        border-right: 0;
        border-bottom: 1px solid rgba(148, 163, 184, .14);
    }
}

@media (max-width: 767.98px) {
    .auth-hero {
        padding-bottom: 4.8rem;
    }

    .auth-shell {
        border-radius: 24px;
    }

    .auth-aside,
    .auth-form-panel {
        padding: 1.35rem;
    }

    .auth-panel-title,
    .auth-form-head h2 {
        font-size: 1.55rem;
    }
}
</style>

<?php include('includes/footer.php'); ?>
