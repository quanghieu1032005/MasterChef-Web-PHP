<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Chỉ cần đăng nhập là được (không cần Admin)
if (!isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => ['message' => 'Vui lòng đăng nhập!']]);
    exit;
}

header('Content-Type: application/json');
$upload_dir = '../uploads/content/'; // Thư mục lưu ảnh bài viết

if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['upload'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'File không hợp lệ']]);
        exit;
    }
    
    $new_name = uniqid('user_img_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        echo json_encode([
            'uploaded' => 1,
            'fileName' => $new_name,
            'url' => SITE_URL . '/uploads/content/' . $new_name
        ]);
    } else {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Lỗi lưu file']]);
    }
}
?>