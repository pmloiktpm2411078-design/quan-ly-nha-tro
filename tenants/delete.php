<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";


// Kiểm tra ID người thuê
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$tenant_id = (int) $_GET["id"];


// Kiểm tra người thuê có tồn tại không
$sql = "
    SELECT tenant_id, full_name
    FROM tenants
    WHERE tenant_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    echo "<h2>Không tìm thấy người thuê</h2>";
    echo '<p><a href="index.php">← Quay lại</a></p>';

    exit;
}

$tenant = $result->fetch_assoc();

$stmt->close();


// Xóa người thuê
$sql_delete = "
    DELETE FROM tenants
    WHERE tenant_id = ?
";

$stmt_delete = $conn->prepare($sql_delete);

if ($stmt_delete) {

    $stmt_delete->bind_param("i", $tenant_id);

    if ($stmt_delete->execute()) {

        header("Location: index.php");
        exit;

    } else {

        echo "<h2>Không thể xóa người thuê</h2>";

        echo "<p>";
        echo htmlspecialchars($stmt_delete->error);
        echo "</p>";

        echo '<p><a href="index.php">← Quay lại danh sách</a></p>';
    }

    $stmt_delete->close();

} else {

    echo "<h2>Lỗi SQL</h2>";

    echo "<p>";
    echo htmlspecialchars($conn->error);
    echo "</p>";

    echo '<p><a href="index.php">← Quay lại danh sách</a></p>';
}

$conn->close();

?>