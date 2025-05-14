<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all registrations, by student, or by course
        if (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT r.*, c.course_code, c.course_name 
                                  FROM CourseRegistrations r
                                  JOIN Courses c ON r.course_id = c.course_id
                                  WHERE r.student_id = ?");
            $stmt->execute([$student_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } elseif (isset($_GET['course_id'])) {
            $course_id = $_GET['course_id'];
            $stmt = $conn->prepare("SELECT r.*, s.student_code, s.full_name 
                                  FROM CourseRegistrations r
                                  JOIN Students s ON r.student_id = s.student_id
                                  WHERE r.course_id = ?");
            $stmt->execute([$course_id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        } elseif (isset($_GET['registration_id'])) {
            $registration_id = $_GET['registration_id'];
            $stmt = $conn->prepare("SELECT * FROM CourseRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                echo json_encode($registration);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
            }
        } else {
            $stmt = $conn->query("SELECT * FROM CourseRegistrations");
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
        }
        break;
        
    case 'POST':
        // Create new registration
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
            $stmt = $conn->prepare("SELECT 1 FROM CourseRegistrations WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$data['student_id'], $data['course_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Student is already registered for this course"]);
                exit();
            }
            
            // Create registration
            $stmt = $conn->prepare("INSERT INTO CourseRegistrations (student_id, course_id, status) 
                                   VALUES (?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['course_id'],
                $data['status'] ?? 'Pending'
            ]);
            
            $registration_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Course registration created successfully",
                "registration_id" => $registration_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating course registration: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update registration (mainly for status changes)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Registration ID is required"]);
            exit();
        }
        
        $registration_id = $_GET['registration_id'];
        
        try {
            // Check if registration exists
            $stmt = $conn->prepare("SELECT 1 FROM CourseRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
                exit();
            }
            
            // Update status if provided
            if (isset($data['status'])) {
                $stmt = $conn->prepare("UPDATE CourseRegistrations SET status = ? WHERE registration_id = ?");
                $stmt->execute([$data['status'], $registration_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(["message" => "Registration updated successfully"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Registration not found or no changes made"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "No valid fields to update"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating registration: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete registration
        if (!isset($_GET['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Registration ID is required"]);
            exit();
        }
        
        $registration_id = $_GET['registration_id'];
        
        try {
            // First check if registration exists
            $stmt = $conn->prepare("SELECT * FROM CourseRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
                exit();
            }
            
            // Delete registration
            $stmt = $conn->prepare("DELETE FROM CourseRegistrations WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            
            echo json_encode(["message" => "Registration deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting registration: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>