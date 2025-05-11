<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all reports, single report, or filtered reports
        if (isset($_GET['report_id'])) {
            $report_id = $_GET['report_id'];
            $stmt = $conn->prepare("SELECT r.*, i.company_name, s.full_name as student_name 
                                  FROM InternshipReports r
                                  JOIN InternshipRegistrations i ON r.internship_id = i.internship_id
                                  JOIN Students s ON i.student_id = s.student_id
                                  WHERE r.report_id = ?");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report) {
                echo json_encode($report);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Report not found"]);
            }
        } elseif (isset($_GET['internship_id'])) {
            $internship_id = $_GET['internship_id'];
            $report_type = $_GET['report_type'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT r.* FROM InternshipReports r WHERE r.internship_id = ?";
            $params = [$internship_id];
            
            if ($report_type) {
                $sql .= " AND r.report_type = ?";
                $params[] = $report_type;
            }
            
            if ($status) {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY r.submission_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($reports);
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $stmt = $conn->prepare("SELECT r.*, i.company_name 
                                  FROM InternshipReports r
                                  JOIN InternshipRegistrations i ON r.internship_id = i.internship_id
                                  WHERE i.student_id = ?
                                  ORDER BY r.submission_date");
            $stmt->execute([$student_id]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($reports);
        } else {
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT r.*, i.company_name, s.full_name as student_name 
                   FROM InternshipReports r
                   JOIN InternshipRegistrations i ON r.internship_id = i.internship_id
                   JOIN Students s ON i.student_id = s.student_id";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE r.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY r.submission_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($reports);
        }
        break;
        
    case 'POST':
        // Create new internship report
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['internship_id', 'report_type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if internship exists
            $stmt = $conn->prepare("SELECT 1 FROM InternshipRegistrations WHERE internship_id = ?");
            $stmt->execute([$data['internship_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Internship not found"]);
                exit();
            }
            
            // Create report
            $stmt = $conn->prepare("INSERT INTO InternshipReports 
                                  (internship_id, report_type, file_path, content, status) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['internship_id'],
                $data['report_type'],
                $data['file_path'] ?? null,
                $data['content'] ?? null,
                $data['status'] ?? 'Submitted'
            ]);
            
            $report_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Internship report created successfully",
                "report_id" => $report_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating internship report: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update internship report
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['report_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "report_id is required"]);
            exit();
        }
        
        $report_id = $_GET['report_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['file_path', 'content', 'status', 'feedback'];
        
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
        
        $params[] = $report_id;
        
        try {
            $sql = "UPDATE InternshipReports SET " . implode(', ', $fields) . " WHERE report_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Internship report updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Report not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating internship report: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete internship report
        if (!isset($_GET['report_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "report_id is required"]);
            exit();
        }
        
        $report_id = $_GET['report_id'];
        
        try {
            // First check if report exists
            $stmt = $conn->prepare("SELECT 1 FROM InternshipReports WHERE report_id = ?");
            $stmt->execute([$report_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Report not found"]);
                exit();
            }
            
            // Delete report
            $stmt = $conn->prepare("DELETE FROM InternshipReports WHERE report_id = ?");
            $stmt->execute([$report_id]);
            
            echo json_encode(["message" => "Internship report deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting internship report: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>