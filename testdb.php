<?php
declare(strict_types=1);

use Platformsh\ConfigReader\Config;

require __DIR__ . '/vendor/autoload.php';

// 1. Khởi tạo đối tượng đọc cấu hình và kiểm tra môi trường
$config = new Config();
if (!$config->isValidPlatform()) {
    die("Not in a Platform.sh/Upsun Environment.");
}

// 2. Lấy thông tin chứng thực CSDL và khởi tạo chuỗi kết nối DSN
$credentials = $config->credentials('database');
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $credentials['host'],
    $credentials['port'],
    $credentials['path']
);

try {
    // 3. Kết nối CSDL bằng PDO
    $conn = new \PDO($dsn, $credentials['username'], $credentials['password'], [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        \PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    // 4. Tự động tạo bảng People nếu chưa tồn tại
    $conn->exec("CREATE TABLE IF NOT EXISTS People (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mssv VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL
    )");

} catch (\Exception $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// 5. Định tuyến (Routing) đơn giản qua query string ?action=
$action = $_GET['action'] ?? 'list';
$errors = [];
$msg = $_GET['msg'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- XỬ LÝ CHỨC NĂNG (POST) ---

// Chức năng THÊM MỚI
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mssv = trim($_POST['mssv'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($mssv === '') $errors[] = "MSSV không được để trống.";
    if ($name === '') $errors[] = "Họ tên không được để trống.";
    if ($email === '') $errors[] = "Email không được để trống.";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO People (mssv, name, email) VALUES (?, ?, ?)");
            $stmt->execute([$mssv, $name, $email]);
            header("Location: testdb.php?action=list&msg=" . urlencode("Thêm dữ liệu thành công!"));
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "MSSV này đã tồn tại trong hệ thống.";
            } else {
                $errors[] = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
}

// Chức năng CẬP NHẬT
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mssv = trim($_POST['mssv'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($mssv === '') $errors[] = "MSSV không được để trống.";
    if ($name === '') $errors[] = "Họ tên không được để trống.";
    if ($email === '') $errors[] = "Email không được để trống.";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE People SET mssv = ?, name = ?, email = ? WHERE id = ?");
            $stmt->execute([$mssv, $name, $email, $id]);
            header("Location: testdb.php?action=list&msg=" . urlencode("Cập nhật dữ liệu thành công!"));
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "MSSV này đã bị trùng với sinh viên khác.";
            } else {
                $errors[] = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
}

// Chức năng XÓA
if ($action === 'delete') {
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM People WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: testdb.php?action=list&msg=" . urlencode("Xóa dữ liệu thành công!"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sinh viên - HUỲNH VĂN HIỂU</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
            color: #333e48;
        }
        .main-container { 
            max-width: 1140px; 
            margin: 50px auto; 
            padding: 0 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.15);
        }
        .card-custom { 
            background: #ffffff; 
            padding: 35px; 
            border-radius: 16px; 
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); 
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            background-color: #f8f9fa !important;
            color: #5c6873;
            border-bottom: 2px solid #e4e7ea;
            padding: 15px 20px;
        }
        .table td {
            padding: 15px 20px;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .form-label {
            font-weight: 500;
            color: #4f5d73;
            margin-bottom: 8px;
        }
        .form-control {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.25rem rgba(42, 82, 152, 0.15);
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 0.85rem;
        }
        .btn-success { background-color: #2e7d32; border: none; }
        .btn-success:hover { background-color: #1b5e20; }
        .btn-primary { background-color: #1a73e8; border: none; }
        .btn-primary:hover { background-color: #1557b0; }
        .btn-action-delete {
            background-color: #fee2e2;
            color: #dc2626;
            border: none;
        }
        .btn-action-delete:hover {
            background-color: #dc2626;
            color: white;
        }
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }
    </style>
</head>
<body>

<div class="main-container">

    <?php if ($msg !== ''): ?>
        <div class="alert alert-success shadow-sm d-flex align-items-center mb-4" role="alert">
            <svg class="bi flex-shrink-0 me-2" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            <div><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4" role="alert">
            <div class="fw-bold mb-1">Vui lòng kiểm tra lại dữ liệu:</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?>
            </ul>
        </div>
    <?php endif; ?>


    <?php if ($action === 'list'): 
        $stmt = $conn->query("SELECT * FROM People ORDER BY id ASC");
        $people = $stmt->fetchAll();
    ?>
        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="text-uppercase tracking-wider opacity-75 small fw-bold">Lớp: DC24V7K011</span>
                <h1 class="fw-bold h3 mb-0 mt-1">Hệ Thống Quản Lý Sinh Viên</h1>
                <p class="mb-0 opacity-75 small mt-1">Sinh viên thực hiện: HUỲNH VĂN HIỂU (Table: People)</p>
            </div>
            <div>
                <a href="testdb.php?action=add" class="btn btn-success d-inline-flex align-items-center gap-2 shadow-sm">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Thêm sinh viên mới
                </a>
            </div>
        </div>
        
        <div class="card-custom p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="80" class="text-center">ID</th>
                            <th width="150">MSSV</th>
                            <th>Họ và tên</th>
                            <th>Địa chỉ Email</th>
                            <th width="180" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($people)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <p class="mb-0 fs-5">Chưa có dữ liệu sinh viên nào được ghi nhận.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($people as $person): ?>
                                <tr>
                                    <td class="text-center fw-bold text-secondary"><?php echo $person['id']; ?></td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1.5 font-monospace fs-6"><?php echo htmlspecialchars($person['mssv']); ?></span></td>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($person['name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($person['email']); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="testdb.php?action=edit&id=<?php echo $person['id']; ?>" class="btn btn-primary btn-sm">Sửa</a>
                                            <a href="testdb.php?action=delete&id=<?php echo $person['id']; ?>" class="btn btn-action-delete btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này khỏi hệ thống?');">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    <?php elseif ($action === 'add'): ?>
        <div class="mb-4">
            <a href="testdb.php?action=list" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary fw-medium">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Quay lại danh sách chính
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-6 col-md-8 mx-auto">
                <div class="card-custom">
                    <h2 class="fw-bold h4 text-dark mb-4">Thêm hồ sơ sinh viên mới</h2>
                    <form action="testdb.php?action=add" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mã số sinh viên (MSSV)</label>
                            <input type="text" name="mssv" class="form-control" placeholder="Ví dụ: B2001234" value="<?php echo htmlspecialchars($_POST['mssv'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="name" class="form-control" placeholder="Nhập đầy đủ họ tên" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Địa chỉ Email</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2.5">Lưu thông tin</button>
                    </form>
                </div>
            </div>
        </div>


    <?php elseif ($action === 'edit' && $id > 0): 
        $stmt = $conn->prepare("SELECT * FROM People WHERE id = ?");
        $stmt->execute([$id]);
        $person = $stmt->fetch();
        if (!$person) { header("Location: testdb.php"); exit; }
    ?>
        <div class="mb-4">
            <a href="testdb.php?action=list" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary fw-medium">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Quay lại danh sách chính
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-6 col-md-8 mx-auto">
                <div class="card-custom">
                    <h2 class="fw-bold h4 text-dark mb-1">Cập nhật hồ sơ sinh viên</h2>
                    <p class="text-muted small mb-4">Mã số bản ghi trong hệ thống (ID): <?php echo $person['id']; ?></p>
                    
                    <form action="testdb.php?action=edit&id=<?php echo $person['id']; ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mã số sinh viên (MSSV)</label>
                            <input type="text" name="mssv" class="form-control" value="<?php echo htmlspecialchars($_POST['mssv'] ?? $person['mssv']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $person['name']); ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Địa chỉ Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? $person['email']); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2.5">Cập nhật thay đổi</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>