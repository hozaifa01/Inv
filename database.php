<?php
$host = 'localhost:3306';
$dbname = 'warehouse_management';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // إنشاء قاعدة البيانات إذا لم تكن موجودة
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // جدول الأصناف
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50),
        quantity INT DEFAULT 0,
        unit VARCHAR(20),
        min_quantity INT DEFAULT 10,
        expiry_date DATE,
        purchase_price DECIMAL(10,2),
        sale_price DECIMAL(10,2),
        supplier_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // جدول الموردين
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // جدول العملاء
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        type ENUM('wholesale', 'retail') DEFAULT 'retail',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // جدول حركة المخزون
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        transaction_type ENUM('in', 'out') NOT NULL,
        quantity INT NOT NULL,
        related_id INT, -- للإشارة إلى المورد أو العميل
        related_type ENUM('supplier', 'client') NOT NULL,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        FOREIGN KEY (item_id) REFERENCES items(id)
    )");
    
    // إضافة بعض البيانات الأولية
    $pdo->exec("INSERT IGNORE INTO suppliers (name, phone, email, address) VALUES
        ('مؤسسة الأغذية المتحدة', '0551234567', 'info@unitedfoods.com', 'الرياض، حي العليا'),
        ('شركة المنتجات الطازجة', '0549876543', 'sales@freshproducts.com', 'جدة، حي الصفا'),
        ('مصنع المعلبات الوطني', '0534567890', 'contact@cannery.com', 'الدمام، الصناعية الثانية')");
    
    $pdo->exec("INSERT IGNORE INTO clients (name, phone, type) VALUES
        ('سوق المركز الكبير', '0512345678', 'retail'),
        ('مطعم اللذيدة', '0523456789', 'wholesale'),
        ('فندق الشرق', '0534567890', 'wholesale')");
    
    $pdo->exec("INSERT IGNORE INTO items (name, category, quantity, unit, min_quantity, purchase_price, sale_price, supplier_id) VALUES
        ('أرز بسمتي', 'أرز', 100, 'كيس', 20, 35.00, 45.00, 1),
        ('سكر', 'مواد غذائية', 80, 'كيس', 15, 25.00, 32.00, 2),
        ('زيت زيتون', 'زيوت', 50, 'عبوة', 10, 40.00, 55.00, 3),
        ('دقيق',مواد غذائية', 120, 'كيس', 25, 20.00, 28.00, 1),
        ('معكرونة', 'معكرونة', 75, 'علبة', 15, 15.00, 22.00, 2)");
    
    echo "تم إنشاء قاعدة البيانات والجداول بنجاح!";
    
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>