<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('/WebBanHang/products.php');
}

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Sản phẩm không tồn tại');
    redirect('/WebBanHang/products.php');
}

// Sản phẩm liên quan
$relatedStmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 5");
$relatedStmt->execute([$product['category_id'], $id]);
$relatedProducts = $relatedStmt->fetchAll();

$discount = discountPercent($product['price'], $product['sale_price']);
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="/WebBanHang/">Trang chủ</a>
    <span>/</span>
    <a href="/WebBanHang/products.php?category=<?= $product['category_id'] ?>"><?= sanitize($product['category_name']) ?></a>
    <span>/</span>
    <strong><?= sanitize($product['name']) ?></strong>
</div>

<div class="product-detail">
    <div class="detail-image">
        <?php if ($product['image']): ?>
            <img src="/WebBanHang/assets/images/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
        <?php else: ?>
            <i class="fa-solid fa-image"></i>
        <?php endif; ?>
    </div>

    <div class="detail-info">
        <h1><?= sanitize($product['name']) ?></h1>

        <div class="detail-price">
            <span class="price-sale"><?= formatPrice($product['sale_price'] ?: $product['price']) ?></span>
            <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                <span class="price-original"><?= formatPrice($product['price']) ?></span>
                <span class="discount-tag">-<?= $discount ?>% GIẢM</span>
            <?php endif; ?>
        </div>

        <div class="detail-meta">
            <p><i class="fa-solid fa-tag"></i> Danh mục: <strong><?= sanitize($product['category_name']) ?></strong></p>
            <p><i class="fa-solid fa-box"></i> Kho: <strong><?= $product['stock'] > 0 ? $product['stock'] . ' sản phẩm' : 'Hết hàng' ?></strong></p>
        </div>

        <?php if ($product['stock'] > 0): ?>
        <div>
            <label style="font-size:14px; font-weight:500; color:#555;">Số lượng:</label>
            <div class="quantity-control">
                <button type="button" onclick="changeQuantity(this.nextElementSibling, -1)">-</button>
                <input type="number" id="qty" value="1" min="1" max="<?= $product['stock'] ?>">
                <button type="button" onclick="changeQuantity(this.previousElementSibling, 1)">+</button>
            </div>
        </div>

        <div class="detail-actions">
            <button class="btn btn-primary btn-lg" onclick="addToCart(<?= $product['id'] ?>, document.getElementById('qty').value)">
                <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
        </div>
        <?php else: ?>
        <div class="detail-actions">
            <button class="btn btn-lg" disabled style="background:#ccc;color:#666;cursor:not-allowed;">Hết hàng</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mô tả -->
    <div class="detail-description">
        <h3>Mô tả sản phẩm</h3>
        <p><?= nl2br(sanitize($product['description'] ?: 'Chưa có mô tả cho sản phẩm này.')) ?></p>
    </div>
</div>

<!-- Sản phẩm liên quan -->
<?php if (!empty($relatedProducts)): ?>
<div class="section-title">
    <h2><i class="fa-solid fa-layer-group"></i> Sản phẩm liên quan</h2>
</div>
<div class="product-grid">
    <?php foreach ($relatedProducts as $p): ?>
    <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>" class="product-card">
        <?php $d = discountPercent($p['price'], $p['sale_price']); ?>
        <?php if ($d > 0): ?>
            <span class="discount-badge">-<?= $d ?>%</span>
        <?php endif; ?>
        <div class="product-img">
            <?php if ($p['image']): ?>
                <img src="/WebBanHang/assets/images/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
            <?php else: ?>
                <i class="fa-solid fa-image"></i>
            <?php endif; ?>
        </div>
        <div class="product-info">
            <div class="product-name"><?= sanitize($p['name']) ?></div>
            <div class="product-price">
                <span class="price-sale"><?= formatPrice($p['sale_price'] ?: $p['price']) ?></span>
                <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                    <span class="price-original"><?= formatPrice($p['price']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
