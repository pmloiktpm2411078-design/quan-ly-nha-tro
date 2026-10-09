<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$sql = "SELECT
            h.hold_id,
            h.started_at,
            h.expires_at,
            h.status,
            h.expiry_action,
            h.notes,
            t.full_name,
            r.room_code,
            r.room_number,
            u.full_name AS created_by_name
        FROM reservation_holds h

        INNER JOIN tenants t
            ON h.tenant_id = t.tenant_id

        INNER JOIN rooms r
            ON h.room_id = r.room_id

        LEFT JOIN users u
            ON h.created_by = u.user_id

        ORDER BY h.hold_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Giữ phòng</title>

</head>

<body>

    <h1>QUẢN LÝ GIỮ PHÒNG</h1>

    <p>
        <a href="../dashboard.php">← Về Dashboard</a>
    </p>

    <p>
        <a href="add.php">+ GIỮ PHÒNG</a>
    </p>

    <hr>

    <h2>Danh sách giữ phòng</h2>

    <table border="1" cellpadding="8" cellspacing="0">

        <tr>

            <th>ID</th>

            <th>Người thuê</th>

            <th>Phòng</th>

            <th>Bắt đầu</th>

            <th>Hết hạn</th>

            <th>Trạng thái</th>

            <th>Xử lý khi hết hạn</th>

            <th>Ghi chú</th>

            <th>Người tạo</th>

        </tr>


        <?php if ($result->num_rows > 0): ?>

            <?php while ($hold = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $hold["hold_id"]; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["full_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["room_code"]); ?>
                        -
                        <?php echo htmlspecialchars($hold["room_number"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["started_at"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["expires_at"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["status"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["expiry_action"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["notes"] ?? ""); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($hold["created_by_name"] ?? ""); ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="9">
                    Chưa có lượt giữ phòng nào.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</body>

</html>

<?php

$conn->close();

?>