<?php
require_once 'admin_header.php';

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Đã xóa danh mục');
    } catch (PDOException $e) {
        setFlash('error', 'Không thể xóa danh mục vì còn sản phẩm liên quan');
    }
    redirect('/WebBanHang/admin/categories.php');
}

// Xử lý thêm/sửa
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $editingId = (int)($_POST['id'] ?? 0);

    if (empty($name)) $errors[] = 'Tên danh mục không được để trống';

    if (empty($errors)) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, icon=? WHERE id=?");
            $stmt->execute([$name, $icon, $editingId]);
            setFlash('success', 'Cập nhật danh mục thành công');
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            setFlash('success', 'Thêm danh mục thành công');
        }
        redirect('/WebBanHang/admin/categories.php');
    }
}

// Lấy danh sách danh mục + số sản phẩm
$categories = $conn->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id")->fetchAll();
?>

<div class="admin-header">
    <h1>Quản lý danh mục</h1>
    <a href="/WebBanHang/admin/categories.php?edit=0" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm danh mục</a>
</div>

<?php if (isset($_GET['edit']) || !empty($errors)): ?>
<div class="admin-form" style="margin-bottom: 24px;">
    <h2><?= $editing ? 'Sửa danh mục' : 'Thêm danh mục mới' ?></h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Tên danh mục *</label>
            <input type="text" name="name" required value="<?= sanitize($_POST['name'] ?? ($editing['name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label>Icon (Font Awesome class)</label>
            <input type="text" name="icon" placeholder="vd: fa-mobile-screen" value="<?= sanitize($_POST['icon'] ?? ($editing['icon'] ?? '')) ?>">
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Cập nhật' : 'Thêm danh mục' ?></button>
            <a href="/WebBanHang/admin/categories.php" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Icon</th>
            <th>Tên danh mục</th>
            <th>Số sản phẩm</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><i class="fa-solid <?= sanitize($c['icon']) ?>" style="font-size:20px; color:var(--palette-accent);"></i></td>
            <td><?= sanitize($c['name']) ?></td>
            <td><?= $c['product_count'] ?></td>
            <td>
                <div class="actions">
                    <a href="/WebBanHang/admin/categories.php?edit=<?= $c['id'] ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Sửa</a>
                    <a href="/WebBanHang/admin/categories.php?delete=<?= $c['id'] ?>" class="btn-delete" onclick="return confirmDelete('Xóa danh mục này? Sẽ xóa tất cả sản phẩm trong danh mục.')"><i class="fa-solid fa-trash"></i> Xóa</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'admin_footer.php'; ?>
