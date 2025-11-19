<?php
// File: ajax/search_suggestions.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = new Database();

// 1. Lấy tham số từ URL
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$post_type_slug = isset($_GET['type']) ? trim($_GET['type']) : '';
$ingredients_input = isset($_GET['ingredients']) ? trim($_GET['ingredients']) : '';

// Nếu không có từ khóa, trả về rỗng
if ($q === '') {
    echo json_encode([]);
    exit;
}

// 2. Xây dựng câu truy vấn
// Lưu ý: Chỉ tìm kiếm theo Title như bạn yêu cầu
$sql = "SELECT p.id, p.title, p.slug, p.thumbnail, 
        fc.name as category_name, pt.name as type_name
        FROM posts p
        LEFT JOIN food_categories fc ON p.food_category_id = fc.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.title LIKE :keyword";

$params = [':keyword' => "%$q%"];

// Lọc theo danh mục
if (!empty($category_slug)) {
    $sql .= " AND fc.slug = :category";
    $params[':category'] = $category_slug;
}

// Lọc theo loại bài viết
if (!empty($post_type_slug)) {
    $sql .= " AND pt.slug = :type";
    $params[':type'] = $post_type_slug;
}

// Lọc theo nguyên liệu (Logic quan trọng)
if (!empty($ingredients_input)) {
    $ing_list = explode(',', $ingredients_input);
    $i = 0;
    foreach ($ing_list as $ing) {
        $ing = trim($ing);
        if (!empty($ing)) {
            // Tạo tên tham số động: :ing_0, :ing_1...
            $key = ":ing_$i";
            $sql .= " AND p.ingredients LIKE $key";
            $params[$key] = "%$ing%";
            $i++;
        }
    }
}

// Giới hạn 5 kết quả mới nhất
$sql .= " ORDER BY p.created_at DESC LIMIT 5";

try {
    $db->query($sql);
    // Bind tất cả tham số
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $results = $db->getAll();
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>