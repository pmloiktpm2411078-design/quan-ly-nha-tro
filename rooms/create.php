<?php

session_start();

require_once "../db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}


// Lấy danh sách loại phòng
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

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm phòng</title>

</head>

<body>

    <h1>THÊM PHÒNG</h1>

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
                placeholder="Ví dụ: P103"
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
                placeholder="Ví dụ: 103"
                required
            >

        </p>


        <p>

            <label>
                Loại phòng:
            </label>

            <br>

            <select name="room_type_id" required>

                <option value="">
                    -- Chọn loại phòng --
                </option>

                <?php while ($type = $result_types->fetch_assoc()): ?>

                    <option value="<?php echo $type["room_type_id"]; ?>">

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
                required
            >

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
                placeholder="Ví dụ: Phòng có cửa sổ, nhà vệ sinh riêng..."
            ></textarea>

        </p>


        <p>

            <button type="submit">
                THÊM PHÒNG
            </button>

            <a href="index.php">
                Hủy
            </a>

        </p>

    </form>

</body>

</html>