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


?>
