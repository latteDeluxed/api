php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET'
         Get all assignments, single assignment, or assignments by course
        if (isset($_GET['assignment_id'])) {
            $assignment_id = $_GET['assignment_id'];
            $stmt = $conn-prepare(SELECT a., c.course_code, c.course_name 
                                  FROM Assignments a
                                  JOIN Courses c ON a.course_id = c.course_id
                                  WHERE a.assignment_id = );
            $stmt-execute([$assignment_id]);
            $assignment = $stmt-fetch(PDOFETCH_ASSOC);
            
            if ($assignment) {
                echo json_encode($assignment);
            } else {
                http_response_code(404);
                echo json_encode([message = Assignment not found]);
            }
        } elseif (isset($_GET['course_id'])) {
            $course_id = $_GET['course_id'];
            
             Optional filter parameters
            $upcoming = isset($_GET['upcoming'])  true  false;
            $past = isset($_GET['past'])  true  false;
            
            if ($upcoming) {
                $stmt = $conn-prepare(SELECT a. FROM Assignments a 
                                      WHERE a.course_id =  
                                      AND a.due_date = NOW()
                                      ORDER BY a.due_date);
            } elseif ($past) {
                $stmt = $conn-prepare(SELECT a. FROM Assignments a 
                                      WHERE a.course_id =  
                                      AND a.due_date  NOW()
                                      ORDER BY a.due_date DESC);
            } else {
                $stmt = $conn-prepare(SELECT a. FROM Assignments a 
                                      WHERE a.course_id =  
                                      ORDER BY a.due_date);
            }
            
            $stmt-execute([$course_id]);
            $assignments = $stmt-fetchAll(PDOFETCH_ASSOC);
            echo json_encode($assignments);
        } else {
            $stmt = $conn-query(SELECT a., c.course_code, c.course_name 
                                 FROM Assignments a
                                 JOIN Courses c ON a.course_id = c.course_id
                                 ORDER BY a.due_date);
            $assignments = $stmt-fetchAll(PDOFETCH_ASSOC);
            echo json_encode($assignments);
        }
        break;
        
    case 'POST'
         Create new assignment
        $data = json_decode(file_get_contents(phpinput), true);
        
        $required_fields = ['course_id', 'title', 'due_date'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode([message = Missing required field $field]);
                exit();
            }
        }
        
        try {
             Check if course exists
            $stmt = $conn-prepare(SELECT 1 FROM Courses WHERE course_id = );
            $stmt-execute([$data['course_id']]);
            if ($stmt-rowCount() == 0) {
                http_response_code(404);
                echo json_encode([message = Course not found]);
                exit();
            }
            
             Create assignment
            $stmt = $conn-prepare(INSERT INTO Assignments (course_id, title, description, due_date, max_score) 
                                   VALUES (, , , , ));
            $stmt-execute([
                $data['course_id'],
                $data['title'],
                $data['description']  null,
                $data['due_date'],
                $data['max_score']  null
            ]);
            
            $assignment_id = $conn-lastInsertId();
            http_response_code(201);
            echo json_encode([
                message = Assignment created successfully,
                assignment_id = $assignment_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([message = Error creating assignment  . $e-getMessage()]);
        }
        break;
        
    case 'PUT'
         Update assignment
        $data = json_decode(file_get_contents(phpinput), true);
        
        if (!isset($_GET['assignment_id'])) {
            http_response_code(400);
            echo json_encode([message = assignment_id is required]);
            exit();
        }
        
        $assignment_id = $_GET['assignment_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['title', 'description', 'due_date', 'max_score'];
        
        foreach ($updatable_fields as $field) {
            if (isset($data[$field])) {
                $fields[] = $field = ;
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode([message = No fields to update]);
            exit();
        }
        
        $params[] = $assignment_id;
        
        try {
            $sql = UPDATE Assignments SET  . implode(', ', $fields) .  WHERE assignment_id = ;
            $stmt = $conn-prepare($sql);
            $stmt-execute($params);
            
            if ($stmt-rowCount()  0) {
                echo json_encode([message = Assignment updated successfully]);
            } else {
                http_response_code(404);
                echo json_encode([message = Assignment not found or no changes made]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([message = Error updating assignment  . $e-getMessage()]);
        }
        break;
        
    case 'DELETE'
         Delete assignment
        if (!isset($_GET['assignment_id'])) {
            http_response_code(400);
            echo json_encode([message = assignment_id is required]);
            exit();
        }
        
        $assignment_id = $_GET['assignment_id'];
        
        try {
             First check if assignment exists
            $stmt = $conn-prepare(SELECT 1 FROM Assignments WHERE assignment_id = );
            $stmt-execute([$assignment_id]);
            
            if ($stmt-rowCount() == 0) {
                http_response_code(404);
                echo json_encode([message = Assignment not found]);
                exit();
            }
            
             Delete assignment
            $stmt = $conn-prepare(DELETE FROM Assignments WHERE assignment_id = );
            $stmt-execute([$assignment_id]);
            
            echo json_encode([message = Assignment deleted successfully]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([message = Error deleting assignment  . $e-getMessage()]);
        }
        break;
        
    default
        http_response_code(405);
        echo json_encode([message = Method not allowed]);
        break;
}
