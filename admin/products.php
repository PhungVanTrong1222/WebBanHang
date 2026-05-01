<?php
require_once 'admin_header.php';

// Xử lý AJAX thêm brand mới
if (isset($_POST['ajax_add_brand'])) {
    header('Content-Type: application/json');
    $brandName = trim($_POST['brand_name'] ?? '');
    if ($brandName) {
        $check = $conn->prepare("SELECT id FROM brands WHERE name = ?");
        $check->execute([$brandName]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Nhãn hàng đã tồn tại']);
        } else {
            $stmt = $conn->prepare("INSERT INTO brands (name, icon) VALUES (?, 'fa-solid fa-tag')");
            $stmt->execute([$brandName]);
            echo json_encode(['success' => true, 'name' => $brandName, 'id' => $conn->lastInsertId()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên không được để trống']);
    }
    exit;
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Đã xóa sản phẩm');
    redirect('/WebBanHang/admin/products.php');
}

// Xử lý thêm/sửa
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_add_brand'])) {
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $salePrice = (float)($_POST['sale_price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $editingId = (int)($_POST['id'] ?? 0);

    // Xử lý upload ảnh
    $image = '';
    if ($editingId > 0 && $editing) {
        $image = $editing['image'] ?? '';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['image']['name']));
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $image = $fileName;
        }
    }

    if (empty($name)) $errors[] = 'Tên sản phẩm không được để trống';
    if ($categoryId <= 0) $errors[] = 'Chọn danh mục';
    if ($price <= 0) $errors[] = 'Giá phải lớn hơn 0';

    if (empty($errors)) {
        if ($editingId > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, brand=?, price=?, sale_price=?, stock=?, description=?, image=? WHERE id=?");
            $stmt->execute([$name, $categoryId, $brand, $price, $salePrice, $stock, $description, $image, $editingId]);
            setFlash('success', 'Cập nhật sản phẩm thành công');
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, category_id, brand, price, sale_price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $categoryId, $brand, $price, $salePrice, $stock, $description, $image]);
            setFlash('success', 'Thêm sản phẩm thành công');
        }
        redirect('/WebBanHang/admin/products.php');
    }
}

// Lấy danh sách danh mục và nhãn hàng cho dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brands = $conn->query("SELECT * FROM brands ORDER BY name")->fetchAll();

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
?>

<div class="admin-header">
    <h1><?= $editing ? 'Sửa sản phẩm' : 'Quản lý sản phẩm' ?></h1>
    <?php if (!$editing): ?>
    <a href="/WebBanHang/admin/products.php?edit=0" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['edit']) || !empty($errors)): ?>
