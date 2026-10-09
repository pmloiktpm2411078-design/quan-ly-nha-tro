<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";


// ==================================================
// KIỂM TRA ID HỢP ĐỒNG
// ==================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Hợp đồng không hợp lệ.");
}

$contract_id = (int) $_GET["id"];


// ==================================================
// LẤY THÔNG TIN HỢP ĐỒNG
// ==================================================

$sql = "
    SELECT
        c.contract_id,
        c.contract_code,
        c.room_id,
        c.status,
        r.room_code,
        r.room_number

    FROM contracts c

    INNER JOIN rooms r
        ON c.room_id = r.room_id

    WHERE c.contract_id = ?
";

$stmt = $conn->prepare($sql);

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

    die("Không tìm thấy hợp đồng.");

}

$contract = $result->fetch_assoc();

$stmt->close();


// ==================================================
// KIỂM TRA TRẠNG THÁI HỢP ĐỒNG
// ==================================================

if ($contract["status"] !== "SIGNED") {

    die(
        "Hợp đồng này không ở trạng thái chờ nhận phòng."
    );
}


// ==================================================
// KIỂM TRA TRẠNG THÁI PHÒNG
// ==================================================

$sql_room = "
    SELECT room_id, status
    FROM rooms
    WHERE room_id = ?
";

$stmt_room = $conn->prepare($sql_room);

$stmt_room->bind_param(
    "i",
    $contract["room_id"]
);

$stmt_room->execute();

$result_room = $stmt_room->get_result();

$room = $result_room->fetch_assoc();

$stmt_room->close();


if (!$room) {

    die("Không tìm thấy phòng.");

}


// ==================================================
// KIỂM TRA PHÒNG CÓ PHÙ HỢP KHÔNG
// ==================================================

if (
    $room["status"] !== "HOLD" &&
    $room["status"] !== "AVAILABLE"
) {

    die(
        "Phòng hiện tại không thể thực hiện nhận phòng."
    );

}


// ==================================================
// BẮT ĐẦU TRANSACTION
// ==================================================

$conn->begin_transaction();

try {


    // ==================================================
    // 1. CẬP NHẬT HỢP ĐỒNG
    // SIGNED → ACTIVE
    // ==================================================

    $sql_contract = "
        UPDATE contracts
        SET
            status = 'ACTIVE',
            checkin_at = NOW(),
            updated_at = NOW()
        WHERE contract_id = ?
        AND status = 'SIGNED'
    ";

    $stmt_contract =
        $conn->prepare($sql_contract);

    $stmt_contract->bind_param(
        "i",
        $contract_id
    );

    if (!$stmt_contract->execute()) {

        throw new Exception(
            "Không thể cập nhật hợp đồng."
        );

    }

    if ($stmt_contract->affected_rows !== 1) {

        throw new Exception(
            "Hợp đồng không còn ở trạng thái SIGNED."
        );

    }

    $stmt_contract->close();


    // ==================================================
    // 2. CẬP NHẬT PHÒNG
    // HOLD → OCCUPIED
    // ==================================================

    $sql_room_update = "
        UPDATE rooms
        SET
            status = 'OCCUPIED',
            updated_at = NOW()
        WHERE room_id = ?
        AND status = 'HOLD'
    ";

    $stmt_room_update =
        $conn->prepare($sql_room_update);

    $stmt_room_update->bind_param(
        "i",
        $contract["room_id"]
    );

    if (!$stmt_room_update->execute()) {

        throw new Exception(
            "Không thể cập nhật trạng thái phòng."
        );

    }

    $stmt_room_update->close();


    // ==================================================
    // 3. COMMIT
    // ==================================================

    $conn->commit();


    // ==================================================
    // 4. THÔNG BÁO THÀNH CÔNG
    // ==================================================

    $_SESSION["checkin_success"] =
        "Nhận phòng thành công cho hợp đồng "
        . $contract["contract_code"]
        . " - "
        . $contract["room_code"]
        . " - Phòng "
        . $contract["room_number"]
        . ".";


    header("Location: index.php");
    exit;


} catch (Exception $e) {


    // ==================================================
    // ROLLBACK NẾU CÓ LỖI
    // ==================================================

    $conn->rollback();


    die(
        "Không thể thực hiện nhận phòng: "
        . htmlspecialchars($e->getMessage())
    );

}


$conn->close();

?>