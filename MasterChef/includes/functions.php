<?php
/**
 * File chứa các hàm tiện ích cho website (Core Functions)
 */

// ============================================================================
// 1. NHÓM HÀM XỬ LÝ CHUỖI & ĐỊNH DẠNG
// ============================================================================

/**
 * Chuyển đổi ký tự tiếng Việt có dấu sang không dấu
 * (Cần thiết để tạo Slug chuẩn không bị lỗi font)
 */
function convertVietnamese($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
    $str = preg_replace("/(đ)/", "d", $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
    $str = preg_replace("/(Đ)/", "D", $str);
    return $str;
}

/**
 * Tạo slug chuẩn SEO từ chuỗi (Hỗ trợ tiếng Việt)
 * VD: "Món Ăn Ngon" -> "mon-an-ngon"
 */
function createSlug($string) {
    // 1. Chuyển tiếng Việt có dấu thành không dấu trước
    $string = convertVietnamese($string);
    
    // 2. Chuyển thành chữ thường
    $string = strtolower($string);
    
    // 3. Thay thế các ký tự không phải chữ cái hoặc số bằng dấu gạch ngang
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    
    // 4. Xóa nhiều dấu gạch ngang liên tiếp (--- -> -)
    $string = preg_replace('/-+/', '-', $string);
    
    // 5. Xóa gạch ngang ở đầu và cuối
    return trim($string, '-');
}

/**
 * Xóa các ký tự đặc biệt để chống XSS (Output Escaping)
 * Dùng khi hiển thị dữ liệu ra màn hình: echo sanitize($input);
 */
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Định dạng ngày tháng
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date; // Trả về nguyên gốc nếu lỗi
    }
}

/**
 * Tạo chuỗi ngẫu nhiên (Dùng cho tên file ảnh, token...)
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        // Dùng random_int an toàn hơn rand()
        try {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        } catch (Exception $e) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
    }
    return $randomString;
}

// ============================================================================
// 2. NHÓM HÀM AUTH & SESSION
// ============================================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    // Đảm bảo không có output trước khi header
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
    }
    exit;
}

function getCurrentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Thiết lập thông báo Flash (Hiện 1 lần rồi mất)
 * VD: setFlash('success', 'Đăng nhập thành công');
 */
function setFlash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'msg' => $message
    ];
}

/**
 * Hiển thị thông báo Flash
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $msg = $_SESSION['flash_message']['msg'];
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show my-3" role="alert">
                ' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        
        // Xóa sau khi hiện
        unset($_SESSION['flash_message']);
    }
}

// ============================================================================
// 3. NHÓM HÀM HIỂN THỊ HTML CƠ BẢN
// ============================================================================

function showError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

function showSuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// ============================================================================
// 4. NHÓM HÀM DATABASE HELPER
// ============================================================================

/**
 * Kiểm tra bài viết đã yêu thích chưa
 */
function isFavorite($db, $postId, $userId) {
    $db->query("SELECT id FROM favorites WHERE user_id = :user_id AND post_id = :post_id");
    $db->bind(':user_id', $userId);
    $db->bind(':post_id', $postId);
    $db->execute();
    return $db->rowCount() > 0;
}
?>
