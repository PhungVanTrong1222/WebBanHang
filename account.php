<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('/WebBanHang/login.php');
}

// Lấy thông tin user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/WebBanHang/logout.php');
}

$errors = [];
$success = false;

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($fullname)) {
            $errors[] = 'Họ tên không được để trống';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$fullname, $phone, $address, $_SESSION['user_id']]);
            $_SESSION['user_name'] = $fullname;

            // Reload user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            setFlash('success', 'Cập nhật thông tin thành công!');
            redirect('/WebBanHang/account.php');
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu mới phải ít nhất 6 ký tự';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }

        if (empty($errors)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);

            setFlash('success', 'Đổi mật khẩu thành công!');
            redirect('/WebBanHang/account.php');
        }
    }

    if ($action === 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WEBP';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Ảnh không được vượt quá 2MB';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $uploadPath = __DIR__ . '/uploads/avatars/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Xóa avatar cũ nếu có
                    if (!empty($user['avatar'])) {
                        $oldPath = __DIR__ . '/uploads/avatars/' . $user['avatar'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }

                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$filename, $_SESSION['user_id']]);
                    $_SESSION['user_avatar'] = $filename;

                    setFlash('success', 'Cập nhật ảnh đại diện thành công!');
                    redirect('/WebBanHang/account.php');
                } else {
                    $errors[] = 'Upload thất bại, vui lòng thử lại';
                }
            }
        } else {
            $errors[] = 'Vui lòng chọn ảnh';
        }
    }
}

// Lấy lịch sử đơn hàng
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$orderStmt->execute([$_SESSION['user_id']]);
$orders = $orderStmt->fetchAll();

$statusMap = [
    'pending' => ['Chờ xử lý', 'badge-pending'],
    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
    'shipping' => ['Đang giao', 'badge-shipping'],
    'completed' => ['Hoàn thành', 'badge-completed'],
    'cancelled' => ['Đã hủy', 'badge-cancelled'],
];
?>

