<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect(SITE_URL);
}

// Khởi tạo biến
$username = '';
$password = '';
$error = '';
$success = '';

// Khởi tạo database
$db = new Database();

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và xác thực dữ liệu
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Kiểm tra dữ liệu
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        // Kiểm tra tài khoản
        $db->query("SELECT * FROM users WHERE username = :username AND is_blocked = 0");
        $db->bind(':username', $username);
        $user = $db->getOne();
        
        if ($user && password_verify($password, $user['password'])) {
            // Đăng nhập thành công, lưu session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Chuyển hướng
            $redirectUrl = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : SITE_URL;
            unset($_SESSION['redirect_url']);
            redirect($redirectUrl);
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

// Lấy URL chuyển hướng (nếu có)
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

include 'layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Đăng nhập</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
                        </button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p class="mb-0">Chưa có tài khoản? <a href="<?php echo SITE_URL; ?>/register.php">Đăng ký ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?> 