<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Khởi tạo database
$db = new Database();

// 1. Lấy tham số từ URL
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$post_type_slug = isset($_GET['type']) ? trim($_GET['type']) : '';
$ingredients_input = isset($_GET['ingredients']) ? trim($_GET['ingredients']) : ''; // Mới thêm

// 2. Xây dựng câu truy vấn
$sql = "SELECT p.*, u.username, u.full_name, fc.name AS category_name, fc.slug AS category_slug, 
        pt.name AS post_type_name, pt.slug AS post_type_slug,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN food_categories fc ON p.food_category_id = fc.id
        JOIN post_types pt ON p.post_type_id = pt.id
        WHERE 1=1";

$bindParams = [];

// Điều kiện từ khóa (Title hoặc Content)
if (!empty($keyword)) {
    $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $search_term = "%$keyword%";
    $bindParams[] = $search_term;
    $bindParams[] = $search_term;
}

// Điều kiện danh mục
if (!empty($category_slug)) {
    $sql .= " AND fc.slug = ?";
    $bindParams[] = $category_slug;
}

// Điều kiện loại bài viết
if (!empty($post_type_slug)) {
    $sql .= " AND pt.slug = ?";
    $bindParams[] = $post_type_slug;
}

// --- MỚI: Điều kiện nguyên liệu ---
if (!empty($ingredients_input)) {
    // Tách chuỗi nguyên liệu bằng dấu phẩy (ví dụ: "trứng, sữa")
    $ing_list = explode(',', $ingredients_input);
    
    foreach ($ing_list as $ing) {
        $ing = trim($ing);
        if (!empty($ing)) {
            // Dùng AND để tìm bài viết có chứa TẤT CẢ nguyên liệu liệt kê
            $sql .= " AND p.ingredients LIKE ?";
            $bindParams[] = "%$ing%";
        }
    }
}

// Sắp xếp
$sql .= " ORDER BY p.created_at DESC";

// Thực hiện truy vấn
$db->query($sql);
foreach ($bindParams as $param) {
    $db->bind(null, $param);
}
$posts = $db->getAll();

// Lấy danh sách danh mục & loại để hiển thị lại vào Form
$db->query("SELECT * FROM food_categories ORDER BY name");
$food_categories = $db->getAll();

$db->query("SELECT * FROM post_types ORDER BY name");
$post_types = $db->getAll();

// Tạo tiêu đề trang động
$page_title = 'Tìm kiếm';
$conditions = [];
if (!empty($keyword)) $conditions[] = "Từ khóa: '$keyword'";
if (!empty($ingredients_input)) $conditions[] = "Nguyên liệu: '$ingredients_input'";
if (!empty($conditions)) {
    $page_title = 'Kết quả cho ' . implode(', ', $conditions);
}

