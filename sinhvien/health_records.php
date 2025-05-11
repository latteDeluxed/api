<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all health records, single record, or filtered records
        if (isset($_GET['record_id'])) {
            $record_id = $_GET['record_id'];
            $stmt = $conn->prepare("SELECT h.*, s.full_name as student_name 
                                  FROM StudentHealthRecords h
                                  JOIN Students s ON h.student_id = s.student_id
                                  WHERE h.record_id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo json_encode($record);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Health record not found"]);
            }
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $record_type = $_GET['record_type'] ?? null;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            $sql = "SELECT h.* FROM StudentHealthRecords h WHERE h.student_id = ?";
            $params = [$student_id];
            
            if ($record_type) {
                $sql .= " AND h.record_type = ?";
                $params[] = $record_type;
            }
            
            if ($start_date) {
                $sql .= " AND h.record_date >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $sql .= " AND h.record_date <= ?";
                $params[] = $end_date;
            }
            
            $sql .= " ORDER BY h.record_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($records);
        } else {
            // For admin/health staff - get all records with filtering
            $record_type = $_GET['record_type'] ?? null;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            $sql = "SELECT h.*, s.full_name as student_name 
                   FROM StudentHealthRecords h
                   JOIN Students s ON h.student_id = s.student_id";
            $where = [];
            $params = [];
            
            if ($record_type) {
                $where[] = "h.record_type = ?";
                $params[] = $record_type;
            }
            
            if ($start_date) {
                $where[] = "h.record_date >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $where[] = "h.record_date <= ?";
                $params[] = $end_date;
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " ORDER BY h.record_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($records);
        }
        break;
        
    case 'POST':
        // Create new health record
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['student_id', 'record_date', 'record_type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
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
            
            // Validate record type
            $valid_types = ['Medical', 'Dental', 'Vaccination', 'Other'];
            if (!in_array($data['record_type'], $valid_types)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid record type"]);
                exit();
            }
            
            // Create health record
            $stmt = $conn->prepare("INSERT INTO StudentHealthRecords 
                                  (student_id, record_date, record_type, description, file_path, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['record_date'],
                $data['record_type'],
                $data['description'] ?? null,
                $data['file_path'] ?? null,
                $data['notes'] ?? null
            ]);
            
            $record_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Health record created successfully",
                "record_id" => $record_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating health record: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update health record
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['record_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "record_id is required"]);
            exit();
        }
        
        $record_id = $_GET['record_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['record_date', 'record_type', 'description', 'file_path', 'notes'];
        
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
        
        // Validate record type if updating
        if (isset($data['record_type'])) {
            $valid_types = ['Medical', 'Dental', 'Vaccination', 'Other'];
            if (!in_array($data['record_type'], $valid_types)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid record type"]);
                exit();
            }
        }
        
        $params[] = $record_id;
        
        try {
            $sql = "UPDATE StudentHealthRecords SET " . implode(', ', $fields) . " WHERE record_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Health record updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Health record not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating health record: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete health record
        if (!isset($_GET['record_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "record_id is required"]);
            exit();
        }
        
        $record_id = $_GET['record_id'];
        
        try {
            // First check if record exists
            $stmt = $conn->prepare("SELECT 1 FROM StudentHealthRecords WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Health record not found"]);
                exit();
            }
            
            // Delete record
            $stmt = $conn->prepare("DELETE FROM StudentHealthRecords WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            echo json_encode(["message" => "Health record deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting health record: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>