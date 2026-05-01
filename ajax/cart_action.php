<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }
        // Kiểm tra sản phẩm tồn tại
        $stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = ['quantity' => $quantity];
        }

        // Giới hạn không vượt quá tồn kho
        if ($_SESSION['cart'][$productId]['quantity'] > $product['stock']) {
            $_SESSION['cart'][$productId]['quantity'] = $product['stock'];
        }

        echo json_encode([
            'success' => true,
            'cart_count' => getCartCount(),
            'message' => 'Đã thêm vào giỏ hàng'
        ]);
        break;

    case 'update':
        if ($productId > 0 && $quantity > 0) {
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            }
        }
        echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        break;

    case 'remove':
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        }
        echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
