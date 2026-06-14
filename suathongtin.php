<?php
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
 ?>