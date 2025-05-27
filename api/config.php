<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Định nghĩa API key
define('API_KEY', '[x%8hc^7TP;=H@zx@Spk');

// Lấy header và query string
$headers = getallheaders();
$headerKey = isset($headers['Authorization']) ? $headers['Authorization'] : null;
$queryKey = isset($_GET['api_key']) ? $_GET['api_key'] : null;

// Ưu tiên kiểm tra header trước, sau đó tới query string
$valid = false;
if ($headerKey === 'Bearer ' . API_KEY) {
    $valid = true;
} elseif ($queryKey === API_KEY) {
    $valid = true;
}

if (!$valid) {
    http_response_code(401);
    echo json_encode(["message" => "Không có quyền truy cập. API key không hợp lệ."]);
    exit();
}

// Thông tin kết nối CSDL
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'management');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USERNAME,
        DB_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Kết nối CSDL thất bại: " . $e->getMessage()]);
    exit();
}
?>
