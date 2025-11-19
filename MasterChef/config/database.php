<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    private $stmt;
    private $result;
    private $bindParams = [];
    private $bindTypes = '';
    
    public function __construct() {
        // Kết nối mysqli
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        // Kiểm tra kết nối
        if ($this->conn->connect_error) {
            $this->error = $this->conn->connect_error;
            echo "Lỗi kết nối: " . $this->error;
            exit;
        }
        
        // Đặt charset utf8mb4
        $this->conn->set_charset("utf8mb4");
    }
    
    // Chuẩn bị câu lệnh
    public function query($query) {
        // Chuyển đổi các tham số có tên (:param) thành dấu ? cho mysqli
        $this->bindParams = [];
        $this->bindTypes = '';
        
        // Chuyển query từ dạng :param sang dạng ?
        $query = preg_replace('/:([a-zA-Z0-9_]+)/', '?', $query);
        
        $this->stmt = $this->conn->prepare($query);
        
        if (!$this->stmt) {
            $this->error = $this->conn->error;
            echo "Lỗi prepare query: " . $this->error;
            return false;
        }
        
        return true;
    }
    
    // Bind values 
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            if (is_int($value)) {
                $type = 'i';    // integer
            } elseif (is_double($value) || is_float($value)) {
                $type = 'd';    // double
            } elseif (is_string($value)) {
                $type = 's';    // string
            } else {
                $type = 'b';    // blob
            }
        }
        
        // Thêm tham số và kiểu dữ liệu vào danh sách
        $this->bindParams[] = $value;
        $this->bindTypes .= $type;
        
        return true;
    }
    
    // Thực thi câu lệnh
    public function execute() {
        if (!$this->stmt) {
            return false;
        }
        
        // Nếu có tham số để bind
        if (!empty($this->bindParams)) {
            // Tạo mảng tham số cho bind_param
            $params = [$this->bindTypes];
            for ($i = 0; $i < count($this->bindParams); $i++) {
                $params[] = &$this->bindParams[$i];
            }
            
            // Gọi bind_param với các tham số đã chuẩn bị
            call_user_func_array([$this->stmt, 'bind_param'], $params);
        }
        
        // Thực thi truy vấn
        $success = $this->stmt->execute();
        
        if ($success) {
            $this->result = $this->stmt->get_result();
        } else {
            $this->error = $this->stmt->error;
        }
        
        return $success;
    }
    
    // Lấy nhiều bản ghi
    public function getAll() {
        $this->execute();
        
        if (!$this->result) {
            return [];
        }
        
        $results = [];
        while ($row = $this->result->fetch_assoc()) {
            $results[] = $row;
        }
        return $results;
    }
    
    // Lấy một bản ghi
    public function getOne() {
        $this->execute();
        
        if (!$this->result) {
            return null;
        }
        
        return $this->result->fetch_assoc();
    }
    
    // Đếm số bản ghi 
    public function rowCount() {
        if (!$this->result) {
            $this->execute();
            if (!$this->result) {
                return 0;
            }
        }
        
        return $this->result->num_rows;
    }
    
    // Lấy ID mới nhất
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    // Bắt đầu transaction
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollBack() {
        return $this->conn->rollback();
    }
    
    // Lấy thông tin lỗi
    public function getError() {
        return $this->error;
    }
    
    // Đóng kết nối
    public function __destruct() {
        if ($this->stmt) {
            $this->stmt->close();
        }
        if ($this->conn) {
            $this->conn->close();
        }
    }
} 