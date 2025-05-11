<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all registrations, single registration, or filtered registrations
        if (isset($_GET['registration_id'])) {
            $registration_id = $_GET['registration_id'];
            $stmt = $conn->prepare("SELECT r.*, e.event_name, s.full_name as student_name 
                                  FROM EventRegistrations r
                                  JOIN Events e ON r.event_id = e.event_id
                                  JOIN Students s ON r.student_id = s.student_id
                                  WHERE r.registration_id = ?");
            $stmt->execute([$registration_id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                echo json_encode($registration);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
            }
        } elseif (isset($_GET['event_id'])) {
            $event_id = $_GET['event_id'];
            $student_id = $_GET['student_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT r.*, s.full_name as student_name 
                   FROM EventRegistrations r
                   JOIN Students s ON r.student_id = s.student_id
                   WHERE r.event_id = ?";
            $params = [$event_id];
            
            if ($student_id) {
                $sql .= " AND r.student_id = ?";
                $params[] = $student_id;
            }
            
            if ($status) {
                $sql .= " AND r.attendance_status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY r.registration_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($student_id) {
                // For specific student check, return single record or 404
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($registration) {
                    echo json_encode($registration);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Registration not found"]);
                }
            } else {
                $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($registrations);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT r.*, e.event_name, e.start_datetime, e.location 
                   FROM EventRegistrations r
                   JOIN Events e ON r.event_id = e.event_id
                   WHERE r.student_id = ?";
            $params = [$student_id];
            
            if ($status) {
                $sql .= " AND r.attendance_status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY e.start_datetime";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Please specify registration_id, event_id, or student_id"]);
        }
        break;
        
    case 'POST':
        // Register for an event
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['event_id', 'student_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if event exists and is active
            $stmt = $conn->prepare("SELECT capacity, start_datetime FROM Events 
                                  WHERE event_id = ? AND is_active = 1");
            $stmt->execute([$data['event_id']]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                http_response_code(404);
                echo json_encode(["message" => "Event not found or not active"]);
                exit();
            }
            
            // Check if event has already started
            if (strtotime($event['start_datetime']) < time()) {
                http_response_code(400);
                echo json_encode(["message" => "Event has already started"]);
                exit();
            }
            
            // Check if student exists
            $stmt = $conn->prepare("SELECT 1 FROM Students WHERE student_id = ?");
            $stmt->execute([$data['student_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
                exit();
            }
            
            // Check if registration already exists
            $stmt = $conn->prepare("SELECT 1 FROM EventRegistrations 
                                  WHERE event_id = ? AND student_id = ?");
            $stmt->execute([$data['event_id'], $data['student_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Student already registered for this event"]);
                exit();
            }
            
            // Check capacity if event has one
            if ($event['capacity'] !== null) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM EventRegistrations 
                                      WHERE event_id = ?");
                $stmt->execute([$data['event_id']]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($count['count'] >= $event['capacity']) {
                    http_response_code(400);
                    echo json_encode(["message" => "Event has reached capacity"]);
                    exit();
                }
            }
            
            // Register for event
            $stmt = $conn->prepare("INSERT INTO EventRegistrations 
                                  (event_id, student_id, attendance_status) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([
                $data['event_id'],
                $data['student_id'],
                $data['attendance_status'] ?? 'Registered'
            ]);
            
            $registration_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Registered for event successfully",
                "registration_id" => $registration_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error registering for event: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update registration (mainly for attendance status)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "registration_id is required"]);
            exit();
        }
        
        $registration_id = $_GET['registration_id'];
        
        try {
            // Check if registration exists
            $stmt = $conn->prepare("SELECT 1 FROM EventRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
                exit();
            }
            
            // Only allow updating attendance status
            if (isset($data['attendance_status'])) {
                $stmt = $conn->prepare("UPDATE EventRegistrations 
                                      SET attendance_status = ? 
                                      WHERE registration_id = ?");
                $stmt->execute([$data['attendance_status'], $registration_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(["message" => "Registration updated successfully"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Registration not found or no changes made"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Only attendance_status can be updated"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating registration: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Cancel registration
        if (!isset($_GET['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "registration_id is required"]);
            exit();
        }
        
        $registration_id = $_GET['registration_id'];
        
        try {
            // First check if registration exists
            $stmt = $conn->prepare("SELECT 1 FROM EventRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
                exit();
            }
            
            // Delete registration
            $stmt = $conn->prepare("DELETE FROM EventRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            
            echo json_encode(["message" => "Registration cancelled successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error cancelling registration: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>