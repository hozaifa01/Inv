<?php
session_start();

$host = 'localhost:3306';
$dbname = 'warehouse_management';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
$pdo->exec("set names utf8mb4");
// دالة لإعادة تنسيق التاريخ
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

// دالة للحصول على إحصائيات سريعة
function getStats($pdo) {
    $stats = [];
    
    // عدد الأصناف
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
    $stats['total_items'] = $stmt->fetch()['count'];
    
    // عدد الموردين
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM suppliers");
    $stats['total_suppliers'] = $stmt->fetch()['count'];
    
    // عدد العملاء
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $stats['total_clients'] = $stmt->fetch()['count'];
    
    // الأصناف المنتهية الصلاحية
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items WHERE expiry_date < CURDATE()");
    $stats['expired_items'] = $stmt->fetch()['count'];
    
    return $stats;
}
?>