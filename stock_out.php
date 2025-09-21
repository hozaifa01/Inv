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
// معالجة إضافة إذن صرف
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_POST['client_id'];
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    $notes = $_POST['notes'];
    
    // التحقق من توفر الكمية
    $stmt = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item['quantity'] < $quantity) {
        $error = "الكمية المطلوبة غير متوفرة في المخزن";
    } else {
        // إضافة الحركة
        $stmt = $pdo->prepare("INSERT INTO inventory_transactions (item_id, transaction_type, quantity, related_id, related_type, notes) VALUES (?, 'out', ?, ?, 'client', ?)");
        $stmt->execute([$item_id, $quantity, $client_id, $notes]);
        
        // تحديث كمية الصنف
        $stmt = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $item_id]);
        
        $message = "تم إضافة إذن الصرف بنجاح";
        header("Location: stock_out.php?message=" . urlencode($message));
        exit;
    }
}

// جلب العملاء
$stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الأصناف
$stmt = $pdo->query("SELECT * FROM items ORDER BY name");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب سجل إذون الصرف
$stmt = $pdo->query("
    SELECT it.*, i.name as item_name, c.name as client_name 
    FROM inventory_transactions it 
    JOIN items i ON it.item_id = i.id 
    JOIN clients c ON it.related_id = c.id 
    WHERE it.transaction_type = 'out' 
    ORDER BY it.transaction_date DESC
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?> -
    إذن صرف</title>
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
                    <h1>إذن صرف المواد</h1>
                    <p>تسجيل المواد الصادرة من المخزن للعملاء</p>
                </div>

                <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_GET['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- نموذج إذن الصرف -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5>تسجيل إذن صرف جديد</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">العميل</label>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">-- اختر العميل --</option>
                                            <?php foreach ($clients as $client): ?>
                                            <option value="<?php echo $client['id']; ?>">
                                                <?php echo $client['name']; ?> (<?php echo $client['type'] == 'wholesale' ? 'جملة' : 'تجزئة'; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="item_id" class="form-label">الصنف</label>
                                        <select class="form-select" id="item_id" name="item_id" required>
                                            <option value="">-- اختر الصنف --</option>
                                            <?php foreach ($items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>" data-quantity="<?php echo $item['quantity']; ?>">
                                                <?php echo $item['name']; ?> (<?php echo $item['quantity']; ?>  <?php
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
                                        <div class="form-text">الكمية المتاحة: <span id="availableQuantity">0</span></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">ملاحظات</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">تسجيل الصرف</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- سجل إذون الصرف -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5>سجل إذون الصرف</h5>
                            </div>
                            <div class="card-body">
<table class="table table-striped" id="stockOutTable">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>العميل</th>
            <th>الصنف</th>
            <th>الكمية</th>
            <th>سعر البيع</th>
            <th>سعر الشراء</th>
            <th>الإيراد</th>
            <th>التكلفة</th>
            <th>ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total_revenue = 0;
        $total_cost = 0;

        foreach ($transactions as $transaction):
            $item_name = $transaction['item_name'];
            $quantity = $transaction['quantity'];

            // البحث عن سعر البيع وسعر الشراء من جدول المواد
            $sale_price = 0;
            $purchase_price = 0;

            foreach ($items as $item) {
                if ($item['name'] == $item_name) {
                    $sale_price = $item['sale_price'];
                    $purchase_price = isset($item['purchase_price']) ? $item['purchase_price'] : 0;
                    break;
                }
            }

            $revenue = $quantity * $sale_price;
            $cost = $quantity * $purchase_price;

            $total_revenue += $revenue;
            $total_cost += $cost;
        ?>
        <tr>
            <td><?php echo $transaction['transaction_date']; ?></td>
            <td><?php echo $transaction['client_name']; ?></td>
            <td><?php echo $item_name; ?></td>
            <td><?php echo $quantity; ?></td>
            <td><?php echo $sale_price; ?> جنيه</td>
            <td><?php echo $purchase_price; ?> جنيه</td>
            <td><?php echo $revenue; ?> جنيه</td>
            <td><?php echo $cost; ?> جنيه</td>
            <td><?php echo $transaction['notes']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="table-info fw-bold">
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
<th></th>
        </tr>
    </tfoot>
</table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>    <?php include 'footer.php'; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // تحديث الكمية المتاحة عند تغيير الصنف
            $('#item_id').change(function() {
                var selectedOption = $(this).find('option:selected');
                var availableQuantity = selectedOption.data('quantity');
                $('#availableQuantity').text(availableQuantity);
                $('#quantity').attr('max', availableQuantity);
            });
        });
    </script>
    <script>
        $('#stockOutTable').DataTable({
        responsive: true,
        serverSide: false,
        processing:true,
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
        stateSave: true,
        pageLength: 10,
  lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "الكل"] ],
        dom: 'Bfrtip,<"buttom"l>rt<"bottom"ip><"clear">',
        buttons: [
            'copy',
            {
                extend: 'excelHtml5',
                text: 'Excel',
                filename: 'inventory_system_Excel',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                filename: 'inventory_system_PDF',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function (doc) {
                    doc.defaultStyle.font = 'Amiri-Regular';
                    doc.defaultStyle.alignment = 'right';
                    doc.styles.tableHeader.alignment = 'right';
                    
                    if (doc.content[1] && doc.content[1].table) {
                        if (doc.content[1].table.body.length > 0) {
                            doc.content[1].table.body[0].reverse();
                        }
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
            select: {
    rows: {
      _: "تم تحديد %d صف",
      0: "لم يتم تحديد أي صف",
      1: "تم تحديد صف واحد"
}
},
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
        },
        // إضافة دالة الجمع هنا
        footerCallback: function (row, data, start, end, display) {
    var api = this.api();

    // تحقق من أن الجدول يحتوي على بيانات
    if (api.data().count() === 0) {
        // امسح محتوى الفوتر أو ضع رسالة مخصصة
        api.columns().every(function () {
            $(api.column(this.index()).footer()).html('<strong>—</strong>');
});
        return; // أوقف التنفيذ
}

    var intVal = function (i) {
        if (typeof i === 'string') {
            return parseFloat(i.replace(/[^\d.-]/g, '')) || 0;
} else if (typeof i === 'number') {
            return i;
}
        return 0;
};

    var columnsToSum = [4,5,6,7];

    columnsToSum.forEach(function (colIndex) {
        var selectedRows = api.rows({ selected: true}).data();
        var pageData = api.column(colIndex, { page: 'current'}).data();

        var selectedTotal = 0;
        if (selectedRows.length> 0) {
            selectedRows.each(function (rowData) {
                if (Array.isArray(rowData) && rowData.length> colIndex) {
                    selectedTotal += intVal(rowData[colIndex]);
}
});
}

        var pageTotal = 0;
        if (pageData.length> 0) {
            pageData.each(function (val) {
                pageTotal += intVal(val);
});
}

        var formattedSelected = selectedTotal.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
});

        var formattedPage = pageTotal.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
});

        $(api.column(colIndex).footer()).html(
            `<strong>المحدد: ${formattedSelected}   ج <br>الصفحة: ${formattedPage}  ج </strong>`
);
});
}
    });
var table = $('#stockOutTable').DataTable();

table.on('select deselect', function () {
    table.draw(); // يعيد تنفيذ footerCallback تلقائيًا
});
    </script>
</body>
</html>