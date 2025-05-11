<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all events, single event, or filtered events
        if (isset($_GET['event_id'])) {
            $event_id = $_GET['event_id'];
            $stmt = $conn->prepare("SELECT e.*, 
                                  (SELECT COUNT(*) FROM EventRegistrations WHERE event_id = e.event_id) AS registered_count
                                  FROM Events e
                                  WHERE e.event_id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                echo json_encode($event);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Event not found"]);
            }
        } else {
            $active = isset($_GET['active']) ? $_GET['active'] : null;
            $upcoming = isset($_GET['upcoming']) ? true : false;
            $past = isset($_GET['past']) ? true : false;
            
            $sql = "SELECT e.*, 
                   (SELECT COUNT(*) FROM EventRegistrations WHERE event_id = e.event_id) AS registered_count
                   FROM Events e";
            $where = [];
            $params = [];
            
            if ($active !== null) {
                $where[] = "e.is_active = ?";
                $params[] = $active ? 1 : 0;
            }
            
            if ($upcoming) {
                $where[] = "e.start_datetime > NOW()";
            } elseif ($past) {
                $where[] = "e.end_datetime < NOW()";
            } else {
                // Default to active upcoming events if no time filter
                if (empty($where)) {
                    $where[] = "e.is_active = 1 AND e.start_datetime > NOW()";
                }
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " ORDER BY e.start_datetime";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($events);
        }
        break;
        
    case 'POST':
        // Create new event
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required_fields = ['event_name', 'start_datetime', 'end_datetime'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate date range
        if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
            http_response_code(400);
            echo json_encode(["message" => "End datetime must be after start datetime"]);
            exit();
        }
        
        try {
            // Create event
            $stmt = $conn->prepare("INSERT INTO Events 
                                  (event_name, description, start_datetime, end_datetime, 
                                  location, organizer, capacity, is_active) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['event_name'],
                $data['description'] ?? null,
                $data['start_datetime'],
                $data['end_datetime'],
                $data['location'] ?? null,
                $data['organizer'] ?? null,
                $data['capacity'] ?? null,
                $data['is_active'] ?? true
            ]);
            
            $event_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Event created successfully",
                "event_id" => $event_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating event: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update event
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['event_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "event_id is required"]);
            exit();
        }
        
        $event_id = $_GET['event_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['event_name', 'description', 'start_datetime', 'end_datetime', 
                            'location', 'organizer', 'capacity', 'is_active'];
        
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
        
        // Validate date range if updating datetimes
        if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
            if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
                http_response_code(400);
                echo json_encode(["message" => "End datetime must be after start datetime"]);
                exit();
            }
        }
        
        $params[] = $event_id;
        
        try {
            $sql = "UPDATE Events SET " . implode(', ', $fields) . " WHERE event_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Event updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Event not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating event: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete event
        if (!isset($_GET['event_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "event_id is required"]);
            exit();
        }
        
        $event_id = $_GET['event_id'];
        
        try {
            // First check if event exists
            $stmt = $conn->prepare("SELECT 1 FROM Events WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Event not found"]);
                exit();
            }
            
            // Check if there are registrations
            $stmt = $conn->prepare("SELECT 1 FROM EventRegistrations WHERE event_id = ?");
            $stmt->execute([$event_id]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete event with existing registrations"]);
                exit();
            }
            
            // Delete event
            $stmt = $conn->prepare("DELETE FROM Events WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            echo json_encode(["message" => "Event deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting event: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>