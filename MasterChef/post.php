<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Khởi tạo database
$db = new Database();

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    // Không có slug, chuyển hướng về trang chủ
    redirect(SITE_URL);
}

// Lấy thông tin bài viết
$db->query("SELECT p.*, u.username, u.full_name, u.avatar, fc.name AS category_name, fc.slug AS category_slug, 
            pt.name AS post_type_name, pt.slug AS post_type_slug
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN food_categories fc ON p.food_category_id = fc.id
            JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.slug = :slug");
$db->bind(':slug', $slug);
$post = $db->getOne();

if (!$post) {
    // Bài viết không tồn tại, chuyển hướng về trang chủ
    redirect(SITE_URL);
}

// Tăng lượt xem
$db->query("UPDATE posts SET view_count = view_count + 1 WHERE id = :id");
$db->bind(':id', $post['id']);
$db->execute();

// Lấy danh sách bình luận
$db->query("SELECT c.*, u.username, u.full_name, u.avatar
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at DESC");
$db->bind(':post_id', $post['id']);
$comments = $db->getAll();

// Xử lý thêm bình luận
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    // Kiểm tra xem đây có phải là Ajax request không
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if (!isLoggedIn()) {
        // Nếu chưa đăng nhập, lưu URL hiện tại và chuyển hướng đến trang đăng nhập
        $_SESSION['redirect_url'] = getCurrentUrl();
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để bình luận', 'redirect' => SITE_URL . '/login.php']);
            exit;
        }
        redirect(SITE_URL . '/login.php');
    }

    $comment_content = trim($_POST['comment']);

    if (empty($comment_content)) {
        $comment_error = 'Vui lòng nhập nội dung bình luận';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $comment_error]);
            exit;
        }
    } else {
        // Kiểm tra xem bình luận này có bị trùng lặp không
        $db->query("SELECT id FROM comments 
                    WHERE user_id = :user_id 
                    AND post_id = :post_id 
                    AND content = :content 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':post_id', $post['id']);
        $db->bind(':content', $comment_content);
        $duplicate_comment = $db->getOne();

        // Nếu tìm thấy bình luận trùng lặp trong khoảng 1 phút gần đây, không thêm vào DB
        if ($duplicate_comment) {
            // Load lại trang
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            // Thêm bình luận mới
            $db->query("INSERT INTO comments (content, user_id, post_id) VALUES (:content, :user_id, :post_id)");
            $db->bind(':content', $comment_content);
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->bind(':post_id', $post['id']);

            if ($db->execute()) {
                $comment_success = 'Bình luận của bạn đã được gửi thành công!';

                // Lấy ID của bình luận vừa thêm
                $comment_id = $db->lastInsertId();

                // Lấy thông tin bình luận mới thêm
                $db->query("SELECT c.*, u.username, u.full_name, u.avatar
                            FROM comments c
                            JOIN users u ON c.user_id = u.id
                            WHERE c.id = :comment_id");
                $db->bind(':comment_id', $comment_id);
                $new_comment = $db->getOne();

                if ($is_ajax) {
                    // Tạo HTML cho bình luận mới để trả về qua Ajax
                    $html = '<div class="comment d-flex">';
                    $html .= '<div class="comment-avatar">';
                    if (!empty($new_comment['avatar'])) {
                        $html .= '<img src="../assets/images/avatar.png" alt="' . $new_comment['full_name'] . '">';
                    } else {
                        $html .= '<img src="../assets/images/avatar.png" alt="' . $new_comment['full_name'] . '">';
                    }
                    $html .= '</div>';
                    $html .= '<div class="comment-content">';
                    $html .= '<div class="comment-header">';
                    $html .= '<div class="comment-name">' . $new_comment['full_name'] . '</div>';
                    $html .= '<div class="comment-date">' . formatDate($new_comment['created_at'], 'd/m/Y H:i') . '</div>';
                    $html .= '</div>';
                    $html .= '<div class="comment-text">' . nl2br(htmlspecialchars($new_comment['content'])) . '</div>';
                    $html .= '</div>';
                    $html .= '</div>';

                    echo json_encode(['success' => true, 'message' => $comment_success, 'html' => $html]);
                    exit;
                } else {
                    // Nếu không phải Ajax request, chuyển hướng để tránh gửi lại form khi refresh
                    redirect(getCurrentUrl() . '#commentsSection');
                }
            } else {
                $comment_error = 'Đã xảy ra lỗi khi gửi bình luận. Vui lòng thử lại!';
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => $comment_error]);
                    exit;
                }
            }
        }
    }
}

