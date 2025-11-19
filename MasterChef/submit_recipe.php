<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// BẮT BUỘC ĐĂNG NHẬP MỚI ĐƯỢC VÀO
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'Vui lòng đăng nhập để chia sẻ công thức!';
    redirect(SITE_URL . '/login.php');
}

$db = new Database();

// Lấy danh mục & loại bài viết
$db->query("SELECT * FROM food_categories ORDER BY name");
$categories = $db->getAll();
$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

$error = '';
$success = '';

// Xử lý khi bấm nút Đăng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = createSlug($title) . '-' . time(); // Thêm time để tránh trùng slug
    $content = $_POST['content'];
    $ingredients = trim($_POST['ingredients']);
    $food_category_id = (int)$_POST['food_category_id'];
    $post_type_id = (int)$_POST['post_type_id'];
    
    if (empty($title) || empty($content) || empty($ingredients)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    } else {
        // Xử lý Thumbnail
        $thumbnail_path = 'assets/images/default-thumbnail.jpg'; // Ảnh mặc định nếu k upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $new_name = uniqid('recipe_', true) . '.' . $ext;
                $path = 'uploads/thumbnails/' . $new_name;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $path)) {
                    $thumbnail_path = $path;
                }
            }
        }

        // Thêm vào CSDL
        $sql = "INSERT INTO posts (title, slug, content, ingredients, thumbnail, user_id, food_category_id, post_type_id, is_featured, view_count, created_at, updated_at) 
                VALUES (:title, :slug, :content, :ingredients, :thumbnail, :user_id, :cat_id, :type_id, 0, 0, NOW(), NOW())";
        
        $db->query($sql);
        $db->bind(':title', $title);
        $db->bind(':slug', $slug);
        $db->bind(':content', $content);
        $db->bind(':ingredients', $ingredients);
        $db->bind(':thumbnail', $thumbnail_path);
        $db->bind(':user_id', $_SESSION['user_id']); // Lấy ID người đang đăng nhập
        $db->bind(':cat_id', $food_category_id);
        $db->bind(':type_id', $post_type_id);

        if ($db->execute()) {
            $success = 'Công thức của bạn đã được đăng thành công!';
            // Reset form
            $title = $content = $ingredients = '';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
}

include 'layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0 text-center"><i class="fas fa-utensils me-2"></i>Chia sẻ công thức nấu ăn</h3>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?> <a href="<?php echo SITE_URL; ?>">Về trang chủ</a></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên món ăn <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control form-control-lg" placeholder="Ví dụ: Phở bò gia truyền..." required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nguyên liệu chuẩn bị <span class="text-danger">*</span></label>
                                    <textarea name="ingredients" class="form-control" rows="5" placeholder="- 500g thịt bò&#10;- 1kg bánh phở&#10;- Hành, tỏi, gừng..." required></textarea>
                                    <small class="text-muted">Mỗi nguyên liệu nên xuống dòng</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ảnh đại diện món ăn</label>
                                    <div class="card">
                                        <div class="card-body text-center p-2">
                                            <img id="preview" src="assets/images/placeholder.png" class="img-fluid mb-2" style="max-height: 200px; object-fit: cover;">
                                            <input type="file" name="thumbnail" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Danh mục</label>
                                    <select name="food_category_id" class="form-select">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Loại bài viết</label>
                                    <select name="post_type_id" class="form-select">
                                        <?php foreach($post_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold">Cách làm chi tiết <span class="text-danger">*</span></label>
                                <textarea name="content" id="editor"></textarea>
                            </div>

                            <div class="col-12 mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow">
                                    <i class="fas fa-paper-plane me-2"></i> Đăng công thức
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.16.2/standard-all/ckeditor.js"></script>
<script>
    // Xem trước ảnh
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Khởi tạo CKEditor
    CKEDITOR.replace('editor', {
        // Trỏ về file upload mới dành cho User
        filebrowserUploadUrl: '<?php echo SITE_URL; ?>/ajax/user_upload_image.php',
        height: 400,
        placeholder: 'Viết hướng dẫn chi tiết cách làm tại đây...'
    });
    
    // Hàm tạo slug đơn giản (Javascript)
    function createSlug(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Thay khoảng trắng bằng dấu gạch ngang
            .replace(/[^\w\-]+/g, '')       // Xóa ký tự đặc biệt
            .replace(/\-\-+/g, '-')         // Xóa gạch ngang lặp
            .replace(/^-+/, '')             // Xóa gạch ngang đầu
            .replace(/-+$/, '');            // Xóa gạch ngang cuối
    }
</script>

<?php include 'layouts/footer.php'; ?>