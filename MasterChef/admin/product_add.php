<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) { redirect(SITE_URL . '/login.php'); }

$db = new Database();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = (int)$_POST['price'];
    $old_price = (int)$_POST['old_price'];
    $link = trim($_POST['affiliate_link']);
    $badge = trim($_POST['badge']);

    // Xử lý upload ảnh bằng hàm chung (Code sạch hơn rất nhiều)
    $upload = uploadImage($_FILES['image'], 'products');

    if ($upload['success']) {
        $sql = "INSERT INTO products (name, price, old_price, affiliate_link, image, badge) VALUES (:name, :price, :old, :link, :img, :badge)";
        $db->query($sql);
        $db->bind(':name', $name);
        $db->bind(':price', $price);
        $db->bind(':old', $old_price);
        $db->bind(':link', $link);
        $db->bind(':img', $upload['path']); // Lấy đường dẫn từ kết quả upload
        $db->bind(':badge', $badge);

        if ($db->execute()) {
            $_SESSION['success_message'] = "Thêm sản phẩm thành công!"; // Dùng session để hiện thông báo đẹp ở trang danh sách
            redirect('products.php');
        } else {
            $error = "Lỗi cơ sở dữ liệu!";
        }
    } else {
        $error = $upload['msg']; // Hiển thị lỗi từ hàm upload (ví dụ: file quá lớn)
    }
}

$page_title = 'Thêm sản phẩm';
include 'layouts/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Thêm sản phẩm</h6>
        <a href="products.php" class="btn btn-secondary btn-sm">Quay lại</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Giá bán <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Giá cũ</label>
                    <input type="number" name="old_price" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Link Shopee <span class="text-danger">*</span></label>
                <input type="url" name="affiliate_link" class="form-control" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nhãn (Ví dụ: -25%)</label>
                    <input type="text" name="badge" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Hình ảnh <span class="text-danger">*</span></label>
                    <input type="file" name="image" class="form-control" required accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu sản phẩm</button>
        </form>
    </div>
</div>
<?php include 'layouts/footer.php'; ?>