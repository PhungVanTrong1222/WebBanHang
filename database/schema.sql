-- Tạo database
CREATE DATABASE IF NOT EXISTS webbanhang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webbanhang;

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    address TEXT,
    avatar VARCHAR(255) DEFAULT '',
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng danh mục
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng nhãn hàng
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng quảng cáo (banners)
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    button_text VARCHAR(100),
    button_link VARCHAR(255),
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng sản phẩm
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT '',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(15,0) NOT NULL,
    sale_price DECIMAL(15,0) DEFAULT 0,
    image VARCHAR(255) DEFAULT '',
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    total_amount DECIMAL(15,0) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    note TEXT,
    payment_method VARCHAR(50) DEFAULT 'cod',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bảng chi tiết đơn hàng
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(15,0) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==================== DỮ LIỆU MẪU ====================

-- Tài khoản admin (password: admin123)
INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin', 'admin@webbanhang.vn', '0901234567', '$2y$10$3KvtLFboHZ8WQpnxL4jrkOns9JJUmR.rvCjaoncothxhW1f9FLTDe', 'admin');

-- Tài khoản khách hàng mẫu (password: 123456)
INSERT INTO users (fullname, email, phone, password, address, role) VALUES
('Nguyễn Văn A', 'nguyenvana@gmail.com', '0912345678', '$2y$10$/9HwC/0fim0KJ5r60a/7CeuG9/iUS2pLdCcFw5y9wq0bIZGAGDwJ6', '123 Nguyễn Huệ, Q1, TP.HCM', 'customer');


-- Danh mục sản phẩm
INSERT INTO categories (name, icon) VALUES
('Điện thoại', 'fa-mobile-screen'),
('Laptop', 'fa-laptop'),
('Máy tính bảng', 'fa-tablet-screen-button'),
('Phụ kiện', 'fa-headphones'),
('Đồng hồ thông minh', 'fa-clock'),
('Âm thanh', 'fa-volume-high');

-- Nhãn hàng mẫu
INSERT INTO brands (name, icon) VALUES
('Apple', 'fa-brands fa-apple'),
('Samsung', 'fa-solid fa-mobile-screen'),
('Xiaomi', 'fa-solid fa-mobile'),
('OPPO', 'fa-solid fa-camera'),
('Dell', 'fa-solid fa-laptop'),
('ASUS', 'fa-solid fa-laptop-code'),
('Lenovo', 'fa-solid fa-desktop'),
('Logitech', 'fa-solid fa-computer-mouse'),
('Sony', 'fa-solid fa-tv'),
('JBL', 'fa-solid fa-volume-high');

-- Banner mẫu
INSERT INTO banners (title, description, button_text, button_link, image, sort_order, is_active) VALUES
('iPhone 16 Pro Max', 'Chip A18 Pro, Camera 48MP, Titanium Design. Giá chỉ từ 32.990.000đ', 'Mua ngay', '/WebBanHang/products.php?category=1', 'banner_iphone.png', 1, 1),
('Siêu Sale Công Nghệ', 'Giảm đến 50% cho tất cả sản phẩm. Miễn phí vận chuyển toàn quốc!', 'Khám phá ngay', '/WebBanHang/products.php', 'banner_sale.png', 2, 1),
('MacBook Air M3', 'Siêu mỏng. Siêu nhanh. Siêu nhẹ. Giá chỉ từ 35.490.000đ', 'Tìm hiểu thêm', '/WebBanHang/products.php?category=2', 'banner_macbook.png', 3, 1);

-- Sản phẩm mẫu
INSERT INTO products (category_id, brand, name, description, price, sale_price, image, stock) VALUES
-- Điện thoại
(1, 'Apple', 'iPhone 15 Pro Max 256GB', 'Chip A17 Pro, Camera 48MP, Titanium Design, USB-C', 34990000, 32990000, 'iphone15promax.jpg', 50),
(1, 'Samsung', 'Samsung Galaxy S24 Ultra', 'Chip Snapdragon 8 Gen 3, Camera 200MP, S Pen, AI Features', 31990000, 29490000, 'galaxys24ultra.jpg', 40),
(1, 'Xiaomi', 'Xiaomi 14 Ultra', 'Chip Snapdragon 8 Gen 3, Camera Leica 50MP, Sạc nhanh 90W', 23990000, 21990000, 'xiaomi14ultra.jpg', 35),
(1, 'OPPO', 'OPPO Find X7 Ultra', 'Chip Dimensity 9300, Camera Hasselblad, Pin 5400mAh', 22990000, 19990000, 'oppofindx7.jpg', 30),

-- Laptop
(2, 'Apple', 'MacBook Air M3 15 inch', 'Chip Apple M3, RAM 8GB, SSD 256GB, Màn hình Liquid Retina', 37990000, 35490000, 'macbookairm3.jpg', 25),
(2, 'Dell', 'Dell XPS 14 2024', 'Intel Core Ultra 7, RAM 16GB, SSD 512GB, OLED 14.5 inch', 42990000, 39990000, 'dellxps14.jpg', 20),
(2, 'ASUS', 'ASUS ROG Zephyrus G16', 'Intel Core i9, RTX 4070, RAM 16GB, 240Hz OLED', 45990000, 42990000, 'rogzephyrus.jpg', 15),
(2, 'Lenovo', 'Lenovo ThinkPad X1 Carbon Gen 12', 'Intel Core Ultra 7, RAM 16GB, SSD 512GB, 2.8K OLED', 38990000, 35990000, 'thinkpadx1.jpg', 20),

-- Máy tính bảng
(3, 'Apple', 'iPad Pro M4 11 inch', 'Chip Apple M4, Màn hình OLED Tandem, WiFi 6E', 28990000, 27490000, 'ipadprom4.jpg', 30),
(3, 'Samsung', 'Samsung Galaxy Tab S9 Ultra', 'Chip Snapdragon 8 Gen 2, Màn hình 14.6 inch, S Pen', 27990000, 24990000, 'tabs9ultra.jpg', 25),

-- Phụ kiện
(4, 'Apple', 'AirPods Pro 2 USB-C', 'Chống ồn chủ động, Âm thanh không gian, Chip H2', 6790000, 5990000, 'airpodspro2.jpg', 100),
(4, 'Samsung', 'Sạc nhanh Samsung 45W', 'Sạc siêu nhanh 45W, Cổng USB-C, Compact Design', 990000, 790000, 'samsung45w.jpg', 200),
(4, 'Logitech', 'Chuột Logitech MX Master 3S', 'Cảm biến 8000 DPI, Kết nối 3 thiết bị, Sạc USB-C', 2490000, 2190000, 'mxmaster3s.jpg', 80),

-- Đồng hồ thông minh
(5, 'Apple', 'Apple Watch Ultra 2', 'Chip S9, GPS + Cellular, Titanium, Chống nước 100m', 21990000, 19990000, 'applewatchultra2.jpg', 25),
(5, 'Samsung', 'Samsung Galaxy Watch 6 Classic', 'Chip Exynos W930, Vòng bezel xoay, Sapphire Crystal', 9990000, 8490000, 'galaxywatch6.jpg', 40),

-- Âm thanh
(6, 'Sony', 'Sony WH-1000XM5', 'Chống ồn hàng đầu, LDAC, 30 giờ pin, Multipoint', 8490000, 7490000, 'sonywh1000xm5.jpg', 50),
(6, 'JBL', 'JBL Charge 5', 'Loa Bluetooth, Chống nước IP67, Pin 20 giờ, Powerbank', 3990000, 3290000, 'jblcharge5.jpg', 60);
