<?php
/**
 * Xử lý AJAX yêu cầu thêm/xóa bài viết khỏi danh sách yêu thích
 */
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra xem đây có phải là AJAX request không
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$is_ajax) {
    // Nếu không phải AJAX request, chuyển hướng về trang chủ
    redirect(SITE_URL);
}

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isLoggedIn()) {
    // Nếu chưa đăng nhập, trả về lỗi và URL để redirect
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện chức năng này',
        'redirect' => SITE_URL . '/login.php'
    ]);
    exit;
}

// Khởi tạo database
$db = new Database();

// Lấy ID bài viết từ request
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID bài viết không hợp lệ'
    ]);
    exit;
}

// Lấy ID người dùng từ session
$user_id = $_SESSION['user_id'];

// Kiểm tra xem bài viết đã được yêu thích chưa
$is_favorite = isFavorite($db, $post_id, $user_id);

if ($is_favorite) {
    // Nếu đã yêu thích, xóa khỏi danh sách yêu thích
    $db->query("DELETE FROM favorites WHERE user_id = :user_id AND post_id = :post_id");
    $db->bind(':user_id', $user_id);
    $db->bind(':post_id', $post_id);
    
    if ($db->execute()) {
        echo json_encode([
            'success' => true,
            'is_favorite' => false,
            'message' => 'Đã xóa bài viết khỏi danh sách yêu thích'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa bài viết khỏi danh sách yêu thích'
        ]);
    }
} else {
    // Nếu chưa yêu thích, thêm vào danh sách yêu thích
    $db->query("INSERT INTO favorites (user_id, post_id) VALUES (:user_id, :post_id)");
    $db->bind(':user_id', $user_id);
    $db->bind(':post_id', $post_id);
    
    if ($db->execute()) {
        echo json_encode([
            'success' => true,
            'is_favorite' => true,
            'message' => 'Đã thêm bài viết vào danh sách yêu thích'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thêm bài viết vào danh sách yêu thích'
        ]);
    }
} 