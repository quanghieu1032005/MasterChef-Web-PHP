<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect(SITE_URL . '/login.php'); }

$db = new Database();
$db->query("SELECT * FROM food_categories ORDER BY name");
$categories = $db->getAll();
$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content']; // Không trim content HTML
    $ingredients = trim($_POST['ingredients']);
    $cat_id = (int)$_POST['food_category_id'];
    $type_id = (int)$_POST['post_type_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Kiểm tra Slug tồn tại
    $db->query("SELECT id FROM posts WHERE slug = :slug");
    $db->bind(':slug', $slug);
    $db->execute();
    
    if ($db->rowCount() > 0) {
        $error = 'Slug này đã tồn tại, vui lòng chọn tiêu đề khác.';
    } else {
        // Xử lý upload Thumbnail bằng hàm chung
        $upload = uploadImage($_FILES['thumbnail'], 'thumbnails');
        
        if ($upload['success']) {
            $sql = "INSERT INTO posts (title, slug, content, ingredients, thumbnail, user_id, food_category_id, post_type_id, is_featured, created_at, updated_at) 
                    VALUES (:title, :slug, :content, :ing, :thumb, :uid, :cat, :type, :feat, NOW(), NOW())";
            
            $db->query($sql);
            $db->bind(':title', $title);
            $db->bind(':slug', $slug);
            $db->bind(':content', $content);
            $db->bind(':ing', $ingredients);
            $db->bind(':thumb', $upload['path']);
            $db->bind(':uid', $_SESSION['user_id']);
            $db->bind(':cat', $cat_id);
            $db->bind(':type', $type_id);
            $db->bind(':feat', $is_featured);

            if ($db->execute()) {
                $_SESSION['success_message'] = "Đăng bài viết thành công!";
                redirect('posts.php');
            } else {
                $error = "Lỗi khi lưu dữ liệu.";
                unlink('../' . $upload['path']); // Xóa ảnh nếu lưu DB thất bại (Dọn rác)
            }
        } else {
            $error = $upload['msg'];
        }
    }
}

$page_title = 'Thêm bài viết';
include 'layouts/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Thêm bài viết mới</h6>
        <a href="posts.php" class="btn btn-secondary btn-sm">Quay lại</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu đề</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" class="form-control" id="slug" name="slug" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nguyên liệu</label>
                        <textarea class="form-control" name="ingredients" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nội dung chi tiết</label>
                        <textarea class="form-control" id="editor" name="content"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ảnh đại diện</label>
                        <input type="file" class="form-control" name="thumbnail" required accept="image/*" onchange="previewImage(this)">
                        <img id="preview" src="#" class="img-thumbnail mt-2" style="display:none; max-height: 200px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Danh mục</label>
                        <select class="form-select" name="food_category_id">
                            <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại bài</label>
                        <select class="form-select" name="post_type_id">
                            <?php foreach($post_types as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="feat" name="is_featured">
                        <label class="form-check-label" for="feat">Bài viết nổi bật</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Đăng bài</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.16.2/standard-all/ckeditor.js"></script>
<script>
    CKEDITOR.replace('editor', { filebrowserUploadUrl: '<?php echo SITE_URL; ?>/admin/upload_image.php', height: 400 });
    document.getElementById('title').addEventListener('keyup', function() {
        document.getElementById('slug').value = createSlug(this.value);
    });
    function createSlug(str) {
        str = str.toLowerCase().trim();
        str = str.replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g,"a").replace(/[èéẹẻẽêềếệểễ]/g,"e").replace(/[ìíịỉĩ]/g,"i").replace(/[òóọỏõôồốộổỗơờớợởỡ]/g,"o").replace(/[ùúụủũưừứựửữ]/g,"u").replace(/[ỳýỵỷỹ]/g,"y").replace(/đ/g,"d");
        return str.replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-');
    }
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').style.display = 'block';
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<?php include 'layouts/footer.php'; ?>