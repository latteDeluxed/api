<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get attendance records by enrollment, date range, or single record
        if (isset($_GET['attendance_id'])) {
            $attendance_id = $_GET['attendance_id'];
            $stmt = $conn->prepare("SELECT a.*, e.registration_id 
                                   FROM Attendance a
                                   JOIN Enrollments e ON a.enrollment_id = e.enrollment_id
                                   WHERE a.attendance_id = ?");
            $stmt->execute([$attendance_id]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance) {
                echo json_encode($attendance);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Attendance record not found"]);
            }
        } elseif (isset($_GET['enrollment_id'])) {
            $enrollment_id = $_GET['enrollment_id'];
            
            // Optional date range parameters
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            if ($start_date && $end_date) {
                $stmt = $conn->prepare("SELECT a.* FROM Attendance a 
                                      WHERE a.enrollment_id = ? 
                                      AND a.date BETWEEN ? AND ?
                                      ORDER BY a.date");
                $stmt->execute([$enrollment_id, $start_date, $end_date]);
            } else {
                $stmt = $conn->prepare("SELECT a.* FROM Attendance a 
                                      WHERE a.enrollment_id = ? 
                                      ORDER BY a.date");
                $stmt->execute([$enrollment_id]);
            }
            
            $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($attendance_records);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "enrollment_id or attendance_id is required"]);
        }
        break;
        
    case 'POST':
        // Create new attendance record
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['enrollment_id', 'date', 'status'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        try {
            // Check if enrollment exists
            $stmt = $conn->prepare("SELECT 1 FROM Enrollments WHERE enrollment_id = ?");
            $stmt->execute([$data['enrollment_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Enrollment not found"]);
                exit();
            }
            
            // Check if attendance record already exists for this date
            $stmt = $conn->prepare("SELECT 1 FROM Attendance WHERE enrollment_id = ? AND date = ?");
            $stmt->execute([$data['enrollment_id'], $data['date']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Attendance record already exists for this date"]);
                exit();
            }
            
            // Create attendance record
            $stmt = $conn->prepare("INSERT INTO Attendance (enrollment_id, date, status, notes) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['enrollment_id'],
                $data['date'],
                $data['status'],
                $data['notes'] ?? null
            ]);
            
            $attendance_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Attendance record created successfully",
                "attendance_id" => $attendance_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating attendance record: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update attendance record
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['attendance_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "attendance_id is required"]);
            exit();
        }
        
        $attendance_id = $_GET['attendance_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['status', 'notes'];
        
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
        
        $params[] = $attendance_id;
        
        try {
            $sql = "UPDATE Attendance SET " . implode(', ', $fields) . " WHERE attendance_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Attendance record updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Attendance record not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating attendance record: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete attendance record
        if (!isset($_GET['attendance_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "attendance_id is required"]);
            exit();
        }
        
        $attendance_id = $_GET['attendance_id'];
        
        try {
            // First check if attendance record exists
            $stmt = $conn->prepare("SELECT 1 FROM Attendance WHERE attendance_id = ?");
            $stmt->execute([$attendance_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Attendance record not found"]);
                exit();
            }
            
            // Delete attendance record
            $stmt = $conn->prepare("DELETE FROM Attendance WHERE attendance_id = ?");
            $stmt->execute([$attendance_id]);
            
            echo json_encode(["message" => "Attendance record deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting attendance record: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>