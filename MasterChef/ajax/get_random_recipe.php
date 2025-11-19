<?php
// File: ajax/get_random_recipe.php
require_once '../config/config.php';
require_once '../config/database.php';

// Đảm bảo không có ký tự trắng thừa trước JSON
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();

    // 1. Lấy giờ hiện tại
    $currentHour = (int)date('G');

    // 2. Logic chọn danh mục
    $category_ids = [];
    $time_message = "";

    if ($currentHour >= 5 && $currentHour < 10) {
        $category_ids = [1, 3, 4]; // Sáng
        $time_message = "Chào buổi sáng! Gợi ý cho bạn nạp năng lượng:";
    } elseif ($currentHour >= 10 && $currentHour < 14) {
        $category_ids = [6, 7]; // Trưa
        $time_message = "Đến giờ trưa rồi! Ăn món này nhé:";
    } elseif ($currentHour >= 14 && $currentHour < 18) {
        $category_ids = [2, 4, 5]; // Chiều
        $time_message = "Bữa xế chiều nhẹ nhàng với:";
    } else {
        $category_ids = [5, 6, 7]; // Tối
        $time_message = "Bữa tối ấm cúng với món ngon này:";
    }

    $recipe = null;

    // 3. Truy vấn chính
    if (!empty($category_ids)) {
        $ids_string = implode(',', $category_ids);
        // Lấy đầy đủ các cột cần thiết
        $sql = "SELECT p.title, p.slug, p.thumbnail, c.name as category_name 
                FROM posts p
                JOIN food_categories c ON p.food_category_id = c.id
                WHERE p.food_category_id IN ($ids_string)
                ORDER BY RAND() 
                LIMIT 1";
        $db->query($sql);
        $recipe = $db->getOne();
    }

    // 4. Trả về kết quả
    if (!empty($recipe)) {
        echo json_encode([
            'success' => true,
            'message' => $time_message,
            'data' => $recipe
        ]);
    } else {
        // 5. Trường hợp dự phòng (Backup): Phải JOIN bảng category để lấy tên danh mục
        $sql_backup = "SELECT p.title, p.slug, p.thumbnail, c.name as category_name 
                       FROM posts p
                       JOIN food_categories c ON p.food_category_id = c.id
                       ORDER BY RAND() LIMIT 1";
        $db->query($sql_backup);
        $backup = $db->getOne();
        
        if ($backup) {
            echo json_encode([
                'success' => true,
                'message' => "Gợi ý ngẫu nhiên cho bạn:",
                'data' => $backup
            ]);
        } else {
            // Trường hợp database chưa có bài viết nào
            echo json_encode([
                'success' => false,
                'message' => "Chưa có dữ liệu món ăn nào."
            ]);
        }
    }

} catch (Exception $e) {
    // Bắt lỗi PHP để trả về JSON thay vì lỗi HTML chết trang
    echo json_encode([
        'success' => false,
        'message' => "Lỗi server: " . $e->getMessage()
    ]);
}
?>