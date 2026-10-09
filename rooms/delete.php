<?php

session_start();

require_once "../db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}


// Kiểm tra ID phòng
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$room_id = (int) $_GET["id"];


// Lấy trạng thái phòng
$sql = "
    SELECT room_id, room_code, status
    FROM rooms
    WHERE room_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$room = $result->fetch_assoc();

$stmt->close();


// Không cho xóa phòng đang HOLD hoặc OCCUPIED
if ($room["status"] !== "AVAILABLE") {

    echo "<h2>Không thể xóa phòng</h2>";

    echo "<p>";
    echo "Phòng <strong>" .
        htmlspecialchars($room["room_code"]) .
        "</strong> hiện đang ở trạng thái <strong>" .
        htmlspecialchars($room["status"]) .
        "</strong>.";
    echo "</p>";

    echo "<p>";
    echo "Chỉ có thể xóa phòng đang ở trạng thái AVAILABLE.";
    echo "</p>";

    echo '<p><a href="index.php">← Quay lại danh sách phòng</a></p>';

    exit;
}


// Xóa phòng
$sql_delete = "
    DELETE FROM rooms
    WHERE room_id = ?
    AND status = 'AVAILABLE'
";

$stmt_delete = $conn->prepare($sql_delete);

if ($stmt_delete) {

    $stmt_delete->bind_param("i", $room_id);

    if ($stmt_delete->execute()) {

        header("Location: index.php");
        exit;

    } else {

        echo "<h2>Không thể xóa phòng</h2>";

        echo "<p>";
        echo htmlspecialchars($stmt_delete->error);
        echo "</p>";

        echo '<p><a href="index.php">← Quay lại</a></p>';
    }

    $stmt_delete->close();

} else {

    echo "<h2>Lỗi SQL</h2>";

    echo "<p>";
    echo htmlspecialchars($conn->error);
    echo "</p>";

    echo '<p><a href="index.php">← Quay lại</a></p>';
}

?>