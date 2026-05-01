<?php
require_once 'includes/header.php';

// Lọc theo danh mục
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$saleFilter = isset($_GET['sale']) ? trim($_GET['sale']) : '';
$brandFilter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Xây dựng query
$where = [];
$params = [];

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($brandFilter !== '') {
    $where[] = "p.brand = ?";
    $params[] = $brandFilter;
}

if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if ($saleFilter === 'shock') {
    $where[] = "p.sale_price > 0";
    $where[] = "p.price > 0";
    $where[] = "((p.price - p.sale_price) / p.price) * 100 > 30";
}

// Lấy nhãn hàng từ DB
$allBrands = $conn->query("SELECT b.name as brand, b.icon, COUNT(p.id) as total FROM brands b LEFT JOIN products p ON b.name = p.brand GROUP BY b.id ORDER BY total DESC")->fetchAll();

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Đếm tổng
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Lấy sản phẩm
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        $whereSQL ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Lấy tên danh mục hiện tại
$currentCategory = null;
if ($categoryId > 0) {
    $catStmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $catStmt->execute([$categoryId]);
    $currentCategory = $catStmt->fetch();
}
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="/WebBanHang/">Trang chủ</a>
    <span>/</span>
    <?php if ($brandFilter): ?>
        <strong>Nhãn hàng: <?= sanitize($brandFilter) ?></strong>
    <?php elseif ($currentCategory): ?>
        <strong><?= sanitize($currentCategory['name']) ?></strong>
    <?php elseif ($saleFilter === 'shock'): ?>
        <strong>Giảm giá sốc trên 30%</strong>
    <?php elseif ($search): ?>
        <strong>Kết quả tìm kiếm: "<?= sanitize($search) ?>"</strong>
    <?php else: ?>
        <strong>Tất cả sản phẩm</strong>
    <?php endif; ?>
</div>

<!-- Nhãn hàng filter -->
<div class="category-grid" style="margin-bottom: 20px;">
    <a href="/WebBanHang/products.php" class="category-card" <?= !$brandFilter && !$categoryId && !$saleFilter ? 'style="border-color:var(--palette-accent)"' : '' ?>>
        <i class="fa-solid fa-border-all"></i>
        <span>Tất cả</span>
    </a>
    <?php foreach ($allBrands as $b): ?>
    <a href="/WebBanHang/products.php?brand=<?= urlencode($b['brand']) ?>" class="category-card" <?= $brandFilter === $b['brand'] ? 'style="border-color:var(--palette-accent)"' : '' ?>>
        <i class="<?= $b['icon'] ?: 'fa-solid fa-tag' ?>"></i>
        <span><?= sanitize($b['brand']) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2><?= $total ?> sản phẩm</h2>
</div>

<?php if (empty($products)): ?>
    <div class="cart-empty">
        <i class="fa-solid fa-box-open"></i>
        <p>Không tìm thấy sản phẩm nào</p>
        <a href="/WebBanHang/products.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>" class="product-card">
            <?php $discount = discountPercent($p['price'], $p['sale_price']); ?>
            <?php if ($discount > 0): ?>
                <span class="discount-badge">-<?= $discount ?>%</span>
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

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $queryParams = $_GET;
        for ($i = 1; $i <= $totalPages; $i++):
            $queryParams['page'] = $i;
            $qs = http_build_query($queryParams);
        ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="/WebBanHang/products.php?<?= $qs ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
