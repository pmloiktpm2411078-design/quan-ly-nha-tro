<?php
echo "<pre>";
echo "FILE DANG CHAY: " . __FILE__;
echo "</pre>";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$error = "";

$user_id = (int) $_SESSION["user_id"];


// ==================================================
// KIỂM TRA HỢP ĐỒNG
// ==================================================

if (!isset($_GET["contract_id"]) || !is_numeric($_GET["contract_id"])) {
    die("Hợp đồng không hợp lệ.");
}

$contract_id = (int) $_GET["contract_id"];


// ==================================================
// LẤY THÔNG TIN HỢP ĐỒNG
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

        t.tenant_id,
        t.full_name,

        r.room_id,
        r.room_code,
        r.room_number

    FROM contracts c

    INNER JOIN tenants t
        ON c.tenant_id = t.tenant_id

    INNER JOIN rooms r
        ON c.room_id = r.room_id

    WHERE c.contract_id = ?
      AND c.status = 'ACTIVE'
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $contract_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    die("Không tìm thấy hợp đồng đang hoạt động.");
}

$contract = $result->fetch_assoc();

$stmt->close();


// ==================================================
// LẤY CÔNG NỢ
// ==================================================

$outstanding_amount = 0;

$sql_debt = "
    SELECT
        COALESCE(
            SUM(amount - paid_amount),
            0
        ) AS total_debt

    FROM receivables

    WHERE contract_id = ?

      AND status IN (
          'UNPAID',
          'PARTIAL',
          'OVERDUE'
      )
";

$stmt_debt = $conn->prepare($sql_debt);

$stmt_debt->bind_param(
    "i",
    $contract_id
);

$stmt_debt->execute();

$result_debt = $stmt_debt->get_result();

if ($row_debt = $result_debt->fetch_assoc()) {

    $outstanding_amount =
        (float) $row_debt["total_debt"];
}

$stmt_debt->close();


// ==================================================
// LẤY CHỈ SỐ ĐIỆN GẦN NHẤT
// ĐƠN VỊ: kWh
// ==================================================

$electricity_initial = 0;

$sql_electricity = "
    SELECT current_reading

    FROM meter_readings

    WHERE room_id = ?
      AND service_type = 'ELECTRICITY'

    ORDER BY
        reading_date DESC,
        meter_reading_id DESC

    LIMIT 1
";

$stmt_electricity =
    $conn->prepare($sql_electricity);

$stmt_electricity->bind_param(
    "i",
    $contract["room_id"]
);

$stmt_electricity->execute();

$result_electricity =
    $stmt_electricity->get_result();

if ($row = $result_electricity->fetch_assoc()) {

    $electricity_initial =
        (float) $row["current_reading"];
}

$stmt_electricity->close();


// ==================================================
// LẤY CHỈ SỐ NƯỚC GẦN NHẤT
// ĐƠN VỊ: m³
// ==================================================

$water_initial = 0;

$sql_water = "
    SELECT current_reading

    FROM meter_readings

    WHERE room_id = ?
      AND service_type = 'WATER'

    ORDER BY
        reading_date DESC,
        meter_reading_id DESC

    LIMIT 1
";

$stmt_water =
    $conn->prepare($sql_water);

$stmt_water->bind_param(
    "i",
    $contract["room_id"]
);

$stmt_water->execute();

$result_water =
    $stmt_water->get_result();

if ($row = $result_water->fetch_assoc()) {

    $water_initial =
        (float) $row["current_reading"];
}

$stmt_water->close();


// ==================================================
// GIÁ TRỊ MẶC ĐỊNH
// ==================================================

$damage_found = 0;

$damage_severity = "";

$damage_description = "";

$damage_cause = "UNKNOWN";

$responsible_party = null;

$damage_cost = 0;

$tenant_support_percent = 0;

$tenant_support_amount = 0;

$deposit_settlement = "";

$deposit_action = "";


