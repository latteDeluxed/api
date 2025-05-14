<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all courses or single course
        if (isset($_GET['course_id'])) {
            $course_id = $_GET['course_id'];
            $stmt = $conn->prepare("SELECT * FROM Courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                echo json_encode($course);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Course not found"]);
            }
        } else {
            $stmt = $conn->query("SELECT * FROM Courses");
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($courses);
        }
        break;
        
    case 'POST':
        // Create new course
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['course_code', 'course_name', 'credits'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO Courses (course_code, course_name, credits, description, semester, academic_year) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['course_code'],
                $data['course_name'],
                $data['credits'],
                $data['description'] ?? null,
                $data['semester'] ?? null,
                $data['academic_year'] ?? null
            ]);
            
            $course_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Course created successfully",
                "course_id" => $course_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating course: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update course
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['course_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Course ID is required"]);
            exit();
        }
        
        $course_id = $_GET['course_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['course_code', 'course_name', 'credits', 'description', 'semester', 'academic_year'];
        
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
        
        $params[] = $course_id;
        
        try {
            $sql = "UPDATE Courses SET " . implode(', ', $fields) . " WHERE course_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Course updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Course not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating course: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete course
        if (!isset($_GET['course_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Course ID is required"]);
            exit();
        }
        
        $course_id = $_GET['course_id'];
        
        try {
            // First check if course exists
            $stmt = $conn->prepare("SELECT * FROM Courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Course not found"]);
                exit();
            }
            
            // Delete course
            $stmt = $conn->prepare("DELETE FROM Courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            echo json_encode(["message" => "Course deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting course: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>