<div class="breadcrumb">
    <a href="/WebBanHang/">Trang chủ</a>
    <span>/</span>
    <strong>Tài khoản của tôi</strong>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-error">
    <?= implode('<br>', $errors) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<div class="account-page">
    <!-- Sidebar -->
    <div class="account-sidebar">
        <div class="account-avatar">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= sanitize(getAvatarUrl($user['avatar'], $user['fullname'])) ?>" alt="Avatar" id="avatar-preview">
            <?php else: ?>
                <div class="avatar-placeholder" id="avatar-preview">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="avatar-form">
                <input type="hidden" name="action" value="upload_avatar">
                <label class="avatar-upload-btn" for="avatar-input">
                    <i class="fa-solid fa-camera"></i> Đổi ảnh
                </label>
                <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none" onchange="this.form.submit()">
            </form>
        </div>
        <div class="account-name"><?= sanitize($user['fullname']) ?></div>
        <div class="account-email"><?= sanitize($user['email']) ?></div>
        <div class="account-since"><i class="fa-solid fa-calendar"></i> Tham gia: <?= date('d/m/Y', strtotime($user['created_at'])) ?></div>

        <nav class="account-nav">
            <a href="#profile" class="active" onclick="showTab('profile', this)"><i class="fa-solid fa-user-pen"></i> Thông tin cá nhân</a>
            <a href="#password" onclick="showTab('password', this)"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</a>
            <a href="#orders" onclick="showTab('orders', this)"><i class="fa-solid fa-receipt"></i> Đơn hàng (<?= count($orders) ?>)</a>
            <?php if ($user['role'] === 'admin'): ?>
            <a href="/WebBanHang/admin/" style="color: var(--palette-hover);"><i class="fa-solid fa-user-shield"></i> Trang quản trị</a>
            <?php endif; ?>
            <a href="/WebBanHang/logout.php" style="margin-top: 10px; border-top: 1px dashed var(--palette-border); color: #e53e3e;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</a>
        </nav>
    </div>

    <!-- Content -->
    <div class="account-content">
        <!-- Tab: Thông tin cá nhân -->
        <div class="account-tab" id="tab-profile">
            <h2><i class="fa-solid fa-user-pen"></i> Thông tin cá nhân</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="fullname" required value="<?= sanitize($user['fullname']) ?>">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= sanitize($user['email']) ?>" disabled style="background:#f5f5f5;">
                    <div style="font-size:12px; color:#999; margin-top:4px;">Email không thể thay đổi</div>
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Địa chỉ</label>
                    <div class="location-input js-location-input">
                        <textarea name="address" class="js-address-textarea"><?= sanitize($user['address'] ?? '') ?></textarea>
                        <input type="hidden" class="js-location-lat" value="">
                        <input type="hidden" class="js-location-lng" value="">
                        <div class="location-tools">
                            <button type="button" class="btn btn-secondary btn-location js-get-current-location">
                                <i class="fa-solid fa-location-crosshairs"></i> Dùng vị trí hiện tại
                            </button>
                            <a href="#" class="location-map-link js-open-map" target="_blank" rel="noopener" hidden>
                                <i class="fa-solid fa-map-location-dot"></i> Xem vị trí đã chọn
                            </a>
                        </div>
                        <div class="location-coordinates js-location-coordinates">Bấm nút để mở bản đồ, ghim sẽ đặt sẵn ở vị trí hiện tại để bạn xác nhận hoặc kéo sang vị trí khác.</div>
                        <div class="location-status js-location-status">Có thể dùng GPS hoặc kéo ghim trực tiếp trên bản đồ trước khi xác nhận.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu thay đổi</button>
            </form>
        </div>

        <!-- Tab: Đổi mật khẩu -->
        <div class="account-tab" id="tab-password" style="display:none;">
            <h2><i class="fa-solid fa-lock"></i> Đổi mật khẩu</h2>
            <form method="POST" style="max-width:400px;">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label>Mật khẩu hiện tại *</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới * (ít nhất 6 ký tự)</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label>Xác nhận mật khẩu mới *</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Đổi mật khẩu</button>
            </form>
        </div>

        <!-- Tab: Đơn hàng -->
        <div class="account-tab" id="tab-orders" style="display:none;">
            <h2><i class="fa-solid fa-receipt"></i> Lịch sử đơn hàng</h2>
            <?php if (empty($orders)): ?>
                <div class="cart-empty">
                    <i class="fa-solid fa-receipt"></i>
                    <p>Bạn chưa có đơn hàng nào</p>
                    <a href="/WebBanHang/products.php" class="btn btn-primary">Mua sắm ngay</a>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr class="order-row" onclick="openTimeline(<?= htmlspecialchars(json_encode([
                            'id' => $o['id'],
                            'status' => $o['status'],
                            'created_at' => date('d M, H:i', strtotime($o['created_at']))
                        ])) ?>)">
                            <td>#<?= $o['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td style="color: var(--palette-accent); font-weight: 600;"><?= formatPrice($o['total_amount']) ?></td>
                            <td>
                                <?php $s = $statusMap[$o['status']] ?? ['Unknown', 'badge-pending']; ?>
                                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(tabName, el) {
    // Ẩn tất cả tab
    document.querySelectorAll('.account-tab').forEach(function(tab) {
        tab.style.display = 'none';
    });
    // Bỏ active tất cả nav
    document.querySelectorAll('.account-nav a').forEach(function(a) {
        a.classList.remove('active');
    });
    // Hiện tab được chọn
    document.getElementById('tab-' + tabName).style.display = 'block';
    el.classList.add('active');
    if (window.location.hash !== '#' + tabName) {
        history.replaceState(null, '', '#' + tabName);
    }
}

(function initAccountTabFromHash() {
    var hash = (window.location.hash || '').replace('#', '');
    if (!hash) return;

    var navLink = document.querySelector('.account-nav a[href="#' + hash + '"]');
    if (navLink) {
        showTab(hash, navLink);
    }
})();