include 'layouts/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h1 class="h4 mb-0 text-primary"><i class="fas fa-search me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            <div class="card-body bg-light">
                <form method="GET" action="<?php echo SITE_URL; ?>/search.php" class="mb-0">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-heading"></i></span>
                                <input id="search-autocomplete" type="text" class="form-control" name="keyword" 
                                       autocomplete="off" placeholder="Nhập tên món ăn hoặc nội dung..." 
                                       value="<?php echo htmlspecialchars($keyword); ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">-- Tất cả danh mục --</option>
                                <?php foreach($food_categories as $category): ?>
                                    <option value="<?php echo $category['slug']; ?>" <?php echo ($category_slug === $category['slug']) ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" name="type">
                                <option value="">-- Tất cả loại --</option>
                                <?php foreach($post_types as $type): ?>
                                    <option value="<?php echo $type['slug']; ?>" <?php echo ($post_type_slug === $type['slug']) ? 'selected' : ''; ?>>
                                        <?php echo $type['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-carrot"></i></span>
                                <input type="text" class="form-control" name="ingredients" 
                                       placeholder="Lọc theo nguyên liệu (phân cách dấu phẩy)" 
                                       value="<?php echo htmlspecialchars($ingredients_input); ?>">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="mb-5">
            <?php if (count($posts) > 0): ?>
                <p class="text-muted mb-3 fst-italic">Tìm thấy <strong><?php echo count($posts); ?></strong> công thức phù hợp.</p>
                
                <div class="row">
                <?php foreach($posts as $post): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm hover-shadow transition">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 position-relative">
                                    <img src="<?php echo SITE_URL . '/' . $post['thumbnail']; ?>" class="img-fluid rounded-start h-100 w-100" alt="<?php echo $post['title']; ?>" style="object-fit: cover; min-height: 200px;">
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-2"><?php echo $post['category_name']; ?></span>
                                </div>
                                <div class="col-md-7">
                                    <div class="card-body d-flex flex-column h-100">
                                        <h5 class="card-title">
                                            <a href="<?php echo SITE_URL . '/post/' . $post['slug']; ?>" class="text-decoration-none text-dark fw-bold stretched-link">
                                                <?php echo $post['title']; ?>
                                            </a>
                                        </h5>
                                        
                                        <?php if(!empty($ingredients_input) && !empty($post['ingredients'])): ?>
                                            <p class="card-text small text-muted mb-2">
                                                <i class="fas fa-shopping-basket me-1"></i> 
                                                <?php echo substr($post['ingredients'], 0, 50) . '...'; ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo substr(strip_tags($post['content']), 0, 80) . '...'; ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mt-auto d-flex justify-content-between align-items-center small text-muted border-top pt-2">
                                            <span><i class="far fa-user me-1"></i> <?php echo $post['full_name']; ?></span>
                                            <span><i class="far fa-clock me-1"></i> <?php echo formatDate($post['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded shadow-sm">
                    <img src="https://cdn-icons-png.flaticon.com/512/6134/6134065.png" alt="No results" style="width: 80px; opacity: 0.5" class="mb-3">
                    <h4>Không tìm thấy kết quả nào</h4>
                    <p class="text-muted">Rất tiếc, không có công thức nào phù hợp với bộ lọc hiện tại.</p>
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/search.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-sync me-1"></i> Xóa bộ lọc
                        </a>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-sm ms-2">
                            <i class="fas fa-home me-1"></i> Về trang chủ
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('search-autocomplete');
    if (!input) return;

    // Tạo container chứa gợi ý
    const parent = input.parentNode;
    // Đảm bảo parent có position relative để box gợi ý nằm đúng chỗ
    if (getComputedStyle(parent).position === 'static') {
        parent.style.position = 'relative';
    }

    const suggestionsBox = document.createElement('div');
    suggestionsBox.id = 'search-suggestions';
    suggestionsBox.className = 'list-group position-absolute w-100 shadow';
    suggestionsBox.style.top = '100%';
    suggestionsBox.style.zIndex = 1050;
    suggestionsBox.style.display = 'none';
    suggestionsBox.style.maxHeight = '350px'; // Giới hạn chiều cao
    suggestionsBox.style.overflowY = 'auto';  // Thêm thanh cuộn
    parent.appendChild(suggestionsBox);

    let timer = null;

    // Hàm tiện ích
    function closeSuggestions(){ suggestionsBox.innerHTML = ''; suggestionsBox.style.display = 'none'; }
    function openSuggestions(){ suggestionsBox.style.display = 'block'; }
    function escapeHtml(s){ return String(s || '').replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }

    // Xử lý sự kiện nhập liệu
    input.addEventListener('input', function(){
        const q = this.value.trim();
        
        // Lấy giá trị các bộ lọc hiện tại
        const categoryEl = document.querySelector('select[name="category"]');
        const typeEl = document.querySelector('select[name="type"]');
        const ingredientsEl = document.querySelector('input[name="ingredients"]');

        const category = categoryEl ? categoryEl.value : '';
        const type = typeEl ? typeEl.value : '';
        const ingredients = ingredientsEl ? ingredientsEl.value.trim() : '';
        
        if (timer) clearTimeout(timer);
        
        if (!q){ 
            closeSuggestions(); 
            return; 
        }

        // Debounce 300ms để tránh gửi quá nhiều request
        timer = setTimeout(() => {
            // Tạo URL query string
            const params = new URLSearchParams({
                q: q,
                category: category,
                type: type,
                ingredients: ingredients
            });

            // Gọi AJAX
            fetch('<?php echo SITE_URL; ?>/ajax/search_suggestions.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                
                if (!Array.isArray(data) || data.length === 0) { 
                    // Tùy chọn: Hiển thị thông báo không tìm thấy
                    // closeSuggestions(); 
                    // return;
                    
                    // Hoặc hiển thị dòng "Không có kết quả"
                     const noResult = document.createElement('div');
                     noResult.className = 'list-group-item text-muted small fst-italic';
                     noResult.textContent = 'Không tìm thấy kết quả phù hợp';
                     suggestionsBox.appendChild(noResult);
                     openSuggestions();
                     return;
                }

                data.forEach(item => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action d-flex align-items-center p-2';
                    a.href = '<?php echo SITE_URL; ?>/post/' + encodeURIComponent(item.slug);
                    
                    // Xử lý ảnh thumbnail
                    let thumbHtml = '';
                    if(item.thumbnail) {
                        thumbHtml = `<img src="<?php echo SITE_URL; ?>/${escapeHtml(item.thumbnail)}" 
                                     style="width:40px;height:40px;object-fit:cover;margin-right:10px;border-radius:4px;"
                                     alt="${escapeHtml(item.title)}">`;
                    } else {
                        thumbHtml = `<div class="bg-light d-flex align-items-center justify-content-center text-secondary" 
                                     style="width:40px;height:40px;margin-right:10px;border-radius:4px;">
                                     <i class="fas fa-utensils"></i></div>`;
                    }

                    a.innerHTML = `
                        ${thumbHtml}
                        <div class="small text-truncate" style="flex:1;">
                            <strong class="text-dark">${escapeHtml(item.title)}</strong><br>
                            <span class="text-muted" style="font-size:0.8rem">
                                ${escapeHtml(item.category_name || 'Món ăn')} 
                                ${item.type_name ? ' • ' + escapeHtml(item.type_name) : ''}
                            </span>
                        </div>
                    `;
                    suggestionsBox.appendChild(a);
                });
                openSuggestions();
            })
            .catch(err => {
                console.error('Lỗi search suggestion:', err);
                closeSuggestions();
            });
        }, 300);
    });

    // Đóng gợi ý khi click ra ngoài
    document.addEventListener('click', function(e){
        if (!suggestionsBox.contains(e.target) && e.target !== input) {
            closeSuggestions();
        }
    });
});
</script>

<?php include 'layouts/footer.php'; ?>