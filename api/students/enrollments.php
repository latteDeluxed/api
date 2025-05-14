<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all enrollments, single enrollment, or enrollments by registration
        if (isset($_GET['enrollment_id'])) {
            $enrollment_id = $_GET['enrollment_id'];
            $stmt = $conn->prepare("SELECT e.*, r.student_id, r.course_id, r.status as registration_status 
                                   FROM Enrollments e
                                   JOIN CourseRegistrations r ON e.registration_id = r.registration_id
                                   WHERE e.enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($enrollment) {
                echo json_encode($enrollment);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Enrollment not found"]);
            }
        } elseif (isset($_GET['registration_id'])) {
            $registration_id = $_GET['registration_id'];
            $stmt = $conn->prepare("SELECT e.* FROM Enrollments e WHERE e.registration_id = ?");
            $stmt->execute([$registration_id]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($enrollments);
        } else {
            $stmt = $conn->query("SELECT e.*, r.student_id, r.course_id 
                                 FROM Enrollments e
                                 JOIN CourseRegistrations r ON e.registration_id = r.registration_id");
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($enrollments);
        }
        break;
        
    case 'POST':
        // Create new enrollment
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['registration_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "registration_id is required"]);
            exit();
        }
        
        try {
            // Check if registration exists and is approved
            $stmt = $conn->prepare("SELECT 1 FROM CourseRegistrations 
                                   WHERE registration_id = ? AND status = 'Approved'");
            $stmt->execute([$data['registration_id']]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(400);
                echo json_encode(["message" => "Registration not found or not approved"]);
                exit();
            }
            
            // Check if enrollment already exists for this registration
            $stmt = $conn->prepare("SELECT 1 FROM Enrollments WHERE registration_id = ?");
            $stmt->execute([$data['registration_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Enrollment already exists for this registration"]);
                exit();
            }
            
            // Create enrollment
            $stmt = $conn->prepare("INSERT INTO Enrollments (registration_id, status) 
                                   VALUES (?, ?)");
            $stmt->execute([
                $data['registration_id'],
                $data['status'] ?? 'Active'
            ]);
            
            $enrollment_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Enrollment created successfully",
                "enrollment_id" => $enrollment_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating enrollment: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update enrollment (mainly for status changes)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['enrollment_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "enrollment_id is required"]);
            exit();
        }
        
        $enrollment_id = $_GET['enrollment_id'];
        
        try {
            // Check if enrollment exists
            $stmt = $conn->prepare("SELECT 1 FROM Enrollments WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Enrollment not found"]);
                exit();
            }
            
            // Update status if provided
            if (isset($data['status'])) {
                $stmt = $conn->prepare("UPDATE Enrollments SET status = ? WHERE enrollment_id = ?");
                $stmt->execute([$data['status'], $enrollment_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(["message" => "Enrollment updated successfully"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Enrollment not found or no changes made"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "No valid fields to update"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating enrollment: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete enrollment
        if (!isset($_GET['enrollment_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "enrollment_id is required"]);
            exit();
        }
        
        $enrollment_id = $_GET['enrollment_id'];
        
        try {
            // First check if enrollment exists
            $stmt = $conn->prepare("SELECT 1 FROM Enrollments WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Enrollment not found"]);
                exit();
            }
            
            // Check if there are attendance records
            $stmt = $conn->prepare("SELECT 1 FROM Attendance WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete enrollment with attendance records"]);
                exit();
            }
            
            // Delete enrollment
            $stmt = $conn->prepare("DELETE FROM Enrollments WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            
            echo json_encode(["message" => "Enrollment deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting enrollment: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>