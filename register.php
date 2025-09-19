<?php
// إعدادات الجلسة الآمنة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
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

require_once 'dbconnection.php';
// التحقق من صلاحية المدير
$stmt = $con->prepare("SELECT level FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
// تفعيل تسجيل الأخطاء وتعطيل عرضها للمستخدم
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $FullName   = trim($_POST['FullName']?? '');
    $AdminEmail = trim($_POST['AdminEmail']?? '');
    $loginid    = trim($_POST['loginid']?? '');
    $password   = $_POST['password']?? '';
    $level      = 0;

    // التحقق من صحة المدخلات
    if (!filter_var($AdminEmail, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('البريد الإلكتروني غير صالح');</script>";
} elseif (empty($FullName) || empty($loginid) || empty($password)) {
        echo "<script>alert('يرجى تعبئة جميع الحقول المطلوبة');</script>";
} elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error']!== UPLOAD_ERR_OK) {
        echo "<script>alert('يرجى تحميل صورة صالحة');</script>";
} else {
        // التحقق من نوع وامتداد الصورة
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_exts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $mime_type     = mime_content_type($_FILES['avatar']['tmp_name']);
        $extension     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $max_size      = 2 * 1024 * 1024; // 2MB

        if (!in_array($mime_type, $allowed_types) ||!in_array($extension, $allowed_exts)) {
            echo "<script>alert('نوع الصورة غير مدعوم');</script>";
} elseif ($_FILES['avatar']['size']> $max_size) {
            echo "<script>alert('حجم الصورة كبير جداً، الحد الأقصى 2MB');</script>";
} else {
            $photo_name  = uniqid('user_'). '.'. $extension;
            $target_path = "uploads/". $photo_name;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $con->prepare("INSERT INTO tbl_login (FullName, AdminEmail, loginid, password, photo, level) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("sssssi", $FullName, $AdminEmail, $loginid, $hashedPassword, $photo_name, $level);

                if ($stmt->execute()) {
                    echo "<script>alert('تم التسجيل بنجاح');window.location='login.php';</script>";
} else {
                    error_log("خطأ في التسجيل: ". $stmt->error);
                    echo "<script>alert('حدث خطأ أثناء التسجيل، حاول مرة أخرى');</script>";
}

                $stmt->close();
} else {
                echo "<script>alert('فشل تحميل الصورة');</script>";
}
}
}
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($site["title"]?? 'موقعي', ENT_QUOTES, 'UTF-8');?> - تسجيل عضو جديد</title>
    <?php include('header.php');?>
</head>
<body class="p-4">
    <div class="container-fluid">
        <div class="row">
 <div class="col-md-3"><?php include "leftbar.php";?></div>
            <div class="col-md-9">
                <h2 class="mb-4">تسجيل مستخدم جديد</h2>
                <hr />
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>الاسم الكامل <span style="color:red"> _</span></label>
                        <input type="text" class="form-control" name="FullName" placeholder="الاسم الكامل" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني <span style="color:red">_ </span></label>
                        <input type="email" class="form-control" name="AdminEmail" placeholder="البريد الإلكتروني" required>
                    </div>
                    <div class="form-group">
                        <label>اسم المستخدم <span style="color:red"> _</span></label>
                        <input type="text" class="form-control" name="loginid" placeholder="اسم المستخدم" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور <span style="color:red">_ </span></label>
                        <input type="password" class="form-control" name="password" placeholder="كلمة المرور" required>
                    </div>
                    <div class="form-group">
                        <label>صورة المستخدم</label>
                        <input type="file" class="form-control-file" name="avatar" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" name="submit">تسجيل</button>
                </form>
            </div>
        </div>
    </div>
    <?php include_once('footer.php');?>
</body>
</html>