<?php
// config.php - Kết nối cơ sở dữ liệu cho XAMPP

header("Content-Type: application/json; charset=UTF-8");

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');     // Tên người dùng mặc định của XAMPP
define('DB_PASSWORD', '');         // Mật khẩu mặc định XAMPP là trống
define('DB_NAME', 'management'); // Tên database bạn tạo trong phpMyAdmin

// Xử lý kết nối PDO
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USERNAME, 
        DB_PASSWORD
    );
    
    // Thiết lập chế độ lỗi để PDO ném ngoại lệ khi có lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Cấu hình múi giờ
    $conn->exec("SET time_zone = '+07:00'");
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>