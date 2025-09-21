<?php
require_once 'dbconnection.php';

// التحقق من صلاحية المدير
$stmt = $con->prepare("SELECT level FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
// التحقق من وجود المعرف وصحته
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $user_id = (int) $_GET['id'];

    // استخدام Prepared Statement للحذف الآمن
    $stmt = $con->prepare("DELETE FROM tbl_login WHERE id =?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "<script>alert('تم حذف المستخدم بنجاح');window.location='manage_users.php';</script>";
} else {
            error_log("فشل تنفيذ الحذف: ". $stmt->error);
            echo "<script>alert('حدث خطأ أثناء حذف المستخدم');window.location='manage_users.php';</script>";
}
        $stmt->close();
} else {
        error_log("فشل إعداد الاستعلام: ". $con->error);
        echo "<script>alert('تعذر حذف المستخدم');window.location='manage_users.php';</script>";
}
} else {
    echo "<script>alert('معرف المستخدم غير صالح');window.location='manage_users.php';</script>";
}
?>