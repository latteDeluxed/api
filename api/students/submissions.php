<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all submissions, single submission, or filtered submissions
        if (isset($_GET['submission_id'])) {
            $submission_id = $_GET['submission_id'];
            $stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, st.full_name as student_name 
                                  FROM submissions s
                                  JOIN assignments a ON s.assignment_id = a.assignment_id
                                  JOIN students st ON s.student_id = st.student_id
                                  WHERE s.submission_id = ?");
            $stmt->execute([$submission_id]);
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($submission) {
                echo json_encode($submission);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Submission not found"]);
            }
        } elseif (isset($_GET['assignment_id'])) {
            $assignment_id = $_GET['assignment_id'];
            $student_id = $_GET['student_id'] ?? null;
            
            if ($student_id) {
                // Get specific student's submission for an assignment
                $stmt = $conn->prepare("SELECT s.* FROM submissions s 
                                      WHERE s.assignment_id = ? AND s.student_id = ?");
                $stmt->execute([$assignment_id, $student_id]);
            } else {
                // Get all submissions for an assignment
                $stmt = $conn->prepare("SELECT s.*, st.full_name as student_name 
                                      FROM submissions s
                                      JOIN students st ON s.student_id = st.student_id
                                      WHERE s.assignment_id = ?");
                $stmt->execute([$assignment_id]);
            }
            
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($submissions);
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT s.*, a.title as assignment_title 
                                   FROM submissions s
                                   JOIN assignments a ON s.assignment_id = a.assignment_id
                                   WHERE s.student_id = ?");
            $stmt->execute([$student_id]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($submissions);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Please specify submission_id, assignment_id, or student_id"]);
        }
        break;
        
    case 'POST':
        // Create new submission
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['assignment_id', 'student_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if assignment exists
            $stmt = $conn->prepare("SELECT 1 FROM assignments WHERE assignment_id = ?");
            $stmt->execute([$data['assignment_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Assignment not found"]);
                exit();
            }
            
            // Check if student exists
            $stmt = $conn->prepare("SELECT 1 FROM students WHERE student_id = ?");
            $stmt->execute([$data['student_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
                exit();
            }
            
            // Check if submission already exists
            $stmt = $conn->prepare("SELECT 1 FROM submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$data['assignment_id'], $data['student_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Submission already exists for this assignment and student"]);
                exit();
            }
            
            // Determine if submission is late
            $stmt = $conn->prepare("SELECT due_date FROM assignments WHERE assignment_id = ?");
            $stmt->execute([$data['assignment_id']]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            $status = (strtotime($data['submission_date'] ?? date('Y-m-d H:i:s')) > strtotime($assignment['due_date'])) 
                     ? 'Late' : 'Submitted';
            
            // Create submission
            $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_date, file_path, content, status) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['assignment_id'],
                $data['student_id'],
                $data['submission_date'] ?? date('Y-m-d H:i:s'),
                $data['file_path'] ?? null,
                $data['content'] ?? null,
                $status
            ]);
            
            $submission_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Submission created successfully",
                "submission_id" => $submission_id,
                "status" => $status
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating submission: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update submission
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['submission_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "submission_id is required"]);
            exit();
        }
        
        $submission_id = $_GET['submission_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['file_path', 'content', 'status'];
        
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
        
        $params[] = $submission_id;
        
        try {
            $sql = "UPDATE submissions SET " . implode(', ', $fields) . " WHERE submission_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Submission updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Submission not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating submission: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete submission
        if (!isset($_GET['submission_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "submission_id is required"]);
            exit();
        }
        
        $submission_id = $_GET['submission_id'];
        
        try {
            // First check if submission exists
            $stmt = $conn->prepare("SELECT 1 FROM submissions WHERE submission_id = ?");
            $stmt->execute([$submission_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Submission not found"]);
                exit();
            }
            
            // Delete submission
            $stmt = $conn->prepare("DELETE FROM submissions WHERE submission_id = ?");
            $stmt->execute([$submission_id]);
            
            echo json_encode(["message" => "Submission deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting submission: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>