<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Chef - Chia sẻ công thức nấu ăn</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="<?php echo SITE_URL; ?>/assets/css/glassmorphism.css" rel="stylesheet">
    
    <?php if(isset($_SESSION['additional_css'])): ?>
        <?php echo $_SESSION['additional_css']; ?>
        <?php unset($_SESSION['additional_css']); // Clear after use ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Master Chef" height="50">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="d-flex justify-content-between collapse navbar-collapse" id="navbarNav">
                    <form class="d-flex" method="GET" action="<?php echo SITE_URL; ?>/search.php">
                        <input class="form-control me-2" type="search" name="keyword" placeholder="Tìm kiếm công thức..." aria-label="Search">
                        <button class="btn btn-outline-success" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <ul class="navbar-nav ms-3 align-items-center">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item me-3">
                                <a class="nav-link btn btn-sm btn-primary text-white px-3 fw-bold shadow-sm" 
                                   href="<?php echo SITE_URL; ?>/submit_recipe.php" 
                                   style="border-radius: 20px;">
                                    <i class="fas fa-plus me-1"></i> Đăng công thức
                                </a>
                            </li>

                            <li class="nav-item me-2">
                                <a class="nav-link" href="#">
                                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                                </a>
                            </li>
                            
                            <?php if($_SESSION['role'] === 'admin'): ?>
                                <li class="nav-item me-2">
                                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/users.php">Quản trị</a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item me-2">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/favorites.php">Yêu thích</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">Đăng xuất</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Đăng nhập</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">Đăng ký</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container my-4">