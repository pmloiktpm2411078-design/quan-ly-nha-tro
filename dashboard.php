<?php

session_start();

require_once "db.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| TỰ ĐỘNG XỬ LÝ GIỮ PHÒNG HẾT HẠN
|--------------------------------------------------------------------------
|
| ACTIVE + hết hạn
|       ↓
| EXPIRED
|
| Phòng HOLD
|       ↓
| AVAILABLE
|
*/

$sql_expired = "
    SELECT hold_id, room_id
    FROM reservation_holds
    WHERE status = 'ACTIVE'
    AND expires_at <= NOW()
";

$result_expired = $conn->query($sql_expired);

if ($result_expired) {

    while ($hold = $result_expired->fetch_assoc()) {

        $hold_id = $hold["hold_id"];
        $room_id = $hold["room_id"];

        // 1. Cập nhật giữ phòng thành EXPIRED
        $sql_hold = "
            UPDATE reservation_holds
            SET
                status = 'EXPIRED',
                expiry_action = 'RELEASE',
                processed_at = NOW(),
                processed_by = ?
            WHERE hold_id = ?
            AND status = 'ACTIVE'
        ";

        $stmt_hold = $conn->prepare($sql_hold);

        if ($stmt_hold) {

            $processed_by = $_SESSION["user_id"];

            $stmt_hold->bind_param(
                "ii",
                $processed_by,
                $hold_id
            );

            $stmt_hold->execute();

            $stmt_hold->close();
        }


        // 2. Trả phòng từ HOLD về AVAILABLE
        $sql_room = "
            UPDATE rooms
            SET
                status = 'AVAILABLE',
                updated_at = NOW()
            WHERE room_id = ?
            AND status = 'HOLD'
        ";

        $stmt_room = $conn->prepare($sql_room);

        if ($stmt_room) {

            $stmt_room->bind_param(
                "i",
                $room_id
            );

            $stmt_room->execute();

            $stmt_room->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| LẤY THÔNG TIN NGƯỜI ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

$full_name = $_SESSION["full_name"] ?? "";
$role_name = $_SESSION["role_name"] ?? "";
$username = $_SESSION["username"] ?? "";

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Dashboard - Quản lý nhà trọ</title>

</head>

<body>

    <h1>HỆ THỐNG QUẢN LÝ NHÀ TRỌ</h1>

    <p>
        Xin chào:
        <strong>
            <?php echo htmlspecialchars($full_name); ?>
        </strong>
    </p>

    <p>
        Vai trò:
        <strong>
            <?php echo htmlspecialchars($role_name); ?>
        </strong>
    </p>

    <p>
        Tên đăng nhập:
        <?php echo htmlspecialchars($username); ?>
    </p>

    <hr>

    <h2>Quản lý</h2>

    <p>
        <a href="rooms/index.php">
            Quản lý phòng
        </a>
        <br>
        <a href="contracts/index.php">Quản lý hợp đồng</a>
    </p>

    <p>
        <a href