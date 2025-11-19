<?php
// Thông tin kết nối cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'master_chef');

// Đường dẫn website (Sửa lại nếu thư mục của bạn khác)
define('SITE_URL', 'http://localhost/MasterChef');

// Cấu hình thời gian
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình hiển thị lỗi (Nên tắt khi chạy thật)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Khởi động Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
