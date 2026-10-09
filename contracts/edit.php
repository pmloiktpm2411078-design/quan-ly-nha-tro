<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$error = "";


// ==================================================
// KIỂM TRA ID HỢP ĐỒNG
// ==================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    die("
        <h2>Hợp đồng không hợp lệ</h2>

        <p>
            <a href='index.php'>
                ← Quay về danh sách hợp đồng
            </a>
        </p>
    ");
}

$contract_id = (int) $_GET["id"];


// ==================================================
// LẤY THÔNG TIN HỢP ĐỒNG
// ==================================================

$sql = "
    SELECT
        c.contract_id,
        c.contract_code,
        c.start_date,
        c.end_date,
        c.actual_end_date,
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

    WHERE c.contract_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Không thể chuẩn bị truy vấn: "
        . $conn->error
    );
}

$stmt->bind_param(
    "i",
    $contract_id
);

$stmt->execute();

$result = $stmt->get_result();


// ==================================================
// KIỂM TRA HỢP ĐỒNG
// ==================================================

if ($result->num_rows !== 1) {

    $stmt->close();

    die("
        <h2>Không tìm thấy hợp đồng</h2>

        <p>
            Hợp đồng không tồn tại trong hệ thống.
        </p>

        <p>
            <a href='index.php'>
                ← Quay về danh sách hợp đồng
            </a>
        </p>
    ");
}


$contract = $result->fetch_assoc();

$stmt->close();


// ==================================================
// KIỂM TRA TRẠNG THÁI HỢP ĐỒNG
// ==================================================

if ($contract["status"] !== "ACTIVE") {

    die("
        <h2>Hợp đồng đã kết thúc</h2>

        <p>
            Hợp đồng này đã kết thúc nên không thể chỉnh sửa.
        </p>

        <p>
            <a href='index.php'>
                ← Quay về danh sách hợp đồng
            </a>
        </p>
    ");
}


// ==================================================
// XỬ LÝ CẬP NHẬT
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $security_deposit = trim(
        $_POST["security_deposit"] ?? ""
    );


    // ==================================================
    // KIỂM TRA TIỀN CỌC
    // ==================================================

    if ($security_deposit === "") {

        $error =
            "Vui lòng nhập tiền cọc.";

    } elseif (!is_numeric($security_deposit)) {

        $error =
            "Tiền cọc phải là số.";

    } elseif ((float) $security_deposit < 0) {

        $error =
            "Tiền cọc không được nhỏ hơn 0.";

    } else {

        $security_deposit =
            (float) $security_deposit;


        // ==================================================
        // CẬP NHẬT TIỀN CỌC
        // ==================================================

        $sql_update = "
            UPDATE contracts

            SET
                security_deposit = ?,
                updated_at = NOW()

            WHERE contract_id = ?
              AND status = 'ACTIVE'
        ";

        $stmt_update =
            $conn->prepare($sql_update);


        if (!$stmt_update) {

            $error =
                "Không thể chuẩn bị cập nhật hợp đồng: "
                . $conn->error;

        } else {

            $stmt_update->bind_param(
                "di",
                $security_deposit,
                $contract_id
            );


            // ==================================================
            // THỰC HIỆN CẬP NHẬT
            // ==================================================

            if (!$stmt_update->execute()) {

                $error =
                    "Không thể cập nhật hợp đồng: "
                    . $stmt_update->error;

            } elseif (
                $stmt_update->affected_rows < 1
            ) {

                $error =
                    "Không thể cập nhật hợp đồng.";

            } else {

                $_SESSION["contract_success"] =
                    "Cập nhật hợp đồng "
                    . $contract["contract_code"]
                    . " thành công!";


                $stmt_update->close();


                header(
                    "Location: index.php"
                );

                exit;
            }


            $stmt_update->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa hợp đồng</title>

</head>

<body>


<h1>SỬA HỢP ĐỒNG</h1>


<p>

    <a href="index.php">
        ← Quay lại danh sách hợp đồng
    </a>

</p>


<hr>


<?php if ($error !== ""): ?>

    <p style="color:red;">

        <strong>Lỗi:</strong>

        <?php

        echo htmlspecialchars(
            $error
        );

        ?>

    </p>

<?php endif; ?>


<form method="POST">


    <!-- ==================================================
         MÃ HỢP ĐỒNG
    ================================================== -->

    <p>

        <label>
            Mã hợp đồng:
        </label>

        <br>

        <input
            type="text"
            value="<?php

            echo htmlspecialchars(
                $contract["contract_code"]
            );

            ?>"
            readonly
        >

    </p>


    <!-- ==================================================
         NGƯỜI THUÊ
    ================================================== -->

    <p>

        <label>
            Người thuê:
        </label>

        <br>

        <input
            type="text"
            value="<?php

            echo htmlspecialchars(
                $contract["full_name"]
            );

            ?>"
            readonly
        >

    </p>


    <!-- ==================================================
         PHÒNG
    ================================================== -->

    <p>

        <label>
            Phòng:
        </label>

        <br>

        <input
            type="text"
            value="<?php

            echo htmlspecialchars(
                $contract["room_code"]
                . " - Phòng "
                . $contract["room_number"]
            );

            ?>"
            readonly
        >

    </p>


    <!-- ==================================================
         NGÀY BẮT ĐẦU
    ================================================== -->

    <p>

        <label>
            Ngày bắt đầu:
        </label>

        <br>

        <input
            type="date"
            value="<?php

            echo htmlspecialchars(
                $contract["start_date"]
            );

            ?>"
            readonly
        >

    </p>


    <!-- ==================================================
         NGÀY KẾT THÚC
    ================================================== -->

    <p>

        <label>
            Ngày kết thúc:
        </label>

        <br>

        <input
            type="date"
            value="<?php

            echo htmlspecialchars(
                $contract["end_date"]
            );

            ?>"
            readonly
        >

    </p>


    <!-- ==================================================
         GIÁ THUÊ
    ================================================== -->

    <p>

        <label>
            Giá thuê/tháng:
        </label>

        <br>

        <input
            type="text"
            value="<?php

            echo number_format(
                (float) $contract["monthly_rent"],
                0,
                ",",
                "."
            );

            ?>"
            readonly
        >

        VNĐ

    </p>


    <!-- ==================================================
         TIỀN CỌC
    ================================================== -->

    <p>

        <label>
            Tiền cọc:
        </label>

        <br>

        <input
            type="number"
            name="security_deposit"
            value="<?php

            echo htmlspecialchars(
                $_POST["security_deposit"]
                ?? $contract["security_deposit"]
            );

            ?>"
            min="0"
            step="1000"
            required
        >

        VNĐ

    </p>


    <!-- ==================================================
         TRẠNG THÁI
    ================================================== -->

    <p>

        <label>
            Trạng thái hợp đồng:
        </label>

        <br>

        <input
            type="text"
            value="<?php

            echo htmlspecialchars(
                $contract["status"]
            );

            ?>"
            readonly
        >

    </p>


    <hr>


    <p>

        <button type="submit">

            CẬP NHẬT HỢP ĐỒNG

        </button>

    </p>


</form>


</body>

</html>


<?php

$conn->close();

?>bươ