<?php

// Format giá tiền VND
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Tính phần trăm giảm giá
function discountPercent($price, $sale_price) {
    if ($price <= 0 || $sale_price <= 0 || $sale_price >= $price) return 0;
    return round(($price - $sale_price) / $price * 100);
}

// Lấy số lượng sản phẩm trong giỏ hàng
function getCartCount() {
    if (!isset($_SESSION['cart'])) return 0;
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'];
}

function getAvatarUrl($avatar, $fallbackName = 'User') {
    $avatar = trim((string) $avatar);
    if ($avatar !== '') {
        if (preg_match('#^https?://#i', $avatar)) {
            return $avatar;
        }

        return '/WebBanHang/uploads/avatars/' . rawurlencode($avatar);
    }

    return 'https://ui-avatars.com/api/?name=' . urlencode($fallbackName) . '&background=010101&color=fff';
}

// Lọc input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Hiển thị thông báo flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}
