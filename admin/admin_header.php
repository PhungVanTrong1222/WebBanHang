<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Kiểm tra quyền admin
if (!isAdmin()) {
    redirect('/WebBanHang/login.php');
}

$flash = getFlash();

// Xác định trang hiện tại
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - TechShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/WebBanHang/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <i class="fa-solid fa-bolt"></i> TechShop
        </div>
        <nav>
            <a href="/WebBanHang/admin/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="/WebBanHang/admin/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i> Sản phẩm
            </a>
            <a href="/WebBanHang/admin/categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group"></i> Danh mục
            </a>
            <a href="/WebBanHang/admin/brands.php" class="<?= $currentPage === 'brands.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i> Nhãn hàng
            </a>
            <a href="/WebBanHang/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Đơn hàng
            </a>
            <a href="/WebBanHang/admin/banners.php" class="<?= $currentPage === 'banners.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-images"></i> Banner
            </a>
            <a href="/WebBanHang/" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <i class="fa-solid fa-store"></i> Về trang chủ
            </a>
            <a href="/WebBanHang/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
    </aside>
    <div class="admin-content">
        <?php if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>">
            <?= $flash['message'] ?>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
