<?php include_once("dbconnection.php");?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> - سياسة الخصوصية واتفاقية  المستخدم  </title>
    <?php include_once("header.php");?>
    <!-- تأكد من تضمين Bootstrap Icons -->
    <style>
        :root {
            --primary-color: #05415d;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #c0c0c0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            line-height: 1.8;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .last-updated {
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: var(--secondary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
        }
        
        .section-title i {
            margin-left: 10px;
            background: var(--secondary-color);
            color: white;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .subsection {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 4px solid var(--primary-color);
        }
        
        .subsection-title {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        ul {
            padding-right: 20px;
            margin: 10px 0;
        }
        
        li {
            margin-bottom: 8px;
        }
        
        .highlight {
            background-color: #fff9e6;
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #ffc107;
            margin: 15px 0;
        }
        
        .contact-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 30px;
        }
        
        .contact-info h3 {
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .contact-details {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .contact-item i {
            margin-left: 10px;
            color: var(--primary-color);
        }
        
        footer {
            text-align: center;
            padding: 20px;
            background: var(--dark-color);
            color: white;
        }
        
        .agree-section {
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 8px;
        }
        
        .btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 10px;
            font-weight: 600;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .section-title {
                font-size: 1.3rem;
            }
            
            .contact-details {
                flex-direction: column;
                align-items: center;
            }
            
            header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="bi bi-shield-check"></i> سياسة الخصوصية واتفاقية المستخدم</h1>
            <p>نظام إدارة المخازن - الإصدار 1.0</p>
        </header>
        
        <div class="content">
            <div class="last-updated">
                <i class="bi bi-clock-history"></i> آخر تحديث: 19 سبتمبر 2025
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-info-circle"></i> مقدمة</h2>
                <p>مرحبًا بكم في نظام إدارة المخازن. تحدد سياسة الخصوصية هذه كيفية جمع واستخدام والكشف عن معلوماتكم الشخصية عند استخدامكم لنظام إدارة المخازن. باستخدامكم للنظام، فإنكم توافقون على الممارسات الموضحة في هذه السياسة.</p>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-shield"></i> المعلومات التي نجمعها</h2>
                
                <div class="subsection">
                    <h3 class="subsection-title">المعلومات الشخصية</h3>
                    <p>عند تسجيل الدخول، قد نجمع المعلومات التالية:</p>
                    <ul>
                        <li><i class="bi bi-person"></i> الاسم الكامل</li>
                        <li><i class="bi bi-image"></i> الصورة </li>
                        <li><i class="bi bi-envelope"></i> عنوان البريد الإلكتروني</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3 class="subsection-title">معلومات الاستخدام</h3>
                    <p>نقوم تلقائيًا بجمع معلومات معينة حول كيفية استخدامكم للنظام، بما في ذلك:</p>
                    <ul>
                        <li><i class="bi bi-pc-display"></i> نوع الجهاز والمتصفح</li>
                        <li><i class="bi bi-globe2"></i> عنوان IP والموقع التقريبي</li>
                        <li><i class="bi bi-clock"></i> أوقات الوصول ومدة الاستخدام</li>
                        <li><i class="bi bi-search"></i> الصفحات التي تم زيارتها والإجراءات المتخذة</li>
                    </ul>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-lock"></i> كيفية استخدام معلوماتكم</h2>
                <p>نستخدم المعلومات التي نجمعها للأغراض التالية:</p>
                <ul>
                    <li>معالجة الدخل والمنصرف وإدارة الحسابات</li>
                    <li>تحسين تجربة المستخدم وتطوير النظام</li>
                    <li>عرض كمية المواد وحساب فترة صلاحيتها</li>
                    <li>توفير خدمة العملاء والدعم</li>
                    <li>الامتثال للالتزامات القانونية والتنظيمية</li>
                </ul>
                
                <div class="highlight">
                    <p><i class="bi bi-exclamation-circle"></i> <strong>ملاحظة هامة:</strong> نحن لا نبيع أو نؤجر معلوماتكم الشخصية إلى أطراف ثالثة لأغراض التسويق.</p>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-database"></i> حماية المعلومات</h2>
                <p>نحن نعمل على حماية معلوماتكم الشخصية من خلال:</p>
                <ul>
                    <li>احدث الاجراءات والدوال لتأمين نقل البيانات</li>
                    <li>الحد من الوصول إلى المعلومات للموظفين المصرح لهم فقط</li>
                    <li>مراجعة وتحديث ممارسات الأمان بشكل منتظم</li>
                </ul>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-cookie"></i> ملفات تعريف الارتباط (Cookies)</h2>
                <p>نستخدم ملفات تعريف الارتباط لتذكر تفضيلاتكم وتحسين تجربة استخدام النظام. يمكنكم ضبط إعدادات المتصفح لرفض ملفات تعريف الارتباط، لكن هذا قد يؤثر على بعض وظائف النظام.</p>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-person-check"></i> حقوقكم</h2>
                <p>لديكم الحق في:</p>
                <ul>
                    <li>طلب تصحيح المعلومات غير الدقيقة</li>
                    <li>طلب حذف معلوماتكم الشخصية في ظروف معينة</li>
                    <li>معارضة معالجة معلوماتكم لأغراض محددة</li>
                    <li>طلب نسخة من معلوماتكم بصيغة قابلة للنقل</li>
                </ul>
            </div>
            
            <div class="section">
                <h2 class="section-title"><i class="bi bi-file-text"></i> اتفاقية المستخدم</h2>
                <p>باستخدامكم لنظام إدارة المخازن، فإنكم توافقون على:</p>
                <ul>
                    <li>تقديم معلومات دقيقة وصحيحة</li>
                    <li>الحفاظ على سرية معلومات حسابكم</li>
                    <li>استخدام النظام لأغراض مشروعة فقط</li>
                    <li>عدم إساءة استخدام النظام أو محاولة اختراقه</li>
                </ul>
                
                <div class="highlight">
                    <p><i class="bi bi-exclamation-triangle"></i>
                    <strong>تحذير:</strong> قد يؤدي انتهاك شروط الاستخدام هذه
                    إلى تعليق أو إنهاء حقكم في استخدام النظام وملاحقتكم قانونيا.</p>
                </div>
            </div>
            
            <div class="agree-section">
                <h3><i class="bi bi-hand-thumbs-up"></i> موافقة على الشروط</h3>
                <p>باستمراركم في استخدام نظام إدارة المخازن، فإنكم تقرون بأنكم قد قرأتم وفهمتكم وتوافقون على الالتزام بسياسة الخصوصية وشروط الاستخدام هذه.</p>
                <div style="margin-top: 20px;">
                    <button class="btn" onclick="acceptPolicy()"><i class="bi bi-check-lg"></i> أوافق على الشروط</button>
                    <button class="btn btn-outline" onclick="declinePolicy()"><i class="bi bi-x-lg"></i> لا أوافق</button>
                </div>
            </div>
            
            <div class="contact-info">
                <h3><i class="bi bi-question-circle"></i> للاستفسارات أو الأسئلة</h3>
                <p>إذا كانت لديكم أي أسئلة حول سياسة الخصوصية أو اتفاقية المستخدم، يرجى التواصل معنا:</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <i class="bi bi-envelope"></i>
                        <span>hozaifa01@gmail.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-telephone"></i>
                        <span>+249903814680</span>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-clock"></i>
                        <span>من السبت الى الخميس، 8 صباحًا - 5 مساءً</span>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>© hozaifa01 نظام إدارة المخازن. جميع الحقوق محفوظة.</p>
        </footer>
    </div>

    <script>
        function acceptPolicy() {
            alert('شكرًا لموافقتكم على شروط الاستخدام وسياسة الخصوصية. يمكنكم الآن استخدام نظام إدارة المخازن بشكل كامل.');
            // هنا يمكنك إضافة كود لتخزين موافقة المستخدم في قاعدة البيانات
        }
        
        function declinePolicy() {
            if(confirm('عذرًا، لا يمكنكم استخدام نظام إدارة المخازن دون الموافقة على الشروط والأحكام. هل ترغب في مغادرة الصفحة؟')) {
                window.location.href = 'index.php'; // أو الصفحة الرئيسية الخاصة بك
            }
        }
        
        // تأثيرات بسيطة عند التمرير
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            sections.forEach(section => {
                section.style.opacity = 0;
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(section);
            });
        });
    </script>
</body>
</html>