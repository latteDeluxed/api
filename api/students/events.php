<?php
require_once '../config.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy tất cả sự kiện hoặc một sự kiện cụ thể
        if (isset($_GET['event_id'])) {
            $event_id = $_GET['event_id'];
            $stmt = $conn->prepare("SELECT e.*, 
                                  (SELECT COUNT(*) FROM eventregistrations WHERE event_id = e.event_id) AS registered_count
                                  FROM events e 
                                  WHERE e.event_id = :event_id");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->execute();
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                echo json_encode($event);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Event not found"]);
            }
        } else {
            $stmt = $conn->query("SELECT e.*, 
                                (SELECT COUNT(*) FROM eventregistrations WHERE event_id = e.event_id) AS registered_count
                                FROM events e");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($events);
        }
        break;
        
    case 'POST':
        // Tạo sự kiện mới
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['event_name', 'description', 'start_datetime', 'end_datetime', 'location', 'organizer', 'capacity', 'type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO events 
                                  (event_name, description, start_datetime, end_datetime, location, 
                                  organizer, capacity, is_active, type) 
                                  VALUES 
                                  (:event_name, :description, :start_datetime, :end_datetime, :location, 
                                  :organizer, :capacity, :is_active, :type)");
            
            $stmt->bindParam(':event_name', $data['event_name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':start_datetime', $data['start_datetime']);
            $stmt->bindParam(':end_datetime', $data['end_datetime']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':organizer', $data['organizer']);
            $stmt->bindParam(':capacity', $data['capacity']);
            $stmt->bindParam(':type', $data['type']);
            $is_active = isset($data['is_active']) ? $data['is_active'] : true;
            $stmt->bindParam(':is_active', $is_active);
            
            if ($stmt->execute()) {
                $event_id = $conn->lastInsertId();
                http_response_code(201);
                echo json_encode([
                    "message" => "Event created successfully",
                    "event_id" => $event_id
                ]);
            } else {
                throw new PDOException("Failed to create event");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create event: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Cập nhật sự kiện
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['event_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Event ID is required"]);
            exit();
        }
        
        $fields = [];
        $params = [':event_id' => $data['event_id']];
        
        $updatable_fields = ['event_name', 'description', 'start_datetime', 'end_datetime', 
                            'location', 'organizer', 'capacity', 'is_active', 'type'];
        
        foreach ($updatable_fields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "No fields to update"]);
            exit();
        }
        
        try {
            $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE event_id = :event_id";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute($params)) {
                echo json_encode(["message" => "Event updated successfully"]);
            } else {
                throw new PDOException("Failed to update event");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update event: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Xóa sự kiện
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['event_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Event ID is required"]);
            exit();
        }
        
        try {
            // Bắt đầu transaction
            $conn->beginTransaction();
            
            // 1. Xóa tất cả đăng ký liên quan đến sự kiện này
            $delete_registrations = $conn->prepare("DELETE FROM eventregistrations WHERE event_id = :event_id");
            $delete_registrations->bindParam(':event_id', $data['event_id']);
            $delete_registrations->execute();
            
            // 2. Xóa sự kiện
            $delete_event = $conn->prepare("DELETE FROM events WHERE event_id = :event_id");
            $delete_event->bindParam(':event_id', $data['event_id']);
            
            if ($delete_event->execute()) {
                $conn->commit();
                echo json_encode(["message" => "Event and all related registrations deleted successfully"]);
            } else {
                throw new PDOException("Failed to delete event");
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete event: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>