class Database {
    private $host = "localhost";
    private $db_name = "giaovien";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
        } catch(PDOException $exception) {
            // Không hiển thị lỗi chi tiết cho client
            error_log("Database error: " . $exception->getMessage());
            die("Database connection failed.");
        }
        return $this->conn;
    }
}
