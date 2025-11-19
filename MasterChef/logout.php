<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Hủy toàn bộ session
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập
redirect(SITE_URL . '/login.php');
?> 