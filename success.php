<?php
require_once 'includes/header.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    redirect('/WebBanHang/');
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/WebBanHang/');
}
?>

<!-- Nội dung trang phía sau (bị mờ) -->
<div class="success-bg-content">
    <div style="text-align:center; padding: 80px 20px; color: var(--palette-muted);">
        <i class="fa-solid fa-bag-shopping" style="font-size:60px; margin-bottom:20px; color: var(--palette-border);"></i>
        <p style="font-size:18px;">TechShop - Siêu thị điện tử</p>
    </div>
</div>

<!-- Modal Overlay -->
<div class="success-overlay" id="successModal">
    <div class="success-modal">

        <!-- Nút đóng -->
        <button class="modal-close" onclick="window.location.href='/WebBanHang/'">&times;</button>

        <!-- Icon check -->
        <div class="success-icon-wrap">
            <div class="success-icon-ring"></div>
            <div class="success-icon-circle">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <h2 class="success-heading">Đơn hàng của bạn<br>đã được đặt thành công!</h2>

        <!-- Chi tiết đơn hàng -->
        <div class="success-info">
            <div class="success-info-row">
                <span class="info-label">Mã đơn hàng</span>
                <span class="info-value">#<?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="success-info-row">
                <span class="info-label">Phương thức thanh toán</span>
                <span class="info-value"><?= sanitize($order['payment_method']) ?></span>
            </div>
            <div class="success-info-row">
                <span class="info-label">Ngày & Giờ</span>
                <span class="info-value"><?= date('d/m/y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="success-info-row last">
                <span class="info-label">Tổng cộng</span>
                <span class="info-value highlight"><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>

        <!-- Nút CTA -->
        <a href="/WebBanHang/account.php#orders" class="success-btn">
            Đến đơn hàng của tôi
        </a>
        <a href="/WebBanHang/" class="success-btn-secondary">
            Tiếp tục mua sắm
        </a>
    </div>
</div>

<style>
/* ===== SUCCESS MODAL OVERLAY ===== */
body {
    overflow: hidden; /* Khóa scroll khi modal mở */
}

.success-bg-content {
    filter: blur(4px);
    pointer-events: none;
    user-select: none;
}

.success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(20, 33, 61, 0.55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.success-modal {
    background: #fff;
    border-radius: 28px;
    padding: 48px 40px 36px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    position: relative;
    box-shadow: 0 32px 80px rgba(0,0,0,0.2);
    animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideUp {
    from { transform: translateY(40px) scale(0.95); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}

/* Nút đóng X */
.modal-close {
    position: absolute;
    top: 16px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    line-height: 1;
    transition: color 0.2s;
    padding: 4px 8px;
}
.modal-close:hover { color: #333; }

/* Icon thành công */
.success-icon-wrap {
    position: relative;
    width: 88px;
    height: 88px;
    margin: 0 auto 28px;
}

.success-icon-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: #d8f5e1;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.7; }
    50%       { transform: scale(1.12); opacity: 0.3; }
}

.success-icon-circle {
    position: absolute;
    inset: 8px;
    background: #4caf50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 28px;
    box-shadow: 0 8px 24px rgba(76,175,80,0.4);
}

/* Tiêu đề */
.success-heading {
    font-size: 20px;
    font-weight: 800;
    color: #14213D;
    line-height: 1.45;
    margin-bottom: 28px;
}

/* Bảng thông tin */
.success-info {
    background: #f7fafc;
    border-radius: 16px;
    padding: 4px 20px;
    margin-bottom: 28px;
    text-align: left;
}

.success-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #edf2f7;
}

.success-info-row.last {
    border-bottom: none;
}

.info-label {
    font-size: 13px;
    color: #718096;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #14213D;
}

.info-value.highlight {
    font-size: 16px;
    font-weight: 800;
    color: var(--palette-accent, #FCA311);
}

/* Nút CTA */
.success-btn {
    display: block;
    background: #14213D;
    color: #fff;
    padding: 16px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    transition: background 0.2s, transform 0.1s;
    margin-bottom: 12px;
    letter-spacing: 0.2px;
}

.success-btn:hover {
    background: #1e3060;
    transform: translateY(-1px);
    color: #fff;
}

.success-btn-secondary {
    display: block;
    background: transparent;
    color: #718096;
    padding: 10px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}

.success-btn-secondary:hover {
    color: #14213D;
}
</style>

<?php require_once 'includes/footer.php'; ?>
