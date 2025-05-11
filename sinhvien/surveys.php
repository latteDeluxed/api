<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all surveys, single survey, or filtered surveys
        if (isset($_GET['survey_id'])) {
            $survey_id = $_GET['survey_id'];
            $stmt = $conn->prepare("SELECT s.*, u.full_name as creator_name 
                                  FROM Surveys s
                                  LEFT JOIN Users u ON s.created_by = u.user_id
                                  WHERE s.survey_id = ?");
            $stmt->execute([$survey_id]);
            $survey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($survey) {
                // Get response count for this survey
                $stmt = $conn->prepare("SELECT COUNT(*) as response_count FROM Feedback WHERE survey_id = ?");
                $stmt->execute([$survey_id]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                $survey['response_count'] = $count['response_count'];
                
                echo json_encode($survey);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Survey not found"]);
            }
        } else {
            $active = isset($_GET['active']) ? $_GET['active'] : null;
            $upcoming = isset($_GET['upcoming']) ? true : false;
            $past = isset($_GET['past']) ? true : false;
            
            $sql = "SELECT s.*, u.full_name as creator_name FROM Surveys s
                    LEFT JOIN Users u ON s.created_by = u.user_id";
            $where = [];
            $params = [];
            
            if ($active !== null) {
                $where[] = "s.is_active = ?";
                $params[] = $active ? 1 : 0;
            }
            
            if ($upcoming) {
                $where[] = "s.start_date > NOW()";
            } elseif ($past) {
                $where[] = "s.end_date < NOW()";
            } else {
                // Default to active surveys if no time filter
                if (empty($where)) {
                    $where[] = "s.is_active = 1 AND s.start_date <= NOW() AND s.end_date >= NOW()";
                }
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " ORDER BY s.start_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get response counts for each survey
            foreach ($surveys as &$survey) {
                $stmt = $conn->prepare("SELECT COUNT(*) as response_count FROM Feedback WHERE survey_id = ?");
                $stmt->execute([$survey['survey_id']]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                $survey['response_count'] = $count['response_count'];
            }
            
            echo json_encode($surveys);
        }
        break;
        
    case 'POST':
        // Create new survey
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['title', 'start_date', 'end_date'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate date range
        if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
            http_response_code(400);
            echo json_encode(["message" => "End date must be after start date"]);
            exit();
        }
        
        try {
            // Create survey
            $stmt = $conn->prepare("INSERT INTO Surveys 
                                  (title, description, start_date, end_date, is_active, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['start_date'],
                $data['end_date'],
                $data['is_active'] ?? true,
                $data['created_by'] ?? null
            ]);
            
            $survey_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Survey created successfully",
                "survey_id" => $survey_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating survey: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update survey
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['survey_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "survey_id is required"]);
            exit();
        }
        
        $survey_id = $_GET['survey_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['title', 'description', 'start_date', 'end_date', 'is_active'];
        
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
        
        // Validate date range if updating dates
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
                http_response_code(400);
                echo json_encode(["message" => "End date must be after start date"]);
                exit();
            }
        }
        
        $params[] = $survey_id;
        
        try {
            $sql = "UPDATE Surveys SET " . implode(', ', $fields) . " WHERE survey_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Survey updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Survey not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating survey: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete survey
        if (!isset($_GET['survey_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "survey_id is required"]);
            exit();
        }
        
        $survey_id = $_GET['survey_id'];
        
        try {
            // First check if survey exists
            $stmt = $conn->prepare("SELECT 1 FROM Surveys WHERE survey_id = ?");
            $stmt->execute([$survey_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Survey not found"]);
                exit();
            }
            
            // Check if there are responses
            $stmt = $conn->prepare("SELECT 1 FROM Feedback WHERE survey_id = ?");
            $stmt->execute([$survey_id]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete survey with existing responses"]);
                exit();
            }
            
            // Delete survey
            $stmt = $conn->prepare("DELETE FROM Surveys WHERE survey_id = ?");
            $stmt->execute([$survey_id]);
            
            echo json_encode(["message" => "Survey deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting survey: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>