function openTimeline(order) {
    const modal = document.getElementById('timelineModal');
    modal.style.display = 'flex';
    
    // Đặt thời gian khởi tạo
    document.getElementById('time-pending').innerText = order.created_at;
    document.getElementById('time-confirmed').innerText = '';
    document.getElementById('time-shipping').innerText = '';
    document.getElementById('time-completed').innerText = '';

    // Trạng thái chung
    const badge = document.getElementById('tl-badge');
    if (order.status === 'completed') {
        badge.innerText = 'Hoàn thành';
        badge.className = 'timeline-badge completed';
    } else if (order.status === 'shipping') {
        badge.innerText = 'Đang giao';
        badge.className = 'timeline-badge progress';
    } else if (order.status === 'confirmed') {
        badge.innerText = 'Đã xác nhận';
        badge.className = 'timeline-badge progress';
    } else if (order.status === 'cancelled') {
        badge.innerText = 'Đã hủy';
        badge.className = 'timeline-badge cancelled';
    } else {
        badge.innerText = 'Chờ xử lý';
        badge.className = 'timeline-badge progress';
    }

    // Reset các step
    const steps = ['pending', 'confirmed', 'shipping', 'completed'];
    steps.forEach(s => {
        document.getElementById('step-' + s).classList.remove('active');
    });
    document.querySelectorAll('.tl-line').forEach(l => l.classList.remove('active'));

    // Bật active theo trạng thái
    let activeIndex = 0;
    if (order.status === 'pending') activeIndex = 0;
    if (order.status === 'confirmed') activeIndex = 1;
    if (order.status === 'shipping') activeIndex = 2;
    if (order.status === 'completed') activeIndex = 3;

    if (order.status !== 'cancelled') {
        for (let i = 0; i <= activeIndex; i++) {
            document.getElementById('step-' + steps[i]).classList.add('active');
            if (i > 0) document.getElementById('line-' + i).classList.add('active');
            // Cập nhật tạm thời gian cho các bước đã qua
            document.getElementById('time-' + steps[i]).innerText = order.created_at;
        }
    }
}

function closeTimeline(e) {
    if (e.target.id === 'timelineModal') {
        document.getElementById('timelineModal').style.display = 'none';
    }
}
</script>

<!-- Timeline Modal -->
<div class="timeline-overlay" id="timelineModal" style="display:none;" onclick="closeTimeline(event)">
    <div class="timeline-modal">
        <div class="timeline-header">
            <span class="timeline-title">Tiến trình giao hàng</span>
            <span class="timeline-badge" id="tl-badge">Đang xử lý</span>
        </div>
        
        <div class="timeline-steps">
            <!-- Step 1 -->
            <div class="tl-step" id="step-pending">
                <div class="tl-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="tl-content">
                    <div class="tl-title">Chờ xử lý</div>
                    <div class="tl-desc">Đơn hàng đang chờ xác nhận</div>
                </div>
                <div class="tl-time" id="time-pending"></div>
            </div>
            
            <div class="tl-line" id="line-1"></div>
            
            <!-- Step 2 -->
            <div class="tl-step" id="step-confirmed">
                <div class="tl-icon"><i class="fa-solid fa-box"></i></div>
                <div class="tl-content">
                    <div class="tl-title">Đã xác nhận</div>
                    <div class="tl-desc">Đơn hàng đã được xác nhận</div>
                </div>
                <div class="tl-time" id="time-confirmed"></div>
            </div>
            
            <div class="tl-line" id="line-2"></div>
            
            <!-- Step 3 -->
            <div class="tl-step" id="step-shipping">
                <div class="tl-icon"><i class="fa-solid fa-truck"></i></div>
                <div class="tl-content">
                    <div class="tl-title">Đang giao hàng</div>
                    <div class="tl-desc">Đơn hàng đang trên đường giao</div>
                </div>
                <div class="tl-time" id="time-shipping"></div>
            </div>
            
            <div class="tl-line" id="line-3"></div>
            
            <!-- Step 4 -->
            <div class="tl-step" id="step-completed">
                <div class="tl-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="tl-content">
                    <div class="tl-title">Hoàn thành</div>
                    <div class="tl-desc">Đã giao hàng thành công</div>
                </div>
                <div class="tl-time" id="time-completed"></div>
            </div>
        </div>
        
        <button class="rate-delivery-btn"><i class="fa-solid fa-hand-holding-heart"></i> Đánh giá đơn hàng</button>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
