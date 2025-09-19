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


// معالجة إضافة إذن دخول
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    $notes = $_POST['notes'];
    
    // إضافة الحركة
    $stmt = $pdo->prepare("INSERT INTO inventory_transactions (item_id, transaction_type, quantity, related_id, related_type, notes) VALUES (?, 'in', ?, ?, 'supplier', ?)");
    $stmt->execute([$item_id, $quantity, $supplier_id, $notes]);
    
    // تحديث كمية الصنف
    $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
    $stmt->execute([$quantity, $item_id]);
    
    $message = "تم إضافة إذن الدخول بنجاح";
    header("Location: stock_in.php?message=" . urlencode($message));
    exit;
}

// جلب الموردين
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الأصناف
$stmt = $pdo->query("SELECT * FROM items ORDER BY name");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب سجل إذون الدخول
$stmt = $pdo->query("
    SELECT it.*, i.name as item_name, s.name as supplier_name 
    FROM inventory_transactions it 
    JOIN items i ON it.item_id = i.id 
    JOIN suppliers s ON it.related_id = s.id 
    WHERE it.transaction_type = 'in' 
    ORDER BY it.transaction_date DESC
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن دخول - نظام المخازن</title>
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
                    <h1>إذن دخول المواد</h1>
                    <p>تسجيل المواد الواردة إلى المخزن من الموردين</p>
                </div>

                <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_GET['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- نموذج إذن الدخول -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5>تسجيل إذن دخول جديد</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">المورد</label>
                                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                                            <option value="">-- اختر المورد --</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['id']; ?>">
                                                <?php echo $supplier['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="item_id" class="form-label">الصنف</label>
                                        <select class="form-select" id="item_id" name="item_id" required>
                                            <option value="">-- اختر الصنف --</option>
                                            <?php foreach ($items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>">
                                                <?php echo $item['name']; ?> (<?php echo $item['quantity']; ?> <?php
    if ($item['unit'] == 1) echo "كيلو ";
    elseif ($item['unit'] == 2) echo "جوال 25";
    elseif ($item['unit'] == 3) echo "كرتونة";
    ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">الكمية</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">ملاحظات</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- سجل إذون الدخول -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5>سجل إذون الدخول</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
<table class="table table-striped" id="stockInTable">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>المورد</th>
            <th>الصنف</th>
            <th>الكمية</th>
            <th>سعر الشراء</th>
            <th>إجمالي القيمة</th>
            <th>ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $transaction): ?>
            <?php
                $item_name = $transaction['item_name'];
                $quantity = $transaction['quantity'];

                // البحث عن سعر الشراء من جدول المواد
                $purchase_price = 0;
                foreach ($items as $item) {
                    if ($item['name'] == $item_name) {
                        $purchase_price = $item['purchase_price']; // تأكد أن هذا الحقل موجود في جدول المواد
                        break;
                    }
                }

                $total_value = $quantity * $purchase_price;
            ?>
            <tr>
                <td><?php echo $transaction['transaction_date']; ?></td>
                <td><?php echo $transaction['supplier_name']; ?></td>
                <td><?php echo $item_name; ?></td>
                <td><?php echo $quantity; ?></td>
                <td><?php echo $purchase_price; ?> جنيه</td>
                <td><?php echo $total_value; ?> جنيه</td>
                <td><?php echo $transaction['notes']; ?></td>
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
        $('#stockInTable').DataTable({
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