<?php
include('includes/database.php');
include('includes/header.php');
include('includes/navbar.php');
require_once __DIR__ . '/../config/auth_helpers.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if ($password !== $confirm) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại.";
        } else {
            $hash = nln_hash_password($password);

            $insert = $conn->prepare("
                INSERT INTO users (username, email, password_hash, role, created_at)
                VALUES (?, ?, ?, 'user', NOW())
            ");
            $insert->bind_param("sss", $username, $email, $hash);

            if ($insert->execute()) {
                $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay.";
            } else {
                $error = "Đã xảy ra lỗi khi tạo tài khoản.";
            }
        }
    }
}
?>

<header class="masthead auth-hero" style="background-image: url('assets/img/login-bg.jpg')">
    <div class="auth-hero-layer"></div>
    <div class="container position-relative px-4 px-lg-5">
        <div class="site-heading text-center auth-heading-shell">
            <span class="auth-kicker">Create Account</span>
            <h1>Đăng ký</h1>
            <span class="subheading auth-subheading">
                Tạo tài khoản để lưu album yêu thích, nhận gợi ý cá nhân và tham gia tương tác trên Musicalisation.
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
                                <span class="auth-panel-kicker">New Member</span>
                                <h2 class="auth-panel-title">Bắt đầu hành trình của bạn</h2>
                                <p class="auth-panel-copy">
                                    Chỉ với một tài khoản, bạn có thể lưu dấu những gì mình thích và tạo trải nghiệm nghe nhạc cá nhân hơn trên hệ thống.
                                </p>

                                <div class="auth-feature-list">
                                    <div class="auth-feature-item">
                                        <i class="fas fa-compact-disc"></i>
                                        <div>
                                            <strong>Lưu album yêu thích</strong>
                                            <span>Đánh dấu nhanh những album bạn muốn nghe lại bất cứ lúc nào.</span>
                                        </div>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="fas fa-magic"></i>
                                        <div>
                                            <strong>Nhận gợi ý cá nhân</strong>
                                            <span>Hệ thống sẽ hiểu gu nghe của bạn tốt hơn qua hành vi tìm kiếm và tương tác.</span>
                                        </div>
                                    </div>
                                    <div class="auth-feature-item">
                                        <i class="fas fa-user-friends"></i>
                                        <div>
                                            <strong>Theo dõi nghệ sĩ</strong>
                                            <span>Cập nhật nhanh những nghệ sĩ bạn quan tâm và các nội dung liên quan.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="auth-form-panel">
                                <div class="auth-form-head">
                                    <span class="auth-form-kicker">Sign Up</span>
                                    <h2>Tạo tài khoản mới</h2>
                                    <p>Điền đầy đủ thông tin bên dưới để bắt đầu sử dụng hệ thống.</p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="auth-alert is-error" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="auth-alert is-success" role="status">
                                        <i class="fas fa-check-circle"></i>
                                        <span>
                                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                                            <a href="login.php">Đăng nhập ngay</a>
                                        </span>
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
                                        <label for="email" class="auth-label">
                                            <i class="fas fa-envelope"></i>
                                            <span>Email</span>
                                        </label>
                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            class="form-control auth-input"
                                            required
                                            autocomplete="email"
                                            placeholder="Nhập email"
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
                                            autocomplete="new-password"
                                            placeholder="Tạo mật khẩu"
                                        >
                                    </div>

                                    <div class="auth-field">
                                        <label for="confirm" class="auth-label">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>Xác nhận mật khẩu</span>
                                        </label>
                                        <input
                                            id="confirm"
                                            type="password"
                                            name="confirm"
                                            class="form-control auth-input"
                                            required
                                            autocomplete="new-password"
                                            placeholder="Nhập lại mật khẩu"
                                        >
                                    </div>

                                    <button type="submit" class="btn btn-success auth-submit-btn auth-submit-btn-success">
                                        Tạo tài khoản
                                    </button>
                                </form>

                                <p class="auth-helper-text">
                                    Đã có tài khoản?
                                    <a href="login.php">Đăng nhập ngay</a>
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

.auth-alert.is-success {
    border: 1px solid rgba(34, 197, 94, .18);
    background: rgba(240, 253, 244, .96);
    color: #15803d;
}

.auth-alert.is-success a {
    color: #166534;
    font-weight: 700;
    text-decoration: none;
}

.auth-alert.is-success a:hover {
    text-decoration: underline;
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
}

.auth-submit-btn-success {
    box-shadow: 0 16px 30px rgba(34, 197, 94, .2);
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
