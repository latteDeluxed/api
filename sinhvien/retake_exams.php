<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all retake exam registrations, single registration, or filtered registrations
        if (isset($_GET['retake_id'])) {
            $retake_id = $_GET['retake_id'];
            $stmt = $conn->prepare("SELECT r.*, s.full_name as student_name, t.test_name 
                                  FROM RetakeExamRegistrations r
                                  JOIN Students s ON r.student_id = s.student_id
                                  JOIN OnlineTests t ON r.test_id = t.test_id
                                  WHERE r.retake_id = ?");
            $stmt->execute([$retake_id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                echo json_encode($registration);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Retake exam registration not found"]);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT r.*, t.test_name 
                                  FROM RetakeExamRegistrations r
                                  JOIN OnlineTests t ON r.test_id = t.test_id
                                  WHERE r.student_id = ?");
            $stmt->execute([$student_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } elseif (isset($_GET['test_id'])) {
            $test_id = $_GET['test_id'];
            $stmt = $conn->prepare("SELECT r.*, s.full_name as student_name 
                                  FROM RetakeExamRegistrations r
                                  JOIN Students s ON r.student_id = s.student_id
                                  WHERE r.test_id = ?");
            $stmt->execute([$test_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } else {
            $stmt = $conn->query("SELECT r.*, s.full_name as student_name, t.test_name 
                                FROM RetakeExamRegistrations r
                                JOIN Students s ON r.student_id = s.student_id
                                JOIN OnlineTests t ON r.test_id = t.test_id");
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        }
        break;
        
    case 'POST':
        // Create new retake exam registration
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['student_id', 'test_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if student exists
            $stmt = $conn->prepare("SELECT 1 FROM Students WHERE student_id = ?");
            $stmt->execute([$data['student_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
                exit();
            }
            
            // Check if test exists
            $stmt = $conn->prepare("SELECT 1 FROM OnlineTests WHERE test_id = ?");
            $stmt->execute([$data['test_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Test not found"]);
                exit();
            }
            
            // Check if registration already exists
            $stmt = $conn->prepare("SELECT 1 FROM RetakeExamRegistrations WHERE student_id = ? AND test_id = ?");
            $stmt->execute([$data['student_id'], $data['test_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Retake exam registration already exists for this student and test"]);
                exit();
            }
            
            // Create registration
            $stmt = $conn->prepare("INSERT INTO RetakeExamRegistrations (student_id, test_id, reason, status) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['test_id'],
                $data['reason'] ?? null,
                $data['status'] ?? 'Pending'
            ]);
            
            $retake_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Retake exam registration created successfully",
                "retake_id" => $retake_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating retake exam registration: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update retake exam registration (mainly for status changes)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['retake_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "retake_id is required"]);
            exit();
        }
        
        $retake_id = $_GET['retake_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['reason', 'status'];
        
        foreach ($updatable_fields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "No fields to update"]);
            exit();
        }
        
        $params[] = $retake_id;
        
        try {
            $sql = "UPDATE RetakeExamRegistrations SET " . implode(', ', $fields) . " WHERE retake_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Retake exam registration updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Retake exam registration not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating retake exam registration: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete retake exam registration
        if (!isset($_GET['retake_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "retake_id is required"]);
            exit();
        }
        
        $retake_id = $_GET['retake_id'];
        
        try {
            // First check if registration exists
            $stmt = $conn->prepare("SELECT 1 FROM RetakeExamRegistrations WHERE retake_id = ?");
            $stmt->execute([$retake_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Retake exam registration not found"]);
                exit();
            }
            
            // Delete registration
            $stmt = $conn->prepare("DELETE FROM RetakeExamRegistrations WHERE retake_id = ?");
            $stmt->execute([$retake_id]);
            
            echo json_encode(["message" => "Retake exam registration deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting retake exam registration: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>