<!-- Form thêm/sửa -->
<div class="admin-form" style="margin-bottom: 24px;">
    <h2><?= $editing ? 'Sửa sản phẩm: ' . sanitize($editing['name']) : 'Thêm sản phẩm mới' ?></h2>

    <?php if (!empty($errors)): ?>
    <div class="flash flash-error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Tên sản phẩm *</label>
            <input type="text" name="name" required value="<?= sanitize($_POST['name'] ?? ($editing['name'] ?? '')) ?>">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Danh mục *</label>
                <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($editing['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= sanitize($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nhãn hàng</label>
                <div style="display:flex; gap:8px;">
                    <select name="brand" id="brandSelect" style="flex:1;">
                        <option value="">-- Chọn nhãn hàng --</option>
                        <?php foreach ($brands as $b): ?>
                        <option value="<?= sanitize($b['name']) ?>" <?= ($editing['brand'] ?? '') === $b['name'] ? 'selected' : '' ?>>
                            <?= sanitize($b['name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Thêm nhãn hàng mới</option>
                    </select>
                </div>
                <!-- Popup thêm brand mới -->
                <div id="newBrandBox" style="display:none; margin-top:8px; padding:12px; background:var(--palette-surface); border-radius:8px; border:1px solid var(--palette-border);">
                    <label style="font-size:13px; font-weight:600;">Tên nhãn hàng mới:</label>
                    <div style="display:flex; gap:8px; margin-top:6px;">
                        <input type="text" id="newBrandName" placeholder="Nhập tên nhãn hàng" style="flex:1;">
                        <button type="button" id="saveBrandBtn" class="btn btn-primary" style="padding:8px 16px; font-size:13px;">Lưu</button>
                        <button type="button" id="cancelBrandBtn" class="btn btn-outline" style="padding:8px 12px; font-size:13px;">Hủy</button>
                    </div>
                    <div id="brandMsg" style="margin-top:6px; font-size:12px;"></div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Giá gốc (VNĐ) *</label>
                <input type="number" name="price" required min="0" value="<?= $_POST['price'] ?? ($editing['price'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Giá khuyến mãi (VNĐ)</label>
                <input type="number" name="sale_price" min="0" value="<?= $_POST['sale_price'] ?? ($editing['sale_price'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Tồn kho</label>
            <input type="number" name="stock" min="0" value="<?= $_POST['stock'] ?? ($editing['stock'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label>Hình ảnh</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($editing && $editing['image']): ?>
                <div style="margin-top: 10px;">
                    <img src="/WebBanHang/assets/images/<?= $editing['image'] ?>" alt="Ảnh hiện tại" style="max-height: 100px;">
                    <p><small>Ảnh hiện tại. Nếu không muốn đổi ảnh, vui lòng để trống.</small></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description" rows="4"><?= sanitize($_POST['description'] ?? ($editing['description'] ?? '')) ?></textarea>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Cập nhật' : 'Thêm sản phẩm' ?></button>
            <a href="/WebBanHang/admin/products.php" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Danh sách sản phẩm -->
<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Sản phẩm</th>
            <th>Danh mục</th>
            <th>Nhãn hàng</th>
            <th>Giá</th>
            <th>Giá KM</th>
            <th>Kho</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= sanitize($p['name']) ?></td>
            <td><?= sanitize($p['category_name']) ?></td>
            <td><?= sanitize($p['brand'] ?? '') ?></td>
            <td><?= formatPrice($p['price']) ?></td>
            <td><?= $p['sale_price'] > 0 ? formatPrice($p['sale_price']) : '-' ?></td>
            <td><?= $p['stock'] ?></td>
            <td>
                <div class="actions">
                    <a href="/WebBanHang/admin/products.php?edit=<?= $p['id'] ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Sửa</a>
                    <a href="/WebBanHang/admin/products.php?delete=<?= $p['id'] ?>" class="btn-delete" onclick="return confirmDelete('Xóa sản phẩm này?')"><i class="fa-solid fa-trash"></i> Xóa</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
(function() {
    var brandSelect = document.getElementById('brandSelect');
    var newBrandBox = document.getElementById('newBrandBox');
    var newBrandName = document.getElementById('newBrandName');
    var saveBrandBtn = document.getElementById('saveBrandBtn');
    var cancelBrandBtn = document.getElementById('cancelBrandBtn');
    var brandMsg = document.getElementById('brandMsg');

    if (!brandSelect) return;

    brandSelect.addEventListener('change', function() {
        if (this.value === '__new__') {
            newBrandBox.style.display = 'block';
            newBrandName.focus();
            this.value = '';
        } else {
            newBrandBox.style.display = 'none';
        }
    });

    cancelBrandBtn.addEventListener('click', function() {
        newBrandBox.style.display = 'none';
        newBrandName.value = '';
        brandMsg.textContent = '';
    });

    saveBrandBtn.addEventListener('click', function() {
        var name = newBrandName.value.trim();
        if (!name) {
            brandMsg.innerHTML = '<span style="color:red;">Vui lòng nhập tên nhãn hàng</span>';
            return;
        }
        saveBrandBtn.disabled = true;
        saveBrandBtn.textContent = 'Đang lưu...';

        var formData = new FormData();
        formData.append('ajax_add_brand', '1');
        formData.append('brand_name', name);

        fetch('/WebBanHang/admin/products.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Thêm option mới vào dropdown
                var opt = document.createElement('option');
                opt.value = data.name;
                opt.textContent = data.name;
                opt.selected = true;
                // Chèn trước option "+ Thêm nhãn hàng mới"
                var lastOpt = brandSelect.querySelector('option[value="__new__"]');
                brandSelect.insertBefore(opt, lastOpt);
                // Ẩn box
                newBrandBox.style.display = 'none';
                newBrandName.value = '';
                brandMsg.textContent = '';
            } else {
                brandMsg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
            }
            saveBrandBtn.disabled = false;
            saveBrandBtn.textContent = 'Lưu';
        })
        .catch(function() {
            brandMsg.innerHTML = '<span style="color:red;">Lỗi kết nối</span>';
            saveBrandBtn.disabled = false;
            saveBrandBtn.textContent = 'Lưu';
        });
    });

    newBrandName.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); saveBrandBtn.click(); }
    });
})();
</script>

<?php require_once 'admin_footer.php'; ?>
