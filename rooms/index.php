<?php

session_start();

require_once "../db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}


// ==================================================
// LẤY DANH SÁCH PHÒNG
// ==================================================

$sql = "
    SELECT 
        r.room_id,
        r.room_code,
        r.room_number,
        r.room_type_id,
        r.area_m2,
        r.listed_rent,
        r.status,
        r.description,
        rt.type_name,

        c.contract_id

    FROM rooms r

    LEFT JOIN room_types rt
        ON r.room_type_id = rt.room_type_id

    LEFT JOIN contracts c
        ON r.room_id = c.room_id
        AND c.status = 'ACTIVE'

    ORDER BY r.room_id ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Quản lý phòng</title>

</head>

<body>

<h1>QUẢN LÝ PHÒNG</h1>


<p>
    <a href="../dashboard.php">
        ← Về Dashboard
    </a>
</p>


<p>
    <a href="create.php">
        + THÊM PHÒNG
    </a>
</p>


<hr>


<h2>Danh sách phòng</h2>


<?php if ($result && $result->num_rows > 0): ?>

    <table border="1" cellpadding="8" cellspacing="0">

        <tr>

            <th>ID</th>

            <th>Mã phòng</th>

            <th>Số phòng</th>

            <th>Loại phòng</th>

            <th>Diện tích</th>

            <th>Giá thuê</th>

            <th>Trạng thái</th>

            <th>Mô tả</th>

            <th>Thao tác</th>

        </tr>


        <?php while ($room = $result->fetch_assoc()): ?>

            <tr>

                <td>
                    <?php
                    echo $room["room_id"];
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["room_code"]
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["room_number"]
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["type_name"] ?? ""
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["area_m2"]
                    );
                    ?>
                    m²
                </td>


                <td>
                    <?php
                    echo number_format(
                        $room["listed_rent"],
                        0,
                        ",",
                        "."
                    );
                    ?>
                    VNĐ
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["status"]
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $room["description"] ?? ""
                    );
                    ?>
                </td>


                <td>

                    <!-- SỬA PHÒNG -->

                    <a
                        href="edit.php?id=<?php
                        echo $room["room_id"];
                        ?>"
                    >
                        Sửa
                    </a>


                    |

                    
                    <!-- TRẢ PHÒNG -->

                    <?php if (!empty($room["contract_id"])): ?>

                        <a
                            href="../checkouts/add.php?contract_id=<?php
                            echo $room["contract_id"];
                            ?>"
                        >
                            Trả phòng
                        </a>

                    <?php else: ?>

                        <span>
                            Không có hợp đồng
                        </span>

                    <?php endif; ?>


                    |


                    <!-- XÓA PHÒNG -->

                    <a
                        href="delete.php?id=<?php
                        echo $room["room_id"];
                        ?>"
                        onclick="
                            return confirm(
                                'Bạn có chắc muốn xóa phòng này không?'
                            );
                        "
                    >
                        Xóa
                    </a>

                </td>

            </tr>

        <?php endwhile; ?>

    </table>


<?php else: ?>

    <p>
        Chưa có phòng nào.
    </p>

<?php endif; ?>


</body>

</html>


<?php

$conn->close();

?>