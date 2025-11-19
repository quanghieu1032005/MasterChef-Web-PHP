<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    // Lưu URL hiện tại để sau khi đăng nhập sẽ chuyển hướng về đây
    $_SESSION['redirect_url'] = SITE_URL . '/favorites.php';
    redirect(SITE_URL . '/login.php');
}

// Khởi tạo database
$db = new Database();

// Lấy ID người dùng
$user_id = $_SESSION['user_id'];

// Xử lý loại bỏ bài viết khỏi danh sách yêu thích
if (isset($_POST['remove_favorite'])) {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    if ($post_id > 0) {
        $db->query("DELETE FROM favorites WHERE user_id = :user_id AND post_id = :post_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':post_id', $post_id);
        
        if ($db->execute()) {
            $_SESSION['success_message'] = 'Đã xóa bài viết khỏi danh sách yêu thích';
        } else {
            $_SESSION['error_message'] = 'Không thể xóa bài viết khỏi danh sách yêu thích';
        }
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Số bài viết trên mỗi trang
$offset = ($page - 1) * $limit;

// Lấy tổng số bài viết yêu thích
$db->query("SELECT COUNT(*) as total FROM favorites WHERE user_id = :user_id");
$db->bind(':user_id', $user_id);
$total = $db->getOne()['total'];

// Tính số trang
$total_pages = ceil($total / $limit);

// Lấy danh sách bài viết yêu thích
$db->query("SELECT p.*, f.created_at AS favorite_date, u.username, u.full_name, fc.name AS category_name, fc.slug AS category_slug
            FROM favorites f
            JOIN posts p ON f.post_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN food_categories fc ON p.food_category_id = fc.id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC
            LIMIT :offset, :limit");
$db->bind(':user_id', $user_id);
$db->bind(':offset', $offset);
$db->bind(':limit', $limit);
$favorites = $db->getAll();

include 'layouts/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-heart text-danger me-2"></i> Bài viết yêu thích
                </h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($favorites)): ?>
                    <div class="text-center py-5">
                        <i class="far fa-heart text-muted mb-3" style="font-size: 4rem;"></i>
                        <h5 class="text-muted">Bạn chưa có bài viết yêu thích nào</h5>
                        <p>Thêm bài viết vào danh sách yêu thích để dễ dàng xem lại sau này</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i> Khám phá món ăn
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($favorites as $post): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="position-relative">
                                        <img src="<?php echo SITE_URL . '/' . $post['thumbnail']; ?>" class="card-img-top" alt="<?php echo $post['title']; ?>">
                                        
                                        <!-- Nút xóa khỏi yêu thích -->
                                        <form method="POST" action="<?php echo SITE_URL; ?>/favorites.php" class="position-absolute top-0 end-0 m-2">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" name="remove_favorite" class="btn btn-sm btn-danger rounded-circle" title="Xóa khỏi yêu thích">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        
                                        <div class="position-absolute bottom-0 start-0 w-100 p-2">
                                            <span class="badge bg-primary">
                                                <i class="fas fa-utensils me-1"></i> <?php echo $post['category_name']; ?>
                                            </span>
                                            <span class="badge bg-secondary">
                                                <i class="far fa-clock me-1"></i> <?php echo formatDate($post['favorite_date'], 'd/m/Y'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="<?php echo SITE_URL . '/post/' . $post['slug']; ?>" class="text-decoration-none"><?php echo $post['title']; ?></a>
                                        </h5>
                                        <p class="card-text text-muted small">
                                            <?php echo isset($post['summary']) ? substr(strip_tags($post['summary']), 0, 100) . '...' : substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="small text-muted">
                                                <i class="far fa-eye me-1"></i> <?php echo $post['view_count']; ?> lượt xem
                                            </div>
                                            <a href="<?php echo SITE_URL . '/post/' . $post['slug']; ?>" class="btn btn-sm btn-outline-primary">
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/favorites.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/favorites.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/favorites.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
