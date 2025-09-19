<?php
// تأكد من أن session_start() هي أول شيء في الملف قبل أي مخرجات HTML
session_start();

require_once 'dbconnection.php'; // تأكد من أن هذا الملف يقوم بإنشاء $con لاتصال قاعدة البيانات

// تهيئة متغيرات رسائل التنبيه
$error_message = '';
$success_message = '';

// التحقق من إرسال النموذج باستخدام طريقة POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $uname = trim($_POST['id'] ?? '');     // استخدام ?? Null Coalescing Operator لتجنب التحذيرات
    $password = $_POST['password'] ?? ''; // لا تقم بتنظيف كلمة المرور قبل التحقق

    // التحقق من صحة المدخلات من جانب الخادم
    if (empty($uname) || empty($password)) {
        $error_message = "الرجاء تعبئة جميع الحقول المطلوبة.";
    } elseif (strlen($uname) < 3 || strlen($uname) > 50) { // مثال: طول اسم المستخدم بين 3 و 50 حرفًا
        $error_message = "طول اسم المستخدم غير صالح.";
    }
    // يمكنك إضافة المزيد من التحقق لاسم المستخدم مثل (preg_match)

    if (empty($error_message)) {
        // استخدام Prepared Statement لجلب بيانات المستخدم
        // يتم استخدام loginid للاستعلام
        $stmt = $con->prepare("SELECT id, loginid, password, level FROM tbl_login WHERE loginid =?");

        if ($stmt === false) {
            // تسجيل الخطأ بدلاً من عرضه للمستخدم
            error_log("Database prepare error (select login): " . mysqli_error($con));
            $error_message = "حدث خطأ في النظام، الرجاء المحاولة لاحقًا.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $uname); // 's' تعني ربط سلسلة نصية
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt); // استخدام get_result() للحصول على النتائج كـ mysqli_result
            $user = mysqli_fetch_assoc($result); // جلب الصف كـ associative array
            mysqli_stmt_close($stmt); // إغلاق العبارة المُعدَّة

            // التحقق من وجود المستخدم وكلمة المرور
            if ($user && password_verify($password, $user['password'])) {
                // تسجيل الدخول بنجاح

                // 1. حماية من Session Fixation
                session_regenerate_id(true);

                // تعيين متغيرات الجلسة
                $_SESSION['aid'] = $user['id'];
                $_SESSION['username'] = $user['loginid']; // يفضل استخدام ID فقط وتوظيفه لجلب البيانات
                $_SESSION['login'] = $user['loginid'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['last_activity'] = time(); // لتتبع نشاط الجلسة

                // تحديث حالة الاتصال في قاعدة البيانات (is_online و last_activity)
                $stmt_update = $con->prepare("UPDATE tbl_login SET is_online = 1, last_activity =? WHERE id =?");
                if ($stmt_update === false) {
                    error_log("Database prepare error (update online status): " . mysqli_error($con));
                    // لا تمنع المستخدم من الدخول بسبب خطأ في التحديث الثانوي
                } else {
                    mysqli_stmt_bind_param($stmt_update, "ii", $_SESSION['last_activity'], $user['id']); // 'ii' تعني ربط عددين صحيحين
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);
                }

                // إعادة التوجيه
                header('Location: index.php');
                exit(); // مهم جداً بعد header()
            } else {
                // رسالة خطأ عامة لتجنب الكشف عن معلومات حول المستخدمين الموجودين
                $error_message = "فشل تسجيل الدخول. اسم المستخدم أو كلمة المرور غير صحيحة.";
                // هنا يمكنك إضافة منطق لتسجيل محاولات الدخول الفاشلة وفرض تأخير (throttling) أو CAPTCHA
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"] ?? 'اسم الموقع', ENT_QUOTES, 'UTF-8'); ?> - تسجيل الدخول</title>
    <!-- تضمين ملفات CSS (مثال: Bootstrap) -->
    <?php include('header.php');?>
    <style>
        :root {
            --primary-color: #05415d;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #c0c0c0;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Tahoma', 'Arial', sans-serif;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
        }
        
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }
        
        .login-header h3 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 25px;
            background: white;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 110, 165, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-links {
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        .login-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .privacy-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
        }
        
        .privacy-link a {
            color: #6c757d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.3s;
        }
        
        .privacy-link a:hover {
            color: var(--primary-color);
        }
        
        .privacy-link i {
            margin-left: 5px;
        }
        
        .alert {
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h3>تسجيل الدخول</h3>
            <p>مرحباً بك في نظام إدارة المخازن</p>
        </div>
        <div class="login-body">
            <div class="logo-container">
                <i class="bi bi-shop fa-3x text-secondary"></i>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fa fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="form-group mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input class="form-control" placeholder="اسم المستخدم" name="id" type="text"
                               required autofocus
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($uname ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input class="form-control" placeholder="كلمة المرور" name="password" type="password"
                               required autocomplete="current-password">
                    </div>
                    
                    <div class="login-links mt-2 d-flex justify-content-between">
                        <a href="password-recovery.php">
                            <i class="fa fa-key me-1"></i> نسيت كلمة المرور؟
                        </a>
                        <a title="راجع الإدارة، لتنشيء لك حساب مستخدم" href="#" class="text-info">
                            <i class="fa fa-info-circle me-1"></i> طلب حساب جديد
                        </a>
                    </div>
                </div>
                
                <button type="submit" name="submit" class="btn btn-login w-100
                mt-3 text-white">
                    <i class="fa fa-sign-in me-2"></i> دخول
                </button>
            </form>
            
            <div class="privacy-link"> بإستخدامك النظام فانت توافق على :
                <a href="privacy-policy.php" target="_blank">
                    <i class="fa fa-shield"></i> سياسة الخصوصية واتفاقية المستخدم
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // إضافة تأثيرات عند التحميل
    document.addEventListener('DOMContentLoaded', function() {
        // تأثير ظهور التدريجي
        const loginCard = document.querySelector('.login-card');
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(20px)';
        loginCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0)';
        }, 100);
        
        // التحقق من صحة المدخلات قبل الإرسال
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="id"]');
            const password = document.querySelector('input[name="password"]');
            
            if (username.value.trim() === '') {
                e.preventDefault();
                username.focus();
                return false;
            }
            
            if (password.value === '') {
                e.preventDefault();
                password.focus();
                return false;
            }
        });
    });
</script>
</body>
</html>