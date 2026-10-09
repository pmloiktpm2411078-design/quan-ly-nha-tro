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

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm phòng</title>

</head>

<body>

    <h1>THÊM PHÒNG</h1>

    <p>
        <a href="index.php">← Quay lại danh sách phòng</a>
    </p>

    <hr>

    <?php if ($error !== ""): ?>

        <p style="color:red;">
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <p style="color:green;">
            <?php echo htmlspecialchars($success); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <p>

            <label>Mã phòng:</label>
            <br>

            <input
                type="text"
                name="room_code"
                placeholder="Ví dụ: P102"
                value="<?php echo htmlspecialchars($room_code ?? ""); ?>"
            >

        </p>


        <p>

            <label>Số phòng:</label>
            <br>

            <input
                type="text"
                name="room_number"
                placeholder="Ví dụ: 102"
                value="<?php echo htmlspecialchars($room_number ?? ""); ?>"
            >

        </p>


        <p>

            <label>Loại phòng:</label>
            <br>

            <select name="room_type_id">

                <option value="">-- Chọn loại phòng --</option>

                <?php while ($type = $result_type->fetch_assoc()): ?>

                    <option
                        value="<?php echo $type["room_type_id"]; ?>"
                    >

                        <?php echo htmlspecialchars($type["type_name"]); ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </p>


        <p>

            <label>Diện tích (m²):</label>
            <br>

            <input
                type="number"
                step="0.01"
                name="area_m2"
                placeholder="Ví dụ: 20"
                value="<?php echo htmlspecialchars($area_m2 ?? ""); ?>"
            >

        </p>


        <p>

            <label>Giá thuê:</label>
            <br>

            <input
                type="number"
                step="0.01"
                name="listed_rent"
                placeholder="Ví dụ: 3000000"
                value="<?php echo htmlspecialchars($listed_rent ?? ""); ?>"
            >

        </p>


        <p>

            <label>Trạng thái:</label>
            <br>

            <select name="status">

                <option value="AVAILABLE">
                    Còn trống
                </option>

                <option value="HELD">
                    Đang giữ
                </option>

                <option value="OCCUPIED">
                    Đang thuê
                </option>

                <option value="MAINTENANCE">
                    Bảo trì
                </option>

            </select>

        </p>


        <p>

            <label>Mô tả:</label>
            <br>

            <textarea
                name="description"
                rows="5"
                cols="50"
                placeholder="Nhập mô tả phòng..."
            ><?php echo htmlspecialchars($description ?? ""); ?></textarea>

        </p>


        <button type="submit">
            THÊM PHÒNG
        </button>

    </form>

</body>

</html>

<?php

$conn->close();

?>