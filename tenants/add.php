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

    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $identity_number = trim($_POST["identity_number"]);
    $date_of_birth = $_POST["date_of_birth"];
    $address = trim($_POST["address"]);
    $contact_info = trim($_POST["contact_info"]);

    if ($full_name === "") {

        $error = "Vui lòng nhập họ tên người thuê.";

    } else {

        $sql = "INSERT INTO tenants
                (
                    full_name,
                    phone,
                    identity_number,
                    date_of_birth,
                    address,
                    contact_info,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, NULLIF(?, ''), ?, ?, NOW(), NOW())";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssss",
            $full_name,
            $phone,
            $identity_number,
            $date_of_birth,
            $address,
            $contact_info
        );

        if ($stmt->execute()) {

            $success = "Thêm người thuê thành công!";

            $full_name = "";
            $phone = "";
            $identity_number = "";
            $date_of_birth = "";
            $address = "";
            $contact_info = "";

        } else {

            $error = "Không thể thêm người thuê: " . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm người thuê</title>

</head>

<body>

    <h1>THÊM NGƯỜI THUÊ</h1>

    <p>
        <a href="index.php">← Quay lại danh sách người thuê</a>
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

            <label>Họ tên người thuê:</label>
            <br>

            <input
                type="text"
                name="full_name"
                value="<?php echo htmlspecialchars($full_name ?? ""); ?>"
                placeholder="Ví dụ: Nguyễn Văn An"
            >

        </p>


        <p>

            <label>Số điện thoại:</label>
            <br>

            <input
                type="text"
                name="phone"
                value="<?php echo htmlspecialchars($phone ?? ""); ?>"
                placeholder="Ví dụ: 0901234567"
            >

        </p>


        <p>

            <label>CCCD/CMND:</label>
            <br>

            <input
                type="text"
                name="identity_number"
                value="<?php echo htmlspecialchars($identity_number ?? ""); ?>"
                placeholder="Ví dụ: 079123456789"
            >

        </p>


        <p>

            <label>Ngày sinh:</label>
            <br>

            <input
                type="date"
                name="date_of_birth"
                value="<?php echo htmlspecialchars($date_of_birth ?? ""); ?>"
            >

        </p>


        <p>

            <label>Địa chỉ:</label>
            <br>

            <input
                type="text"
                name="address"
                value="<?php echo htmlspecialchars($address ?? ""); ?>"
                placeholder="Nhập địa chỉ"
            >

        </p>


        <p>

            <label>Thông tin liên hệ:</label>
            <br>

            <textarea
                name="contact_info"
                rows="4"
                cols="50"
                placeholder="Thông tin liên hệ khác..."
            ><?php echo htmlspecialchars($contact_info ?? ""); ?></textarea>

        </p>


        <button type="submit">
            THÊM NGƯỜI THUÊ
        </button>

    </form>

</body>

</html>

<?php

$conn->close();

?>