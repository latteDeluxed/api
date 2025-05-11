<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all tests, single test, or tests by course
        if (isset($_GET['test_id'])) {
            $test_id = $_GET['test_id'];
            $stmt = $conn->prepare("SELECT t.*, c.course_code, c.course_name 
                                  FROM OnlineTests t
                                  JOIN Courses c ON t.course_id = c.course_id
                                  WHERE t.test_id = ?");
            $stmt->execute([$test_id]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($test) {
                echo json_encode($test);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Test not found"]);
            }
        } elseif (isset($_GET['course_id'])) {
            $course_id = $_GET['course_id'];
            
            // Optional filter parameters
            $upcoming = isset($_GET['upcoming']) ? true : false;
            $active = isset($_GET['active']) ? true : false;
            $past = isset($_GET['past']) ? true : false;
            
            $sql = "SELECT t.* FROM OnlineTests t WHERE t.course_id = ?";
            
            if ($upcoming) {
                $sql .= " AND t.start_time > NOW()";
            } elseif ($active) {
                $sql .= " AND t.start_time <= NOW() AND t.end_time >= NOW()";
            } elseif ($past) {
                $sql .= " AND t.end_time < NOW()";
            }
            
            $sql .= " ORDER BY t.start_time";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$course_id]);
            $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tests);
        } else {
            $stmt = $conn->query("SELECT t.*, c.course_code, c.course_name 
                                FROM OnlineTests t
                                JOIN Courses c ON t.course_id = c.course_id
                                ORDER BY t.start_time");
            $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tests);
        }
        break;
        
    case 'POST':
        // Create new test
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['course_id', 'test_name', 'start_time', 'end_time'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if course exists
            $stmt = $conn->prepare("SELECT 1 FROM Courses WHERE course_id = ?");
            $stmt->execute([$data['course_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Course not found"]);
                exit();
            }
            
            // Validate time range
            if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                http_response_code(400);
                echo json_encode(["message" => "End time must be after start time"]);
                exit();
            }
            
            // Create test
            $stmt = $conn->prepare("INSERT INTO OnlineTests (course_id, test_name, description, start_time, end_time, duration_minutes, max_score) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['course_id'],
                $data['test_name'],
                $data['description'] ?? null,
                $data['start_time'],
                $data['end_time'],
                $data['duration_minutes'] ?? null,
                $data['max_score'] ?? null
            ]);
            
            $test_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Test created successfully",
                "test_id" => $test_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating test: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update test
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['test_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "test_id is required"]);
            exit();
        }
        
        $test_id = $_GET['test_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['test_name', 'description', 'start_time', 'end_time', 'duration_minutes', 'max_score'];
        
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
        
        // Validate time range if updating times
        if (isset($data['start_time']) && isset($data['end_time'])) {
            if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                http_response_code(400);
                echo json_encode(["message" => "End time must be after start time"]);
                exit();
            }
        }
        
        $params[] = $test_id;
        
        try {
            $sql = "UPDATE OnlineTests SET " . implode(', ', $fields) . " WHERE test_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Test updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Test not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating test: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete test
        if (!isset($_GET['test_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "test_id is required"]);
            exit();
        }
        
        $test_id = $_GET['test_id'];
        
        try {
            // First check if test exists
            $stmt = $conn->prepare("SELECT 1 FROM OnlineTests WHERE test_id = ?");
            $stmt->execute([$test_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Test not found"]);
                exit();
            }
            
            // Check if there are test scores
            $stmt = $conn->prepare("SELECT 1 FROM TestScores WHERE test_id = ?");
            $stmt->execute([$test_id]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete test with existing scores"]);
                exit();
            }
            
            // Delete test
            $stmt = $conn->prepare("DELETE FROM OnlineTests WHERE test_id = ?");
            $stmt->execute([$test_id]);
            
            echo json_encode(["message" => "Test deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting test: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>