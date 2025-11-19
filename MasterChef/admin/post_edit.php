<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

// Kiểm tra ID bài viết
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID bài viết không hợp lệ';
    redirect(SITE_URL . '/admin/posts.php');
}

$post_id = (int)$_GET['id'];

// Khởi tạo database
$db = new Database();

// Lấy thông tin bài viết
$db->query("SELECT * FROM posts WHERE id = :id");
$db->bind(':id', $post_id);
$post = $db->getOne();

if (!$post) {
    $_SESSION['error_message'] = 'Bài viết không tồn tại';
    redirect(SITE_URL . '/admin/posts.php');
}

// Lấy danh sách danh mục món ăn
$db->query("SELECT * FROM food_categories ORDER BY name");
$categories = $db->getAll();

// Lấy danh sách loại bài viết
$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

// Khởi tạo biến từ dữ liệu cũ
$title = $post['title'];
$slug = $post['slug'];
$content = $post['content'];
$ingredients = isset($post['ingredients']) ? $post['ingredients'] : ''; // Lấy nguyên liệu cũ
$food_category_id = $post['food_category_id'];
$post_type_id = $post['post_type_id'];
$is_featured = $post['is_featured'];
$thumbnail = $post['thumbnail'];
$error = '';
$success = '';

// Xử lý cập nhật bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content'];
    $ingredients = $_POST['ingredients']; // Lấy nguyên liệu từ form
    $food_category_id = (int)$_POST['food_category_id'];
    $post_type_id = (int)$_POST['post_type_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Kiểm tra dữ liệu
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết';
    } elseif (empty($slug)) {
        $error = 'Vui lòng nhập slug cho bài viết';
    } elseif (empty($content)) {
        $error = 'Vui lòng nhập nội dung bài viết';
    } elseif (empty($food_category_id)) {
        $error = 'Vui lòng chọn danh mục món ăn';
    } elseif (empty($post_type_id)) {
        $error = 'Vui lòng chọn loại bài viết';
    } else {
        // Kiểm tra slug đã tồn tại chưa (không tính bài viết hiện tại)
        $db->query("SELECT id FROM posts WHERE slug = :slug AND id != :id");
        $db->bind(':slug', $slug);
        $db->bind(':id', $post_id);
        $db->execute();
        if ($db->rowCount() > 0) {
            $error = 'Slug đã tồn tại, vui lòng chọn slug khác';
        } else {
            // Xử lý upload hình ảnh thumbnail nếu có
            $thumbnail_path = $thumbnail;
            
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/thumbnails/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = $_FILES['thumbnail']['name'];
                $file_tmp = $_FILES['thumbnail']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name = uniqid('thumbnail_', true) . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $thumbnail_path = 'uploads/thumbnails/' . $new_file_name;
                        if (!empty($thumbnail) && file_exists('../' . $thumbnail) && $thumbnail != $thumbnail_path) {
                            unlink('../' . $thumbnail);
                        }
                    } else {
                        $error = 'Có lỗi khi upload hình ảnh thumbnail';
                    }
                } else {
                    $error = 'Định dạng file không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif';
                }
            }
            
            // Nếu không có lỗi, cập nhật bài viết vào database
            if (empty($error)) {
                // Sửa lại câu truy vấn UPDATE chính xác
                $sql = "UPDATE posts SET 
                        title = :title, 
                        slug = :slug, 
                        content = :content, 
                        ingredients = :ingredients, 
                        thumbnail = :thumbnail, 
                        food_category_id = :food_category_id, 
                        post_type_id = :post_type_id, 
                        is_featured = :is_featured, 
                        updated_at = NOW() 
                        WHERE id = :id";
                
                $db->query($sql);
                
                $db->bind(':title', $title);
                $db->bind(':slug', $slug);
                $db->bind(':content', $content);
                $db->bind(':ingredients', $ingredients); // Bind dữ liệu nguyên liệu
                $db->bind(':thumbnail', $thumbnail_path);
                $db->bind(':food_category_id', $food_category_id);
                $db->bind(':post_type_id', $post_type_id);
                $db->bind(':is_featured', $is_featured);
                $db->bind(':id', $post_id);
                
                if ($db->execute()) {
                    $success = 'Cập nhật bài viết thành công';
                    // Cập nhật lại biến hiển thị
                    $db->query("SELECT * FROM posts WHERE id = :id");
                    $db->bind(':id', $post_id);
                    $post = $db->getOne();
                    $title = $post['title'];
                    $slug = $post['slug'];
                    $content = $post['content'];
                    $ingredients = $post['ingredients'];
                    $thumbnail = $post['thumbnail'];
                } else {
                    $error = 'Đã xảy ra lỗi khi cập nhật bài viết';
                }
            }
        }
    }
}

