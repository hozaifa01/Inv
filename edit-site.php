<?php
// إعدادات الجلسة الآمنة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once 'dbconnection.php';

// التحقق من الجلسة
if (!isset($_SESSION['aid']) ||!filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
    header('Location: logout.php');
    exit();
}
$aid = (int) $_SESSION['aid'];

// التحقق من صلاحية المستخدم
$stmt = $con->prepare("SELECT level FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || (int)$user['level']!== 99) {
    echo '<script>alert("عفوًا، لا تملك صلاحيات كافية")</script>';
    echo "<script>window.location.href='logout.php'</script>";
    exit();
}

// معالجة التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $title = trim($_POST['title']);
    $news = trim($_POST['news']);
    $description = trim($_POST['description']);

    if ($id && $title && $news && $description) {
        $stmt = $con->prepare("UPDATE site SET title =?, news =?, description =? WHERE id =?");
        $stmt->bind_param("sssi", $title, $news, $description, $id);
        if ($stmt->execute()) {
            echo '<script>alert("تم تحديث المعلومات بنجاح")</script>';
            echo "<script>window.location.href='site.php'</script>";
            exit();
} else {
            error_log("فشل التحديث: ". $stmt->error);
            echo '<script>alert("فشل التحديث")</script>';
}
        $stmt->close();
} else {
        echo '<script>alert("البيانات المدخلة غير صالحة")</script>';
}
}
?>
<?php include('header.php');?>
<title><?= htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8')?> - تعديل بيانات النظام</title>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3"><?php include "leftbar.php";?></div>
        <div class="col-md-9">
            <h2 class="mb-4">تعديل بيانات النظام</h2>
            <hr />
            <form method="POST">
                <?php
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if ($id) {
                    $stmt = $con->prepare("SELECT * FROM site WHERE id =?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $res = $result->fetch_assoc();
                    $stmt->close();
}
?>

                <input type="hidden" name="id" value="<?= htmlspecialchars($res['id'], ENT_QUOTES, 'UTF-8')?>">

                <label>اسم الموقع</label>
                <input class="form-control" name="title" id="title" value="<?= htmlspecialchars($res['title'], ENT_QUOTES, 'UTF-8')?>" required>

                <label>نسخة النظام</label>
                <textarea cols="100" rows="10" class="ckeditor form-control" name="description" id="description" required><?= htmlspecialchars($res['description'], ENT_QUOTES, 'UTF-8')?></textarea>

                <label>أحدث الأخبار</label>
                <textarea cols="100" rows="10" class="ckeditor form-control" name="news" id="news" required><?= htmlspecialchars($res['news'], ENT_QUOTES, 'UTF-8')?></textarea>

                <input type="submit" class="ui-button ui-widget ui-corner-all mt-3" name="submit" value="تحديث البيانات">
            </form>
        </div>
    </div> </div>
<?php include_once("footer.php");?>
</body>
</html>