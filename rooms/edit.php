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


// Lấy thông tin phòng
$sql = "
    SELECT *
    FROM rooms
    WHERE room_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$room = $result->fetch_assoc();

$stmt->close();


// Lấy danh sách loại phòng
$sql_types = "
    SELECT room_type_id, type_name
    FROM room_types
    ORDER BY room_type_id ASC
";

$result_types = $conn->query($sql_types);


// Xử lý cập nhật
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_code = trim($_POST["room_code"]);
    $room_number = trim($_POST["room_number"]);
    $room_type_id = (int) $_POST["room_type_id"];
    $area_m2 = (float) $_POST["area_m2"];
    $listed_rent = (float) $_POST["listed_rent"];
    $description = trim($_POST["description"]);


    $sql_update = "
        UPDATE rooms
        SET
            room_code = ?,
            room_number = ?,
            room_type_id = ?,
            area_m2 = ?,
            listed_rent = ?,
            description = ?,
            updated_at = NOW()
        WHERE room_id = ?
    ";

    $stmt_update = $conn->prepare($sql_update);

    if ($stmt_update) {

        $stmt_update->bind_param(
            "ssidssi",
            $room_code,
            $room_number,
            $room_type_id,
            $area_m2,
            $listed_rent,
            $description,
            $room_id
        );

        if ($stmt_update->execute()) {

            header("Location: index.php");
            exit;

        } else {

            $error = "Không thể cập nhật phòng: " . $stmt_update->error;
        }

        $stmt_update->close();

    } else {

        $error = "Lỗi SQL: " . $conn->error;
    }
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa phòng</title>

</head>

<body>

    <h1>SỬA PHÒNG</h1>

    <p>
        <a href="index.php">
            ← Quay lại danh sách phòng
        </a>
    </p>


    <?php if (isset($error)): ?>

        <p>
            <strong>
                <?php echo htmlspecialchars($error); ?>
            </strong>
        </p>

    <?php endif; ?>


    <form method="POST">

        <p>

            <label>
                Mã phòng:
            </label>

            <br>

            <input
                type="text"
                name="room_code"
                value="<?php echo htmlspecialchars($room["room_code"]); ?>"
                required
            >

        </p>


        <p>

            <label>
                Số phòng:
            </label>

            <br>

            <input
                type="text"
                name="room_number"
                value="<?php echo htmlspecialchars($room["room_number"]); ?>"
                required
            >

        </p>


        <p>

            <label>
                Loại phòng:
            </label>

            <br>

            <select name="room_type_id" required>

                <?php while ($type = $result_types->fetch_assoc()): ?>

                    <option
                        value="<?php echo $type["room_type_id"]; ?>"
                        <?php
                        if ($type["room_type_id"] == $room["room_type_id"]) {
                            echo "selected";
                        }
                        ?>
                    >

                        <?php echo htmlspecialchars($type["type_name"]); ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </p>


        <p>

            <label>
                Diện tích (m²):
            </label>

            <br>

            <input
                type="number"
                name="area_m2"
                step="0.01"
                min="0"
                value="<?php echo htmlspecialchars($room["area_m2"]); ?>"
                required
            >

        </p>


        <p>

            <label>
                Giá thuê:
            </label>

            <br>

            <input
                type="number"
                name="listed_rent"
                step="1000"
                min="0"
                value="<?php echo htmlspecialchars($room["listed_rent"]); ?>"
                required
            >

        </p>


        <p>

            <label>
                Trạng thái:
            </label>

            <br>

            <strong>
                <?php echo htmlspecialchars($room["status"]); ?>
            </strong>

        </p>


        <p>

            <label>
                Mô tả:
            </label>

            <br>

            <textarea
                name="description"
                rows="4"
                cols="50"
            ><?php echo htmlspecialchars($room["description"] ?? ""); ?></textarea>

        </p>


        <p>

            <button type="submit">
                LƯU THAY ĐỔI
            </button>

            <a href="index.php">
                Hủy
            </a>

        </p>

    </form>

</body>

</html>