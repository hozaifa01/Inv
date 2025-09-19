<?php
require_once('dbconnection.php');

// تهيئة متغيرات الأخطاء لكي يمكن عرضها في نموذج HTML إذا لزم الأمر
$error_message = '';
$success_message = '';

if (isset($_POST['submit'])) {
    // 1. التحقق من صحة المدخلات من جانب الخادم (Input Validation)
    $uname = trim($_POST['id'] ?? ''); // استخدام ?? لتعيين قيمة افتراضية فارغة إذا لم يتم تعيين المتغير
    $emailid = trim($_POST['emailid'] ?? '');
    $password = $_POST['password'] ?? ''; // لا نستخدم trim لكلمة المرور قبل التشفير

    if (empty($uname) || empty($emailid) || empty($password)) {
        $error_message = "الرجاء تعبئة جميع الحقول المطلوبة.";
    } elseif (!filter_var($emailid, FILTER_VALIDATE_EMAIL)) {
        $error_message = "البريد الإلكتروني المدخل غير صالح.";
    } elseif (strlen($password) < 8) { // مثال: كلمة المرور يجب أن تكون 8 أحرف على الأقل
        $error_message = "يجب أن تتكون كلمة المرور الجديدة من 8 أحرف على الأقل.";
    } else {
        // 2. تشفير كلمة المرور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 3. استخدام Prepared Statements للاستعلام عن المستخدم
        // الاستعلام عن المستخدم للتأكد من وجوده قبل التحديث
        $stmt_select = mysqli_prepare($con, "SELECT ID, loginid FROM tbl_login WHERE loginid=? AND AdminEmail=?");

        if ($stmt_select === false) {
            error_log("Database prepare error (select): " . mysqli_error($con)); // تسجيل الخطأ في السجلات
            $error_message = "حدث خطأ في قاعدة البيانات. الرجاء المحاولة لاحقًا.";
        } else {
            mysqli_stmt_bind_param($stmt_select, "ss", $uname, $emailid); // 'ss' تعني ربط سلسلتين
            mysqli_stmt_execute($stmt_select);
            $result_select = mysqli_stmt_get_result($stmt_select);
            $user_found = mysqli_fetch_array($result_select);
            mysqli_stmt_close($stmt_select); // إغلاق العبارة المُعدَّة

            if ($user_found) {
                // 4. استخدام Prepared Statements لتحديث كلمة المرور
                $stmt_update = mysqli_prepare($con, "UPDATE tbl_login SET password=? WHERE loginid=? AND AdminEmail=?");

                if ($stmt_update === false) {
                    error_log("Database prepare error (update): " . mysqli_error($con)); // تسجيل الخطأ
                    $error_message = "حدث خطأ في قاعدة البيانات. الرجاء المحاولة لاحقًا.";
                } else {
                    mysqli_stmt_bind_param($stmt_update, "sss", $hashedPassword, $uname, $emailid); // 'sss' تعني ربط ثلاث سلاسل
                    $update_success = mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update); // إغلاق العبارة المُعدَّة

                    if ($update_success) {
                        $success_message = "تم تحديث كلمة المرور بنجاح.";
                        // 5. إعادة التوجيه باستخدام header() أفضل من JavaScript
                        // تخزين الرسالة في الجلسة إذا كنت تريد عرضها بعد إعادة التوجيه
                        session_start();
                        $_SESSION['status_message'] = $success_message;
                        header("Location: login.php");
                        exit(); // مهم جداً بعد header()
                    } else {
                        error_log("Database update error: " . mysqli_error($con)); // تسجيل الخطأ
                        $error_message = "حدث خطأ أثناء تحديث كلمة المرور. الرجاء المحاولة مرة أخرى.";
                    }
                }
            } else {
                $error_message = "بيانات الدخول أو البريد الإلكتروني خاطئة.";
            }
        }
    }

    // عرض رسالة الخطأ إذا كانت موجودة (إذا لم يتم إعادة التوجيه)
    if (!empty($error_message)) {
        echo '<script>alert("' . htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') . '");</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>استرجاع كلمة المرور - <?php echo htmlentities($site['title'] ?? 'عنوان الموقع'); ?></title>
<!-- يجب أن تكون ملفات CSS هنا -->
<?php include("header.php"); // هذا الملف قد يحتوي على المزيد من أكواد <head> مثل الروابط لملفات CSS والـ meta tags ?>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <form class="ui-widget-header ui-corner-all p-4 shadow" method="post">
                <h2 class="ui-widget-header ui-corner-all text-center mb-4 p-2">استرجاع كلمة المرور</h2>
                <fieldset>
                    <?php
                    // عرض رسائل الحالة المخزنة في الجلسة بعد إعادة التوجيه
                    session_start();
                    if (isset($_SESSION['status_message'])) {
                        echo '<div class="alert alert-info text-center">' . htmlspecialchars($_SESSION['status_message'], ENT_QUOTES, 'UTF-8') . '</div>';
                        unset($_SESSION['status_message']); // حذف الرسالة بعد عرضها
                    }
                    ?>

                    <div class="form-group mb-3">
                        <input class="form-control" placeholder="معرف الدخول (Login Id)" id="id" name="id" type="text"
                               required autocomplete="username" value="<?php echo htmlspecialchars($uname); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <input class="form-control" placeholder="البريد الإلكتروني للمسؤول (Admin Email id)" id="emailid"
                               name="emailid" type="email" required autocomplete="email" value="<?php echo htmlspecialchars($emailid); ?>">
                    </div>

                    <div class="form-group mb-4">
                        <input class="form-control" placeholder="كلمة المرور الجديدة" id="password" name="password" type="password" required autocomplete="new-password">
                    </div>

                    <input type="submit" value="تحديث كلمة المرور" name="submit" class="btn btn-lg btn-success btn-block w-100 mb-3">
                    <a class="btn btn-lg btn-outline-primary btn-block w-100" href="login.php">دخول</a>
                </fieldset>
            </form>
        </div>
    </div>
</div>


<?php include_once("footer.php"); ?>
</body>
</html>