<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}
$success = "";

if (isset($_SESSION["contract_success"])) {

    $success = $_SESSION["contract_success"];

    unset($_SESSION["contract_success"]);
}

require_once "../db.php";


$sql = "SELECT
            c.contract_id,
            c.contract_code,
            c.start_date,
            c.end_date,
            c.actual_end_date,
            c.monthly_rent,
            c.security_deposit,
            c.checkin_at,
            c.checkout_at,
            c.signed_at,
            c.status,
            t.full_name,
            r.room_code,
            r.room_number
        FROM contracts c
        INNER JOIN tenants t
            ON c.tenant_id = t.tenant_id
        INNER JOIN rooms r
            ON c.room_id = r.room_id
        ORDER BY c.contract_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Quản lý hợp đồng</title>

</head>

<body>

    <h1>QUẢN LÝ HỢP ĐỒNG</h1>


    <p>
        <a href="../dashboard.php">
            ← Về Dashboard
        </a>
    </p>

    <p>
        <a href="add.php?contract_id=1">
            + THÊM HỢP ĐỒNG
        </a>
    </p>

    <hr>

    <h2>Danh sách hợp đồng</h2>

    <table border="1" cellpadding="8" cellspacing="0">

        <tr>

            <th>ID</th>

            <th>Mã hợp đồng</th>

            <th>Người thuê</th>

            <th>Phòng</th>

            <th>Ngày bắt đầu</th>

            <th>Ngày kết thúc</th>

            <th>Tiền thuê/tháng</th>

            <th>Tiền cọc</th>

            <th>Trạng thái</th>

            <th>Thao tác</th>

        </tr>


        <?php if ($result && $result->num_rows > 0): ?>

            <?php while ($contract = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $contract["contract_id"]; ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($contract["contract_code"]); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($contract["full_name"]); ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $contract["room_code"] . " - " . $contract["room_number"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($contract["start_date"]); ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($contract["end_date"]); ?>
                    </td>


                    <td>
                        <?php
                        echo number_format(
                            (float) $contract["monthly_rent"],
                            0,
                            ",",
                            "."
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo number_format(
                            (float) $contract["security_deposit"],
                            0,
                            ",",
                            "."
                        );
                        ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($contract["status"]); ?>
                    </td>


                    <td>

                        <a href="edit.php?id=<?php echo $contract["contract_id"]; ?>">
                            Sửa
                        </a>
                        &nbsp; | &nbsp;

<a href="../checkout/create.php?contract_id=<?php echo $contract["contract_id"]; ?>">
    Trả phòng
</a>

                    </td>

                </tr>

            <?php endwhile; ?>


        <?php else: ?>

            <tr>

                <td colspan="10">
                    Chưa có hợp đồng nào.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</body>

</html>

<?php

$conn->close();

?>