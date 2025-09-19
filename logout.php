<?php
session_start();
require_once 'dbconnection.php';

// التحقق من وجود معرف المستخدم في الجلسة
if (isset($_SESSION['aid']) && filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
    $user_id = (int) $_SESSION['aid'];

    // تحديث حالة الاتصال إلى غير متصل باستخدام Prepared Statement
    $stmt = $con->prepare("UPDATE tbl_login SET is_online = 0 WHERE id =?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
} else {
        error_log("فشل إعداد استعلام تسجيل الخروج: ". $con->error);
}
}

// تدمير الجلسة بأمان
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
);
}
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header('Location: login.php');
exit();
?>