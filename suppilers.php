<?php
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
require_once 'config.php';
require_once 'dbconnection.php';
$stats = getStats($pdo);
// التحقق من الجلسة
if (!isset($_SESSION['aid']) || !filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
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

// معالجة حذف المورد
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: suppilers.php?message=تم حذف المورد بنجاح");
    exit;
}

// معالجة إضافة/تعديل المورد
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    
    if (isset($_POST['supplier_id'])) {
        // تحديث المورد
        $id = $_POST['supplier_id'];
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $address, $id]);
        $message = "تم تحديث المورد بنجاح";
    } else {
        // إضافة مورد جديد
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, email, address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $address]);
        $message = "تم إضافة المورد بنجاح";
    }
    
    header("Location: suppilers.php?message=" . urlencode($message));
    exit;
}

// جلب بيانات الموردين
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب بيانات مورد للتعديل إذا كان هناك معرف
$edit_supplier = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
    <?php include_once "header.php";?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> -
    شاشة الموردين</title>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <?php include 'sidebar.php'; ?>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="dashboard-header">
                    <h1>إدارة الموردين</h1>
                    <p>إضافة وتعديل وحذف الموردين في النظام</p>
                </div>

                <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_GET['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- نموذج إضافة/تعديل مورد -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo $edit_supplier ? 'تعديل المورد' : 'إضافة مورد جديد'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_supplier): ?>
                                    <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">اسم المورد</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo $edit_supplier ? $edit_supplier['name'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo $edit_supplier ? $edit_supplier['phone'] : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo $edit_supplier ? $edit_supplier['email'] : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">العنوان</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $edit_supplier ? $edit_supplier['address'] : ''; ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_supplier ? 'تحديث' : 'إضافة'; ?></button>
                                    
                                    <?php if ($edit_supplier): ?>
                                    <a href="suppilers.php" class="btn btn-secondary">إلغاء</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- جدول الموردين -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5>قائمة الموردين</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="suppliersTable">
                                        <thead>
                                            <tr>
                                                <th>اسم المورد</th>
                                                <th>الهاتف</th>
                                                <th>البريد الإلكتروني</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($suppliers as $supplier): ?>
                                            <tr>
                                                <td><?php echo $supplier['name']; ?></td>
                                                <td><?php echo $supplier['phone']; ?></td>
                                                <td><?php echo $supplier['email']; ?></td>
                                                <td>
                                                    <a href="suppilers.php?edit_id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                                                    <a href="suppilers.php?delete_id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا المورد؟')">حذف</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
           </main>    <?php include 'footer.php'; ?>
        </div>
    </div>
     <script>
        $('#suppliersTable').DataTable({
        responsive: true,
        serverSide: false,
        lengthChange: true,
        select: {
            style: 'multi'
        },
        keys: true,
        colReorder: false,
        searching: true,
        paging: true,
        info: true,
        fixedHeader: true,
        stateSave: true, // يحفظ حالة الجدول (ترتيب، بحث، صفحة) عند إعادة التحميل
        pageLength: 10,
        dom: 'Bfrtip', // تحكم في ترتيب عناصر DataTables (B=Buttons, f=filter, r=processing, t=table, i=information, p=pagination)
        buttons: [
            'copy',
            {
                extend: 'excelHtml5',
                text: 'Excel',
                filename: 'Customer_Bookings_Excel',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                filename: 'Customer_Bookings_PDF',
                exportOptions: {
                    columns: ':visible'
                },
                // Custom PDF styling for RTL and Arabic font
                customize: function (doc) {
                    // For proper Arabic font support, ensure 'Amiri-Regular' (or another Arabic font)
                    // is properly defined and loaded within pdfmake (usually via vfs_fonts.js)
                    // This is a placeholder; actual implementation requires font embedding.
                    doc.defaultStyle.font = 'Amiri-Regular'; 
                    doc.defaultStyle.alignment = 'right';
                    doc.styles.tableHeader.alignment = 'right';
                    
                    // Reverse column order for RTL in PDF, if header and body are handled together
                    if (doc.content[1] && doc.content[1].table) {
                        // Reverse the headers
                        if (doc.content[1].table.body.length > 0) {
                            doc.content[1].table.body[0].reverse();
                        }
                        // Reverse all data rows
                        for (let i = 1; i < doc.content[1].table.body.length; i++) {
                            doc.content[1].table.body[i].reverse();
                        }
                    }
                }
            },
            'print',
            'colvis'
        ],
        language: {
            search: 'بحث:',
            info: 'عرض _START_ إلى _END_ من _TOTAL_ صفحة',
            infoEmpty: 'لا توجد سجلات',
            infoFiltered: '(مفلترة من إجمالي _MAX_ السجلات)',
            lengthMenu: 'عرض _MENU_ سجلات',
            loadingRecords: 'تحميل...',
            processing: 'معالجة...',
            zeroRecords: 'لا توجد سجلات مطابقة',
            paginate: {
                first: 'الأول',
                last: 'الأخير',
                next: 'التالي',
                previous: 'السابق'
            },
            aria: {
                sortAscending: ': ترتيب تصاعدي',
                sortDescending: ': ترتيب تنازلي'
            },
            buttons: {
                copy: 'نسخ',
                csv: 'CSV',
                excel: 'Excel',
                pdf: 'PDF',
                print: 'طباعة',
                colvis: 'عرض/إخفاء الأعمدة'
            }
        }
    });
    </script>
</body>
</html>