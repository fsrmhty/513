<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/json_functions.php'; // 新增JSON函数

// 处理添加到购物车
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    cart_add($product_id, $quantity);
    header("Location: " . $_SERVER['REQUEST_URI']); // 刷新页面保持搜索条件
    exit;
}

$search = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

// 从JSON获取所有产品
$all_products = get_all_products();

// 搜索和过滤产品
$filtered_products = array_filter($all_products, function($p) use ($search, $category) {
    // 检查状态
    if (isset($p['status']) && $p['status'] !== 'active') {
        return false;
    }
    
    // 搜索条件
    $search_match = true;
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $name_match = strpos(strtolower($p['name']), $search_lower) !== false;
        $desc_match = strpos(strtolower($p['description']), $search_lower) !== false;
        $category_match = strpos(strtolower($p['category']), $search_lower) !== false;
        $search_match = $name_match || $desc_match || $category_match;
    }
    
    // 分类条件
    $category_match = true;
    if (!empty($category) && $category !== 'all') {
        $category_match = $p['category'] === $category;
    }
    
    return $search_match && $category_match;
});

// 按名称排序
usort($filtered_products, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// 获取所有分类用于筛选
$all_categories = array_unique(array_column($all_products, 'category'));
$all_categories = array_filter($all_categories);
sort($all_categories);
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Search Results</h1>
    <p class="page-subtitle">
        <?php if (!empty($search)): ?>
            Found <?= count($filtered_products) ?> product<?= count($filtered_products) !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($search) ?>"
        <?php else: ?>
            Found <?= count($filtered_products) ?> product<?= count($filtered_products) !== 1 ? 's' : '' ?>
        <?php endif; ?>
    </p>
</div>

<!-- 搜索和筛选表单 -->
<div class="card-hover" style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <form method="GET" action="">
        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
            <div>
                <label class="form-label" for="q">Search Products:</label>
                <input type="text" id="q" name="q" class="form-control" 
                       placeholder="Search by name, description, or category..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div>
                <label class="form-label" for="category">Category:</label>
                <select id="category" name="category" class="form-control">
                    <option value="all">All Categories</option>
                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" 
                                <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="grid-column: 1 / -1; display: flex; gap: 1rem; justify-content: center;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="search.php" class="btn btn-outline">Clear</a>
            </div>
        </div>
    </form>
    
    <!-- 搜索提示 -->
    <?php if (!empty($search) && count($filtered_products) === 0): ?>
        <div style="margin-top: 1rem; padding: 1rem; background: var(--warning-light); border-radius: var(--radius);">
            <p style="margin: 0; color: var(--warning);">
                No products found for "<?= htmlspecialchars($search) ?>". 
                Try different keywords or <a href="search.php">browse all products</a>.
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- JSON存储状态 -->
<div class="card-hover" style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Storage:</strong> JSON File 
            <span style="color: var(--success);">✓ Active</span>
        </div>
        <div>
            <strong>Total in Database:</strong> <?= count($all_products) ?> products
        </div>
    </div>
</div>

<?php if (!empty($filtered_products)): ?>
    <div class="products-grid">
        <?php foreach ($filtered_products as $p): ?>
        <div class="product-card">
            <?php if (!empty($p['image'])): ?>
                <div class="product-image">
                    <img src="/techbuild-pro/assets/images/<?= htmlspecialchars($p['image']) ?>" 
                         alt="<?= htmlspecialchars($p['name']) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="product-image-placeholder" style="background: var(--gray-100); display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <span style="color: var(--gray-400); font-size: 3rem;">
                            <?php 
                            switch($p['type']) {
                                case 'component': echo '💻'; break;
                                case 'build_package': echo '⚙️'; break;
                                case 'repair_service': echo '🔧'; break;
                                default: echo '⚙️';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="product-image" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center;">
                    <span style="color: var(--gray-400); font-size: 3rem;">
                        <?php 
                        switch($p['type']) {
                            case 'component': echo '💻'; break;
                            case 'build_package': echo '⚙️'; break;
                            case 'repair_service': echo '🔧'; break;
                            default: echo '⚙️';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="product-content">
                <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="product-description"><?= htmlspecialchars($p['description']) ?></p>
                
                <div class="product-meta">
                    <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                    <span class="product-type">
                        <?= ucfirst(str_replace('_', ' ', $p['type'])) ?>
                        <?php if (!empty($p['category'])): ?>
                            <span style="color: var(--gray-500);">• <?= htmlspecialchars($p['category']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                    <button class="btn btn-secondary btn-full" disabled>Out of Stock</button>
                <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn btn-primary btn-full">
                            <?= $p['type'] === 'repair_service' ? 'Book Service' : 'Add to Cart' ?>
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- 产品详情链接 -->
                <div style="text-align: center; margin-top: 0.5rem;">
                    <a href="view.php?id=<?= $p['id'] ?>" class="text-sm" style="color: var(--primary); font-size: 0.875rem;">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 搜索结果统计 -->
    <div class="card-hover" style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); margin-top: 2rem;">
        <div style="text-align: center; color: var(--gray-600);">
            <p style="margin: 0;">
                Showing <?= count($filtered_products) ?> of <?= count($all_products) ?> products
                <?php if (!empty($search)): ?>
                    matching "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
                <?php if (!empty($category) && $category !== 'all'): ?>
                    in <?= htmlspecialchars($category) ?> category
                <?php endif; ?>
            </p>
        </div>
    </div>

<?php elseif (empty($search) && empty($category)): ?>
    <!-- 空搜索状态 -->
    <div class="card-hover" style="background: white; padding: 3rem; border-radius: var(--radius-lg); text-align: center;">
        <div style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem;">🔍</div>
        <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Search Products</h3>
        <p style="color: var(--gray-500); margin-bottom: 2rem;">
            Enter keywords to search through our product catalog.<br>
            You can search by product name, description, or category.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="../products/" class="btn btn-primary">Browse All Products</a>
            <a href="?category=GPU" class="btn btn-outline">Popular: GPUs</a>
            <a href="?category=CPU" class="btn btn-outline">Popular: CPUs</a>
        </div>
    </div>
<?php endif; ?>

<script>
// 图片加载错误处理
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.product-image img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            const placeholder = this.nextElementSibling;
            if (placeholder && placeholder.classList.contains('product-image-placeholder')) {
                placeholder.style.display = 'flex';
            }
        });
    });
    
    // 自动聚焦搜索框
    const searchInput = document.getElementById('q');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
});
</script>

<?php include '../includes/footer.php'; ?>