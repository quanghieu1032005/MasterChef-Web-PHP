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
$email = '';
$full_name = '';
$password = '';
$confirm_password = '';
$error = '';
$success = '';

// Khởi tạo database
$db = new Database();

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và xác thực dữ liệu
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Tên đăng nhập phải từ 3 đến 50 ký tự';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Kiểm tra tên đăng nhập đã tồn tại chưa
        $db->query("SELECT * FROM users WHERE username = :username");
        $db->bind(':username', $username);
        if ($db->rowCount() > 0) {
            $error = 'Tên đăng nhập đã tồn tại';
        } else {
            // Kiểm tra email đã tồn tại chưa
            $db->query("SELECT * FROM users WHERE email = :email");
            $db->bind(':email', $email);
            if ($db->rowCount() > 0) {
                $error = 'Email đã được sử dụng';
            } else {
                // Tạo tài khoản mới
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $db->query("INSERT INTO users (username, password, email, full_name) VALUES (:username, :password, :email, :full_name)");
                $db->bind(':username', $username);
                $db->bind(':password', $password_hash);
                $db->bind(':email', $email);
                $db->bind(':full_name', $full_name);
                
                if ($db->execute()) {
                    $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                    // Reset form
                    $username = '';
                    $email = '';
                    $full_name = '';
                } else {
                    $error = 'Đã xảy ra lỗi, vui lòng thử lại sau';
                }
            }
        }
    }
}

include 'layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Đăng ký tài khoản</h2>
                
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
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ tên</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Đăng ký
                        </button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p class="mb-0">Đã có tài khoản? <a href="<?php echo SITE_URL; ?>/login.php">Đăng nhập</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?> 