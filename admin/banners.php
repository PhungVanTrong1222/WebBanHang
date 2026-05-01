<?php
require_once 'admin_header.php';

// Xóa banner
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if ($banner && $banner['image']) {
        $imgPath = __DIR__ . '/../assets/images/' . $banner['image'];
        if (file_exists($imgPath)) @unlink($imgPath);
    }
    $conn->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    setFlash('success', 'Đã xóa banner');
    redirect('/WebBanHang/admin/banners.php');
}

// Toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    redirect('/WebBanHang/admin/banners.php');
}

// Load banner đang sửa
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

// Xử lý POST (thêm/sửa)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $buttonText  = trim($_POST['button_text'] ?? 'Xem ngay');
    $buttonLink  = trim($_POST['button_link'] ?? '#');
    $sortOrder   = (int)($_POST['sort_order'] ?? 0);
    $editingId   = (int)($_POST['id'] ?? 0);

    if (!$title) $errors[] = 'Tiêu đề không được để trống';

    // Upload ảnh
    $imageName = $_POST['current_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Chỉ chấp nhận file ảnh (jpg, png, webp, gif)';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ảnh không được vượt quá 5MB';
        } else {
            $newName = 'banner_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest    = __DIR__ . '/../assets/images/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Xóa ảnh cũ nếu là sửa
                if ($editingId && $_POST['current_image']) {
                    $old = __DIR__ . '/../assets/images/' . $_POST['current_image'];
                    if (file_exists($old) && !in_array($_POST['current_image'], ['banner_iphone.png','banner_sale.png','banner_macbook.png'])) {
                        @unlink($old);
                    }
                }
                $imageName = $newName;
            } else {
                $errors[] = 'Lỗi khi upload ảnh';
            }
        }
    }

    if (!$imageName && !$editingId) $errors[] = 'Vui lòng chọn ảnh banner';

    if (empty($errors)) {
        if ($editingId) {
            $stmt = $conn->prepare("UPDATE banners SET title=?, description=?, button_text=?, button_link=?, image=?, sort_order=? WHERE id=?");
            $stmt->execute([$title, $description, $buttonText, $buttonLink, $imageName, $sortOrder, $editingId]);
            setFlash('success', 'Đã cập nhật banner');
        } else {
            $stmt = $conn->prepare("INSERT INTO banners (title, description, button_text, button_link, image, sort_order) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $description, $buttonText, $buttonLink, $imageName, $sortOrder]);
            setFlash('success', 'Đã thêm banner mới');
        }
        redirect('/WebBanHang/admin/banners.php');
    }
}

// Lấy danh sách banner
$banners = $conn->query("SELECT * FROM banners ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<div class="admin-header">
    <h1><i class="fa-solid fa-images"></i> Quản lý Banner</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start;">

<!-- Danh sách banner -->
<div>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:80px">Ảnh</th>
                <th>Tiêu đề</th>
                <th style="width:60px">Thứ tự</th>
                <th style="width:80px">Trạng thái</th>
                <th style="width:120px">Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <tr>
                <td>
                    <img src="/WebBanHang/assets/images/<?= htmlspecialchars($b['image']) ?>"
                         style="width:70px;height:40px;object-fit:cover;border-radius:6px;background:#eee;"
                         onerror="this.style.background='#ddd';this.src=''">
                </td>
                <td>
                    <strong><?= htmlspecialchars($b['title']) ?></strong>
                    <div style="font-size:12px;color:#888;margin-top:2px"><?= htmlspecialchars(substr($b['description'],0,60)) ?>...</div>
                </td>
                <td style="text-align:center"><?= $b['sort_order'] ?></td>
                <td>
                    <a href="?toggle=<?= $b['id'] ?>" style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;<?= $b['is_active'] ? 'background:#e8f5e9;color:#2e7d32' : 'background:#ffebee;color:#c62828' ?>">
                        <i class="fa-solid fa-<?= $b['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                        <?= $b['is_active'] ? 'Hiện' : 'Ẩn' ?>
                    </a>
                </td>
                <td>
                    <div class="actions">
                        <a href="?edit=<?= $b['id'] ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Sửa</a>
                        <a href="?delete=<?= $b['id'] ?>" class="btn-delete" onclick="return confirm('Xóa banner này?')"><i class="fa-solid fa-trash"></i> Xóa</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($banners)): ?>
            <tr><td colspan="5" style="text-align:center;color:#999;padding:30px">Chưa có banner nào</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Form thêm/sửa -->
<div class="admin-form">
    <h2><?= $editing ? 'Sửa banner' : 'Thêm banner mới' ?></h2>

    <?php if ($errors): ?>
        <?php foreach ($errors as $e): ?>
            <div class="flash flash-error" style="margin-bottom:12px"><?= $e ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editing['image']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Tiêu đề *</label>
            <input type="text" name="title" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" placeholder="VD: iPhone 16 Pro Max" required>
        </div>

        <div class="form-group">
            <label>Mô tả ngắn</label>
            <textarea name="description" rows="2" placeholder="Mô tả hiển thị dưới tiêu đề..."><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Ảnh banner <?= $editing ? '(để trống = giữ ảnh cũ)' : '*' ?></label>
            <?php if ($editing && $editing['image']): ?>
                <div style="margin-bottom:8px">
                    <img src="/WebBanHang/assets/images/<?= htmlspecialchars($editing['image']) ?>"
                         style="width:100%;height:120px;object-fit:cover;border-radius:8px;background:#eee;">
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*" style="padding:8px">
            <div style="font-size:11px;color:#999;margin-top:4px">Khuyến nghị: 1200×400px, tối đa 5MB</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Nút bấm</label>
                <input type="text" name="button_text" value="<?= htmlspecialchars($editing['button_text'] ?? 'Xem ngay') ?>">
            </div>
            <div class="form-group">
                <label>Thứ tự</label>
                <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? 0 ?>" min="0">
            </div>
        </div>

        <div class="form-group">
            <label>Link nút bấm</label>
            <input type="text" name="button_link" value="<?= htmlspecialchars($editing['button_link'] ?? '#') ?>" placeholder="/WebBanHang/products.php">
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fa-solid fa-<?= $editing ? 'floppy-disk' : 'plus' ?>"></i>
                <?= $editing ? 'Cập nhật' : 'Thêm banner' ?>
            </button>
            <?php if ($editing): ?>
                <a href="/WebBanHang/admin/banners.php" class="btn btn-outline">Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

</div>

<?php require_once 'admin_footer.php'; ?>
