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

// التحقق من الجلسة
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

if (!$user || (int)$user['level']!== 99) {
    echo '<script>alert("عفوا، لا تملك صلاحيات كافية")</script>';
    echo "<script>window.location.href='index.php'</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8')?> - معلومات الموقع</title>
    <?php include('header.php');?>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3"><?php include "leftbar.php";?></div>
        <div class="col-md-9">
            <h2 class="mb-4">بيانات النظام</h2>
            <hr />
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>اسم المركز</th>
                                <th>وصف مختصر</th>
                                <th>أحدث الأخبار</th>
                                <th>أدوات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $con->prepare("SELECT id, title, description, news FROM site");
                            if ($stmt && $stmt->execute()) {
                                $result = $stmt->get_result();
                                while ($res = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>". htmlspecialchars(strtoupper($res['title']), ENT_QUOTES, 'UTF-8'). "</td>
                                        <td>". htmlspecialchars(strtoupper($res['description']), ENT_QUOTES, 'UTF-8'). "</td>
                                        <td>". htmlspecialchars(strtoupper($res['news']), ENT_QUOTES, 'UTF-8'). "</td>
                                        <td>
                                            <a href='edit-site.php?id=". (int)$res['id']. "' class='btn btn-sm btn-outline-primary'>
                                                <i class='fa fa-edit'></i> تعديل
                                            </a>
                                        </td>
                                    </tr>";
}
                                $stmt->close();
} else {
                                error_log("فشل في جلب بيانات الموقع: ". $con->error);
                                echo "<tr><td colspan='4'>حدث خطأ أثناء تحميل البيانات.</td></tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once("footer.php");?>
</body>
</html>