<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Khởi tạo kết nối database
$db = new Database();

// 1. Lấy bài viết mới nhất
$db->query("SELECT p.*, u.username, u.full_name, fc.name AS category_name, fc.slug AS category_slug, 
            pt.name AS post_type_name, pt.slug AS post_type_slug,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN food_categories fc ON p.food_category_id = fc.id
            JOIN post_types pt ON p.post_type_id = pt.id
            ORDER BY p.created_at DESC
            LIMIT 6");
$latest_posts = $db->getAll();

// 2. Lấy bài viết nổi bật
$db->query("SELECT p.*, u.username, u.full_name, fc.name AS category_name, fc.slug AS category_slug, 
            pt.name AS post_type_name, pt.slug AS post_type_slug,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN food_categories fc ON p.food_category_id = fc.id
            JOIN post_types pt ON p.post_type_id = pt.id
            ORDER BY comment_count DESC
            LIMIT 3");
$featured_posts = $db->getAll();

// 3. [MỚI] LẤY TOP CAO THỦ (ĐẾM SỐ BÀI VIẾT)
$db->query("SELECT u.username, u.full_name, u.avatar, COUNT(p.id) as recipe_count
            FROM users u
            JOIN posts p ON u.id = p.user_id
            GROUP BY u.id
            ORDER BY recipe_count DESC
            LIMIT 4");
$top_chefs = $db->getAll();

// Lấy danh mục & loại bài viết
$db->query("SELECT * FROM food_categories ORDER BY name");
$food_categories = $db->getAll();

$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

// Include Header
include 'layouts/header.php';
?>

<style>
    /* Modal & Gift Box */
    #luckyFoodModal .modal-content {
        background-color: #ffffff !important;
        border-radius: 15px !important;
        box-shadow: 0 25px 50px rgba(0,0,0,0.5) !important;
        border: none !important;
    }
    #food-result-container {
        background-color: #ffffff !important;
        backdrop-filter: none !important;
        box-shadow: none !important;
        border: none !important;
    }
    #food-result-container .card-body { background-color: #ffffff !important; }
    #food-title { color: #2c3e50 !important; text-shadow: none !important; }
    #gift-animation-container { background-color: #ff5722 !important; border-radius: 15px; }

    /* Banner Suggestion */
    .bg-gradient-food {
        background: linear-gradient(135deg, #ff5722 0%, #ff8a65 100%) !important;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(255, 87, 34, 0.2);
        overflow: hidden;
    }
    .gift-icon-large {
        font-size: 100px !important;
        animation: floatGift 3s ease-in-out infinite;
        filter: drop-shadow(0 10px 15px rgba(0,0,0,0.2));
        cursor: pointer;
        display: inline-block;
    }
    .bg-white-opacity {
        background: transparent !important;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .suggestion-section h2, .suggestion-section p {
        color: #ffffff !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    @keyframes floatGift {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* Categories Shortcuts */
    .categories-shortcuts .shortcuts-row {
        display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;
        -webkit-overflow-scrolling: touch; align-items: flex-start;
    }
    .categories-shortcuts .shortcut { flex: 0 0 auto; min-width: 120px; max-width: 160px; text-align: center; }
    .categories-shortcuts .shortcut .card { border: 1px solid rgba(0,0,0,0.06); padding: 0.5rem; background: #fff; height: 100%; }
    .categories-shortcuts .category-icon { display: flex; justify-content: center; align-items: center; height: 48px; margin-bottom: 0.4rem; }
    .categories-shortcuts .category-icon i { font-size: 1.25rem; color: #ff6b6b; }
    .categories-shortcuts .card-title { font-size: 0.95rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media (min-width: 768px) {
        .categories-shortcuts .shortcuts-row { justify-content: center; }
        .categories-shortcuts .shortcut { min-width: 140px; }
    }

    /* [MỚI] CSS CHO BẢNG VÀNG CAO THỦ */
    .top-chefs-section { background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%); }
    .chef-card {
        background: #fff;
        border-radius: 20px;
        padding: 25px 15px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        height: 100%;
    }
    .chef-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    
    .chef-avatar { position: relative; width: 100px; height: 100px; margin: 0 auto 15px; }
    .chef-avatar img {
        width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
        border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .rank-badge {
        position: absolute; top: -10px; right: -10px; font-size: 2.5rem;
        filter: drop-shadow(0 4px 4px rgba(0,0,0,0.2)); z-index: 2;
    }
    
    .chef-card.rank-1 { border: 2px solid #FFD700; background: linear-gradient(to bottom, #fff, #fff9db); }
    .chef-card.rank-2 { border: 2px solid #C0C0C0; background: linear-gradient(to bottom, #fff, #f8f9fa); }
    .chef-card.rank-3 { border: 2px solid #CD7F32; background: linear-gradient(to bottom, #fff, #fff5eb); }
    
    .chef-name { font-weight: 700; color: #2c3e50; margin-bottom: 5px; font-size: 1.1rem; }
    .recipe-count { color: #6c757d; font-size: 0.9rem; }
    .recipe-count b { color: #ff6b6b; font-size: 1.1rem; }
</style>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1>Khám phá ẩm thực cùng<br>Master Chef</h1>
                <p class="mb-4">Nơi chia sẻ những công thức nấu ăn tuyệt vời, mẹo nấu bếp hữu ích và cảm hứng ẩm thực từ cộng đồng yêu bếp.</p>
                <a href="#featured" class="btn btn-primary me-3">
                    <i class="fas fa-utensils me-2"></i>Khám phá ngay
                </a>
                <a href="#filter" class="btn btn-outline-light">
                    <i class="fas fa-search me-2"></i>Tìm kiếm
                </a>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image-container">
                    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="<?php echo SITE_URL; ?>/assets/images/slide1.png" class="d-block w-100" alt="Master Chef">
                            </div>
                            <div class="carousel-item">
                                <img src="<?php echo SITE_URL; ?>/assets/images/slide2.png" class="d-block w-100" alt="Công thức">
                            </div>
                            <div class="carousel-item">
                                <img src="<?php echo SITE_URL; ?>/assets/images/slide3.png" class="d-block w-100" alt="Mẹo nấu ăn">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="wave-container">
        <div class="wave"></div>
    </div>
</section>

<section class="suggestion-section my-5">
    <div class="container">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="row g-0 align-items-center bg-gradient-food">
                <div class="col-md-4 text-center py-4 bg-white-opacity">
                    <div class="gift-box-container">
                        <div class="gift-icon-large" onclick="openGift()">🎁</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card-body p-4 text-center text-md-start text-white">
                        <h2 class="fw-bold mb-2">Hôm nay ăn gì?</h2>
                        <p class="mb-4 opacity-90 fs-5">Đừng để câu hỏi này làm khó bạn. Hãy để Master Chef gợi ý một món ngon phù hợp với khung giờ hiện tại nhé!</p>
                        <button id="btn-open-surprise" class="btn btn-light btn-lg rounded-pill px-5 fw-bold shadow-sm hover-scale">
                            <i class="fas fa-magic me-2 text-primary"></i> Khám phá ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4 categories-shortcuts">
    <div class="container">
        <div class="section-title">
            <h2>Khám phá theo danh mục</h2>
            <p class="mb-3">Tìm công thức theo danh mục nhanh</p>
        </div>
        <div class="shortcuts-row">
            <?php foreach ($food_categories as $index => $category): ?>
                <?php if ($index < 12): ?>
                    <div class="shortcut">
                        <a href="<?php echo SITE_URL; ?>/category/<?php echo $category['slug']; ?>" class="text-decoration-none">
                            <div class="card category-card text-center">
                                <div class="card-body p-2">
                                    <div class="category-icon"><i class="fas fa-utensils"></i></div>
                                    <h6 class="card-title"><?php echo $category['name']; ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="filter" class="filter-section">
    <div class="container">
        <div class="section-title">
            <h2>Tìm kiếm bài viết</h2>
            <p>Tìm công thức dựa trên danh mục, loại hoặc nguyên liệu có sẵn</p>
        </div>
        <form method="GET" action="<?php echo SITE_URL; ?>/search.php" class="row g-3">
            <div class="col-md-3">
                <label for="foodCategory" class="form-label fw-bold">Danh mục</label>
                <select class="form-select" id="foodCategory" name="category">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($food_categories as $category): ?>
                        <option value="<?php echo $category['slug']; ?>"><?php echo $category['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="postType" class="form-label fw-bold">Loại bài viết</label>
                <select class="form-select" id="postType" name="type">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($post_types as $type): ?>
                        <option value="<?php echo $type['slug']; ?>"><?php echo $type['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="ingredients" class="form-label fw-bold">Nguyên liệu có sẵn</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-carrot"></i></span>
                    <input type="text" class="form-control" id="ingredients" name="ingredients" placeholder="Vd: trứng, thịt gà, nấm...">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Tìm
                </button>
            </div>
        </form>
    </div>
</section>

<section class="top-chefs-section py-5">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2><i class="fas fa-crown text-warning me-2"></i>Xếp Hạng Thực Thần</h2>
            <p>Đua top rinh quà</p>
        </div>
        
      <div class="row justify-content-center">
            <?php 
            $rank = 1;
            foreach ($top_chefs as $chef): 
                // --- SỬA LẠI PHẦN NÀY ---
                // Sử dụng dịch vụ UI Avatars để tự tạo ảnh theo tên (Mặc định, không cần upload)
                $avatar_name = urlencode($chef['full_name']);
                $avatar_url = "https://ui-avatars.com/api/?name={$avatar_name}&background=random&color=fff&size=128&font-size=0.5&bold=true";
                
                // Xử lý huy hiệu
                $rank_class = 'rank-common';
                $icon = '<span class="badge bg-secondary rounded-pill">#' . $rank . '</span>';
                
                if ($rank == 1) { 
                    $rank_class = 'rank-1'; 
                    $icon = '🥇'; 
                } elseif ($rank == 2) { 
                    $rank_class = 'rank-2'; 
                    $icon = '🥈'; 
                } elseif ($rank == 3) { 
                    $rank_class = 'rank-3'; 
                    $icon = '🥉'; 
                }
            ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="chef-card <?php echo $rank_class; ?> h-100">
                        <div class="rank-badge"><?php echo $icon; ?></div>
                        <div class="chef-avatar">
                            <img src="<?php echo $avatar_url; ?>" alt="<?php echo $chef['full_name']; ?>">
                        </div>
                        <div class="chef-info mt-3">
                            <h5 class="chef-name text-truncate px-2" title="<?php echo $chef['full_name']; ?>">
                                <?php echo $chef['full_name']; ?>
                            </h5>
                            <p class="recipe-count mb-0">
                                <i class="fas fa-utensils me-1"></i> <b><?php echo $chef['recipe_count']; ?></b> công thức
                            </p>
                        </div>
                    </div>
                </div>
            <?php 
                $rank++;
            endforeach; 
            ?>
        </div>
</section>

<section id="featured">
    <div class="container">
        <div class="section-title">
            <h2>Các bài viết nổi bật</h2>
            <p>Những bài viết được yêu thích và bình luận nhiều nhất từ cộng đồng</p>
        </div>

        <?php if (count($featured_posts) > 0): ?>
            <div class="row">
                <?php foreach ($featured_posts as $index => $post): ?>
                    <?php if ($index === 0): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card featured-card h-100">
                                <div class="card-img-wrapper">
                                    <img src="<?php echo SITE_URL . '/' . $post['thumbnail']; ?>" class="card-img-top" alt="<?php echo $post['title']; ?>" style="height: 300px;">
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap mb-3">
                                        <span class="category-label"><i class="fas fa-tag me-1"></i><?php echo $post['category_name']; ?></span>
                                        <span class="type-label"><i class="fas fa-bookmark me-1"></i><?php echo $post['post_type_name']; ?></span>
                                    </div>
                                    <h4 class="card-title"><a href="<?php echo SITE_URL . '/post/' . $post['slug']; ?>"><?php echo $post['title']; ?></a></h4>
                                    <p class="card-text"><?php echo substr(strip_tags($post['content']), 0, 150) . '...'; ?></p>
                                </div>
                                <div class="card-footer">
                                    <i class="far fa-comment me-1"></i> <?php echo $post['comment_count']; ?> bình luận
                                    <i class="far fa-clock ms-3 me-1"></i> <?php echo formatDate($post['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6"><div class="row">
                    <?php else: ?>
                        <div class="col-md-12 mb-4">
                            <div class="card featured-card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <div class="card-img-wrapper h-100">
                                            <img src="<?php echo SITE_URL . '/' . $post['thumbnail']; ?>" class="img-fluid rounded-start" style="height: 270px; width: 100%; object-fit: cover;" alt="<?php echo $post['title']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <span class="category-label"><?php echo $post['category_name']; ?></span>
                                            <h5 class="card-title mt-2"><a href="<?php echo SITE_URL . '/post/' . $post['slug']; ?>"><?php echo $post['title']; ?></a></h5>
                                            <p class="card-text small">
                                                <i class="far fa-comment me-1"></i><?php echo $post['comment_count']; ?> bình luận
                                                <i class="far fa-clock ms-2 me-1"></i><?php echo formatDate($post['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($index === count($featured_posts) - 1 && $index > 0): ?> </div></div> <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Chưa có bài viết nổi bật nào.</div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="card cta-card">
            <div class="card-body text-center p-5">
                <h2 class="mb-4">Trở thành thành viên của Master Chef</h2>
                <p class="mb-4">Tham gia cộng đồng của chúng tôi để chia sẻ công thức và trải nghiệm nấu ăn của bạn</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="d-flex justify-content-center">
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary me-3"><i class="fas fa-user-plus me-2"></i>Đăng ký</a>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-light"><i class="fas fa-sign-in-alt me-2"></i>Đăng nhập</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/favorites.php" class="btn btn-primary"><i class="fas fa-heart me-2"></i>Xem bài viết yêu thích</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<a href="#" class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></a>

<div class="modal fade" id="luckyFoodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center border-0">
            <div id="gift-animation-container" style="display: none;">
                <div class="gift-shaking" style="font-size: 80px;">🎁</div>
                <h4 class="text-white mt-3">Đang chọn món ngon...</h4>
            </div>
            <div id="food-result-container" class="card shadow-lg rounded-lg overflow-hidden" style="display: none;">
                <div class="card-header bg-primary text-white position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title" id="time-greeting">Gợi ý món ngon</h5>
                </div>
                <div class="card-body p-0">
                    <img id="food-img" src="" alt="" class="w-100">
                    <div class="p-4">
                        <span class="badge bg-warning text-dark mb-2" id="food-cat"></span>
                        <h3 class="card-title fw-bold text-primary mb-3" id="food-title"></h3>
                        <a id="food-link" href="#" class="btn btn-lg btn-danger w-100 rounded-pill">
                            <i class="fas fa-utensils me-2"></i> Xem công thức ngay
                        </a>
                        <button class="btn btn-outline-secondary btn-sm mt-3 w-100" onclick="openGift()">
                            <i class="fas fa-sync-alt me-1"></i> Chọn món khác
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS GIAN HÀNG SHOPEE (AFFILIATE) */
    .shopee-section {
        background-color: #fff5f1 !important; /* Màu nền cam rất nhạt */
        border-top: 1px solid #ffecd9 !important;
    }

    .shopee-title {
        color: #ee4d2d !important; /* Màu cam Shopee */
        font-weight: 800 !important;
    }

    .product-card {
        background: #fff !important;
        border-radius: 15px !important;
        overflow: hidden !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important;
        transition: all 0.3s ease !important;
        border: 1px solid transparent !important;
        position: relative !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .product-card:hover {
        transform: translateY(-5px) !important;
        box-shadow: 0 15px 30px rgba(238, 77, 45, 0.15) !important;
        border-color: #ee4d2d !important;
    }

    .product-badge {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        background: #ee4d2d !important; /* Màu đỏ Shopee */
        color: #fff !important;
        font-size: 0.8rem !important;
        font-weight: bold !important;
        padding: 2px 8px !important;
        border-radius: 4px !important;
        z-index: 2 !important;
    }

    .product-img {
        height: 200px !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #fff !important;
        padding: 10px !important;
    }

    .product-img img {
        max-width: 100% !important;
        max-height: 100% !important;
        transition: transform 0.5s ease !important;
        object-fit: contain !important; /* Đảm bảo ảnh không bị méo */
    }

    .product-card:hover .product-img img {
        transform: scale(1.05) !important;
    }

    .product-body {
        padding: 15px !important;
        text-align: center !important;
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }

    .product-name {
        font-size: 0.95rem !important;
        color: #333 !important;
        font-weight: 600 !important;
        margin-bottom: 10px !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important; /* Giới hạn 2 dòng */
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        height: 40px !important; /* Cố định chiều cao tên */
        line-height: 1.3 !important;
    }

    .product-price {
        margin-bottom: 15px !important;
    }

    .new-price {
        color: #ee4d2d !important;
        font-weight: 700 !important;
        font-size: 1.1rem !important;
        margin-right: 5px !important;
    }

    .old-price {
        color: #999 !important;
        text-decoration: line-through !important;
        font-size: 0.85rem !important;
    }

    .btn-shopee {
        background: #ee4d2d !important;
        color: #fff !important;
        border: none !important;
        font-weight: 600 !important;
        border-radius: 5px !important;
        padding: 8px !important;
        transition: all 0.3s ease !important;
        display: block !important;
        width: 100% !important;
    }

    .btn-shopee:hover {
        background: #d73211 !important;
        color: #fff !important;
        box-shadow: 0 5px 15px rgba(238, 77, 45, 0.3) !important;
    }
</style>

<section class="shopee-section py-5">
        <div class="container">
        <div class="section-title text-center mb-5">
            <h2 class="shopee-title"><i class="fas fa-shopping-bag me-2"></i>Góc Bếp Tiện Nghi</h2>
            <p class="text-muted">Những dụng cụ làm bếp tốt nhất mà Master Chef khuyên dùng</p>
        </div>

        <div class="row justify-content-center">
            <?php
            // Lấy 4 sản phẩm mới nhất từ database
            $db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
            $products = $db->getAll();

            if (!empty($products)):
                foreach ($products as $prod):
            ?>
            <div class="col-md-3 col-6 mb-4">
                <div class="product-card h-100">
                    <?php if (!empty($prod['badge'])): ?>
                        <div class="product-badge"><?php echo htmlspecialchars($prod['badge']); ?></div>
                    <?php endif; ?>
                    
                    <div class="product-img">
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($prod['image']); ?>" 
                             alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                             onerror="this.src='https://placehold.co/300x300?text=No+Image'">
                    </div>
                    
                    <div class="product-body">
                        <h6 class="product-name" title="<?php echo htmlspecialchars($prod['name']); ?>">
                            <?php echo htmlspecialchars($prod['name']); ?>
                        </h6>
                        <div class="product-price">
                            <span class="new-price"><?php echo number_format($prod['price'], 0, ',', '.'); ?>₫</span>
                            <?php if ($prod['old_price'] > 0): ?>
                                <br><span class="old-price"><?php echo number_format($prod['old_price'], 0, ',', '.'); ?>₫</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($prod['affiliate_link']); ?>" target="_blank" class="btn btn-shopee w-100">
                            <i class="fas fa-shopping-cart me-1"></i> Mua ngay
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endforeach; 
            else:
            ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <p>Chưa có sản phẩm nào trong gian hàng.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="https://shopee.vn" target="_blank" class="btn btn-outline-danger rounded-pill px-4">
                Xem thêm dụng cụ nhà bếp <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
<script>
let giftModalInstance = null;
function openGift() {
    const modalEl = document.getElementById('luckyFoodModal');
    if (modalEl && modalEl.parentNode !== document.body) { document.body.appendChild(modalEl); }
    if (!giftModalInstance) { giftModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }); }
    giftModalInstance.show();
    document.getElementById('gift-animation-container').style.display = 'block';
    document.getElementById('food-result-container').style.display = 'none';
    setTimeout(() => {
        fetch('<?php echo SITE_URL; ?>/ajax/get_random_recipe.php')
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const food = res.data;
                    document.getElementById('time-greeting').textContent = res.message;
                    document.getElementById('food-title').textContent = food.title;
                    document.getElementById('food-cat').textContent = food.category_name;
                    document.getElementById('food-img').src = '<?php echo SITE_URL; ?>/' + food.thumbnail;
                    document.getElementById('food-link').href = '<?php echo SITE_URL; ?>/post/' + food.slug;
                    document.getElementById('gift-animation-container').style.display = 'none';
                    document.getElementById('food-result-container').style.display = 'block';
                    const resultContainer = document.getElementById('food-result-container');
                    resultContainer.classList.remove('animate__zoomIn');
                    void resultContainer.offsetWidth;
                    resultContainer.classList.add('animate__animated', 'animate__zoomIn');
                }
            })
            .catch(err => {
                console.error(err); alert('Có lỗi kết nối!');
                if(giftModalInstance) giftModalInstance.hide();
            });
    }, 1500);
}
document.addEventListener('DOMContentLoaded', function() {
    const newGiftBtn = document.getElementById('btn-open-surprise');
    if(newGiftBtn) { newGiftBtn.addEventListener('click', openGift); }
});
</script>

<?php include 'layouts/footer.php'; ?>