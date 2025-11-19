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
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn tìm kiếm
$search_condition = '';
$params = [];

if (!empty($search_user) || !empty($role_filter)) {
    $search_condition = " WHERE";
    
    if (!empty($search_user)) {
        $search_condition .= " (username LIKE :search_user OR email LIKE :search_email OR full_name LIKE :search_name)";
        $params[':search_user'] = "%$search_user%";
        $params[':search_email'] = "%$search_user%";
        $params[':search_name'] = "%$search_user%";
    }
    
    if (!empty($role_filter)) {
        if (!empty($search_user)) {
            $search_condition .= " AND";
        }
        $search_condition .= " role = :role";
        $params[':role'] = $role_filter;
    }
}

// Lấy tổng số người dùng
$db->query("SELECT COUNT(*) as total FROM users" . $search_condition);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_users = $db->getOne()['total'];

// Tính tổng số trang
$total_pages = ceil($total_users / $limit);

// Đảm bảo trang hiện tại không vượt quá tổng số trang
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Lấy danh sách người dùng với phân trang
$sql = "SELECT * FROM users" . $search_condition . " 
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':limit', $limit);
$db->bind(':offset', $offset);
$users = $db->getAll();

// Xử lý nâng/hạ cấp quyền người dùng
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_admin'])) {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $new_role = $_POST['current_role'] === 'admin' ? 'user' : 'admin';
        
        // Kiểm tra người dùng tồn tại
        $db->query("SELECT * FROM users WHERE id = :id");
        $db->bind(':id', $user_id);
        $user = $db->getOne();
        
        if (!$user) {
            $error = 'Người dùng không tồn tại';
        } else if ($user['id'] === $_SESSION['user_id']) {
            $error = 'Bạn không thể thay đổi quyền của chính mình';
        } else {
            // Cập nhật quyền người dùng
            $db->query("UPDATE users SET role = :role WHERE id = :id");
            $db->bind(':role', $new_role);
            $db->bind(':id', $user_id);
            
            if ($db->execute()) {
                $success = 'Đã cập nhật quyền người dùng thành công';
                $_SESSION['success_message'] = $success;
                redirect(SITE_URL . '/admin/users.php');
                exit;
            } else {
                $error = 'Đã xảy ra lỗi khi cập nhật quyền người dùng';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        // Kiểm tra người dùng tồn tại
        $db->query("SELECT * FROM users WHERE id = :id");
        $db->bind(':id', $user_id);
        $user = $db->getOne();
        
        if (!$user) {
            $error = 'Người dùng không tồn tại';
        } else if ($user['id'] === $_SESSION['user_id']) {
            $error = 'Bạn không thể xóa tài khoản của chính mình';
        } else {
            // Xóa bài viết và bình luận của người dùng
            $db->query("DELETE FROM comments WHERE user_id = :user_id");
            $db->bind(':user_id', $user_id);
            $db->execute();
            
            $db->query("DELETE FROM posts WHERE user_id = :user_id");
            $db->bind(':user_id', $user_id);
            $db->execute();
            
            // Xóa người dùng
            $db->query("DELETE FROM users WHERE id = :id");
            $db->bind(':id', $user_id);
            
            if ($db->execute()) {
                $success = 'Đã xóa người dùng thành công';
                $_SESSION['success_message'] = $success;
                redirect(SITE_URL . '/admin/users.php');
                exit;
            } else {
                $error = 'Đã xảy ra lỗi khi xóa người dùng';
            }
        }
    }
}

// Retrieve success message from session if it exists
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Tiêu đề trang
$page_title = 'Quản lý người dùng';
include 'layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4 border-0 rounded-lg">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-primary mb-0">
                    <i class="fas fa-users me-2"></i> Danh sách người dùng
                </h6>
                <span class="badge bg-primary rounded-pill"><?php echo $total_users; ?> người dùng</span>
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
                                            <i class="fas fa-search me-1"></i> Tìm kiếm người dùng:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="search_user" name="search_user" placeholder="Tên đăng nhập, email hoặc họ tên..." value="<?php echo htmlspecialchars($search_user); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="role" class="form-label">
                                            <i class="fas fa-user-tag me-1"></i> Lọc theo vai trò:
                                        </label>
                                        <select class="form-select" id="role" name="role">
                                            <option value="">-- Tất cả vai trò --</option>
                                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Người dùng</option>
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
                            
                            <?php if (!empty($search_user) || !empty($role_filter)): ?>
                                <div class="mt-1">
                                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-secondary">
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
                                <th width="15%">Tên đăng nhập</th>
                                <th width="20%">Họ và tên</th>
                                <th width="25%">Email</th>
                                <th width="10%" class="text-center">Vai trò</th>
                                <th width="10%" class="text-center">Ngày tạo</th>
                                <th width="15%" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $user['id']; ?></td>
                                        <td>
                                            <span class="d-inline-block text-truncate">
                                                <i class="fas fa-user-circle me-1 text-secondary"></i>
                                                <?php echo $user['username']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['full_name']; ?></td>
                                        <td>
                                            <a href="mailto:<?php echo $user['email']; ?>" class="text-decoration-none">
                                                <i class="fas fa-envelope me-1 text-secondary"></i>
                                                <?php echo $user['email']; ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Quản trị viên</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Người dùng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span data-bs-toggle="tooltip" title="<?php echo formatDate($user['created_at'], 'd/m/Y H:i'); ?>">
                                                <?php echo formatDate($user['created_at'], 'd/m/Y'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="current_role" value="<?php echo $user['role']; ?>">
                                                    <button type="submit" name="toggle_admin" class="btn btn-sm <?php echo $user['role'] === 'admin' ? 'btn-warning' : 'btn-success'; ?> me-1" 
                                                        data-bs-toggle="tooltip" title="<?php echo $user['role'] === 'admin' ? 'Hạ xuống người dùng thường' : 'Nâng lên quản trị viên'; ?>">
                                                        <i class="fas <?php echo $user['role'] === 'admin' ? 'fa-user-minus' : 'fa-user-plus'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này? Tất cả bài viết và bình luận của họ cũng sẽ bị xóa.');" data-bs-toggle="tooltip" title="Xóa người dùng">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tài khoản hiện tại</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i> Không tìm thấy người dùng nào
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
                                        <a class="page-link" href="?page=1<?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" data-bs-toggle="tooltip" title="Trang đầu tiên">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search_user) ? '&search_user=' . urlencode($search_user) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" data-bs-toggle="tooltip" title="Trang cuối">
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