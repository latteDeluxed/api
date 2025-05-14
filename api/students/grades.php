<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all grades, single grade, or filtered grades
        if (isset($_GET['grade_id'])) {
            $grade_id = $_GET['grade_id'];
            $stmt = $conn->prepare("SELECT g.*, a.title as assignment_title, e.student_id, s.full_name as student_name
                                   FROM Grades g
                                   LEFT JOIN Assignments a ON g.assignment_id = a.assignment_id
                                   JOIN Enrollments e ON g.enrollment_id = e.enrollment_id
                                   JOIN Students s ON e.student_id = s.student_id
                                   WHERE g.grade_id = ?");
            $stmt->execute([$grade_id]);
            $grade = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($grade) {
                echo json_encode($grade);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Grade not found"]);
            }
        } elseif (isset($_GET['enrollment_id'])) {
            $enrollment_id = $_GET['enrollment_id'];
            $assignment_id = $_GET['assignment_id'] ?? null;
            
            if ($assignment_id) {
                // Get specific assignment grade for enrollment
                $stmt = $conn->prepare("SELECT g.* FROM Grades g 
                                      WHERE g.enrollment_id = ? AND g.assignment_id = ?");
                $stmt->execute([$enrollment_id, $assignment_id]);
            } else {
                // Get all grades for enrollment
                $stmt = $conn->prepare("SELECT g.*, a.title as assignment_title 
                                      FROM Grades g
                                      LEFT JOIN Assignments a ON g.assignment_id = a.assignment_id
                                      WHERE g.enrollment_id = ?");
                $stmt->execute([$enrollment_id]);
            }
            
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($grades);
        } elseif (isset($_GET['assignment_id'])) {
            $assignment_id = $_GET['assignment_id'];
            $stmt = $conn->prepare("SELECT g.*, e.student_id, s.full_name as student_name 
                                  FROM Grades g
                                  JOIN Enrollments e ON g.enrollment_id = e.enrollment_id
                                  JOIN Students s ON e.student_id = s.student_id
                                  WHERE g.assignment_id = ?");
            $stmt->execute([$assignment_id]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($grades);
        } elseif (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            $course_id = $_GET['course_id'] ?? null;
            
            $sql = "SELECT g.*, a.title as assignment_title, c.course_name 
                    FROM Grades g
                    LEFT JOIN Assignments a ON g.assignment_id = a.assignment_id
                    JOIN Enrollments e ON g.enrollment_id = e.enrollment_id
                    JOIN Courses c ON a.course_id = c.course_id
                    WHERE e.student_id = ?";
            
            $params = [$student_id];
            
            if ($course_id) {
                $sql .= " AND a.course_id = ?";
                $params[] = $course_id;
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($grades);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Please specify grade_id, enrollment_id, assignment_id, or student_id"]);
        }
        break;
        
    case 'POST':
        // Create new grade
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['enrollment_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "enrollment_id is required"]);
            exit();
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
            
            // If assignment_id is provided, check if it exists
            if (isset($data['assignment_id'])) {
                $stmt = $conn->prepare("SELECT 1 FROM Assignments WHERE assignment_id = ?");
                $stmt->execute([$data['assignment_id']]);
                if ($stmt->rowCount() == 0) {
                    http_response_code(404);
                    echo json_encode(["message" => "Assignment not found"]);
                    exit();
                }
                
                // Check if grade already exists for this assignment and enrollment
                $stmt = $conn->prepare("SELECT 1 FROM Grades WHERE enrollment_id = ? AND assignment_id = ?");
                $stmt->execute([$data['enrollment_id'], $data['assignment_id']]);
                if ($stmt->rowCount() > 0) {
                    http_response_code(409);
                    echo json_encode(["message" => "Grade already exists for this assignment and enrollment"]);
                    exit();
                }
            }
            
            // Calculate grade letter if score is provided
            $grade_letter = null;
            if (isset($data['score'])) {
                $grade_letter = calculateGradeLetter($data['score']);
            }
            
            // Create grade
            $stmt = $conn->prepare("INSERT INTO Grades (enrollment_id, assignment_id, score, grade_letter, comments, published) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['enrollment_id'],
                $data['assignment_id'] ?? null,
                $data['score'] ?? null,
                $grade_letter ?? $data['grade_letter'] ?? null,
                $data['comments'] ?? null,
                $data['published'] ?? false
            ]);
            
            $grade_id = $conn->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "message" => "Grade created successfully",
                "grade_id" => $grade_id,
                "grade_letter" => $grade_letter
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error creating grade: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update grade
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($_GET['grade_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "grade_id is required"]);
            exit();
        }
        
        $grade_id = $_GET['grade_id'];
        $fields = [];
        $params = [];
        
        $updatable_fields = ['score', 'grade_letter', 'comments', 'published'];
        
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
        
        // Recalculate grade letter if score is being updated
        if (isset($data['score'])) {
            $grade_letter = calculateGradeLetter($data['score']);
            $fields[] = "grade_letter = ?";
            $params[] = $grade_letter;
        }
        
        $params[] = $grade_id;
        
        try {
            $sql = "UPDATE Grades SET " . implode(', ', $fields) . " WHERE grade_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Grade updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Grade not found or no changes made"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error updating grade: " . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete grade
        if (!isset($_GET['grade_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "grade_id is required"]);
            exit();
        }
        
        $grade_id = $_GET['grade_id'];
        
        try {
            // First check if grade exists
            $stmt = $conn->prepare("SELECT 1 FROM Grades WHERE grade_id = ?");
            $stmt->execute([$grade_id]);
            
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Grade not found"]);
                exit();
            }
            
            // Delete grade
            $stmt = $conn->prepare("DELETE FROM Grades WHERE grade_id = ?");
            $stmt->execute([$grade_id]);
            
            echo json_encode(["message" => "Grade deleted successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting grade: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}

// Helper function to calculate grade letter
function calculateGradeLetter($score) {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'F';
}
?>