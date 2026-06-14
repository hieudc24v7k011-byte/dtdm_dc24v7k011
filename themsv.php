<?php
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
?>