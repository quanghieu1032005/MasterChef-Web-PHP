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

// Các tham số tìm kiếm và phân trang
$search_title = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn tìm kiếm
$search_condition = '';
$params = [];

if (!empty($search_title) || $category_id > 0) {
    $search_condition = " WHERE";
    
    if (!empty($search_title)) {
        $search_condition .= " p.title LIKE :search_title";
        $params[':search_title'] = "%$search_title%";
    }
    
    if ($category_id > 0) {
        if (!empty($search_title)) {
            $search_condition .= " AND";
        }
$search_condition .= " p.food_category_id = :category_id";        $params[':category_id'] = $category_id;
    }
}

// Lấy tổng số bài viết
$db->query("SELECT COUNT(*) as total FROM posts p" . $search_condition);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_posts = $db->getOne()['total'];

// Tính tổng số trang
$total_pages = ceil($total_posts / $limit);

// Đảm bảo trang hiện tại không vượt quá tổng số trang
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Lấy danh sách bài viết với phân trang
$sql = "SELECT p.*, c.name as category_name, u.username as author_name
        FROM posts p
        LEFT JOIN food_categories c ON p.food_category_id = c.id
        LEFT JOIN users u ON p.user_id = u.id
        " . $search_condition . "
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset";
$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $limit);
$db->bind(':offset', $offset);
$posts = $db->getAll();

// Lấy danh sách danh mục
$db->query("SELECT * FROM food_categories ORDER BY name");
$categories = $db->getAll();

// Xử lý xóa bài viết
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    // Kiểm tra bài viết tồn tại
    $db->query("SELECT * FROM posts WHERE id = :id");
    $db->bind(':id', $post_id);
    $post = $db->getOne();
    
    if (!$post) {
        $error = 'Bài viết không tồn tại';
    } else {
        // Xóa bình luận liên quan
        $db->query("DELETE FROM comments WHERE post_id = :post_id");
        $db->bind(':post_id', $post_id);
        $db->execute();

        // Xóa ảnh bài viết nếu có
        if (!empty($post['thumbnail']) && file_exists('../' . $post['thumbnail'])) {
            unlink('../' . $post['thumbnail']);
        }
        
        // Xóa bài viết
        $db->query("DELETE FROM posts WHERE id = :id");
        $db->bind(':id', $post_id);
        
        if ($db->execute()) {
            $success = 'Đã xóa bài viết thành công';
            
            // Redirect to prevent form resubmission
            $_SESSION['success_message'] = $success;
            redirect(SITE_URL . '/admin/posts.php');
            exit;
        } else {
            $error = 'Đã xảy ra lỗi khi xóa bài viết';
        }
    }
}

// Retrieve success message from session if it exists
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Tiêu đề trang
$page_title = 'Quản lý bài viết';
include 'layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4 border-0 rounded-lg">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-primary mb-0">
                    <i class="fas fa-newspaper me-2"></i> Danh sách bài viết
                </h6>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/post_add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Thêm bài viết mới
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Form tìm kiếm -->
                <div class="card mb-4 bg-light border-0">
                    <div class="card-body">
                        <form method="GET" action="" class="mb-0">
                            <div class="row">
                                <div class="col-md-5 col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="search_title" class="form-label">
                                            <i class="fas fa-search me-1"></i> Tìm theo tiêu đề:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-heading"></i></span>
                                            <input type="text" class="form-control" id="search_title" name="search_title" placeholder="Tiêu đề bài viết..." value="<?php echo htmlspecialchars($search_title); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="category_id" class="form-label">
                                            <i class="fas fa-tag me-1"></i> Lọc theo danh mục:
                                        </label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="0">-- Tất cả danh mục --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $category['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12 d-flex align-items-end">
                                    <div class="form-group w-100 mb-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter"></i> Lọc
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($search_title) || $category_id > 0): ?>
                                <div class="mt-1">
                                    <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-times"></i> Xóa bộ lọc
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Kết quả -->
                <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">Mã</th>
                                <th width="10%" class="text-center">Ảnh</th>
                                <th width="35%">Tiêu đề</th>
                                <th width="10%">Danh mục</th>
                                <th width="10%" class="text-center">Ngày đăng</th>
                                <th width="15%" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($posts) > 0): ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $post['id']; ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($post['thumbnail'])): ?>
                                                <img src="<?php echo SITE_URL . '/' . $post['thumbnail']; ?>" alt="<?php echo $post['title']; ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                <i class="fas fa-image text-muted" style="font-size: 2rem;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="d-inline-block" data-bs-toggle="tooltip" title="<?php echo $post['title']; ?>">
                                                <?php echo strlen($post['title']) > 50 ? substr($post['title'], 0, 50) . '...' : $post['title']; ?>
                                            </span>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-link me-1"></i> <?php echo $post['slug']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                <?php echo $post['category_name']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span data-bs-toggle="tooltip" title="<?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?>">
                                                <?php echo formatDate($post['created_at'], 'd/m/Y'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo SITE_URL; ?>/admin/post_edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-info text-white me-1" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/post/<?php echo $post['slug']; ?>" target="_blank" class="btn btn-sm btn-success me-1" data-bs-toggle="tooltip" title="Xem bài viết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" name="delete_post" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này? Mọi bình luận liên quan cũng sẽ bị xóa.');" data-bs-toggle="tooltip" title="Xóa bài viết">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i> Không tìm thấy bài viết nào
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1<?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo $category_id > 0 ? '&category_id=' . $category_id : ''; ?>" data-bs-toggle="tooltip" title="Trang đầu tiên">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo $category_id > 0 ? '&category_id=' . $category_id : ''; ?>">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-angle-left"></i></span>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                if ($end_page - $start_page < 4 && $total_pages > 5) {
                                    $start_page = max(1, $end_page - 4);
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo $category_id > 0 ? '&category_id=' . $category_id : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo $category_id > 0 ? '&category_id=' . $category_id : ''; ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search_title) ? '&search_title=' . urlencode($search_title) : ''; ?><?php echo $category_id > 0 ? '&category_id=' . $category_id : ''; ?>" data-bs-toggle="tooltip" title="Trang cuối">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-angle-right"></i></span>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?> 