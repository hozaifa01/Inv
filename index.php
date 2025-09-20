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


// الحصول على حركة المخزون الأخيرة
$stmt = $pdo->query("
    SELECT it.*, i.name as item_name, 
           CASE 
               WHEN it.related_type = 'supplier' THEN s.name
               WHEN it.related_type = 'client' THEN c.name
           END as related_name
    FROM inventory_transactions it
    JOIN items i ON it.item_id = i.id
    LEFT JOIN suppliers s ON it.related_type = 'supplier' AND it.related_id = s.id
    LEFT JOIN clients c ON it.related_type = 'client' AND it.related_id = c.id
    ORDER BY it.transaction_date DESC
    LIMIT 10
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// الحصول على الأصناف المنتهية الصلاحية
$stmt = $pdo->query("SELECT * FROM items WHERE expiry_date < CURDATE() OR expiry_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY expiry_date ASC");
$expiring_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> -
    لوحة التحكم</title>
    <?php require_once "header.php";?>

</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <?php require_once 'sidebar.php'; ?>
            <!-- المحتوى الرئيسي -->
            <main class="col-md-9  main-content">
                <div class="dashboard-header">
                    <h1>لوحة تحكم <?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> </h1>
                    <p>مرحبًا بك في نظام إدارة مخازن المواد الغذائية</p>
                </div>

                <div class="row">
                    <!-- بطاقات الإحصائيات -->
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                                <h3 class="stat-number"><?php echo $stats['total_items']; ?></h3>
                                <p>إجمالي الأصناف</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                                <h3 class="stat-number"><?php echo $stats['total_suppliers']; ?></h3>
                                <p>عدد الموردين</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <i class="bi bi-person-badge" style="font-size: 2rem;"></i>
                                <h3 class="stat-number"><?php echo $stats['total_clients']; ?></h3>
                                <p>عدد العملاء</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                                <h3 class="stat-number"><?php echo $stats['expired_items']; ?></h3>
                                <p>أصناف منتهية الصلاحية</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- الأصناف المنتهية الصلاحية -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>تنبيهات الأصناف المنتهية الصلاحية</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($expiring_items) > 0): ?>
                                    <?php foreach ($expiring_items as $item): 
                                        $is_expired = strtotime($item['expiry_date']) < strtotime('today');
                                        $alert_class = $is_expired ? 'alert-expired' : 'alert-expiry';
                                    ?>
                                        <div class="alert <?php echo $alert_class; ?> mb-2">
                                            <strong><?php echo $item['name']; ?></strong> - 
                                            ينتهي الصلاحية في: <?php echo $item['expiry_date']; ?>
                                            <?php if ($is_expired): ?>
                                                <span class="badge bg-danger">منتهي</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        لا توجد أصناف منتهية الصلاحية أو مقتربة من الانتهاء
                                    </div>
                                <?php endif; ?>
                                <a href="items.php" class="btn btn-outline-warning">عرض جميع الأصناف</a>
                            </div>
                        </div>
                    </div>

                    <!-- الأصناف الأكثر مبيعًا -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>أحدث حركات المخزون</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>نوع الحركة</th>
                                                <th>الصنف</th>
                                                <th>الكمية</th>
                                                <th>التاريخ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($transaction['transaction_type'] == 'in'): ?>
                                                        <span class="badge badge-in">دخول</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-out">صرف</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $transaction['item_name']; ?></td>
                                                <td><?php echo $transaction['quantity']; ?></td>
                                                <td><?php echo $transaction['transaction_date']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="reports.php" class="btn btn-outline-primary">عرض تقرير كامل</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول حركة المخزون -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>إدارة سريعة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="stock_in.php" class="btn btn-success btn-lg w-100 py-3">
                                    <i class="bi bi-plus-circle"></i><br>
                                    إذن دخول
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="stock_out.php" class="btn btn-danger btn-lg w-100 py-3">
                                    <i class="bi bi-dash-circle"></i><br>
                                    إذن صرف
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="items.php" class="btn btn-primary btn-lg w-100 py-3">
                                    <i class="bi bi-box"></i><br>
                                    إدارة الأصناف
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="btn btn-info btn-lg w-100 py-3">
                                    <i class="bi bi-graph-up"></i><br>
                                    التقارير
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
           </main>    <?php require_once 'footer.php'; ?>
        </div>
    </div>

    <!-- الروابط إلى مكتبات JavaScript -->
    <script>
        $(document).ready(function() {
            // تأثيرات الواجهة
            $('.card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
        });
    </script>
</body>
</html>