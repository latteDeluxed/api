<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all feedback, single feedback, or filtered feedback
        if (isset($_GET['feedback_id'])) {
            $feedback_id = $_GET['feedback_id'];
            $stmt = $conn->prepare("SELECT f.*, s.title as survey_title, st.full_name as student_name 
                                  FROM Feedback f
                                  JOIN Surveys s ON f.survey_id = s.survey_id
                                  JOIN Students st ON f.student_id = st.student_id
                                  WHERE f.feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($feedback) {
                echo json_encode($feedback);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Feedback not found"]);
            }
        } elseif (isset($_GET['survey_id'])) {
            $survey_id = $_GET['survey_id'];
            $student_id = $_GET['student_id'] ?? null;
            
            if ($student_id) {
                // Get specific student's feedback for a survey
                $stmt = $conn->prepare("SELECT f.* FROM Feedback f 
                                      WHERE f.survey_id = ? AND f.student_id = ?");
                $stmt->execute([$survey_id, $student_id]);
                $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($feedback) {
                    echo json_encode($feedback);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Feedback not found"]);
                }
            } else {
                // Get all feedback for a survey
                $stmt = $conn->prepare("SELECT f.*, st.full_name as student_name 
                                      FROM Feedback f
                                      JOIN Students st ON f.student_id = st.student_id
                                      WHERE f.survey_id = ?");
                $stmt->execute([$survey_id]);
                $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($feedback);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT f.*, s.title as survey_title 
                                  FROM Feedback f
                                  JOIN Surveys s ON f.survey_id = s.survey_id
                                  WHERE f.student_id = ?");
            $stmt->execute([$student_id]);
            $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($feedback);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Please specify feedback_id, survey_id, or student_id"]);
        }
        break;
        
    case 'POST':
        // Submit new feedback
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['survey_id', 'student_id', 'response_data'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if survey exists and is active
            $stmt = $conn->prepare("SELECT 1 FROM Surveys 
                                  WHERE survey_id = ? AND is_active = 1 
                                  AND start_date <= NOW() AND end_date >= NOW()");
            $stmt->execute([$data['survey_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(400);
                echo json_encode(["message" => "Survey not found or not currently active"]);
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
            
            // Check if feedback already exists
            $stmt = $conn->prepare("SELECT 1 FROM Feedback WHERE survey_id = ? AND student_id = ?");
            $stmt->execute([$data['survey_id'], $data['student_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Feedback already submitted for this survey"]);
                exit();
            }
            
            // Validate JSON response data
            if (!is_array($data['response_data'])) {
                try {
                    $data['response_data'] = json_decode($data['response_data'], true, 512, JSON_THROW_ON_ERROR);
                } catch (Exception $e) {
                    http_response_code(400);
                    echo json_encode(["message" => "Invalid response_data format. Must be valid JSON"]);
                    exit();
                }
            }
            
            // Submit feedback
            $stmt = $conn->prepare("INSERT INTO Feedback 
                                  (survey_id, student_id, response_data) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([
                $data['survey_id'],
                $data['student_id'],
                json_encode($data['response_data'])
            ]);
            
            $feedback_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Feedback submitted successfully",
                "feedback_id" => $feedback_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error submitting feedback: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update feedback (admin only - typically shouldn't be allowed)
        http_response_code(403);
        echo json_encode(["message" => "Feedback submissions cannot be modified after submission"]);
        break;
        
    case 'DELETE':
        // Delete feedback (admin only)
        if (!isset($_GET['feedback_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "feedback_id is required"]);
            exit();
        }
        
        $feedback_id = $_GET['feedback_id'];
        
        try {
            // First check if feedback exists
            $stmt = $conn->prepare("SELECT 1 FROM Feedback WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Feedback not found"]);
                exit();
            }
            
            // Delete feedback
            $stmt = $conn->prepare("DELETE FROM Feedback WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            
            echo json_encode(["message" => "Feedback deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting feedback: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>