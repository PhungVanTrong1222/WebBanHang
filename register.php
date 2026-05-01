<?php
require_once 'includes/header.php';

if (isLoggedIn()) {
    redirect('/WebBanHang/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($fullname)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải ít nhất 6 ký tự';
    if ($password !== $confirm) $errors[] = 'Mật khẩu xác nhận không khớp';

    // Kiểm tra email trùng
    if (empty($errors)) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'Email này đã được đăng ký';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $email, $phone, $hashedPassword]);

        setFlash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        redirect('/WebBanHang/login.php');
    }
}
?>

<div class="auth-page">
    <h2><i class="fa-solid fa-user-plus"></i> Đăng ký tài khoản</h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error">
        <?= implode('<br>', $errors) ?>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm(this)">
        <div class="form-group">
            <label>Họ và tên</label>
            <input type="text" name="fullname" required value="<?= sanitize($_POST['fullname'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="tel" name="phone" value="<?= sanitize($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Mật khẩu (ít nhất 6 ký tự)</label>
            <input type="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label>Xác nhận mật khẩu</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Đăng ký</button>
    </form>
    <div class="auth-link">
        Đã có tài khoản? <a href="/WebBanHang/login.php">Đăng nhập</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
