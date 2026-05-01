<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
$flash = getFlash();

// Lấy avatar user nếu đã đăng nhập
$currentUserAvatar = '';
if (isLoggedIn()) {
    $avatarStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $avatarStmt->execute([$_SESSION['user_id']]);
    $avatarRow = $avatarStmt->fetch();
    if ($avatarRow) {
        $currentUserAvatar = $avatarRow['avatar'] ?? '';
    }
}

// Lấy danh mục cho menu
$stmtCat = $conn->query("SELECT * FROM categories ORDER BY name");
$menuCategories = $stmtCat->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechShop - Siêu thị điện tử</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/WebBanHang/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container header-main">
        <a href="/WebBanHang/" class="logo">
            Tech<span>Shop</span>
        </a>

        <form class="search-bar" action="/WebBanHang/products.php" method="GET">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="search" placeholder="Search" value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>">
        </form>

        <div class="header-actions">
            <a href="/WebBanHang/cart.php" class="action-item">
                <div class="icon-wrapper">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if($cartCount > 0): ?><span class="badge"><?= $cartCount ?></span><?php endif; ?>
                </div>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="/WebBanHang/account.php" class="user-avatar">
                    <?php if (!empty($currentUserAvatar)): ?>
                        <img src="/WebBanHang/uploads/avatars/<?= sanitize($currentUserAvatar) ?>" alt="User">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=010101&color=fff" alt="User">
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="/WebBanHang/login.php" class="user-avatar empty-avatar">
                    <i class="fa-regular fa-user"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container header-nav">
        <ul class="nav-list">
            <li><a href="/WebBanHang/">Home</a></li>
            <?php foreach ($menuCategories as $cat): ?>
                <li>
                    <a href="/WebBanHang/products.php?category=<?= $cat['id'] ?>">
                        <?= sanitize($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li><a href="/WebBanHang/products.php?sale=1" class="sale-link">Sale</a></li>
        </ul>
    </div>
</header>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="container">
    <div class="flash flash-<?= $flash['type'] ?>">
        <?= $flash['message'] ?>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
</div>
<?php endif; ?>

<main class="main-content">
    <div class="container">
