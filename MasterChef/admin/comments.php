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
$search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$search_post = isset($_GET['search_post']) ? trim($_GET['search_post']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn tìm kiếm
$search_condition = '';
$params = [];

if (!empty($search_user) || !empty($search_post)) {
    $search_condition = " WHERE";
    
    if (!empty($search_user)) {
        $search_condition .= " (u.username LIKE :search_username OR u.full_name LIKE :search_fullname)";
        $params[':search_username'] = "%$search_user%";
        $params[':search_fullname'] = "%$search_user%";
    }
    
    if (!empty($search_post)) {
        if (!empty($search_user)) {
            $search_condition .= " AND";
        }
        $search_condition .= " (p.title LIKE :search_post)";
        $params[':search_post'] = "%$search_post%";
    }
}

// Lấy tổng số bình luận
$db->query("SELECT COUNT(*) as total FROM comments c
           JOIN users u ON c.user_id = u.id
           JOIN posts p ON c.post_id = p.id" . $search_condition);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_comments = $db->getOne()['total'];

// Tính tổng số trang
$total_pages = ceil($total_comments / $limit);

// Đảm bảo trang hiện tại không vượt quá tổng số trang
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Lấy danh sách bình luận với phân trang
$sql = "SELECT c.*, u.username, u.full_name, p.title as post_title, p.slug as post_slug
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN posts p ON c.post_id = p.id" . $search_condition . "
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset";
$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $limit);
$db->bind(':offset', $offset);
$comments = $db->getAll();

// Xử lý xóa bình luận
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    
    // Kiểm tra bình luận tồn tại
    $db->query("SELECT * FROM comments WHERE id = :id");
    $db->bind(':id', $comment_id);
    $comment = $db->getOne();
    
    if (!$comment) {
        $error = 'Bình luận không tồn tại';
    } else {
        // Xóa bình luận
        $db->query("DELETE FROM comments WHERE id = :id");
        $db->bind(':id', $comment_id);
        
        if ($db->execute()) {
            $success = 'Đã xóa bình luận thành công';
            
            // Redirect to prevent form resubmission and refresh the comment list
            $redirect_url = 'comments.php';
            if (!empty($search_user)) {
                $redirect_url .= '?search_user=' . urlencode($search_user);
                if (!empty($search_post)) {
                    $redirect_url .= '&search_post=' . urlencode($search_post);
                }
            } elseif (!empty($search_post)) {
                $redirect_url .= '?search_post=' . urlencode($search_post);
            }
            if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'page=' . (int)$_GET['page'];
            }
            
            $_SESSION['success_message'] = $success;
            redirect(SITE_URL . '/admin/' . $redirect_url);
            exit;
        } else {
            $error = 'Đã xảy ra lỗi khi xóa bình luận';
        }
    }
}

// Retrieve success message from session if it exists
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Tiêu đề trang
$page_title = 'Quản lý bình luận';
include 'layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4 border-0 rounded-lg">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-primary mb-0">
                    <i class="fas fa-comments me-2"></i> Danh sách bình luận
                </h6>
                <span class="badge bg-primary rounded-pill"><?php echo $total_comments; ?> bình luận</span>
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
                                        <label for="search_user" class="form-label">
                                            <i class="fas fa-user me-1"></i> Tìm theo người dùng:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="search_user" name="search_user" placeholder="Tên đăng nhập hoặc họ tên..." value="<?php echo htmlspecialchars($search_user); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="search_post" class="form-label">
                                            <i class="fas fa-newspaper me-1"></i> Tìm theo bài viết:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="search_post" name="search_post" placeholder="Tiêu đề bài viết..." value="<?php echo htmlspecialchars($search_post); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12 d-flex align-items-end">
                                    <div class="form-group w-100 mb-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Tìm kiếm
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($search_user) || !empty($search_post)): ?>
                                <div class="mt-1">
                                    <a href="<?php echo SITE_URL; ?>/admin/comments.php" class="btn btn-sm btn-secondary">
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
                                <th width="5%" class="text-center">#</th>
                                <th width="15%">Người dùng</th>
                                <th width="25%">Bài viết</th>
                                <th width="20%">Nội dung</th>
                                <th width="20%" class="text-center">Ngày đăng</th>
                                <th width="10%" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $comment['id']; ?></td>
                                        <td>
                                            <span class="d-inline-block text-truncate">
                                                <i class="fas fa-user-circle me-1 text-secondary"></i>
                                                <?php echo $comment['full_name']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL . '/post/' . $comment['post_slug']; ?>" target="_blank" class="text-decoration-none">
                                                <span class="d-inline-block text-truncate" style="max-width: 250px;">
                                                    <?php echo $comment['post_title']; ?>
                                                </span>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="comment-content">
                                                <?php echo nl2br(htmlspecialchars(substr($comment['content'], 0, 40) . (strlen($comment['content']) > 40 ? '...' : ''))); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span data-bs-toggle="tooltip" title="<?php echo formatDate($comment['created_at'], 'd/m/Y H:i'); ?>">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo formatDate($comment['created_at'], 'd/m/Y'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="delete_comment" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa bình luận này?');" data-bs-toggle="tooltip" title="Xóa bình luận">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i> Không tìm thấy bình luận nào
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
                                        <a class="page-link" href="?page=1<?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($search_post) ? '&search_post=' . urlencode($search_post) : ''; ?>" data-bs-toggle="tooltip" title="Trang đầu tiên">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($search_post) ? '&search_post=' . urlencode($search_post) : ''; ?>">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($search_post) ? '&search_post=' . urlencode($search_post) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($search_post) ? '&search_post=' . urlencode($search_post) : ''; ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($search_post) ? '&search_post=' . urlencode($search_post) : ''; ?>" data-bs-toggle="tooltip" title="Trang cuối">
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