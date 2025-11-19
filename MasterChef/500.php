<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

include 'layouts/header.php';
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <i class="fas fa-cogs text-danger display-1 mb-4"></i>
                    <h1 class="display-4">500</h1>
                    <h2 class="mb-4">Lỗi máy chủ</h2>
                    <p class="lead mb-4">Đã xảy ra lỗi trong quá trình xử lý yêu cầu của bạn. Vui lòng thử lại sau.</p>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i> Quay về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?> 