<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Kết nối đến cơ sở dữ liệu
$conn = new mysqli("localhost", "root", "", "management");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Kiểm tra xem có student_id được truyền không
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Truy vấn lấy dữ liệu attendance theo student_id
    $sql = "SELECT attendance_id, student_id, course, date, status, notes 
            FROM attendance 
            WHERE student_id = $student_id";

    $result = $conn->query($sql);

    $attendances = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendances[] = $row;
        }
    }

    // Trả về dữ liệu JSON
    echo json_encode($attendances);
} else {
    // Trường hợp không có student_id
    echo json_encode(["error" => "Missing student_id parameter"]);
}

$conn->close();
?>
