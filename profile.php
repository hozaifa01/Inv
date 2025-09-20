<?php
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']),
    'samesite' => 'Strict'
]);
session_start();

require_once('dbconnection.php');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['aid']) ||!filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
    header('Location: logout.php');
    exit();
}

$aid = (int) $_SESSION['aid'];

// التحقق من صلاحية المدير
$stmt = $con->prepare("SELECT level FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!isset($user) || (int)$user['level'] <= 0) {
    echo '<script>alert("عفوا، لا تملك صلاحيات كافية")</script>';
    echo "<script>window.location.href='index.php'</script>";
    exit();
}
$stmt = $con->prepare("SELECT FullName, AdminEmail, loginid, photo FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("معرف مستخدم غير صالح: $aid");
    exit("حدث خطأ، يرجى المحاولة لاحقاً.");
}

$rowuser = $result->fetch_assoc();
$result->free();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $fullName = trim($_POST['FullName']?? '');
    $adminEmail = trim($_POST['AdminEmail']?? '');
    $password = $_POST['password']?? '';

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('البريد الإلكتروني غير صالح');</script>";
} else {
        // تحديث البيانات الأساسية
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE tbl_login SET FullName =?, AdminEmail =?, password =? WHERE id =?");
            $stmt->bind_param("sssi", $fullName, $adminEmail, $hashedPassword, $aid);
} else {
            $stmt = $con->prepare("UPDATE tbl_login SET FullName =?, AdminEmail =? WHERE id =?");
            $stmt->bind_param("ssi", $fullName, $adminEmail, $aid);
}

        if (!$stmt->execute()) {
            error_log("خطأ في تحديث البيانات: ". $stmt->error);
}
        $stmt->close();

        // معالجة الصورة
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $mime_type = mime_content_type($_FILES['photo']['tmp_name']);
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($mime_type, $allowed_types) ||!in_array($extension, $allowed_exts)) {
                echo "<script>alert('نوع الصورة غير مدعوم');</script>";
} elseif ($_FILES['photo']['size']> $max_size) {
                echo "<script>alert('حجم الصورة كبير جداً، الحد الأقصى 2MB');</script>";
} else {
                $photo_name = uniqid('profile_'). '.'. $extension;
                $target_path = "uploads/". $photo_name;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                    $stmt = $con->prepare("UPDATE tbl_login SET photo =? WHERE id =?");
                    $stmt->bind_param("si", $photo_name, $aid);
                    if (!$stmt->execute()) {
                        error_log("خطأ في تحديث الصورة: ". $stmt->error);
}
                    $stmt->close();
} else {
                    echo "<script>alert('فشل تحميل الصورة');</script>";
}
}
}

        header('Location: index.php');
        exit();
}
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <?php include('header.php');?>
<title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> -
الملف الشخصي</title>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3"><?php include('leftbar.php');?></div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">الملف الشخصي</div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">الاسم الكامل <span style="color:red"> _</span></label>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control" name="FullName" value="<?php echo htmlspecialchars($rowuser['FullName'], ENT_QUOTES, 'UTF-8');?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">البريد الإلكتروني <span style="color:red">_ </span></label>
                                <div class="col-lg-6">
                                    <input type="email" class="form-control" name="AdminEmail" value="<?php echo htmlspecialchars($rowuser['AdminEmail'], ENT_QUOTES, 'UTF-8');?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">اسم المستخدم</label>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rowuser['loginid'], ENT_QUOTES, 'UTF-8');?>" disabled>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">كلمة المرور</label>
                                <div class="col-lg-6">
                                    <input type="password" class="form-control" name="password">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">الصورة الحالية</label>
                                <div class="col-lg-6">
                                    <img src="uploads/<?php echo htmlspecialchars($rowuser['photo'], ENT_QUOTES, 'UTF-8');?>" alt="صورة المستخدم" width="100">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-4 col-form-label">تغيير الصورة</label>
                                <div class="col-lg-6">
                                    <input type="file" class="form-control-file" name="photo" accept="image/*">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-lg-4"></div>
                                <div class="col-lg-6">
                                    <button type="submit" name="update" class="btn btn-primary">تحديث البيانات</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include_once("footer.php");?>
</body>
</html>