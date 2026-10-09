<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";

$error = "";
$success = "";

/*
 * Thời hạn giữ phòng mặc định: 5 ngày
 */
$hold_days = 5;


/*
 * Lấy danh sách người thuê
 */
$sql_tenants = "SELECT tenant_id, full_name, phone
                FROM tenants
                ORDER BY full_name ASC";

$result_tenants = $conn->query($sql_tenants);


/*
 * Lấy các phòng đang trống
 */
$sql_rooms = "SELECT room_id, room_code, room_number, listed_rent
              FROM rooms
              WHERE status = 'AVAILABLE'
              ORDER BY room_number ASC";

$result_rooms = $conn->query($sql_rooms);


/*
 * Xử lý khi bấm GIỮ PHÒNG
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tenant_id = (int) $_POST["tenant_id"];
    $room_id = (int) $_POST["room_id"];
    $started_at = $_POST["started_at"];
    $expiry_action = $_POST["expiry_action"];
    $notes = trim($_POST["notes"]);

    /*
     * Tự tính thời gian hết hạn = bắt đầu + 5 ngày
     */
    $start_timestamp = strtotime($started_at);

    if ($start_timestamp !== false) {

        $expires_at = date(
            "Y-m-d H:i:s",
            strtotime("+{$hold_days} days", $start_timestamp)
        );

    } else {

        $expires_at = "";
    }


    /*
     * Kiểm tra dữ liệu
     */
    if (
        $tenant_id <= 0 ||
        $room_id <= 0 ||
        $started_at === ""
    ) {

        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc.";

    } elseif ($start_timestamp === false) {

        $error = "Thời gian bắt đầu không hợp lệ.";

    } else {

        /*
         * Kiểm tra phòng vẫn còn trống
         */
        $sql_check = "SELECT room_id
                      FROM rooms
                      WHERE room_id = ?
                      AND status = 'AVAILABLE'";

        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $room_id);
        $stmt_check->execute();

        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows !== 1) {

            $error = "Phòng này không còn ở trạng thái Còn trống.";

        } else {

            /*
             * Tạo lượt giữ phòng
             */
            $sql = "INSERT INTO reservation_holds
                    (
                        tenant_id,
                        room_id,
                        started_at,
                        expires_at,
                        status,
                        expiry_action,
                        notes,
                        created_by,
                        created_at
                    )
                    VALUES (?, ?, ?, ?, 'ACTIVE', ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);

            $created_by = $_SESSION["user_id"];

            /*
             * MySQL datetime cần dạng:
             * Y-m-d H:i:s
             */
            $started_at_db = date(
                "Y-m-d H:i:s",
                $start_timestamp
            );

            $stmt->bind_param(
                "iissssi",
                $tenant_id,
                $room_id,
                $started_at_db,
                $expires_at,
                $expiry_action,
                $notes,
                $created_by
            );

            if ($stmt->execute()) {

                /*
                 * Chuyển phòng sang trạng thái HELD
                 */
                $sql_room = "UPDATE rooms
                             SET status = 'HELD',
                                 updated_at = NOW()
                             WHERE room_id = ?";

                $stmt_room = $conn->prepare($sql_room);
                $stmt_room->bind_param("i", $room_id);
                $stmt_room->execute();
                $stmt_room->close();

                $success = "Giữ phòng thành công! Thời gian hết hạn là "
                         . date("d/m/Y H:i", strtotime($expires_at));

            } else {

                $error = "Không thể tạo lượt giữ phòng: "
                       . $stmt->error;
            }

            $stmt->close();
        }

        $stmt_check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Giữ phòng</title>

    <script>

        /*
         * Hiển thị thời gian hết hạn ngay trên giao diện
         * bằng cách cộng 5 ngày.
         */
        function calculateExpiry() {

            const startedInput =
                document.getElementById("started_at");

            const expiryDisplay =
                document.getElementById("expiry_display");

            if (startedInput.value === "") {

                expiryDisplay.innerHTML =
                    "Chưa chọn thời gian bắt đầu.";

                return;
            }

            const startDate =
                new Date(startedInput.value);

            startDate.setDate(
                startDate.getDate() + 5
            );

            const day =
                String(startDate.getDate()).padStart(2, "0");

            const month =
                String(startDate.getMonth() + 1).padStart(2, "0");

            const year =
                startDate.getFullYear();

            const hours =
                String(startDate.getHours()).padStart(2, "0");

            const minutes =
                String(startDate.getMinutes()).padStart(2, "0");

            expiryDisplay.innerHTML =
                day + "/" +
                month + "/" +
                year + " " +
                hours + ":" +
                minutes;
        }

    </script>