// Kiểm tra xem bài viết đã được yêu thích chưa
$is_favorite = false;
if (isLoggedIn()) {
    $is_favorite = isFavorite($db, $post['id'], $_SESSION['user_id']);
}

include 'layouts/header.php';
?>

<!-- Chi tiết bài viết -->
<div class="recipe-header mb-4" style="background-image: url('<?php echo SITE_URL . '/' . $post['thumbnail']; ?>');">
    <div class="container">
        <div class="recipe-header-content">
            <h1 class="display-4"><?php echo $post['title']; ?></h1>
            <div class="d-flex align-items-center mt-3">
                <img src="../assets/images/avatar.png" alt="<?php echo $post['full_name']; ?>" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <div class="text-white">Đăng bởi: <?php echo $post['full_name']; ?></div>
                    <div class="text-white-50"><i class="far fa-clock me-1"></i> <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="category-label">
                            <a href="<?php echo SITE_URL . '/category/' . $post['category_slug']; ?>" class="text-decoration-none">
                                <i class="fas fa-utensils me-1"></i> <?php echo $post['category_name']; ?>
                            </a>
                        </span>
                        <span class="type-label">
                            <a href="<?php echo SITE_URL . '/type/' . $post['post_type_slug']; ?>" class="text-decoration-none">
                                <i class="fas fa-tag me-1"></i> <?php echo $post['post_type_name']; ?>
                            </a>
                        </span>
                    </div>

                    <div class="post-header-actions">
                        <button class="favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                            <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                </div>

                <div class="recipe-meta text-muted">
                    <span><i class="far fa-eye me-1"></i> <?php echo $post['view_count']; ?> lượt xem</span>
                    <span><a href="#commentsSection" class="text-muted comment-link"><i class="far fa-comment me-1"></i> <?php echo count($comments); ?> bình luận</a></span>
                </div>

                <hr>

                <div class="recipe-content">
                    <div class="post-content-wrapper">
                        <?php echo $post['content']; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phần bình luận -->
        <div class="card mb-4" id="commentsSection">
            <div class="card-header">
                <h5 class="mb-0">Bình luận (<?php echo count($comments); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (isLoggedIn()): ?>
                    <?php if (!empty($comment_error)): ?>
                        <div class="alert alert-danger"><?php echo $comment_error; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($comment_success)): ?>
                        <div class="alert alert-success"><?php echo $comment_success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="commentForm">
                        <div class="mb-2">
                            <label for="comment" class="form-label">Thêm bình luận <small class="text-muted">(spam sẽ bị xóa)</small></label>
                            <textarea class="form-control" id="comment" name="comment" rows="2" required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="far fa-paper-plane me-1"></i> Gửi bình luận
                            </button>
                        </div>
                    </form>
                    <hr class="my-2">
                <?php else: ?>
                    <div class="alert alert-info">
                        Vui lòng <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode(getCurrentUrl()); ?>">đăng nhập</a> để bình luận.
                    </div>
                <?php endif; ?>

                <div id="commentList">
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment d-flex">
                                <div class="comment-avatar">
                                    <img src="../assets/images/avatar.png">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <div class="comment-name"><?php echo $comment['full_name']; ?></div>
                                        <div class="comment-date"><?php echo formatDate($comment['created_at'], 'd/m/Y H:i'); ?></div>
                                    </div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="far fa-comment-dots fs-4 mb-2"></i>
                            <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
    // Định nghĩa SITE_URL cho JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<?php include 'layouts/footer.php'; ?>