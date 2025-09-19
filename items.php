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


// معالجة حذف الصنف
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: items.php?message=تم حذف الصنف بنجاح");
    exit;
}

// معالجة إضافة/تعديل الصنف
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $min_quantity = $_POST['min_quantity'];
    $expiry_date = $_POST['expiry_date'];
    $purchase_price = $_POST['purchase_price'];
    $sale_price = $_POST['sale_price'];
    $supplier_id = $_POST['supplier_id'];
    
    if (isset($_POST['item_id'])) {
        // تحديث الصنف
        $id = $_POST['item_id'];
        $stmt = $pdo->prepare("UPDATE items SET name=?, category=?, quantity=?, unit=?, min_quantity=?, expiry_date=?, purchase_price=?, sale_price=?, supplier_id=? WHERE id=?");
        $stmt->execute([$name, $category, $quantity, $unit, $min_quantity, $expiry_date, $purchase_price, $sale_price, $supplier_id, $id]);
        $message = "تم تحديث الصنف بنجاح";
    } else {
        // إضافة صنف جديد
        $stmt = $pdo->prepare("INSERT INTO items (name, category, quantity, unit, min_quantity, expiry_date, purchase_price, sale_price, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $quantity, $unit, $min_quantity, $expiry_date, $purchase_price, $sale_price, $supplier_id]);
        $message = "تم إضافة الصنف بنجاح";
    }
    
    header("Location: items.php?message=" . urlencode($message));
    exit;
}

// جلب بيانات الأصناف
$stmt = $pdo->query("SELECT i.*, s.name as supplier_name FROM items i LEFT JOIN suppliers s ON i.supplier_id = s.id ORDER BY i.name");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الموردين للقائمة المنسدلة
$stmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب بيانات صنف للتعديل إذا كان هناك معرف
$edit_item = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأصناف - نظام المخازن</title>
    <?php include_once "header.php";?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <?php include 'sidebar.php'; ?>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="dashboard-header">
                    <h1>إدارة الأصناف</h1>
                    <p>إضافة وتعديل وحذف الأصناف في المخزن</p>
                </div>

                <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_GET['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- نموذج إضافة/تعديل صنف -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5><?php echo $edit_item ? 'تعديل الصنف' : 'إضافة صنف جديد'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_item): ?>
                                    <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">اسم الصنف</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo $edit_item ? $edit_item['name'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category" class="form-label">التصنيف</label>
                                                   <select name="category" id="category" class="form-control" onchange="togglePaymentFields()"         value="<?php echo $edit_item ? $edit_item['category'] : ''; ?>"> required>
                                            <option value="1">مواد غذائية</option>
                                            <option value="2">محروقات</option>
                                        </select>
                                       
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="quantity" class="form-label">الكمية</label>
                                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                                       value="<?php echo $edit_item ? $edit_item['quantity'] : '0'; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="unit" class="form-label">الوحدة</label>                                                             <select name="unit" id="unit" class="form-control" onchange="togglePaymentFields()"         value="<?php echo $edit_item ? $edit_item['unit'] : ''; ?>" required>
                                            <option value="1">كيلو </option>
                                            <option value="2">جوال 25 ك </option>
                                            <option value="3">كرتونة</option>
                                        </select>                                  </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="min_quantity" class="form-label">الحد الأدنى للكمية</label>
                                        <input type="number" class="form-control" id="min_quantity" name="min_quantity" 
                                               value="<?php echo $edit_item ? $edit_item['min_quantity'] : '10'; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="expiry_date" class="form-label">تاريخ انتهاء الصلاحية</label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                               value="<?php echo $edit_item ? $edit_item['expiry_date'] : ''; ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="purchase_price" class="form-label">سعر الشراء</label>
                                                <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                                                       value="<?php echo $edit_item ? $edit_item['purchase_price'] : '0'; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sale_price" class="form-label">سعر البيع</label>
                                                <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" 
                                                       value="<?php echo $edit_item ? $edit_item['sale_price'] : '0'; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">المورد</label>
                                        <select class="form-select" id="supplier_id" name="supplier_id">
                                            <option value="">-- اختر المورد --</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['id']; ?>" 
                                                <?php if ($edit_item && $edit_item['supplier_id'] == $supplier['id']) echo 'selected'; ?>>
                                                <?php echo $supplier['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary"><?php echo $edit_item ? 'تحديث' : 'إضافة'; ?></button>
                                    
                                    <?php if ($edit_item): ?>
                                    <a href="items.php" class="btn btn-secondary">إلغاء</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- جدول الأصناف -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5>قائمة الأصناف</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
<?php
// دوال لحساب الكمية المستلمة والمصروفة لكل صنف
function getTotalReceived($item_name, $stockInTransactions) {
    $total = 0;
    foreach ($stockInTransactions as $trans) {
        if ($trans['item_name'] == $item_name) {
            $total += $trans['quantity'];
        }
    }
    return $total;
}

function getTotalIssued($item_name, $stockOutTransactions) {
    $total = 0;
    foreach ($stockOutTransactions as $trans) {
        if ($trans['item_name'] == $item_name) {
            $total += $trans['quantity'];
        }
    }
    return $total;
}
?>

<table class="table table-striped" id="itemsTable">
    <thead>
        <tr>
            <th>اسم الصنف</th>
            <th>التصنيف</th>
            <th>الكمية الحالية</th>
            <th>الكمية بعد التحديث</th>
            <th>سعر الشراء</th>
            <th>القيمة الإجمالية</th>
            <th>سعر التكلفة المتوسط</th>
            <th>سعر البيع</th>
            <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <?php
                $item_name = $item['name'];
                $initial_quantity = $item['quantity'];
                $purchase_price = isset($item['purchase_price']) ? $item['purchase_price'] : 0;

                $received_qty = getTotalReceived($item_name, $stockInTransactions);
                $issued_qty = getTotalIssued($item_name, $stockOutTransactions);

                $new_quantity = $initial_quantity + $received_qty - $issued_qty;
                $new_value = ($initial_quantity * $purchase_price) + ($received_qty * $purchase_price) - ($issued_qty * $purchase_price);
                $average_cost = ($new_quantity > 0) ? round($new_value / $new_quantity, 2) : 0;
            ?>
            <tr>
                <td><?php echo $item_name; ?></td>
                <td>
                    <?php
                        if ($item['category'] == 1) echo "مواد غذائية";
                        elseif ($item['category'] == 2) echo "محروقات";
                    ?>
                </td>
                <td>
                    <?php echo $initial_quantity; ?>
                    <?php
                        if ($item['unit'] == 1) echo " كيلو";
                        elseif ($item['unit'] == 2) echo " جوال 25";
                        elseif ($item['unit'] == 3) echo " كرتونة";
                    ?>
                    <?php if ($initial_quantity <= $item['min_quantity']): ?>
                        <span class="badge bg-warning">كمية منخفضة</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $new_quantity; ?></td>
                <td><?php echo $purchase_price; ?> جنيه</td>
                <td><?php echo $new_value; ?> جنيه</td>
                <td><?php echo $average_cost; ?> جنيه</td>
                <td><?php echo $item['sale_price']; ?> جنيه</td>
                <td>
                    <a href="items.php?edit_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <a href="items.php?delete_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('هل أنت متأكد من حذف هذا الصنف؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>      </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>    <?php include 'footer.php'; ?>
            
        </div>
    </div>
        <script>
        $('#itemsTable').DataTable({
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