<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";
$success = "";

if (isset($_SESSION["checkin_success"])) {

    $success = $_SESSION["checkin_success"];

    unset($_SESSION["checkin_success"]);
}


// ==================================================
// LẤY DANH SÁCH HỢP ĐỒNG ĐANG CHỜ NHẬN PHÒNG
// ==================================================

$sql = "
    SELECT
        c.contract_id,
        c.contract_code,
        c.start_date,
        c.end_date,
        c.monthly_rent,
        c.security_deposit,
        c.status,

        t.full_name,

        r.room_code,
        r.room_number

    FROM contracts c

    INNER JOIN tenants t
        ON c.tenant_id = t.tenant_id

    INNER JOIN rooms r
        ON c.room_id = r.room_id

    WHERE c.status = 'SIGNED'

    ORDER BY c.contract_id DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Nhận phòng</title>

</head>

<body>

    <h1>NHẬN PHÒNG</h1>


    <p>

        <a href="../dashboard.php">
            ← Về Dashboard
        </a>

    </p>


    <hr>
    <?php if ($success !== ""): ?>

    <p style="color:green;">
        <?php echo htmlspecialchars($success); ?>
    </p>

<?php endif; ?>


    <h2>Danh sách hợp đồng chờ nhận phòng</h2>


    <?php if ($result && $result->num_rows > 0): ?>


        <table
            border="1"
            cellpadding="8"
            cellspacing="0"
        >

            <tr>

                <th>ID</th>

                <th>Mã hợp đồng</th>

                <th>Người thuê</th>

                <th>Phòng</th>

                <th>Ngày bắt đầu</th>

                <th>Ngày kết thúc</th>

                <th>Giá thuê/tháng</th>

                <th>Tiền cọc</th>

                <th>Trạng thái</th>

                <th>Thao tác</th>

            </tr>


            <?php while ($contract = $result->fetch_assoc()): ?>


                <tr>


                    <td>

                        <?php
                        echo $contract["contract_id"];
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $contract["contract_code"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $contract["full_name"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $contract["room_code"]
                            . " - Phòng "
                            . $contract["room_number"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $contract["start_date"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $contract["end_date"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php

                        echo number_format(
                            (float)
                            $contract["monthly_rent"],
                            0,
                            ",",
                            "."
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo number_format(
                            (float)
                            $contract["security_deposit"],
                            0,
                            ",",
                            "."
                        );

                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $contract["status"]
                        );
                        ?>

                    </td>


                    <td>

                        <a
                            href="process.php?id=<?php
                            echo $contract["contract_id"];
                            ?>"
                        >
                            Nhận phòng
                        </a>

                    </td>


                </tr>


            <?php endwhile; ?>


        </table>


    <?php else: ?>


        <p>
            Hiện tại không có hợp đồng nào đang chờ nhận phòng.
        </p>


    <?php endif; ?>


</body>

</html>

<?php

$conn->close();

?>