// ==================================================
// XỬ LÝ FORM
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ----------------------------------------------
    // ĐIỆN NƯỚC
    // ----------------------------------------------

    $electricity_final =
        trim($_POST["electricity_final"] ?? "");

    $water_final =
        trim($_POST["water_final"] ?? "");


    // ----------------------------------------------
    // TÌNH TRẠNG PHÒNG
    // ----------------------------------------------

    $room_condition =
        trim($_POST["room_condition"] ?? "");

    $asset_condition =
        trim($_POST["asset_condition"] ?? "");


    // ----------------------------------------------
    // HƯ HỎNG
    // ----------------------------------------------

    $no_damage =
        isset($_POST["no_damage"])
        ? 1
        : 0;

    $damage_description =
        trim($_POST["damage_description"] ?? "");

    $damage_severity =
        $_POST["damage_severity"] ?? "";

    $damage_cause =
        $_POST["damage_cause"] ?? "UNKNOWN";

    $responsible_party =
        $_POST["responsible_party"] ?? "";

    $damage_cost =
        trim($_POST["damage_cost"] ?? "0");


    // ----------------------------------------------
    // TIỀN CỌC
    // ----------------------------------------------

    $deposit_action =
        $_POST["deposit_action"] ?? "";

    $deposit_settlement =
        trim($_POST["deposit_settlement"] ?? "");


    // ----------------------------------------------
    // GHI CHÚ
    // Ghi chú KHÔNG bắt buộc
    // ----------------------------------------------

    $notes =
        trim($_POST["notes"] ?? "");


    // ==================================================
    // KIỂM TRA THÔNG TIN BẮT BUỘC
    // ==================================================

    if ($electricity_final === "") {

        $error =
            "Vui lòng nhập chỉ số điện cuối.";

    } elseif ($water_final === "") {

        $error =
            "Vui lòng nhập chỉ số nước cuối.";

    } elseif ($room_condition === "") {

        $error =
            "Vui lòng nhập tình trạng phòng.";

    } elseif ($asset_condition === "") {

        $error =
            "Vui lòng nhập tình trạng tài sản.";

    } elseif ($deposit_action === "") {

        $error =
            "Vui lòng chọn phương án xử lý tiền cọc.";

    } elseif ($deposit_settlement === "") {

        $error =
            "Vui lòng nhập số tiền cọc dùng để xử lý.";

    } elseif (!is_numeric($electricity_final)) {

        $error =
            "Chỉ số điện phải là số.";

    } elseif (!is_numeric($water_final)) {

        $error =
            "Chỉ số nước phải là số.";

    } elseif (
        (float) $electricity_final < $electricity_initial
    ) {

        $error =
            "Chỉ số điện cuối không được nhỏ hơn chỉ số điện đầu.";

    } elseif (
        (float) $water_final < $water_initial
    ) {

        $error =
            "Chỉ số nước cuối không được nhỏ hơn chỉ số nước đầu.";

    } elseif (
        !is_numeric($deposit_settlement) ||
        (float) $deposit_settlement < 0
    ) {

        $error =
            "Số tiền xử lý cọc không hợp lệ.";

    } elseif (
        (float) $deposit_settlement >
        (float) $contract["security_deposit"]
    ) {

        $error =
            "Số tiền xử lý cọc không được lớn hơn tiền cọc.";

    } else {

        $electricity_final =
            (float) $electricity_final;

        $water_final =
            (float) $water_final;

        $deposit_settlement =
            (float) $deposit_settlement;


        // ==================================================
        // XỬ LÝ HƯ HỎNG
        // ==================================================

        if ($no_damage == 1) {

            // Không phát hiện hư hỏng

            $damage_found = 0;

            $damage_description = "";

            $damage_severity = "";

            $damage_cause = "UNKNOWN";

            $responsible_party = null;

            $damage_cost = 0;

            $tenant_support_percent = 0;

            $tenant_support_amount = 0;


        } else {

            $damage_found = 1;


            // ------------------------------------------
            // KIỂM TRA NGUYÊN NHÂN
            // ------------------------------------------

            if (
                !in_array(
                    $damage_cause,
                    [
                        "UNKNOWN",
                        "LANDLORD",
                        "TENANT",
                        "OTHER"
                    ],
                    true
                )
            ) {

                $error =
                    "Nguyên nhân hư hỏng không hợp lệ.";


            } elseif ($damage_description === "") {

                $error =
                    "Vui lòng nhập mô tả hư hỏng.";


            } elseif ($damage_severity === "") {

                $error =
                    "Vui lòng chọn mức độ hư hỏng.";


            } elseif (
                !in_array(
                    $damage_severity,
                    [
                        "LOW",
                        "MEDIUM",
                        "HIGH",
                        "CRITICAL"
                    ],
                    true
                )
            ) {

                $error =
                    "Mức độ hư hỏng không hợp lệ.";


            } elseif (
                !is_numeric($damage_cost) ||
                (float) $damage_cost < 0
            ) {

                $error =
                    "Chi phí xử lý hư hỏng không hợp lệ.";


            } else {

                $damage_cost =
                    (float) $damage_cost;


                // ------------------------------------------
                // DO CHỦ TRỌ
                // ------------------------------------------

                if ($damage_cause === "LANDLORD") {

                    $responsible_party =
                        "LANDLORD";

                    $tenant_support_percent =
                        0;

                    $tenant_support_amount =
                        0;


                // ------------------------------------------
                // DO NGƯỜI THUÊ
                // ------------------------------------------

                } elseif ($damage_cause === "TENANT") {

                    $responsible_party =
                        "TENANT";


                    switch ($damage_severity) {

                        case "LOW":

                            $tenant_support_percent =
                                10;

                            break;

                        case "MEDIUM":

                            $tenant_support_percent =
                                30;

                            break;

                        case "HIGH":

                            $tenant_support_percent =
                                50;

                            break;

                        case "CRITICAL":

                            $tenant_support_percent =
                                70;

                            break;
                    }


                    $tenant_support_amount =
                        $damage_cost
                        *
                        $tenant_support_percent
                        /
                        100;


                // ------------------------------------------
                // KHÁC
                // ------------------------------------------

                } elseif ($damage_cause === "OTHER") {

                    $responsible_party =
                        "OTHER";

                    $tenant_support_percent =
                        0;

                    $tenant_support_amount =
                        0;


                } else {

                    $responsible_party =
                        null;

                    $tenant_support_percent =
                        0;

                    $tenant_support_amount =
                        0;
                }
            }
        }


        // ==================================================
        // KIỂM TRA XỬ LÝ TIỀN CỌC
        // ==================================================

        if ($error === "") {

            $security_deposit =
                (float) $contract["security_deposit"];


            // ------------------------------------------
            // HOÀN LẠI TOÀN BỘ
            // ------------------------------------------

            if ($deposit_action === "REFUND") {

                if ($deposit_settlement > 0) {

                    $error =
                        "Nếu chọn hoàn lại tiền cọc, số tiền cọc dùng để xử lý phải bằng 0.";

                }

            }


            // ------------------------------------------
            // XỬ LÝ HƯ HỎNG
            // ------------------------------------------

            elseif ($deposit_action === "DAMAGE") {

                if ($damage_found == 0) {

                    $error =
                        "Không có hư hỏng thì không thể chọn xử lý cọc do hư hỏng.";

                } elseif ($damage_cost <= 0) {

                    $error =
                        "Chi phí hư hỏng phải lớn hơn 0.";

                } else {

                    /*
                     * Số tiền hư hỏng thực tế người thuê phải chịu
                     * = số tiền hỗ trợ của người thuê
                     */

                    $tenant_damage_amount =
                        $tenant_support_amount;


                    /*
                     * Nếu số tiền người thuê chịu
                     * <= tiền cọc
                     * thì dùng tiền cọc để bù trừ.
                     */

                    if (
                        $tenant_damage_amount
                        <=
                        $security_deposit
                    ) {

                        $expected_deposit_use =
                            $tenant_damage_amount;

                    } else {

                        $expected_deposit_use =
                            $security_deposit;
                    }


                    if (
                        abs(
                            $deposit_settlement
                            -
                            $expected_deposit_use
                        ) > 0.01
                    ) {

                        $error =
                            "Số tiền cọc dùng để xử lý phải là "
                            .
                            number_format(
                                $expected_deposit_use,
                                0,
                                ",",
                                "."
                            )
                            .
                            " VNĐ.";
                    }
                }
            }
        }


        // ==================================================
        // NẾU KHÔNG CÓ LỖI → LƯU
        // ==================================================

        if ($error === "") {

            $conn->begin_transaction();

            try {

                // ==================================================
                // 1. TẠO PHIẾU TRẢ PHÒNG
                // ==================================================

                $sql_checkout = "
                    INSERT INTO checkouts
                    (
                        contract_id,
                        room_id,
                        checkout_at,
                        electricity_final,
                        water_final,
                        room_condition,
                        asset_condition,
                        damage_found,
                        outstanding_amount,
                        deposit_settlement,
                        notes,
                        performed_by
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        NOW(),
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ";

                $stmt_checkout =
                    $conn->prepare($sql_checkout);


                if (!$stmt_checkout) {

                    throw new Exception(
                        "Không thể chuẩn bị phiếu trả phòng: "
                        . $conn->error
                    );
                }


              $stmt_checkout->bind_param(
                "iiddssiddsi",
                $contract["contract_id"],
                $contract["room_id"],
                $electricity_final,
                $water_final,
                $room_condition,
                $asset_condition,
                $damage_found,
                $outstanding_amount,
                $deposit_settlement,
                $notes,
                $user_id
);



                if (!$stmt_checkout->execute()) {

                    throw new Exception(
                        "Không thể tạo phiếu trả phòng: "
                        . $stmt_checkout->error
                    );
                }


                $stmt_checkout->close();


                // ==================================================
                // 2. GHI NHẬN HƯ HỎNG
                // ==================================================

                if ($damage_found == 1) {

                    $sql_damage = "
                        INSERT INTO damages
                        (
                            room_id,
                            reported_by,
                            reported_at,
                            description,
                            severity,
                            cause,
                            status,
                            responsible_party,
                            tenant_support_percent,
                            tenant_support_amount,
                            damage_cost
                        )

                        VALUES
                        (
                            ?,
                            ?,
                            NOW(),
                            ?,
                            ?,
                            ?,
                            'OPEN',
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ";


                    $stmt_damage =
                        $conn->prepare($sql_damage);


                    if (!$stmt_damage) {

                        throw new Exception(
                            "Không thể chuẩn bị ghi nhận hư hỏng: "
                            . $conn->error
                        );
                    }


                    $stmt_damage->bind_param(
                        "iissssddd",
                        $contract["room_id"],
                        $user_id,
                        $damage_description,
                        $damage_severity,
                        $damage_cause,
                        $responsible_party,
                        $tenant_support_percent,
                        $tenant_support_amount,
                        $damage_cost
                    );


                    if (!$stmt_damage->execute()) {

                        throw new Exception(
                            "Không thể ghi nhận hư hỏng: "
                            . $stmt_damage->error
                        );
                    }


                    $stmt_damage->close();
                }


                // ==================================================
                // 3. KẾT THÚC HỢP ĐỒNG
                // ==================================================

                $sql_contract = "
                    UPDATE contracts

                    SET
                        status = 'ENDED',
                        actual_end_date = CURDATE(),
                        checkout_at = NOW(),
                        updated_at = NOW()

                    WHERE contract_id = ?
                      AND status = 'ACTIVE'
                ";


                $stmt_contract =
                    $conn->prepare($sql_contract);


                $stmt_contract->bind_param(
                    "i",
                    $contract_id
                );


                if (!$stmt_contract->execute()) {

                    throw new Exception(
                        "Không thể kết thúc hợp đồng: "
                        . $stmt_contract->error
                    );
                }


                if ($stmt_contract->affected_rows !== 1) {

                    throw new Exception(
                        "Hợp đồng không còn ở trạng thái ACTIVE."
                    );
                }


                $stmt_contract->close();


                // ==================================================
                // 4. CHUYỂN PHÒNG SANG PROCESSING
                // ==================================================

                $sql_room = "
                    UPDATE rooms

                    SET
                        status = 'PROCESSING'

                    WHERE room_id = ?
                ";


                $stmt_room =
                    $conn->prepare($sql_room);


                $stmt_room->bind_param(
                    "i",
                    $contract["room_id"]
                );


                if (!$stmt_room->execute()) {

                    throw new Exception(
                        "Không thể cập nhật trạng thái phòng: "
                        . $stmt_room->error
                    );
                }


                $stmt_room->close();


                // ==================================================
                // 5. HOÀN TẤT
                // ==================================================

                $conn->commit();


                $_SESSION["checkout_success"] =
                    "Trả phòng thành công! Hợp đồng "
                    . $contract["contract_code"]
                    . " đã kết thúc. Phòng đã chuyển sang PROCESSING.";


                header("Location: index.php");

                exit;


            } catch (Exception $e) {

                $conn->rollback();

                $error =
                    $e->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Trả phòng</title>

</head>

<body>

<h1>TRẢ PHÒNG</h1>


<p>

    <a href="index.php">
        ← Quay lại danh sách trả phòng
    </a>

</p>


<hr>


<?php if ($error !== ""): ?>

    <p style="color:red;">

        <strong>Lỗi:</strong>

        <?php
        echo htmlspecialchars($error);
        ?>

    </p>

<?php endif; ?>


<!-- ==================================================
     THÔNG TIN HỢP ĐỒNG
================================================== -->

<h2>Thông tin hợp đồng</h2>

<p>
    <strong>Mã hợp đồng:</strong>
    <?php
    echo htmlspecialchars(
        $contract["contract_code"]
    );
    ?>
</p>

<p>
    <strong>Người thuê:</strong>
    <?php
    echo htmlspecialchars(
        $contract["full_name"]
    );
    ?>
</p>

<p>
    <strong>Phòng:</strong>

    <?php

    echo htmlspecialchars(
        $contract["room_code"]
        . " - Phòng "
        . $contract["room_number"]
    );

    ?>
</p>

<p>
    <strong>Ngày bắt đầu:</strong>
    <?php
    echo htmlspecialchars(
        $contract["start_date"]
    );
    ?>
</p>

<p>
    <strong>Ngày kết thúc:</strong>
    <?php
    echo htmlspecialchars(
        $contract["end_date"]
    );
    ?>
</p>

<p>

    <strong>Tiền thuê/tháng:</strong>

    <?php

    echo number_format(
        (float) $contract["monthly_rent"],
        0,
        ",",
        "."
    );

    ?>

    VNĐ

</p>

<p>

    <strong>Tiền cọc:</strong>

    <?php

    echo number_format(
        (float) $contract["security_deposit"],
        0,
        ",",
        "."
    );

    ?>

    VNĐ

</p>


<hr>


<!-- ==================================================
     CÔNG NỢ
================================================== -->

<h2>Kiểm tra công nợ</h2>

<p>

    <strong>Công nợ hiện tại:</strong>

    <?php

    echo number_format(
        $outstanding_amount,
        0,
        ",",
        "."
    );

    ?>

    VNĐ

</p>


<?php if ($outstanding_amount > 0): ?>

    <p style="color:red;">

        Khách thuê còn công nợ.
        Cần xử lý trước khi hoàn tất trả phòng.

    </p>

<?php else: ?>

    <p style="color:green;">

        Không còn công nợ.

    </p>

<?php endif; ?>


<hr>


<form method="POST" id="checkoutForm">


<!-- ==================================================
     ĐIỆN NƯỚC
================================================== -->

<h2>Chốt điện nước</h2>

<p>

    <label>
        Chỉ số điện gần nhất:
    </label>

    <br>

    <input
        type="number"
        value="<?php
        echo htmlspecialchars(
            $electricity_initial
        );
        ?>"
        readonly
    >

    <strong>kWh</strong>

</p>


<p>

    <label>
        Chỉ số điện cuối:
    </label>

    <br>

    <input
        type="number"
        name="electricity_final"
        min="<?php
        echo htmlspecialchars(
            $electricity_initial
        );
        ?>"
        step="0.01"
        value="<?php
        echo htmlspecialchars(
            $_POST["electricity_final"] ?? ""
        );
        ?>"
        required
    >

    <strong>kWh</strong>

</p>


<p>

    <label>
        Chỉ số nước gần nhất:
    </label>

    <br>

    <input
        type="number"
        value="<?php
        echo htmlspecialchars(
            $water_initial
        );
        ?>"
        readonly
    >

    <strong>m³</strong>

</p>


<p>

    <label>
        Chỉ số nước cuối:
    </label>

    <br>

    <input
        type="number"
        name="water_final"
        min="<?php
        echo htmlspecialchars(
            $water_initial
        );
        ?>"
        step="0.01"
        value="<?php
        echo htmlspecialchars(
            $_POST["water_final"] ?? ""
        );
        ?>"
        required
    >

    <strong>m³</strong>

</p>


<hr>


<!-- ==================================================
     KIỂM TRA PHÒNG
================================================== -->

<h2>Kiểm tra tình trạng phòng</h2>


<p>

    <label>
        Tình trạng phòng:
    </label>

    <br>

    <textarea
        name="room_condition"
        rows="4"
        cols="60"
        required
    ><?php
    echo htmlspecialchars(
        $_POST["room_condition"] ?? ""
    );
    ?></textarea>

</p>


<p>

    <label>
        Tình trạng tài sản:
    </label>

    <br>

    <textarea
        name="asset_condition"
        rows="4"
        cols="60"
        required
    ><?php
    echo htmlspecialchars(
        $_POST["asset_condition"] ?? ""
    );
    ?></textarea>

</p>


<hr>


<!-- ==================================================
     GHI NHẬN HƯ HỎNG
================================================== -->

<h2>Ghi nhận hư hỏng</h2>


<p>

    <label>

        <input
            type="checkbox"
            name="no_damage"
            id="no_damage"
            value="1"
            <?php
            echo (
                isset($_POST["no_damage"])
                &&
                $_POST["no_damage"] == "1"
            )
            ? "checked"
            : "";
            ?>
        >

        <strong>
            Không phát hiện hư hỏng
        </strong>

    </label>

</p>


<div
    id="damageSection"
    style="
        display:
        <?php
        echo (
            isset($_POST["no_damage"])
            &&
            $_POST["no_damage"] == "1"
        )
        ? "none"
        : "block";
        ?>;
    "
>


<!-- NGUYÊN NHÂN -->

<p>

    <label>
        Nguyên nhân hư hỏng:
    </label>

    <br>

    <select
        name="damage_cause"
        id="damage_cause"
    >

        <option value="UNKNOWN"
            <?php
            echo (
                ($_POST["damage_cause"] ?? "UNKNOWN")
                === "UNKNOWN"
            )
            ? "selected"
            : "";
            ?>
        >
            Chưa xác định
        </option>

        <option value="LANDLORD"
            <?php
            echo (
                ($_POST["damage_cause"] ?? "")
                === "LANDLORD"
            )
            ? "selected"
            : "";
            ?>
        >
            Do chủ trọ
        </option>

        <option value="TENANT"
            <?php
            echo (
                ($_POST["damage_cause"] ?? "")
                === "TENANT"
            )
            ? "selected"
            : "";
            ?>
        >
            Do người thuê
        </option>

        <option value="OTHER"
            <?php
            echo (
                ($_POST["damage_cause"] ?? "")
                === "OTHER"
            )
            ? "selected"
            : "";
            ?>
        >
            Khác
        </option>

    </select>

</p>


<!-- BÊN CHỊU TRÁCH NHIỆM -->

<p>

    <label>
        Bên chịu trách nhiệm:
    </label>

    <br>

    <input
        type="text"
        id="responsible_party_display"
        value="Chưa xác định"
        readonly
    >

</p>


<!-- MÔ TẢ -->

<p>

    <label>
        Mô tả hư hỏng:
    </label>

    <br>

    <textarea
        name="damage_description"
        id="damage_description"
        rows="4"
        cols="60"
    ><?php
    echo htmlspecialchars(
        $_POST["damage_description"] ?? ""
    );
    ?></textarea>

</p>


<!-- MỨC ĐỘ -->

<p>

    <label>
        Mức độ hư hỏng:
    </label>

    <br>

    <select
        name="damage_severity"
        id="damage_severity"
    >

        <option value="">
            -- Chọn mức độ --
        </option>

        <option value="LOW"
            <?php
            echo (
                ($_POST["damage_severity"] ?? "")
                === "LOW"
            )
            ? "selected"
            : "";
            ?>
        >
            Thấp
        </option>

        <option value="MEDIUM"
            <?php
            echo (
                ($_POST["damage_severity"] ?? "")
                === "MEDIUM"
            )
            ? "selected"
            : "";
            ?>
        >
            Trung bình
        </option>

        <option value="HIGH"
            <?php
            echo (
                ($_POST["damage_severity"] ?? "")
                === "HIGH"
            )
            ? "selected"
            : "";
            ?>
        >
            Cao
        </option>

        <option value="CRITICAL"
            <?php
            echo (
                ($_POST["damage_severity"] ?? "")
                === "CRITICAL"
            )
            ? "selected"
            : "";
            ?>
        >
            Nghiêm trọng
        </option>

    </select>

</p>


<!-- CHI PHÍ -->

<p>

    <label>
        Chi phí xử lý hư hỏng:
    </label>

    <br>

    <input
        type="number"
        name="damage_cost"
        id="damage_cost"
        min="0"
        step="1000"
        value="<?php
        echo htmlspecialchars(
            $_POST["damage_cost"] ?? "0"
        );
        ?>"
    >

    VNĐ

</p>


<!-- TỶ LỆ -->

<p>

    <label>
        Tỷ lệ người thuê hỗ trợ:
    </label>

    <br>

    <input
        type="text"
        id="tenant_support_percent_display"
        value="0%"
        readonly
    >

</p>


<!-- TIỀN HỖ TRỢ -->

<p>

    <label>
        Số tiền người thuê hỗ trợ:
    </label>

    <br>

    <input
        type="text"
        id="tenant_support_amount_display"
        value="0"
        readonly
    >

    VNĐ

</p>


<!-- CHỦ TRỌ CHI -->

<p>

    <label>
        Số tiền chủ trọ chịu:
    </label>

    <br>

    <input
        type="text"
        id="landlord_amount_display"
        value="0"
        readonly
    >

    VNĐ

</p>

</div>


<hr>


<!-- ==================================================
     XỬ LÝ TIỀN CỌC
================================================== -->

<h2>Xử lý tiền cọc</h2>


<p>

    <label>
        Phương án xử lý tiền cọc:
    </label>

    <br>

    <select
        name="deposit_action"
        id="deposit_action"
        required
    >

        <option value="">
            -- Chọn phương án --
        </option>

        <option value="REFUND"
            <?php
            echo (
                ($_POST["deposit_action"] ?? "")
                === "REFUND"
            )
            ? "selected"
            : "";
            ?>
        >
            Hoàn lại tiền cọc cho người thuê
        </option>

        <option value="DAMAGE"
            <?php
            echo (
                ($_POST["deposit_action"] ?? "")
                === "DAMAGE"
            )
            ? "selected"
            : "";
            ?>
        >
            Xử lý tiền cọc do hư hỏng
        </option>

    </select>

</p>


<p>

    <label>
        Số tiền cọc dùng để xử lý:
    </label>

    <br>

    <input
        type="number"
        name="deposit_settlement"
        id="deposit_settlement"
        min="0"
        max="<?php
        echo htmlspecialchars(
            $contract["security_deposit"]
        );
        ?>"
        step="1000"
        value="<?php
        echo htmlspecialchars(
            $_POST["deposit_settlement"] ?? "0"
        );
        ?>"
        required
    >

    VNĐ

</p>


<p>

    Tiền cọc hiện tại:

    <strong>

        <?php

        echo number_format(
            (float) $contract["security_deposit"],
            0,
            ",",
            "."
        );

        ?>

        VNĐ

    </strong>

</p>


<p id="depositResult"></p>


<hr>


<!-- ==================================================
     GHI CHÚ
================================================== -->

<h2>Ghi chú</h2>


<textarea
    name="notes"
    rows="5"
    cols="60"
><?php
echo htmlspecialchars(
    $_POST["notes"] ?? ""
);
?></textarea>

<p>
    <small>
        Ghi chú không bắt buộc.
    </small>
</p>


<hr>


<button
    type="submit"
    onclick="
        return confirm(
            'Bạn có chắc chắn muốn xác nhận trả phòng không?'
        );
    "
>

    XÁC NHẬN TRẢ PHÒNG

</button>


</form>


<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script>

const noDamage =
    document.getElementById("no_damage");

const damageSection =
    document.getElementById("damageSection");

const damageCause =
    document.getElementById("damage_cause");

const responsibleDisplay =
    document.getElementById(
        "responsible_party_display"
    );

const damageSeverity =
    document.getElementById(
        "damage_severity"
    );

const damageCost =
    document.getElementById(
        "damage_cost"
    );

const supportPercentDisplay =
    document.getElementById(
        "tenant_support_percent_display"
    );

const supportAmountDisplay =
    document.getElementById(
        "tenant_support_amount_display"
    );

const landlordAmountDisplay =
    document.getElementById(
        "landlord_amount_display"
    );

const depositAction =
    document.getElementById(
        "deposit_action"
    );

const depositSettlement =
    document.getElementById(
        "deposit_settlement"
    );

const depositResult =
    document.getElementById(
        "depositResult"
    );


const securityDeposit =
    <?php
    echo (float) $contract["security_deposit"];
    ?>;


// ==================================================
// TỶ LỆ HỖ TRỢ
// ==================================================

function getSupportPercent(severity) {

    switch (severity) {

        case "LOW":
            return 10;

        case "MEDIUM":
            return 30;

        case "HIGH":
            return 50;

        case "CRITICAL":
            return 70;

        default:
            return 0;
    }
}


// ==================================================
// CẬP NHẬT BÊN CHỊU TRÁCH NHIỆM
// ==================================================

function updateResponsibleParty() {

    const cause =
        damageCause.value;


    if (cause === "LANDLORD") {

        responsibleDisplay.value =
            "Chủ trọ";

    } else if (cause === "TENANT") {

        responsibleDisplay.value =
            "Người thuê";

    } else if (cause === "OTHER") {

        responsibleDisplay.value =
            "Khác";

    } else {

        responsibleDisplay.value =
            "Chưa xác định";
    }
}


// ==================================================
// TÍNH TIỀN HỖ TRỢ
// ==================================================

function calculateSupport() {

    const cause =
        damageCause.value;

    const severity =
        damageSeverity.value;

    const cost =
        parseFloat(
            damageCost.value
        ) || 0;


    let percent = 0;


    if (cause === "TENANT") {

        percent =
            getSupportPercent(
                severity
            );
    }


    const supportAmount =
        cost * percent / 100;


    const landlordAmount =
        cost - supportAmount;


    supportPercentDisplay.value =
        percent + "%";


    supportAmountDisplay.value =
        supportAmount.toLocaleString(
            "vi-VN"
        );


    landlordAmountDisplay.value =
        landlordAmount.toLocaleString(
            "vi-VN"
        );


    calculateDeposit();
}


// ==================================================
// TÍNH XỬ LÝ CỌC
// ==================================================

function calculateDeposit() {

    const action =
        depositAction.value;

    const cost =
        parseFloat(
            damageCost.value
        ) || 0;

    const support =
        parseFloat(
            supportAmountDisplay.value
                .replace(/\./g, "")
                .replace(/,/g, ".")
        ) || 0;


    if (action === "REFUND") {

        depositResult.innerHTML =
            "Tiền cọc được hoàn lại cho người thuê.";

        return;
    }


    if (
        action === "DAMAGE" &&
        !noDamage.checked
    ) {

        const damageAmount =
            support;

        if (damageAmount <= securityDeposit) {

            const refund =
                securityDeposit -
                damageAmount;

            depositResult.innerHTML =
                "Tiền cọc dùng để bù hư hỏng: "
                +
                damageAmount.toLocaleString("vi-VN")
                +
                " VNĐ. "
                +
                "Tiền cọc còn lại hoàn cho người thuê: "
                +
                refund.toLocaleString("vi-VN")
                +
                " VNĐ.";

        } else {

            const extra =
                damageAmount -
                securityDeposit;

            depositResult.innerHTML =
                "Tiền cọc được sử dụng hết. "
                +
                "Người thuê cần trả thêm: "
                +
                extra.toLocaleString("vi-VN")
                +
                " VNĐ.";
        }

    } else {

        depositResult.innerHTML = "";
    }
}


// ==================================================
// BẬT / TẮT HƯ HỎNG
// ==================================================

function toggleDamageSection() {

    if (noDamage.checked) {

        damageSection.style.display =
            "none";

        damageCause.disabled =
            true;

        document.getElementById(
            "damage_description"
        ).disabled = true;

        damageSeverity.disabled =
            true;

        damageCost.disabled =
            true;


        damageCause.value =
            "UNKNOWN";

        damageSeverity.value =
            "";

        damageCost.value =
            "0";


        responsibleDisplay.value =
            "Chưa xác định";

        supportPercentDisplay.value =
            "0%";

        supportAmountDisplay.value =
            "0";

        landlordAmountDisplay.value =
            "0";


    } else {

        damageSection.style.display =
            "block";

        damageCause.disabled =
            false;

        document.getElementById(
            "damage_description"
        ).disabled = false;

        damageSeverity.disabled =
            false;

        damageCost.disabled =
            false;


        updateResponsibleParty();

        calculateSupport();
    }

    calculateDeposit();
}


// ==================================================
// SỰ KIỆN
// ==================================================

noDamage.addEventListener(
    "change",
    toggleDamageSection
);


damageCause.addEventListener(
    "change",
    function () {

        updateResponsibleParty();

        calculateSupport();

    }
);


damageSeverity.addEventListener(
    "change",
    calculateSupport
);


damageCost.addEventListener(
    "input",
    calculateSupport
);


depositAction.addEventListener(
    "change",
    calculateDeposit
);


depositSettlement.addEventListener(
    "input",
    calculateDeposit
);


// ==================================================
// CHẠY LẦN ĐẦU
// ==================================================

toggleDamageSection();

</script>


</body>

</html>


<?php

$conn->close();

?>