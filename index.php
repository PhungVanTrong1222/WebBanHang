<?php
require_once 'includes/header.php';

// Lấy banner từ database
$banners = $conn->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
if (!$banners) $banners = [];

// Lấy sản phẩm giảm giá trên 30%
$featuredProducts = $conn->query("SELECT * FROM products WHERE sale_price > 0 AND sale_price < price AND ((price - sale_price) / price) * 100 > 30 ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Lấy sản phẩm bán chạy (stock thấp = bán nhiều)
$bestSellers = $conn->query("SELECT * FROM products ORDER BY stock ASC, created_at DESC LIMIT 10")->fetchAll();

// Lấy sản phẩm mới nhất
$newProducts = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Lấy nhãn hàng từ database
$brands = $conn->query("SELECT b.name as brand, b.icon, COUNT(p.id) as total FROM brands b LEFT JOIN products p ON b.name = p.brand GROUP BY b.id ORDER BY total DESC")->fetchAll();
?>

<!-- Hero Banners: Slider + 2 Side Banners -->
<div class="hero-banners">
    <?php if (!empty($banners)): ?>
    <div class="hero-slider" id="heroSlider">
        <div class="slides" id="heroSlides">
            <?php foreach ($banners as $b): ?>
            <div class="slide">
                <img src="/WebBanHang/assets/images/<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
                <div class="slide-content">
                    <h2><?= htmlspecialchars($b['title']) ?></h2>
                    <p><?= htmlspecialchars($b['description']) ?></p>
                    <a href="<?= htmlspecialchars($b['button_link']) ?>" class="btn btn-primary"><?= htmlspecialchars($b['button_text']) ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="slider-arrow prev" onclick="heroSliderPrev()"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="slider-arrow next" onclick="heroSliderNext()"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="slider-dots" id="sliderDots">
            <?php foreach ($banners as $i => $b): ?>
            <button class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="hero-side-banners">
        <a href="/WebBanHang/products.php?sale=1" class="side-banner side-banner-1">
            <div class="side-banner-content">
                <span class="side-banner-tag">Giảm đến 20%</span>
                <h4>Phụ kiện công nghệ</h4>
                <span class="side-banner-link">Mua ngay →</span>
            </div>
            <div class="side-banner-icon"><i class="fa-solid fa-headphones"></i></div>
        </a>
        <a href="/WebBanHang/products.php?category=2" class="side-banner side-banner-2">
            <div class="side-banner-content">
                <span class="side-banner-tag">Hàng mới về</span>
                <h4>Laptop cao cấp</h4>
                <span class="side-banner-link">Khám phá →</span>
            </div>
            <div class="side-banner-icon"><i class="fa-solid fa-laptop"></i></div>
        </a>
    </div>
</div>

<!-- Nhãn hàng nổi bật -->
<div class="section-title">
    <h2><i class="fa-solid fa-tags"></i> Nhãn hàng nổi bật</h2>
    <a href="/WebBanHang/products.php">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
</div>
<div class="brand-grid">
    <?php foreach ($brands as $b):
        $icon = $b['icon'] ?: 'fa-solid fa-tag';
    ?>
    <a href="/WebBanHang/products.php?brand=<?= urlencode($b['brand']) ?>" class="brand-card">
        <div class="brand-icon"><i class="<?= $icon ?>"></i></div>
        <span class="brand-name"><?= sanitize($b['brand']) ?></span>
        <span class="brand-count"><?= $b['total'] ?> sản phẩm</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Giảm giá sốc -->
<?php if (!empty($featuredProducts)): ?>
<div class="section-title">
    <h2><i class="fa-solid fa-fire"></i> Giảm giá sốc</h2>
    <a href="/WebBanHang/products.php?sale=1">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
</div>
<div class="product-grid">
    <?php foreach ($featuredProducts as $p): ?>
    <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>" class="product-card">
        <?php $discount = discountPercent($p['price'], $p['sale_price']); ?>
        <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
        <div class="product-img">
            <?php if ($p['image']): ?>
                <img src="/WebBanHang/assets/images/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
            <?php else: ?><i class="fa-solid fa-image"></i><?php endif; ?>
        </div>
        <div class="product-info">
            <div class="product-name"><?= sanitize($p['name']) ?></div>
            <div class="product-price">
                <span class="price-sale"><?= formatPrice($p['sale_price']) ?></span>
                <span class="price-original"><?= formatPrice($p['price']) ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Sản phẩm bán chạy -->
<div class="section-title">
    <h2><i class="fa-solid fa-trophy"></i> Sản phẩm bán chạy</h2>
    <a href="/WebBanHang/products.php">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
</div>
<div class="product-grid">
    <?php foreach ($bestSellers as $p): ?>
    <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>" class="product-card">
        <?php $discount = discountPercent($p['price'], $p['sale_price']); ?>
        <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
        <div class="product-img">
            <?php if ($p['image']): ?>
                <img src="/WebBanHang/assets/images/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
            <?php else: ?><i class="fa-solid fa-image"></i><?php endif; ?>
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

<!-- Sản phẩm mới -->
<div class="section-title">
    <h2><i class="fa-solid fa-star"></i> Sản phẩm mới</h2>
    <a href="/WebBanHang/products.php">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
</div>
<div class="product-grid">
    <?php foreach ($newProducts as $p): ?>
    <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>" class="product-card">
        <?php $discount = discountPercent($p['price'], $p['sale_price']); ?>
        <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
        <div class="product-img">
            <?php if ($p['image']): ?>
                <img src="/WebBanHang/assets/images/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
            <?php else: ?><i class="fa-solid fa-image"></i><?php endif; ?>
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

<!-- Features Bar -->
<div class="features-bar">
    <div class="feature-item">
        <div class="feature-icon"><i class="fa-solid fa-store"></i></div>
        <div class="feature-text"><h4>Nhận tại cửa hàng</h4><p>Miễn phí, nhanh chóng</p></div>
    </div>
    <div class="feature-item">
        <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
        <div class="feature-text"><h4>Giao hàng miễn phí</h4><p>Đơn từ 500.000₫</p></div>
    </div>
    <div class="feature-item">
        <div class="feature-icon"><i class="fa-solid fa-credit-card"></i></div>
        <div class="feature-text"><h4>Thanh toán linh hoạt</h4><p>COD, chuyển khoản, thẻ</p></div>
    </div>
    <div class="feature-item">
        <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
        <div class="feature-text"><h4>Hỗ trợ 24/7</h4><p>Luôn sẵn sàng giúp bạn</p></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>
(function() {
    var slides = document.getElementById('heroSlides');
    if (!slides) return;
    var current = 0, total = slides.children.length, timer = null;
    function go(n) {
        current = (n + total) % total;
        slides.style.transform = 'translateX(-' + (current * 100) + '%)';
        var dots = document.querySelectorAll('#sliderDots .dot');
        for (var i = 0; i < dots.length; i++) dots[i].classList.toggle('active', i === current);
    }
    function start() { clearInterval(timer); timer = setInterval(function() { go(current + 1); }, 3000); }
    window.heroSliderNext = function() { go(current + 1); start(); };
    window.heroSliderPrev = function() { go(current - 1); start(); };
    window.goToSlide = function(i) { go(i); start(); };
    start();
})();
</script>
