<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

$conn = new mysqli("localhost", "root", "", "management");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Kết nối DB thất bại: " . $conn->connect_error]);
    exit;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $sql = "SELECT * FROM studenthealthrecords WHERE record_id = $id";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc());
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Không tìm thấy bản ghi"]);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $conn->real_escape_string($_GET['student_id']);

            // 👉 Thêm JOIN với bảng students để lấy full_name
            $sql = "
                SELECT r.*, s.full_name 
                FROM studenthealthrecords r 
                LEFT JOIN students s ON r.student_id = s.student_id 
                WHERE r.student_id = '$student_id' 
                ORDER BY r.created_at DESC
            ";
            $result = $conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // Nếu chỉ trả về bản ghi mới nhất
            if (count($data) === 1) {
                echo json_encode($data[0]);
            } else {
                echo json_encode($data);
            }
        } else {
            $sql = "SELECT * FROM studenthealthrecords ORDER BY created_at DESC";
            $result = $conn->query($sql);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode($data);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['student_id'], $input['record_date'], $input['record_type'], $input['description'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu dữ liệu bắt buộc"]);
            exit;
        }

        $student_id = $conn->real_escape_string($input['student_id']);
        $record_date = $conn->real_escape_string($input['record_date']);
        $record_type = $conn->real_escape_string($input['record_type']);
        $description = $conn->real_escape_string($input['description']);
        $file_path = isset($input['file_path']) ? $conn->real_escape_string($input['file_path']) : "";
        $notes = isset($input['notes']) ? $conn->real_escape_string($input['notes']) : "";
        $created_at = date("Y-m-d H:i:s");

        $sql = "INSERT INTO studenthealthrecords (student_id, record_date, record_type, description, file_path, notes, created_at) 
                VALUES ('$student_id', '$record_date', '$record_type', '$description', '$file_path', '$notes', '$created_at')";

        if ($conn->query($sql) === TRUE) {
            http_response_code(201);
            echo json_encode(["message" => "Thêm mới thành công", "record_id" => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Lỗi khi thêm bản ghi: " . $conn->error]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu tham số id"]);
            exit;
        }
        $id = (int)$_GET['id'];
        $input = json_decode(file_get_contents('php://input'), true);

        $fields = [];
        $allowed = ['student_id', 'record_date', 'record_type', 'description', 'file_path', 'notes'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = '" . $conn->real_escape_string($input[$field]) . "'";
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["error" => "Không có trường nào để cập nhật"]);
            exit;
        }

        $sql = "UPDATE studenthealthrecords SET " . implode(', ', $fields) . " WHERE record_id = $id";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["message" => "Cập nhật thành công"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Lỗi khi cập nhật: " . $conn->error]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu tham số id"]);
            exit;
        }
        $id = (int)$_GET['id'];
        $sql = "DELETE FROM studenthealthrecords WHERE record_id = $id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["message" => "Xóa thành công"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Lỗi khi xóa: " . $conn->error]);
        }
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Phương thức không được hỗ trợ"]);
        break;
}

$conn->close();
?>
