<?php
require_once 'includes/header.php';

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total = 0;
$cartProducts = [];

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $cartProducts = $stmt->fetchAll();
}
?>

<div class="breadcrumb">
    <a href="/WebBanHang/">Trang chủ</a>
    <span>/</span>
    <strong>Giỏ hàng</strong>
</div>

<div class="section-title">
    <h2><i class="fa-solid fa-cart-shopping"></i> Giỏ hàng của bạn</h2>
</div>

<div class="cart-page">
    <?php if (empty($cartProducts)): ?>
        <div class="cart-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Giỏ hàng của bạn đang trống</p>
            <a href="/WebBanHang/products.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartProducts as $p):
                    $qty = $cart[$p['id']]['quantity'];
                    $price = $p['sale_price'] > 0 ? $p['sale_price'] : $p['price'];
                    $subtotal = $price * $qty;
                    $total += $subtotal;
                ?>
                <tr>
                    <td>
                        <div class="cart-product">
                            <div class="cart-product-img">
                                <?php if ($p['image']): ?>
                                    <img src="/WebBanHang/assets/images/<?= $p['image'] ?>" alt="" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\'></i>'">
                                <?php else: ?>
                                    <i class="fa-solid fa-image"></i>
                                <?php endif; ?>
                            </div>
                            <a href="/WebBanHang/product_detail.php?id=<?= $p['id'] ?>"><?= sanitize($p['name']) ?></a>
                        </div>
                    </td>
                    <td><?= formatPrice($price) ?></td>
                    <td>
                        <div class="quantity-control">
                            <button type="button" onclick="updateCartQuantity(<?= $p['id'] ?>, <?= $qty - 1 ?>)">-</button>
                            <input type="number" value="<?= $qty ?>" min="1" onchange="updateCartQuantity(<?= $p['id'] ?>, this.value)">
                            <button type="button" onclick="updateCartQuantity(<?= $p['id'] ?>, <?= $qty + 1 ?>)">+</button>
                        </div>
                    </td>
                    <td style="color: var(--palette-accent); font-weight: 600;"><?= formatPrice($subtotal) ?></td>
                    <td>
                        <button type="button" class="remove-btn" onclick="removeFromCart(<?= $p['id'] ?>)">
                            <i class="fa-solid fa-trash"></i> Xóa
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="cart-total">
                Tổng thanh toán: <span><?= formatPrice($total) ?></span>
            </div>
            <a href="/WebBanHang/checkout.php" class="btn btn-primary btn-lg">Đặt hàng</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
