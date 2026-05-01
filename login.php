<?php
require_once 'includes/header.php';

if (isLoggedIn()) {
    redirect('/WebBanHang/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = 'Vui lòng nhập email';
    if (empty($password)) $errors[] = 'Vui lòng nhập mật khẩu';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);

            setFlash('success', 'Đăng nhập thành công!');
            if ($user['role'] === 'admin') {
                redirect('/WebBanHang/admin/');
            }
            redirect('/WebBanHang/');
        } else {
            $errors[] = 'Email hoặc mật khẩu không đúng';
        }
    }
}
?>

<div class="auth-page">
    <h2><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập</h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error">
        <?= implode('<br>', $errors) ?>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm(this)">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Đăng nhập</button>
    </form>
    <div class="auth-link">
        Chưa có tài khoản? <a href="/WebBanHang/register.php">Đăng ký ngay</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
