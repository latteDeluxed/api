<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy tất cả đăng ký hoặc đăng ký theo student_id/event_id
        if (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT er.*, e.event_name, e.start_datetime, e.location 
                                  FROM eventregistrations er
                                  JOIN events e ON er.event_id = e.event_id
                                  WHERE er.student_id = :student_id");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } elseif (isset($_GET['event_id'])) {
            $event_id = $_GET['event_id'];
            $stmt = $conn->prepare("SELECT * FROM eventregistrations WHERE event_id = :event_id");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } else {
            $stmt = $conn->query("SELECT * FROM eventregistrations");
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        }
        break;
        
    case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);

    $required_fields = ['event_id', 'student_id'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required field: $field"]);
            exit();
        }
    }

    // Kiểm tra xem student đã đăng ký sự kiện này chưa
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM eventregistrations WHERE event_id = :event_id AND student_id = :student_id");
    $check_stmt->bindParam(':event_id', $data['event_id']);
    $check_stmt->bindParam(':student_id', $data['student_id']);
    $check_stmt->execute();
    $existing_registration = $check_stmt->fetchColumn();

    if ($existing_registration > 0) {
        http_response_code(400);
        echo json_encode(["message" => "Bạn đã đăng ký sự kiện này rồi!"]);
        exit();
    }

    // Kiểm tra sự kiện còn chỗ không
    $event_stmt = $conn->prepare("SELECT capacity, is_active FROM events WHERE event_id = :event_id");
    $event_stmt->bindParam(':event_id', $data['event_id']);
    $event_stmt->execute();
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(["message" => "Event not found"]);
        exit();
    }

    if (!$event['is_active']) {
        http_response_code(400);
        echo json_encode(["message" => "Event is not active"]);
        exit();
    }

    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM eventregistrations WHERE event_id = :event_id");
    $count_stmt->bindParam(':event_id', $data['event_id']);
    $count_stmt->execute();
    $registrations_count = $count_stmt->fetchColumn();

    if ($registrations_count >= $event['capacity']) {
        http_response_code(400);
        echo json_encode(["message" => "Event is full"]);
        exit();
    }

    // Tạo đăng ký mới
    $stmt = $conn->prepare("INSERT INTO eventregistrations (event_id, student_id, attendance_status) VALUES (:event_id, :student_id, :attendance_status)");
    $attendance_status = $data['attendance_status'] ?? 'registered';
    $stmt->bindParam(':event_id', $data['event_id']);
    $stmt->bindParam(':student_id', $data['student_id']);
    $stmt->bindParam(':attendance_status', $attendance_status);

    if ($stmt->execute()) {
    $registration_id = $conn->lastInsertId();

    // Lấy thông tin sự kiện vừa đăng ký để trả về
    $event_info_stmt = $conn->prepare("
        SELECT er.registration_id, er.student_id, er.attendance_status, e.event_name, e.start_datetime, e.location
        FROM eventregistrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.registration_id = :registration_id
    ");
    $event_info_stmt->bindParam(':registration_id', $registration_id);
    $event_info_stmt->execute();
    $event_info = $event_info_stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode([
        "message" => "Đăng ký thành công",
        "registration" => $event_info
    ]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Lỗi khi tạo đăng ký"]);
}
    break;

        
    case 'PUT':
        // Cập nhật trạng thái đăng ký
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Registration ID is required"]);
            exit();
        }
        
        $fields = [];
        $params = [':registration_id' => $data['registration_id']];
        
        if (isset($data['attendance_status'])) {
            $fields[] = "attendance_status = :attendance_status";
            $params[':attendance_status'] = $data['attendance_status'];
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "No fields to update"]);
            exit();
        }
        
        $sql = "UPDATE eventregistrations SET " . implode(', ', $fields) . " WHERE registration_id = :registration_id";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode(["message" => "Registration updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update registration"]);
        }
        break;
        
    case 'DELETE':
        // Hủy đăng ký
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Registration ID is required"]);
            exit();
        }
        
        $stmt = $conn->prepare("DELETE FROM eventregistrations WHERE registration_id = :registration_id");
        $stmt->bindParam(':registration_id', $data['registration_id']);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Registration deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete registration"]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>