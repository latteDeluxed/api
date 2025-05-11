<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all retake course registrations, single registration, or filtered registrations
        if (isset($_GET['retake_course_id'])) {
            $retake_course_id = $_GET['retake_course_id'];
            $stmt = $conn->prepare("SELECT r.*, s.full_name as student_name, c.course_name 
                                  FROM RetakeCourseRegistrations r
                                  JOIN Students s ON r.student_id = s.student_id
                                  JOIN Courses c ON r.course_id = c.course_id
                                  WHERE r.retake_course_id = ?");
            $stmt->execute([$retake_course_id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                echo json_encode($registration);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Retake course registration not found"]);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT r.*, c.course_name 
                                  FROM RetakeCourseRegistrations r
                                  JOIN Courses c ON r.course_id = c.course_id
                                  WHERE r.student_id = ?");
            $stmt->execute([$student_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } elseif (isset($_GET['course_id'])) {
            $course_id = $_GET['course_id'];
            $stmt = $conn->prepare("SELECT r.*, s.full_name as student_name 
                                  FROM RetakeCourseRegistrations r
                                  JOIN Students s ON r.student_id = s.student_id
                                  WHERE r.course_id = ?");
            $stmt->execute([$course_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } else {
            $stmt = $conn->query("SELECT r.*, s.full_name as student_name, c.course_name 
                                FROM RetakeCourseRegistrations r
                                JOIN Students s ON r.student_id = s.student_id
                                JOIN Courses c ON r.course_id = c.course_id");
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        }
        break;
        
    case 'POST':
        // Create new retake course registration
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['student_id', 'course_id'];
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
            
            // Check if course exists
            $stmt = $conn->prepare("SELECT 1 FROM Courses WHERE course_id = ?");
            $stmt->execute([$data['course_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Course not found"]);
                exit();
            }
            
            // Check if registration already exists
            $stmt = $conn->prepare("SELECT 1 FROM RetakeCourseRegistrations WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$data['student_id'], $data['course_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Retake course registration already exists for this student and course"]);
                exit();
            }
            
            // Create registration
            $stmt = $conn->prepare("INSERT INTO RetakeCourseRegistrations (student_id, course_id, reason, status) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['course_id'],
                $data['reason'] ?? null,
                $data['status'] ?? 'Pending'
            ]);
            
            $retake_course_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Retake course registration created successfully",
                "retake_course_id" => $retake_course_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating retake course registration: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update retake course registration (mainly for status changes)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['retake_course_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "retake_course_id is required"]);
            exit();
        }
        
        $retake_course_id = $_GET['retake_course_id'];
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
        
        $params[] = $retake_course_id;
        
        try {
            $sql = "UPDATE RetakeCourseRegistrations SET " . implode(', ', $fields) . " WHERE retake_course_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Retake course registration updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Retake course registration not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating retake course registration: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete retake course registration
        if (!isset($_GET['retake_course_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "retake_course_id is required"]);
            exit();
        }
        
        $retake_course_id = $_GET['retake_course_id'];
        
        try {
            // First check if registration exists
            $stmt = $conn->prepare("SELECT 1 FROM RetakeCourseRegistrations WHERE retake_course_id = ?");
            $stmt->execute([$retake_course_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Retake course registration not found"]);
                exit();
            }
            
            // Delete registration
            $stmt = $conn->prepare("DELETE FROM RetakeCourseRegistrations WHERE retake_course_id = ?");
            $stmt->execute([$retake_course_id]);
            
            echo json_encode(["message" => "Retake course registration deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting retake course registration: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>