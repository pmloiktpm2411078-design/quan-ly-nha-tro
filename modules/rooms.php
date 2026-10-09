<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_code = trim($_POST["room_code"]);
    $room_number = trim($_POST["room_number"]);
    $room_type_id = $_POST["room_type_id"];
    $area_m2 = $_POST["area_m2"];
    $listed_rent = $_POST["listed_rent"];
    $status = $_POST["status"];
    $description = trim($_POST["description"]);

    if (
        $room_code === "" ||
        $room_number === "" ||
        $room_type_id === "" ||
        $area_m2 === "" ||
        $listed_rent === ""
    ) {

        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc.";

    } else {

        $sql = "INSERT INTO rooms
                (
                    room_code,
                    room_number,
                    room_type_id,
                    area_m2,
                    listed_rent,
                    status,
                    description,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssidsss",
            $room_code,
            $room_number,
            $room_type_id,
            $area_m2,
            $listed_rent,
            $status,
            $description
        );

        if ($stmt->execute()) {

            $success = "Thêm phòng thành công!";

            $room_code = "";
            $room_number = "";
            $area_m2 = "";
            $listed_rent = "";
            $description = "";

        } else {

            $error = "Không thể thêm phòng: " . $stmt->error;
        }

        $stmt->close();
    }
}

/*
 * Lấy danh sách loại phòng
 */
$sql_type = "SELECT room_type_id, type_name
             FROM room_types
             ORDER BY type_name ASC";

$result_type = $conn->query($sql_type);

$sql_types = "
    SELECT room_type_id, type_name
    FROM room_types
    ORDER BY room_type_id ASC
";

$result_types = $conn->query($sql_types);


// Xử lý khi bấm thêm phòng
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_code = trim($_POST["room_code"]);
    $room_number = trim($_POST["room_number"]);
    $room_type_id = (int) $_POST["room_type_id"];
    $area_m2 = (float) $_POST["area_m2"];
    $listed_rent = (float) $_POST["listed_rent"];
    $description = trim($_POST["description"]);

    // Phòng mới mặc định AVAILABLE
    $status = "AVAILABLE";

    $sql = "
        INSERT INTO rooms
        (
            room_code,
            room_number,
            room_type_id,
            area_m2,
            listed_rent,
            status,
            description,
            created_at,
            updated_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "ssidsss",
            $room_code,
            $room_number,
            $room_type_id,
            $area_m2,
            $listed_rent,
            $status,
            $description
        );

        if ($stmt->execute()) {

            header("Location: index.php");
            exit;

        } else {

            $error = "Không thể thêm phòng: " . $stmt->error;
        }

        $stmt->close();

    } else {

        $error = "Lỗi SQL: " . $conn->error;
    }
}

?>
