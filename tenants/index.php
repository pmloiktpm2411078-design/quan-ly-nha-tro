<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$sql = "SELECT
            tenant_id,
            full_name,
            phone,
            identity_number,
            date_of_birth,
            address,
            contact_info
        FROM tenants
        ORDER BY tenant_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Quản lý người thuê</title>

</head>

<body>

    <h1>QUẢN LÝ NGƯỜI THUÊ</h1>

    <p>
        <a href="../dashboard.php">
            ← Về Dashboard
        </a>
    </p>

    <p>
        <a href="add.php">
            + THÊM NGƯỜI THUÊ
        </a>
    </p>

    <hr>

    <h2>Danh sách người thuê</h2>

    <table border="1" cellpadding="8" cellspacing="0">

        <tr>

            <th>ID</th>

            <th>Họ tên</th>

            <th>Số điện thoại</th>

            <th>CCCD/CMND</th>

            <th>Ngày sinh</th>

            <th>Địa chỉ</th>

            <th>Thông tin liên hệ</th>

            <th>Thao tác</th>

        </tr>


        <?php if ($result->num_rows > 0): ?>

            <?php while ($tenant = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $tenant["tenant_id"]; ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["full_name"]); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["phone"] ?? ""); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["identity_number"] ?? ""); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["date_of_birth"] ?? ""); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["address"] ?? ""); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($tenant["contact_info"] ?? ""); ?>
                    </td>


                    <td>

                        <a href="edit.php?id=<?php echo $tenant["tenant_id"]; ?>">
                            Sửa
                        </a>

                        |

                        <a
                            href="delete.php?id=<?php echo $tenant["tenant_id"]; ?>"
                            onclick="return confirm('Bạn có chắc muốn xóa người thuê này không?');"
                        >
                            Xóa
                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>


        <?php else: ?>

            <tr>

                <td colspan="8">
                    Chưa có người thuê nào.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</body>

</html>

<?php

$conn->close();

?>