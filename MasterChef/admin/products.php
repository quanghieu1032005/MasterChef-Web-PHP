<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra Admin
if (!isLoggedIn() || !isAdmin()) { redirect(SITE_URL . '/login.php'); }

$db = new Database();

// --- XỬ LÝ XÓA SẢN PHẨM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    
    // Lấy thông tin ảnh để xóa file khỏi thư mục
    $db->query("SELECT image FROM products WHERE id = :id");
    $db->bind(':id', $id);
    $prod = $db->getOne();
    
    if ($prod) {
        // Xóa file ảnh vật lý
        if (file_exists('../' . $prod['image'])) { 
            unlink('../' . $prod['image']); 
        }
        // Xóa dữ liệu trong CSDL
        $db->query("DELETE FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        echo "<script>alert('Đã xóa sản phẩm!'); window.location.href='products.php';</script>";
    }
}

// Lấy danh sách sản phẩm
$db->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $db->getAll();

$page_title = 'Quản lý sản phẩm';
include 'layouts/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Gian hàng Shopee</h6>
        <a href="product_add.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Thêm sản phẩm
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="100">Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá bán</th>
                        <th>Link Shopee</th>
                        <th width="100">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Chưa có sản phẩm nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="text-center">
                                <img src="<?php echo SITE_URL . '/' . $p['image']; ?>" width="60" height="60" style="object-fit: contain; border-radius: 5px; border: 1px solid #eee;">
                            </td>
                            <td>
                                <strong><?php echo $p['name']; ?></strong>
                                <?php if($p['badge']): ?>
                                    <br><small class="badge bg-danger"><?php echo $p['badge']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-danger fw-bold"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</span>
                                <?php if($p['old_price'] > 0): ?>
                                    <br><small class="text-decoration-line-through text-muted"><?php echo number_format($p['old_price'], 0, ',', '.'); ?>đ</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo $p['affiliate_link']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i> Xem link
                                </a>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>