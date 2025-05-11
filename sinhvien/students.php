<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all students or single student
        if (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT * FROM Students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                echo json_encode($student);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
            }
        } else {
            $stmt = $conn->query("SELECT * FROM Students");
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($students);
        }
        break;
        
    case 'POST':
        // Create new student
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['student_code', 'full_name', 'email'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO Students (student_code, full_name, email, phone_number, date_of_birth, gender, address, major, academic_year) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['student_code'],
                $data['full_name'],
                $data['email'],
                $data['phone_number'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['address'] ?? null,
                $data['major'] ?? null,
                $data['academic_year'] ?? null
            ]);
            
            $student_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Student created successfully",
                "student_id" => $student_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating student: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update student
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['student_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Student ID is required"]);
            exit();
        }
        
        $student_id = $_GET['student_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['student_code', 'full_name', 'email', 'phone_number', 'date_of_birth', 'gender', 'address', 'major', 'academic_year'];
        
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
        
        $params[] = $student_id;
        
        try {
            $sql = "UPDATE Students SET " . implode(', ', $fields) . " WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Student updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Student not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating student: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete student
        if (!isset($_GET['student_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Student ID is required"]);
            exit();
        }
        
        $student_id = $_GET['student_id'];
        
        try {
            // First check if student exists
            $stmt = $conn->prepare("SELECT * FROM Students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
                exit();
            }
            
            // Delete student
            $stmt = $conn->prepare("DELETE FROM Students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            echo json_encode(["message" => "Student deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting student: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>