<?php
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

// الاتصال بقاعدة البيانات
$con = new mysqli("localhost:3306", "root", "root", "warehouse_management");
if ($con->connect_error) {
    error_log("Database connection failed: ". $con->connect_error);
    die("حدث خطأ في الاتصال بقاعدة البيانات.");
}
// جلب معلومات الموقع
$site = [];
$site_info = $con->prepare("SELECT * FROM site LIMIT 1");
if ($site_info && $site_info->execute()) {
    $result = $site_info->get_result();
    $site = $result->fetch_assoc();
    $site_info->close();
}

// إدارة الجلسة والنشاط
$timeout = 1800; // 30 دقيقة
$current_time = time();
$timeout_time = $current_time - $timeout;

if (isset($_SESSION['aid']) && filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
    $user_id = (int) $_SESSION['aid'];

    // التحقق من عدم النشاط
    if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'])> $timeout) {
        // تحديث حالة المستخدم إلى غير متصل
        $stmt = $con->prepare("UPDATE tbl_login SET is_online = 0 WHERE id =?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        session_unset();
        session_destroy();
        header('Location: logout.php');
        exit;
} else {
        // تحديث آخر نشاط وحالة المستخدم
        $_SESSION['last_activity'] = $current_time;
        $stmt = $con->prepare("UPDATE tbl_login SET is_online = 1, last_activity =? WHERE id =?");
        $stmt->bind_param("ii", $current_time, $user_id);
        $stmt->execute();
        $stmt->close();
}

    // تحديث حالة المستخدمين غير النشطين
    $stmt = $con->prepare("UPDATE tbl_login SET is_online = 0 WHERE last_activity <? AND id!=?");
    $stmt->bind_param("ii", $timeout_time, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // إذا لم يكن المستخدم مسجل الدخول، تحديث حالة الجميع إلى غير متصل
    $stmt = $con->prepare("UPDATE tbl_login SET is_online = 0 WHERE last_activity <?");
    $stmt->bind_param("i", $timeout_time);
    $stmt->execute();
    $stmt->close();
}
?>