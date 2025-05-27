<?php
require_once '../config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['student_id'])) {
                // Lấy danh sách môn học đã đăng ký của sinh viên
                $student_id = $_GET['student_id'];
                
                // Kiểm tra sinh viên có tồn tại không
                $stmt = $conn->prepare("SELECT 1 FROM Students WHERE student_id = ?");
                $stmt->execute([$student_id]);
                if ($stmt->rowCount() == 0) {
                    http_response_code(404);
                    echo json_encode(["message" => "Student not found"]);
                    exit();
                }
                
                // Lấy danh sách môn học đã đăng ký
                $stmt = $conn->prepare("SELECT c.course_id, c.course_code, c.course_name, c.credits, r.registration_date 
                                      FROM CourseRegistrations r
                                      JOIN Courses c ON r.course_id = c.course_id
                                      WHERE r.student_id = ?");
                $stmt->execute([$student_id]);
                $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode($registrations);
                
            } elseif (isset($_GET['courses'])) {
				$stmt = $conn->query("SELECT * FROM Courses");
				$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
				echo json_encode($courses);
                
            } elseif (isset($_GET['registration_id'])) {
                // Lấy thông tin đăng ký cụ thể
                $registration_id = $_GET['registration_id'];
                $stmt = $conn->prepare("SELECT * FROM CourseRegistrations WHERE registration_id = ?");
                $stmt->execute([$registration_id]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($registration) {
                    echo json_encode($registration);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Registration not found"]);
                }
            } else {
                // Trả về tất cả đăng ký nếu không có tham số
                $stmt = $conn->query("SELECT * FROM CourseRegistrations");
                $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($registrations);
            }
            break;
            
        case 'POST':
            // Tạo đăng ký mới
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['student_id']) || empty($data['course_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required fields: student_id and course_id"]);
                exit();
            }
            
            // Kiểm tra sinh viên tồn tại
            $stmt = $conn->prepare("SELECT 1 FROM Students WHERE student_id = ?");
            $stmt->execute([$data['student_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Student not found"]);
                exit();
            }
            
            // Kiểm tra môn học tồn tại
            $stmt = $conn->prepare("SELECT 1 FROM Courses WHERE course_id = ?");
            $stmt->execute([$data['course_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Course not found"]);
                exit();
            }
            
            // Kiểm tra đã đăng ký chưa
            $stmt = $conn->prepare("SELECT 1 FROM CourseRegistrations 
                                   WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$data['student_id'], $data['course_id']]);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Student already registered for this course"]);
                exit();
            }
            
            // Tạo đăng ký mới
            $stmt = $conn->prepare("INSERT INTO CourseRegistrations 
                                  (student_id, course_id, status) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([
                $data['student_id'],
                $data['course_id'],
                $data['status'] ?? 'Pending'
            ]);
            
            http_response_code(201);
            $registration_id = $conn->lastInsertId();

			$stmt = $conn->prepare("SELECT c.course_id, c.course_code, c.course_name, c.credits, r.registration_date 
									FROM CourseRegistrations r
									JOIN Courses c ON r.course_id = c.course_id
									WHERE r.registration_id = ?");
			$stmt->execute([$registration_id]);
			$course = $stmt->fetch(PDO::FETCH_ASSOC);

			echo json_encode([
				"message" => "Đăng ký thành công",
				"registration_id" => $registration_id,
				"success" => true,
				"course" => $course
			]);

            break;
            
        case 'PUT':
            // Cập nhật trạng thái đăng ký
            if (!isset($_GET['registration_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "registration_id is required"]);
                exit();
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $registration_id = $_GET['registration_id'];
            
            if (empty($data['status'])) {
                http_response_code(400);
                echo json_encode(["message" => "status field is required"]);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE CourseRegistrations 
                                   SET status = ? 
                                   WHERE registration_id = ?");
            $stmt->execute([$data['status'], $registration_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Registration updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
            }
            break;
            
        case 'DELETE':
            // Xóa đăng ký
            if (!isset($_GET['registration_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "registration_id is required"]);
                exit();
            }
            
            $registration_id = $_GET['registration_id'];
            
            $stmt = $conn->prepare("DELETE FROM CourseRegistrations 
                                  WHERE registration_id = ?");
            $stmt->execute([$registration_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Registration deleted successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Registration not found"]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server error: " . $e->getMessage()]);
}
?>