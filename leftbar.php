<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
require_once 'dbconnection.php';
if (!isset($_SESSION['aid']) ||!filter_var($_SESSION['aid'], FILTER_VALIDATE_INT)) {
  header('Location: logout.php');
  exit();
}
// إنشاء رمز CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$aid = (int) $_SESSION['aid'];
$stmt = $con->prepare("SELECT level, photo,FullName FROM tbl_login WHERE id =?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$user_level = (int) $row['level'];
$photo =!empty($row['photo'])? htmlspecialchars($row['photo'], ENT_QUOTES, 'UTF-8'): 'default.png';
?>
<nav class="col-md-3 col-lg-2 d-md-block bg-custom">
    <div class="logo mb-4">
        <i class="bi bi-shop"></i> <?php echo htmlspecialchars($site["title"], ENT_QUOTES, 'UTF-8');?>  
    </div>
         <h6><?php echo htmlspecialchars($site["description"], ENT_QUOTES, 'UTF-8');?> </h6>
    <?= "أهلاً وسهلاً <span style='color:red;'> ". htmlspecialchars($row['FullName'], ENT_QUOTES,
    'UTF-8') . " </span> طاب يومك يا"
    ?>
    <?php
    if ($user_level === 1) echo "موظف إستقبالنا ";
    elseif ($user_level === 2) echo "محاسبنا";
    elseif ($user_level === 99) echo "مديرنا";
$aid = $_SESSION['aid'];
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
    ?>
 <hr>   <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i> لوحة التحكم
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'items.php' ? 'active' : ''; ?>" href="items.php">
                    <i class="bi bi-boxes"></i> إدارة الأصناف
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'stock_in.php' ? 'active' : ''; ?>" href="stock_in.php">
                    <i class="bi bi-arrow-down-circle"></i> إذن دخول
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'stock_out.php' ? 'active' : ''; ?>" href="stock_out.php">
                    <i class="bi bi-arrow-up-circle"></i> إذن صرف
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'suppilers.php' ? 'active' : ''; ?>" href="suppilers.php">
                    <i class="bi bi-people"></i> الموردين
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
                    <i class="bi bi-person-badge"></i> العملاء
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="bi bi-graph-up"></i> التقارير
                </a>
            </li>
        </ul>
        <hr>
          <div class="dropdown mt-3">
    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
      <img src="uploads/<?= $photo ?>" alt="صورة المستخدم" width="32" height="32" class="rounded-circle me-2">
      <strong><?= htmlspecialchars($_SESSION['login'], ENT_QUOTES, 'UTF-8') ?></strong>
    </a>
    <ul class="dropdown-menu text-small shadow">
      <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
      <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>الإعدادات</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>تسجيل خروج</a></li>
    </ul>
  </div>
    </div>
</nav>