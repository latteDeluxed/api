<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all internships, single internship, or internships by student
        if (isset($_GET['internship_id'])) {
            $internship_id = $_GET['internship_id'];
            $stmt = $conn->prepare("SELECT i.*, s.full_name as student_name 
                                  FROM InternshipRegistrations i
                                  JOIN Students s ON i.student_id = s.student_id
                                  WHERE i.internship_id = ?");
            $stmt->execute([$internship_id]);
            $internship = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($internship) {
                echo json_encode($internship);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Internship not found"]);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT i.* FROM InternshipRegistrations i WHERE i.student_id = ?";
            $params = [$student_id];
            
            if ($status) {
                $sql .= " AND i.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY i.start_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($internships);
        } else {
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT i.*, s.full_name as student_name 
                   FROM InternshipRegistrations i
                   JOIN Students s ON i.student_id = s.student_id";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE i.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY i.start_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($internships);
        }
        break;
        
    case 'POST':
        // Create new internship registration
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['student_id', 'company_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate date range if both dates are provided
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                http_response_code(400);
                echo json_encode(["message" => "End date must be after start date"]);
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
            
            // Create internship
            $stmt = $conn->prepare("INSERT INTO InternshipRegistrations 
                                  (student_id, company_name, position, start_date, end_date, 
                                  supervisor_name, supervisor_email, supervisor_phone, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['company_name'],
                $data['position'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['supervisor_name'] ?? null,
                $data['supervisor_email'] ?? null,
                $data['supervisor_phone'] ?? null,
                $data['status'] ?? 'Planning'
            ]);
            
            $internship_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Internship registration created successfully",
                "internship_id" => $internship_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating internship registration: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update internship registration
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['internship_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "internship_id is required"]);
            exit();
        }
        
        $internship_id = $_GET['internship_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['company_name', 'position', 'start_date', 'end_date', 
                            'supervisor_name', 'supervisor_email', 'supervisor_phone', 'status'];
        
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
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                http_response_code(400);
                echo json_encode(["message" => "End date must be after start date"]);
                exit();
            }
        }
        
        $params[] = $internship_id;
        
        try {
            $sql = "UPDATE InternshipRegistrations SET " . implode(', ', $fields) . " WHERE internship_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Internship registration updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Internship not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating internship registration: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete internship registration
        if (!isset($_GET['internship_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "internship_id is required"]);
            exit();
        }
        
        $internship_id = $_GET['internship_id'];
        
        try {
            // First check if internship exists
            $stmt = $conn->prepare("SELECT 1 FROM InternshipRegistrations WHERE internship_id = ?");
            $stmt->execute([$internship_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Internship not found"]);
                exit();
            }
            
            // Check if there are reports
            $stmt = $conn->prepare("SELECT 1 FROM InternshipReports WHERE internship_id = ?");
            $stmt->execute([$internship_id]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete internship with existing reports"]);
                exit();
            }
            
            // Delete internship
            $stmt = $conn->prepare("DELETE FROM InternshipRegistrations WHERE internship_id = ?");
            $stmt->execute([$internship_id]);
            
            echo json_encode(["message" => "Internship registration deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting internship registration: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>