</head>

<body>

    <h1>GIỮ PHÒNG</h1>

    <p>
        <a href="index.php">
            ← Quay lại danh sách giữ phòng
        </a>
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


        <!-- NGƯỜI THUÊ -->

        <p>

            <label>
                Người thuê:
            </label>

            <br>

            <select
                name="tenant_id"
                required
            >

                <option value="">
                    -- Chọn người thuê --
                </option>

                <?php while (
                    $tenant =
                    $result_tenants->fetch_assoc()
                ): ?>

                    <option
                        value="<?php
                            echo $tenant["tenant_id"];
                        ?>"
                    >

                        <?php
                            echo htmlspecialchars(
                                $tenant["full_name"]
                            );
                        ?>

                        -

                        <?php
                            echo htmlspecialchars(
                                $tenant["phone"] ?? ""
                            );
                        ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </p>


        <!-- PHÒNG -->

        <p>

            <label>
                Phòng:
            </label>

            <br>

            <select
                name="room_id"
                required
            >

                <option value="">
                    -- Chọn phòng còn trống --
                </option>

                <?php while (
                    $room =
                    $result_rooms->fetch_assoc()
                ): ?>

                    <option
                        value="<?php
                            echo $room["room_id"];
                        ?>"
                    >

                        <?php
                            echo htmlspecialchars(
                                $room["room_code"]
                            );
                        ?>

                        -

                        Phòng

                        <?php
                            echo htmlspecialchars(
                                $room["room_number"]
                            );
                        ?>

                        -

                        <?php
                            echo number_format(
                                $room["listed_rent"]
                            );
                        ?>

                        VNĐ

                    </option>

                <?php endwhile; ?>

            </select>

        </p>


        <!-- THỜI GIAN BẮT ĐẦU -->

        <p>

            <label>
                Thời gian bắt đầu:
            </label>

            <br>

            <input
                type="datetime-local"
                name="started_at"
                id="started_at"
                onchange="calculateExpiry()"
                required
            >

        </p>


        <!-- THỜI HẠN -->

        <p>

            <label>
                Thời hạn giữ:
            </label>

            <br>

            <strong>
                5 ngày
            </strong>

            <small>
                (Theo quy định hệ thống)
            </small>

        </p>


        <!-- HẾT HẠN TỰ ĐỘNG -->

        <p>

            <label>
                Thời gian hết hạn:
            </label>

            <br>

            <strong
                id="expiry_display"
            >
                Chưa chọn thời gian bắt đầu.
            </strong>

            <br>

            <small>
                Hệ thống tự động tính bằng
                thời gian bắt đầu + 5 ngày.
            </small>

        </p>


        <!-- XỬ LÝ KHI HẾT HẠN -->

        <p>

            <label>
                Xử lý khi hết hạn:
            </label>

            <br>

            <select name="expiry_action">

                <option value="RELEASE">
                    Trả phòng về trạng thái Còn trống
                </option>



            </select>

        </p>


        <!-- GHI CHÚ -->

        <p>

            <label>
                Ghi chú:
            </label>

            <br>

            <textarea
                name="notes"
                rows="5"
                cols="50"
                placeholder="Nhập ghi chú nếu có..."
            ></textarea>

        </p>


        <button type="submit">
            GIỮ PHÒNG
        </button>


    </form>

</body>

</html>

<?php

$conn->close();

?>