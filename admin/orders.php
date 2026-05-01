<?php
require_once 'admin_header.php';

// Xử lý cập nhật trạng thái
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        setFlash('success', 'Cập nhật trạng thái đơn hàng #' . $orderId . ' thành công');
        redirect('/WebBanHang/admin/orders.php');
    }
}

// Xem chi tiết đơn hàng
$viewOrder = null;
$orderDetails = [];
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT o.*, u.fullname as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $detailStmt = $conn->prepare("SELECT od.*, p.name as product_name, p.image FROM order_details od LEFT JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
        $detailStmt->execute([$viewId]);
        $orderDetails = $detailStmt->fetchAll();
    }
}

// Lấy danh sách đơn hàng
$orders = $conn->query("SELECT o.*, u.fullname as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();

$statusMap = [
    'pending' => ['Chờ xử lý', 'badge-pending'],
    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
    'shipping' => ['Đang giao', 'badge-shipping'],
    'completed' => ['Hoàn thành', 'badge-completed'],
    'cancelled' => ['Đã hủy', 'badge-cancelled'],
];
?>

<div class="admin-header">
    <h1>Quản lý đơn hàng</h1>
</div>

<?php if ($viewOrder): ?>
<!-- Chi tiết đơn hàng -->
<div class="admin-form" style="max-width:800px; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Đơn hàng #<?= $viewOrder['id'] ?></h2>
        <a href="/WebBanHang/admin/orders.php" class="btn btn-outline btn-sm">Quay lại</a>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
        <div>
            <p><strong>Khách hàng:</strong> <?= sanitize($viewOrder['fullname']) ?></p>
            <p><strong>Email:</strong> <?= sanitize($viewOrder['email']) ?></p>
            <p><strong>SĐT:</strong> <?= sanitize($viewOrder['phone']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= sanitize($viewOrder['address']) ?></p>
        </div>
        <div>
            <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($viewOrder['created_at'])) ?></p>
            <p><strong>Trạng thái:</strong>
                <?php $s = $statusMap[$viewOrder['status']] ?? ['Unknown', 'badge-pending']; ?>
                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
            </p>
            <p><strong>Ghi chú:</strong> <?= sanitize($viewOrder['note'] ?: 'Không có') ?></p>

            <!-- Cập nhật trạng thái -->
            <form method="POST" style="margin-top:10px; display:flex; gap:8px;">
                <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                <select name="status" style="padding:6px 10px; border:1px solid #ddd; border-radius:4px; font-size:13px;">
                    <option value="pending" <?= $viewOrder['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                    <option value="confirmed" <?= $viewOrder['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="shipping" <?= $viewOrder['status'] === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                    <option value="completed" <?= $viewOrder['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="cancelled" <?= $viewOrder['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Cập nhật</button>
            </form>
        </div>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderDetails as $d): ?>
            <tr>
                <td><?= sanitize($d['product_name']) ?></td>
                <td><?= formatPrice($d['price']) ?></td>
                <td><?= $d['quantity'] ?></td>
                <td style="color:var(--palette-accent); font-weight:600;"><?= formatPrice($d['price'] * $d['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:600;">Tổng cộng:</td>
                <td style="color:var(--palette-accent); font-weight:700; font-size:16px;"><?= formatPrice($viewOrder['total_amount']) ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>

<!-- Danh sách đơn hàng -->
<table class="admin-table">
    <thead>
        <tr>
            <th>Mã ĐH</th>
            <th>Khách hàng</th>
            <th>SĐT</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="7" style="text-align:center; color:#999; padding:30px;">Chưa có đơn hàng nào</td></tr>
        <?php else: ?>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= sanitize($o['fullname']) ?></td>
            <td><?= sanitize($o['phone']) ?></td>
            <td style="color:var(--palette-accent); font-weight:600;"><?= formatPrice($o['total_amount']) ?></td>
            <td>
                <?php $s = $statusMap[$o['status']] ?? ['Unknown', 'badge-pending']; ?>
                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
            <td>
                <div class="actions">
                    <a href="/WebBanHang/admin/orders.php?view=<?= $o['id'] ?>" class="btn-edit"><i class="fa-solid fa-eye"></i> Xem</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'admin_footer.php'; ?>
