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


// معالجة حذف العميل
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: clients.php?message=تم حذف العميل بنجاح");
    exit;
}

// معالجة إضافة/تعديل العميل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $type = $_POST['type'];
    
    if (isset($_POST['client_id'])) {
        // تحديث العميل
        $id = $_POST['client_id'];
        $stmt = $pdo->prepare("UPDATE clients SET name=?, phone=?, type=? WHERE id=?");
        $stmt->execute([$name, $phone, $type, $id]);
        $message = "تم تحديث العميل بنجاح";
    } else {
        // إضافة عميل جديد
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone, type) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $type]);
        $message = "تم إضافة العميل بنجاح";
    }
    
    header("Location: clients.php?message=" . urlencode($message));
    exit;
}

// جلب بيانات العملاء
$stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب بيانات عميل للتعديل إذا كان هناك معرف
$edit_client = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_client = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> -
    إدارة العملاء</title>
    <?php include "header.php";?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <?php include 'sidebar.php'; ?>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="dashboard-header">
                    <h1>إدارة العملاء</h1>
                    <p>إضافة وتعديل وحذف العملاء في النظام</p>
                </div>

                <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_GET['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- نموذج إضافة/تعديل عميل -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo $edit_client ? 'تعديل العميل' : 'إضافة عميل جديد'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_client): ?>
                                    <input type="hidden" name="client_id" value="<?php echo $edit_client['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">اسم العميل</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo $edit_client ? $edit_client['name'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo $edit_client ? $edit_client['phone'] : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="type" class="form-label">نوع العميل</label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="retail" <?php echo ($edit_client && $edit_client['type'] == 'retail') ? 'selected' : ''; ?>>تجزئة</option>
                                            <option value="wholesale" <?php echo ($edit_client && $edit_client['type'] == 'wholesale') ? 'selected' : ''; ?>>جملة</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_client ? 'تحديث' : 'إضافة'; ?></button>
                                    
                                    <?php if ($edit_client): ?>
                                    <a href="clients.php" class="btn btn-secondary">إلغاء</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- جدول العملاء -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5>قائمة العملاء</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="clientsTable">
                                        <thead>
                                            <tr>
                                                <th>اسم العميل</th>
                                                <th>الهاتف</th>
                                                <th>النوع</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clients as $client): ?>
                                            <tr>
                                                <td><?php echo $client['name']; ?></td>
                                                <td><?php echo $client['phone']; ?></td>
                                                <td><?php echo $client['type'] == 'wholesale' ? 'جملة' : 'تجزئة'; ?></td>
                                                <td>
                                                    <a href="clients.php?edit_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                                                    <a href="clients.php?delete_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا العميل؟')">حذف</a>
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
        $('#clientsTable').DataTable({
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