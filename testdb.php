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
        mssv VARCHAR(20) NOT NULL,
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
        $stmt = $conn->prepare("INSERT INTO People (mssv, name, email) VALUES (?, ?, ?)");
        $stmt->execute([$mssv, $name, $email]);
        header("Location: testdb.php?action=list&msg=" . urlencode("Thêm dữ liệu thành công!"));
        exit;
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
        $stmt = $conn->prepare("UPDATE People SET mssv = ?, name = ?, email = ? WHERE id = ?");
        $stmt->execute([$mssv, $name, $email, $id]);
        header("Location: testdb.php?action=list&msg=" . urlencode("Cập nhật dữ liệu thành công!"));
        exit;
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


// --- GIAO DIỆN HIỂN THỊ (HTML) ---
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 40px; font-family: sans-serif; }
        .container-custom { max-width: 1100px; margin: 0 auto; }
        .form-card { max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container-custom">

    <?php if ($msg !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error) echo htmlspecialchars($error) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): 
        // Lấy danh sách hiển thị
        $stmt = $conn->query("SELECT * FROM People ORDER BY id ASC");
        $people = $stmt->fetchAll();
    ?>
        <h1 class="fw-bold mb-4">Hệ Thống Quản Lý Sinh Viên DC24V7K011 HUỲNH VĂN HIỂU (People)</h1>
        <a href="testdb.php?action=add" class="btn btn-success mb-3">+ Thêm mới</a>
        
        <table class="table table-bordered bg-white align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>MSSV</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($people)): ?>
                    <tr><td colspan="5" class="text-center">Chưa có dữ liệu sinh viên nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($people as $person): ?>
                        <tr>
                            <td><?php echo $person['id']; ?></td>
                            <td><?php echo htmlspecialchars($person['mssv']); ?></td>
                            <td><?php echo htmlspecialchars($person['name']); ?></td>
                            <td><?php echo htmlspecialchars($person['email']); ?></td>
                            <td>
                                <a href="testdb.php?action=edit&id=<?php echo $person['id']; ?>" class="btn btn-primary btn-sm">Sửa</a>
                                <a href="testdb.php?action=delete&id=<?php echo $person['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa bản ghi này?');">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($action === 'add'): ?>
        <a href="testdb.php?action=list" class="text-decoration-none d-block mb-3">← Quay lại danh sách</a>
        <h1 class="fw-bold mb-4">Thêm mới</h1>
        
        <div class="form-card">
            <form action="testdb.php?action=add" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">MSSV</label>
                    <input type="text" name="mssv" class="form-control" value="<?php echo htmlspecialchars($_POST['mssv'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Họ tên</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-success">Lưu</button>
            </form>
        </div>

    <?php elseif ($action === 'edit' && $id > 0): 
        // Lấy thông tin bản ghi hiện tại để sửa
        $stmt = $conn->prepare("SELECT * FROM People WHERE id = ?");
        $stmt->execute([$id]);
        $person = $stmt->fetch();
        if (!$person) { header("Location: testdb.php"); exit; }
    ?>
        <a href="testdb.php?action=list" class="text-decoration-none d-block mb-3">← Quay lại danh sách</a>
        <h1 class="fw-bold mb-4">Sửa thông tin (ID: <?php echo $person['id']; ?>)</h1>
        
        <div class="form-card">
            <form action="testdb.php?action=edit&id=<?php echo $person['id']; ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">MSSV</label>
                    <input type="text" name="mssv" class="form-control" value="<?php echo htmlspecialchars($person['mssv']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Họ tên</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($person['name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($person['email']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </form>
        </div>
    <?php endif; ?>

</div>
</body>
</html>