<?php
session_start();
require_once 'dbconnection.php';

// التحقق من الجلسة وصلاحية المدير
if (!isset($_SESSION['aid']) ||!filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
    header('Location: logout.php');
    exit();
}

$aid = (int) $_SESSION['aid'];
$stmt = $con->prepare("SELECT level FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();


// تحديث بيانات المستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id         = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $fullName   = trim($_POST['FullName']);
    $adminEmail = trim($_POST['AdminEmail']);
    $loginid    = trim($_POST['loginid']);
    $password   = $_POST['password'];
    $level      = filter_var($_POST['level'], FILTER_VALIDATE_INT);

    if ($id && $fullName && $adminEmail && $loginid && $level) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE tbl_login SET FullName =?, AdminEmail =?, loginid =?, password =?, level =? WHERE id =?");
            $stmt->bind_param("ssssii", $fullName, $adminEmail, $loginid, $hashedPassword, $level, $id);
} else {
            $stmt = $con->prepare("UPDATE tbl_login SET FullName =?, AdminEmail =?, loginid =?, level =? WHERE id =?");
            $stmt->bind_param("sssii", $fullName, $adminEmail, $loginid, $level, $id);
}

        if ($stmt->execute()) {
            $stmt->close();

            // معالجة رفع الصورة
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mime_type = mime_content_type($_FILES['photo']['tmp_name']);
                $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $max_size = 2 * 1024 * 1024;

                if (in_array($mime_type, $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
                    $photo_name = uniqid('user_'). '.'. $extension;
                    $target_path = "uploads/". $photo_name;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                        $stmt = $con->prepare("UPDATE tbl_login SET photo =? WHERE id =?");
                        $stmt->bind_param("si", $photo_name, $id);
                        $stmt->execute();
                        $stmt->close();
} else {
                        echo '<script>alert("فشل تحميل الصورة")</script>';
}
} else {
                    echo '<script>alert("نوع الصورة غير مدعوم أو الحجم كبير")</script>';
}
}

            echo '<script>alert("تم تحديث بيانات العضو بنجاح"); window.location.href="manage_users.php";</script>';
            exit();
} else {
            error_log("فشل التحديث: ". $stmt->error);
            echo '<script>alert("حدث خطأ أثناء تحديث البيانات")</script>';
}
} else {
        echo '<script>alert("البيانات المدخلة غير صالحة")</script>';
}
}
?>
<?php include('header.php');?>
<title>إدارة المستخدمين - <?= htmlspecialchars($site['title'], ENT_QUOTES, 'UTF-8')?></title>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="row">
                  <div class="col-md-3"><?php include('leftbar.php');?></div>
        <div class="col-md-9">
            <h2 class="mb-4">إدارة المستخدمين</h2>
            <hr />
            <a href="register.php" class="btn btn-success mb-3">+ إضافة مستخدم</a>
            <div class="table-responsive">
                <table id="usersTable" class="table table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الاسم الكامل</th>
                            <th>البريد الإلكتروني</th>
                            <th>اسم المستخدم</th>
                            <th>الصلاحيات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $con->query("SELECT * FROM tbl_login");
                        while ($row = $result->fetch_assoc()) {
                            $photo =!empty($row['photo'])? htmlspecialchars($row['photo'], ENT_QUOTES, 'UTF-8'): 'default.png';
                            switch ((int)$row['level']) {
                                case 1: $levelText = 'موظف استقبال'; break;
                                case 2: $levelText = 'محاسب'; break;
                                case 99: $levelText = 'مدير عام'; break;
                                default: $levelText = 'غير محدد'; break;
}
                            echo "<tr>
                                <td><img src='uploads/{$photo}' alt='صورة العضو' width='50'></td>
                                <td>". htmlspecialchars($row['FullName']). "</td>
                                <td>". htmlspecialchars($row['AdminEmail']). "</td>
                                <td>". htmlspecialchars($row['loginid']). "</td>
                                <td>{$levelText}</td>
                                <td>
                                    <button type='button' class='btn btn-sm btn-outline-primary' onclick=\"showEditModal(
                                        '{$row['id']}',
                                        '". addslashes($row['FullName']). "',
                                        '". addslashes($row['AdminEmail']). "',
                                        '". addslashes($row['loginid']). "',
                                        '{$row['level']}'
)\">تعديل</button>
                                    |
                                    <a href='delete_user.php?id={$row['id']}' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"هل أنت متأكد؟\")'>حذف</a>
                                </td>
                            </tr>";
}
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal لتعديل المستخدم -->
    <div id="editModal" title="تعديل العضو" style="display:none;">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" id="user_id" name="user_id">
            <div class="form-group">
                <label for="FullName">الاسم الكامل</label>
                <input type="text" class="form-control" id="FullName" name="FullName" required>
            </div>
            <div class="form-group">
                <label for="AdminEmail">البريد الإلكتروني</label>
                <input type="email" class="form-control" id="AdminEmail" name="AdminEmail" required>
            </div>
            <div class="form-group">
                <label for="loginid">اسم المستخدم</label>
                <input type="text" class="form-control" id="loginid" name="loginid" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور (اتركها فارغة إذا لم ترغب في تغييرها)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="form-group">
                <select class="form-control" id="level" name="level" required>
                    <option selected="" value="1">موظف استقبال</option>
                    <option selected="" value="99">مدير </option>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">الصورة الشخصية</label>
                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
            </div>
            <button type="submit" name="update" class="btn btn-primary mt-3">تحديث</button>
        </form>
    </div>

    <!-- DataTables و Modal -->
    <script>
        $(document).ready(function () {
            $('#usersTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                language: {
                    search: "بحث:",
                    paginate: {
                        next: "التالي",
                        previous: "السابق"
}
}
});
});

        function showEditModal(id, fullName, adminEmail, loginid, level) {
            $('#user_id').val(id);
            $('#FullName').val(fullName);
            $('#AdminEmail').val(adminEmail);
            $('#loginid').val(loginid);
 $('#password').val('');
            $('#level').val(level);
            $('#editModal').dialog({
                modal: true,
                width: 400,
                height: 500,
                close: function () {
                    $('#user_id').val('');
                    $('#FullName').val('');
                    $('#AdminEmail').val('');
                    $('#loginid').val('');
                    $('#password').val('');
                    $('#level').val('');
}
});
}
    </script>
</div>
<?php include_once("footer.php");?>
</body>
</html>