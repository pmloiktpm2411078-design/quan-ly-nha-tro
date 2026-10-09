<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "quan_ly_nha_tro";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>