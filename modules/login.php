<?php

session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username === "" || $password === "") {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
    } else {

        $sql = "SELECT 
                    u.user_id,
                    u.role_id,
                    u.username,
                    u.password_hash,
                    u.full_name,
                    u.status,
                    r.role_code,
                    r.role_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.role_id
                WHERE u.username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user["status"] !== "ACTIVE") {
                $error = "Tài khoản đang bị khóa hoặc không hoạt động.";
            } elseif (password_verify($password, $user["password_hash"])) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["role_code"] = $user["role_code"];
                $_SESSION["role_name"] = $user["role_name"];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
            }

        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Quản lý nhà trọ</title>
</head>

<body>

    <h2>ĐĂNG NHẬP HỆ THỐNG</h2>

    <?php if ($error !== ""): ?>
        <p style="color:red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <div>
            <label>Tên đăng nhập:</label>
            <br>
            <input type="text" name="username">
        </div>

        <br>

        <div>
            <label>Mật khẩu:</label>
            <br>
            <input type="password" name="password">
        </div>

        <br>

        <button type="submit">ĐĂNG NHẬP</button>

    </form>

</body>
</html>