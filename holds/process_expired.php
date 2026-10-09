<?php

require_once "../db.php";

/*
 * Tìm các lượt giữ phòng đã hết hạn
 * nhưng vẫn đang ACTIVE
 */
$sql = "SELECT hold_id, room_id
        FROM reservation_holds
        WHERE status = 'ACTIVE'
        AND expires_at <= NOW()";

$result = $conn->query($sql);

$count = 0;

while ($hold = $result->fetch_assoc()) {

    $hold_id = $hold["hold_id"];
    $room_id = $hold["room_id"];

    /*
     * Đánh dấu lượt giữ đã hết hạn
     */
    $sql_hold = "UPDATE reservation_holds
                 SET
                     status = 'EXPIRED',
                     expiry_action = 'RELEASE',
                     processed_at = NOW()
                 WHERE hold_id = ?
                 AND status = 'ACTIVE'";

    $stmt = $conn->prepare($sql_hold);
    $stmt->bind_param("i", $hold_id);
    $stmt->execute();
    $stmt->close();


    /*
     * Trả phòng về trạng thái Còn trống
     */
    $sql_room = "UPDATE rooms
                 SET
                     status = 'AVAILABLE',
                     updated_at = NOW()
                 WHERE room_id = ?";

    $stmt = $conn->prepare($sql_room);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->close();

    $count++;
}

echo "Đã xử lý $count lượt giữ phòng hết hạn.";

$conn->close();

?>