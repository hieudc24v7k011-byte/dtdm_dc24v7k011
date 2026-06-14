<?php
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