$page_title = 'Sửa bài viết';
include 'layouts/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Sửa bài viết</h6>
        <div>
            <a href="<?php echo SITE_URL . '/post/' . $slug; ?>" class="btn btn-info" target="_blank">
                <i class="fas fa-eye"></i> Xem bài viết
            </a>
            <a href="posts.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề bài viết <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($slug); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Nguyên liệu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="5"><?php echo htmlspecialchars($ingredients); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Nội dung bài viết <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editor" name="content" rows="10"><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Hình ảnh thumbnail</label>
                        <?php if (!empty($thumbnail)): ?>
                            <div class="mb-2">
                                <img src="<?php echo SITE_URL . '/' . $thumbnail; ?>" alt="Current Thumbnail" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*" onchange="previewImage(this, 'thumbnailPreview')">
                        <div class="mt-2">
                            <img id="thumbnailPreview" src="#" alt="Thumbnail Preview" class="img-thumbnail" style="max-height: 200px; display: none;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="food_category_id" class="form-label">Danh mục món ăn <span class="text-danger">*</span></label>
                        <select class="form-control" id="food_category_id" name="food_category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($food_category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="post_type_id" class="form-label">Loại bài viết <span class="text-danger">*</span></label>
                        <select class="form-control" id="post_type_id" name="post_type_id" required>
                            <option value="">-- Chọn loại bài viết --</option>
                            <?php foreach($post_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo ($post_type_id == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo $type['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?php echo ($is_featured) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                Đánh dấu là bài viết nổi bật
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật bài viết
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.16.2/standard-all/ckeditor.js"></script>
<script>
    CKEDITOR.replace('editor', {
        filebrowserUploadUrl: '<?php echo SITE_URL; ?>/admin/upload_image.php',
        height: 400
    });
    
    document.getElementById('title').addEventListener('keyup', function() {
        if (document.getElementById('slug').getAttribute('data-modified') !== 'true') {
            var title = this.value;
            var slug = createSlug(title);
            document.getElementById('slug').value = slug;
        }
    });
    
    document.getElementById('slug').addEventListener('keyup', function() {
        this.setAttribute('data-modified', 'true');
    });
    
    function createSlug(text) {
        text = text.toLowerCase().trim();
        text = text.replace(/[áàảãạâấầẩẫậăắằẳẵặ]/g, 'a');
        text = text.replace(/[éèẻẽẹêếềểễệ]/g, 'e');
        text = text.replace(/[íìỉĩị]/g, 'i');
        text = text.replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o');
        text = text.replace(/[úùủũụưứừửữự]/g, 'u');
        text = text.replace(/[ýỳỷỹỵ]/g, 'y');
        text = text.replace(/đ/g, 'd');
        text = text.replace(/[^a-z0-9\s-]/g, ' ');
        text = text.replace(/\s+/g, '-');
        text = text.replace(/-+/g, '-');
        return text;
    }
    
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).style.display = 'block';
                document.getElementById(previewId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include 'layouts/footer.php'; ?>