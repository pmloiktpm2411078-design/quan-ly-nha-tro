<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$error = "";
$success = "";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Người thuê không hợp lệ.");
}

$tenant_id = (int) $_GET["id"];

/* Lấy thông tin người thuê */
$sql = "SELECT
            tenant_id,
            full_name,
            phone,
            identity_number,
            date_of_birth,
            address,
            contact_info
        FROM tenants
        WHERE tenant_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Không tìm thấy người thuê.");
}

$tenant = $result->fetch_assoc();

$stmt->close();


/* Xử lý cập nhật */
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

        $sql_update = "UPDATE tenants
                       SET
                           full_name = ?,
                           phone = ?,
                           identity_number = ?,
                           date_of_birth = NULLIF(?, ''),
                           address = ?,
                           contact_info = ?,
                           updated_at = NOW()
                       WHERE tenant_id = ?";

        $stmt = $conn->prepare($sql_update);

        $stmt->bind_param(
            "ssssssi",
            $full_name,
            $phone,
            $identity_number,
            $date_of_birth,
            $address,
            $contact_info,
            $tenant_id
        );

        if ($stmt->execute()) {

            $success = "Cập nhật người thuê thành công!";

            $tenant["full_name"] = $full_name;
            $tenant["phone"] = $phone;
            $tenant["identity_number"] = $identity_number;
            $tenant["date_of_birth"] = $date_of_birth;
            $tenant["address"] = $address;
            $tenant["contact_info"] = $contact_info;

        } else {

            $error = "Không thể cập nhật: " . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa người thuê</title>

</head>

<body>

    <h1>SỬA THÔNG TIN NGƯỜI THUÊ</h1>

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
                value="<?php echo htmlspecialchars($tenant["full_name"]); ?>"
            >

        </p>


        <p>

            <label>Số điện thoại:</label>
            <br>

            <input
                type="text"
                name="phone"
                value="<?php echo htmlspecialchars($tenant["phone"] ?? ""); ?>"
            >

        </p>


        <p>

            <label>CCCD/CMND:</label>
            <br>

            <input
                type="text"
                name="identity_number"
                value="<?php echo htmlspecialchars($tenant["identity_number"] ?? ""); ?>"
            >

        </p>


        <p>

            <label>Ngày sinh:</label>
            <br>

            <input
                type="date"
                name="date_of_birth"
                value="<?php echo htmlspecialchars($tenant["date_of_birth"] ?? ""); ?>"
            >

        </p>


        <p>

            <label>Địa chỉ:</label>
            <br>

            <input
                type="text"
                name="address"
                value="<?php echo htmlspecialchars($tenant["address"] ?? ""); ?>"
            >

        </p>


        <p>

            <label>Thông tin liên hệ:</label>
            <br>

            <textarea
                name="contact_info"
                rows="4"
                cols="50"
            ><?php echo htmlspecialchars($tenant["contact_info"] ?? ""); ?></textarea>

        </p>


        <button type="submit">
            CẬP NHẬT NGƯỜI THUÊ
        </button>

    </form>

</body>

</html>

<?php

$conn->close();

?>