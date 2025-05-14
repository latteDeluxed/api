<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all scores, single score, or filtered scores
        if (isset($_GET['score_id'])) {
            $score_id = $_GET['score_id'];
            $stmt = $conn->prepare("SELECT s.*, t.test_name, st.full_name as student_name 
                                  FROM TestScores s
                                  JOIN OnlineTests t ON s.test_id = t.test_id
                                  JOIN Students st ON s.student_id = st.student_id
                                  WHERE s.score_id = ?");
            $stmt->execute([$score_id]);
            $score = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($score) {
                echo json_encode($score);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Test score not found"]);
            }
        } elseif (isset($_GET['test_id'])) {
            $test_id = $_GET['test_id'];
            $student_id = $_GET['student_id'] ?? null;
            
            if ($student_id) {
                // Get specific student's score for a test
                $stmt = $conn->prepare("SELECT s.* FROM TestScores s 
                                      WHERE s.test_id = ? AND s.student_id = ?");
                $stmt->execute([$test_id, $student_id]);
            } else {
                // Get all scores for a test
                $stmt = $conn->prepare("SELECT s.*, st.full_name as student_name 
                                      FROM TestScores s
                                      JOIN Students st ON s.student_id = st.student_id
                                      WHERE s.test_id = ?");
                $stmt->execute([$test_id]);
            }
            
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($scores);
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $course_id = $_GET['course_id'] ?? null;
            
            $sql = "SELECT s.*, t.test_name, t.course_id, c.course_name 
                    FROM TestScores s
                    JOIN OnlineTests t ON s.test_id = t.test_id
                    JOIN Courses c ON t.course_id = c.course_id
                    WHERE s.student_id = ?";
            
            $params = [$student_id];
            
            if ($course_id) {
                $sql .= " AND t.course_id = ?";
                $params[] = $course_id;
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($scores);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Please specify score_id, test_id, or student_id"]);
        }
        break;
        
    case 'POST':
        // Create new test score
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['test_id', 'student_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if test exists
            $stmt = $conn->prepare("SELECT 1 FROM OnlineTests WHERE test_id = ?");
            $stmt->execute([$data['test_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Test not found"]);
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
            
            // Check if score already exists
            $stmt = $conn->prepare("SELECT 1 FROM TestScores WHERE test_id = ? AND student_id = ?");
            $stmt->execute([$data['test_id'], $data['student_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Test score already exists for this student"]);
                exit();
            }
            
            // Determine status based on provided data
            $status = 'Pending';
            if (isset($data['score']) || isset($data['completion_time'])) {
                $status = 'Completed';
            }
            
            // Create test score
            $stmt = $conn->prepare("INSERT INTO TestScores (test_id, student_id, score, completion_time, status) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['test_id'],
                $data['student_id'],
                $data['score'] ?? null,
                $data['completion_time'] ?? null,
                $status
            ]);
            
            $score_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Test score recorded successfully",
                "score_id" => $score_id,
                "status" => $status
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error recording test score: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update test score
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['score_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "score_id is required"]);
            exit();
        }
        
        $score_id = $_GET['score_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['score', 'completion_time', 'status'];
        
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
        
        // Automatically set status to Completed if score is being updated
        if (isset($data['score'])) {
            if (!in_array('status = ?', $fields)) {
                $fields[] = "status = ?";
                $params[] = 'Completed';
            }
        }
        
        $params[] = $score_id;
        
        try {
            $sql = "UPDATE TestScores SET " . implode(', ', $fields) . " WHERE score_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Test score updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Test score not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating test score: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete test score
        if (!isset($_GET['score_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "score_id is required"]);
            exit();
        }
        
        $score_id = $_GET['score_id'];
        
        try {
            // First check if score exists
            $stmt = $conn->prepare("SELECT 1 FROM TestScores WHERE score_id = ?");
            $stmt->execute([$score_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Test score not found"]);
                exit();
            }
            
            // Delete score
            $stmt = $conn->prepare("DELETE FROM TestScores WHERE score_id = ?");
            $stmt->execute([$score_id]);
            
            echo json_encode(["message" => "Test score deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting test score: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>