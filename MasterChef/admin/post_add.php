<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

// Khởi tạo database
$db = new Database();

// Lấy danh sách danh mục món ăn
$db->query("SELECT * FROM food_categories ORDER BY name");
$categories = $db->getAll();

// Lấy danh sách loại bài viết
$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

// Khởi tạo biến
$title = '';
$slug = '';
$content = '';
$ingredients = '';
$food_category_id = '';
$post_type_id = '';
$is_featured = 0;
$thumbnail = '';
$error = '';
$success = '';

// Xử lý thêm bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content'];
    $food_category_id = (int)$_POST['food_category_id'];
    $post_type_id = (int)$_POST['post_type_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $ingredients = trim($_POST['ingredients'] ?? '');
    
    // Kiểm tra dữ liệu
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết';
    } elseif (empty($slug)) {
        $error = 'Vui lòng nhập slug cho bài viết';
    } elseif (empty($content)) {
        $error = 'Vui lòng nhập nội dung bài viết';
    } elseif (empty($ingredients)) {
        $error = 'Vui lòng nhập nguyên liệu';
    } elseif (empty($food_category_id)) {
        $error = 'Vui lòng chọn danh mục món ăn';
    } elseif (empty($post_type_id)) {
        $error = 'Vui lòng chọn loại bài viết';
    } else {
        // Kiểm tra slug đã tồn tại chưa
        $db->query("SELECT id FROM posts WHERE slug = :slug");
        $db->bind(':slug', $slug);
        $db->execute();
        if ($db->rowCount() > 0) {
            $error = 'Slug đã tồn tại, vui lòng chọn slug khác';
        } else {
            // Xử lý upload hình ảnh thumbnail
            $thumbnail_path = '';
            $upload_success = false;
            
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/thumbnails/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Xử lý tên file
                $file_name = $_FILES['thumbnail']['name'];
                $file_tmp = $_FILES['thumbnail']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Kiểm tra định dạng file hợp lệ
                $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($file_ext, $allowed_exts)) {
                    // Tạo tên file ngẫu nhiên để tránh trùng lặp
                    $new_file_name = uniqid('thumbnail_', true) . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $thumbnail_path = 'uploads/thumbnails/' . $new_file_name;
                        $upload_success = true;
                    } else {
                        $error = 'Có lỗi khi upload hình ảnh thumbnail';
                    }
                } else {
                    $error = 'Định dạng file không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif';
                }
            } else {
                $error = 'Vui lòng chọn hình ảnh thumbnail cho bài viết';
            }
            
            // Nếu upload thành công, thêm bài viết vào database
            if ($upload_success) {
                $db->query("INSERT INTO posts (title, slug, content, ingredients, thumbnail, user_id, food_category_id, post_type_id, is_featured, created_at, updated_at) 
                    VALUES (:title, :slug, :content, :ingredients, :thumbnail, :user_id, :food_category_id, :post_type_id, :is_featured, NOW(), NOW())");
                $db->bind(':title', $title);
                $db->bind(':slug', $slug);
                $db->bind(':content', $content);
                $db->bind(':ingredients', $ingredients);
                $db->bind(':thumbnail', $thumbnail_path);
                $db->bind(':user_id', $_SESSION['user_id']);
                $db->bind(':food_category_id', $food_category_id);
                $db->bind(':post_type_id', $post_type_id);
                $db->bind(':is_featured', $is_featured);
                
                if ($db->execute()) {
                    // Thêm thành công
                    $_SESSION['success_message'] = 'Thêm bài viết mới thành công';
                    redirect(SITE_URL . '/admin/posts.php');
                } else {
                    $error = 'Đã xảy ra lỗi khi thêm bài viết';
                    
                    // Xóa file ảnh đã upload nếu thêm bài viết thất bại
                    if (file_exists('../' . $thumbnail_path)) {
                        unlink('../' . $thumbnail_path);
                    }
                }
            }
        }
    }
}

// Tiêu đề trang
$page_title = 'Thêm bài viết mới';
include 'layouts/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Thêm bài viết mới</h6>
        <a href="posts.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
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
                        <small class="form-text text-muted">Slug sẽ được sử dụng trong URL của bài viết. Ví dụ: bai-viet-moi</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Nguyên liệu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required><?php echo htmlspecialchars($ingredients); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Nội dung bài viết <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editor" name="content" rows="10"><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Hình ảnh thumbnail <span class="text-danger">*</span></label>
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
                            <i class="fas fa-save"></i> Lưu bài viết
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Import CKEditor -->
<script src="https://cdn.ckeditor.com/4.16.2/standard-all/ckeditor.js"></script>
<script>
    // Khởi tạo CKEditor
    CKEDITOR.replace('editor', {
        filebrowserUploadUrl: '<?php echo SITE_URL; ?>/admin/upload_image.php',
        height: 400,
        toolbarGroups: [
            { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
            { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
            { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
            { name: 'forms', groups: [ 'forms' ] },
            '/',
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
            { name: 'links', groups: [ 'links' ] },
            { name: 'insert', groups: [ 'insert' ] },
            '/',
            { name: 'styles', groups: [ 'styles' ] },
            { name: 'colors', groups: [ 'colors' ] },
            { name: 'tools', groups: [ 'tools' ] },
            { name: 'others', groups: [ 'others' ] },
            { name: 'about', groups: [ 'about' ] }
        ],
        removeButtons: 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Subscript,Superscript,CopyFormatting,RemoveFormat,Blockquote,CreateDiv,JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,BidiLtr,BidiRtl,Language,Anchor,Flash,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Maximize,ShowBlocks,About',
        extraPlugins: 'image2,uploadimage',
        removePlugins: 'image',
        contentsCss: ['https://cdn.ckeditor.com/4.16.2/full-all/contents.css']
    });
    
    // Tạo slug từ tiêu đề
    document.getElementById('title').addEventListener('keyup', function() {
        var title = this.value;
        var slug = createSlug(title);
        document.getElementById('slug').value = slug;
    });
    
    // Hàm tạo slug
    function createSlug(text) {
        // Chuyển về chữ thường và loại bỏ khoảng trắng ở 2 đầu
        text = text.toLowerCase().trim();
        
        // Chuyển đổi có dấu thành không dấu
        text = text.replace(/[áàảãạâấầẩẫậăắằẳẵặ]/g, 'a');
        text = text.replace(/[éèẻẽẹêếềểễệ]/g, 'e');
        text = text.replace(/[íìỉĩị]/g, 'i');
        text = text.replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o');
        text = text.replace(/[úùủũụưứừửữự]/g, 'u');
        text = text.replace(/[ýỳỷỹỵ]/g, 'y');
        text = text.replace(/đ/g, 'd');
        
        // Thay thế ký tự đặc biệt bằng khoảng trắng
        text = text.replace(/[^a-z0-9\s-]/g, ' ');
        
        // Thay thế khoảng trắng bằng dấu gạch ngang
        text = text.replace(/\s+/g, '-');
        
        // Thay thế nhiều dấu gạch ngang liên tiếp thành 1 dấu gạch ngang
        text = text.replace(/-+/g, '-');
        
        return text;
    }
    
    // Hiển thị preview hình ảnh khi chọn file
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