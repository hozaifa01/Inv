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


// تحديد نوع التقرير
$report_type = isset($_GET['type']) ? $_GET['type'] : 'inventory';

// جلب البيانات حسب نوع التقرير
if ($report_type == 'inventory') {
    $stmt = $pdo->query("
        SELECT i.*, s.name as supplier_name,
               (SELECT SUM(quantity) FROM inventory_transactions WHERE item_id = i.id AND transaction_type = 'in') as total_in,
               (SELECT SUM(quantity) FROM inventory_transactions WHERE item_id = i.id AND transaction_type = 'out') as total_out
        FROM items i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        ORDER BY i.name
    ");
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($report_type == 'transactions') {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT it.*, i.name as item_name,
               CASE 
                   WHEN it.related_type = 'supplier' THEN s.name
                   WHEN it.related_type = 'client' THEN c.name
               END as related_name
        FROM inventory_transactions it
        JOIN items i ON it.item_id = i.id
        LEFT JOIN suppliers s ON it.related_type = 'supplier' AND it.related_id = s.id
        LEFT JOIN clients c ON it.related_type = 'client' AND it.related_id = c.id
        WHERE DATE(it.transaction_date) BETWEEN ? AND ?
        ORDER BY it.transaction_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($report_type == 'expiry') {
    $stmt = $pdo->query("
        SELECT * FROM items 
        WHERE expiry_date IS NOT NULL 
        ORDER BY expiry_date ASC
    ");
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - نظام المخازن</title>
                <?php include 'header.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <?php include 'sidebar.php'; ?>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="dashboard-header">
                    <h1>التقارير</h1>
                    <p>عرض تقارير المخزون والحركات والتحليلات</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-pills card-header-pills">
                            <li class="nav-item">
                                <a class="text-white nav-link <?php echo $report_type == 'inventory' ? 'active' : ''; ?>" href="reports.php?type=inventory">تقرير المخزون</a>
                            </li>
                            <li class="nav-item">
                                <a class="text-white nav-link <?php echo $report_type == 'transactions' ? 'active' : ''; ?>" href="reports.php?type=transactions">تقرير الحركات</a>
                            </li>
                            <li class="nav-item">
                                <a class="text-white nav-link <?php echo $report_type == 'expiry' ? 'active' : ''; ?>" href="reports.php?type=expiry">تقرير الصلاحية</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if ($report_type == 'transactions'): ?>
                        <form method="GET" class="row mb-4">
                            <input type="hidden" name="type" value="transactions">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">من تاريخ</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">عرض التقرير</button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped" id="reportsTable">
                                <thead>
                                    <tr>
                                        <?php if ($report_type == 'inventory'): ?>
                                            <th>اسم الصنف</th>
                                            <th>المورد</th>
                                            <th>الكمية الحالية</th>
                                            <th>إجمالي الدخول</th>
                                            <th>إجمالي الصرف</th>
                                            <th>سعر البيع</th>
                                        <?php elseif ($report_type == 'transactions'): ?>
                                            <th>التاريخ</th>
                                            <th>نوع الحركة</th>
                                            <th>الصنف</th>
                                            <th>الكمية</th>
                                            <th>الطرف</th>
                                            <th>ملاحظات</th>
                                        <?php elseif ($report_type == 'expiry'): ?>
                                            <th>اسم الصنف</th>
                                            <th>الكمية</th>
                                            <th>تاريخ الصلاحية</th>
                                            <th>الحالة</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <?php if ($report_type == 'inventory'): ?>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['supplier_name']; ?></td>
                                                                                        <td>
                                                    <?php echo
                                                    $row['quantity']; ?> -
 <?php
    if ($row['unit'] == 1) echo "كيلو ";
    elseif ($row['unit'] == 2) echo "جوال 25";
    elseif ($row['unit'] == 3) echo "كرتونة";
    ?>
                                                    <?php if ($row['quantity'] <= $row['min_quantity']): ?>
                                                    <span class="badge bg-warning">كمية منخفضة</span>
                                                    <?php endif; ?>
                                                </td>
                                            <td><?php echo $row['total_in'] ? $row['total_in'] : 0; ?></td>
                                            <td><?php echo $row['total_out'] ? $row['total_out'] : 0; ?></td>
                                            <td><?php echo $row['sale_price']; ?> جنيه</td>
                                        <?php elseif ($report_type == 'transactions'): ?>
                                            <td><?php echo $row['transaction_date']; ?></td>
                                            <td>
                                                <?php if ($row['transaction_type'] == 'in'): ?>
                                                    <span class="badge bg-success">دخول</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">صرف</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['item_name']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td><?php echo $row['related_name']; ?></td>
                                            <td><?php echo $row['notes']; ?></td>
                                        <?php elseif ($report_type == 'expiry'): ?>
                                            <td><?php echo $row['name']; ?></td>
                                                                                   <td>
                                                    <?php echo
                                                    $row['quantity']; ?> -
 <?php
    if ($row['unit'] == 1) echo "كيلو ";
    elseif ($row['unit'] == 2) echo "جوال 25";
    elseif ($row['unit'] == 3) echo "كرتونة";
    ?>
                                                    <?php if ($row['quantity'] <= $row['min_quantity']): ?>
                                                    <span class="badge bg-warning">كمية منخفضة</span>
                                                    <?php endif; ?>
                                                </td>
                                            <td><?php echo $row['expiry_date']; ?></td>
                                            <td>
                                                <?php
                                                $expiry_date = strtotime($row['expiry_date']);
                                                $today = strtotime('today');
                                                $diff = ($expiry_date - $today) / (60 * 60 * 24);
                                                
                                                if ($diff < 0) {
                                                    echo '<span class="badge bg-danger">منتهي</span>';
                                                } elseif ($diff <= 7) {
                                                    echo '<span class="badge bg-warning">ينتهي قريبًا</span>';
                                                } else {
                                                    echo '<span class="badge bg-success">ساري</span>';
                                                }
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
             <?php include 'footer.php'; ?>
        </div>
    </div>

         <script>
        $('#reportsTable').DataTable({
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