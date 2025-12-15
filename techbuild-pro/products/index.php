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
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// 获取产品类型参数
$type = $_GET['type'] ?? 'all';
$category = $_GET['category'] ?? '';

// 从JSON获取所有产品
$all_products = get_all_products();

// 过滤产品基于类型和分类
$filtered_products = array_filter($all_products, function($p) use ($type, $category) {
    // 检查状态
    if (isset($p['status']) && $p['status'] !== 'active') {
        return false;
    }
    
    // 检查类型
    if ($type !== 'all' && $p['type'] !== $type) {
        return false;
    }
    
    // 检查分类
    if (!empty($category) && $p['category'] !== $category) {
        return false;
    }
    
    return true;
});

// 按类型排序
usort($filtered_products, function($a, $b) {
    $type_order = ['component' => 1, 'build_package' => 2, 'repair_service' => 3];
    $a_order = $type_order[$a['type']] ?? 999;
    $b_order = $type_order[$b['type']] ?? 999;
    
    if ($a_order !== $b_order) {
        return $a_order - $b_order;
    }
    
    return strcmp($a['name'], $b['name']);
});

// 按类型分组产品
$components = array_filter($filtered_products, fn($p) => $p['type'] === 'component');
$build_packages = array_filter($filtered_products, fn($p) => $p['type'] === 'build_package');
$services = array_filter($filtered_products, fn($p) => $p['type'] === 'repair_service');
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Products & Services</h1>
    <p class="page-subtitle">Discover components, custom builds, and professional repair services</p>
</div>

<!-- JSON存储状态提示 -->
<div class="card-hover" style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Storage:</strong> JSON File 
            <span style="color: var(--success);">✓ Active</span>
        </div>
        <div>
            <strong>Showing:</strong> <?= count($filtered_products) ?> of <?= count($all_products) ?> products
        </div>
    </div>
</div>

<!-- 导航标签 -->
<div class="card-hover" style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
        <a href="?type=all" class="btn <?= $type === 'all' ? 'btn-primary' : 'btn-outline' ?>">All Items</a>
        <a href="?type=component" class="btn <?= $type === 'component' ? 'btn-primary' : 'btn-outline' ?>">Components</a>
        <a href="?type=build_package" class="btn <?= $type === 'build_package' ? 'btn-primary' : 'btn-outline' ?>">Custom Builds</a>
        <a href="?type=repair_service" class="btn <?= $type === 'repair_service' ? 'btn-primary' : 'btn-outline' ?>">Repair Services</a>
    </div>
    
    <!-- 分类筛选（如果有分类数据） -->
    <?php
    $all_categories = array_unique(array_column($all_products, 'category'));
    $all_categories = array_filter($all_categories);
    if (!empty($all_categories) && $type !== 'repair_service'):
    ?>
    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
        <p style="text-align: center; margin-bottom: 0.5rem; color: var(--gray-600);">Filter by Category:</p>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
            <a href="?type=<?= $type ?>" class="btn btn-sm <?= empty($category) ? 'btn-primary' : 'btn-outline' ?>">All Categories</a>
            <?php foreach ($all_categories as $cat): ?>
                <a href="?type=<?= $type ?>&category=<?= urlencode($cat) ?>" 
                   class="btn btn-sm <?= $category === $cat ? 'btn-primary' : 'btn-outline' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($type === 'all' || $type === 'component'): ?>
<!-- 电脑组件部分 -->
<div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="color: var(--primary); margin: 0;">🖥️ Computer Components</h2>
        <span class="product-count"><?= count($components) ?> items</span>
    </div>
    
    <?php if (!empty($components)): ?>
        <div class="products-grid">
            <?php foreach ($components as $p): ?>
            <div class="product-card">
                <?php if (!empty($p['image'])): ?>
                    <div class="product-image">
                        <img src="/techbuild-pro/assets/images/<?= htmlspecialchars($p['image']) ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="product-image-placeholder" style="background: var(--gray-100); display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <span style="color: var(--gray-400); font-size: 3rem;">💻</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-image" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--gray-400); font-size: 3rem;">💻</span>
                    </div>
                <?php endif; ?>
                
                <div class="product-content">
                    <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="product-description"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="product-meta">
                        <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="product-type"><?= htmlspecialchars($p['category']) ?></span>
                    </div>
                    
                    <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                        <button class="btn btn-secondary btn-full" disabled>Out of Stock</button>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn btn-primary btn-full">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
            <p>No components found.</p>
            <?php if ($type === 'component'): ?>
                <p class="text-sm">Try changing the category filter or <a href="?type=all">view all products</a>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($type === 'all' || $type === 'build_package'): ?>
<!-- 定制构建部分 -->
<div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="color: var(--primary); margin: 0;">🔧 Custom PC Builds</h2>
        <span class="product-count"><?= count($build_packages) ?> builds</span>
    </div>
    
    <?php if (!empty($build_packages)): ?>
        <div class="products-grid">
            <?php foreach ($build_packages as $p): ?>
            <div class="product-card">
                <?php if (!empty($p['image'])): ?>
                    <div class="product-image">
                        <img src="/techbuild-pro/assets/images/<?= htmlspecialchars($p['image']) ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="product-image-placeholder" style="background: var(--gray-100); display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <span style="color: var(--gray-400); font-size: 3rem;">⚙️</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-image" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--gray-400); font-size: 3rem;">⚙️</span>
                    </div>
                <?php endif; ?>
                
                <div class="product-content">
                    <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="product-description"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="product-meta">
                        <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="product-type"><?= ucfirst(str_replace('_', ' ', $p['type'])) ?></span>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn btn-primary btn-full">Add to Cart</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
            <p>No custom builds found.</p>
            <?php if ($type === 'build_package'): ?>
                <p class="text-sm">Try <a href="?type=all">viewing all products</a>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($type === 'all' || $type === 'repair_service'): ?>
<!-- 维修服务部分 -->
<div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="color: var(--primary); margin: 0;">🛠️ Repair Services</h2>
        <span class="product-count"><?= count($services) ?> services</span>
    </div>
    
    <?php if (!empty($services)): ?>
        <div class="products-grid">
            <?php foreach ($services as $p): ?>
            <div class="product-card">
                <div class="product-content">
                    <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2.5rem; color: var(--primary);">🔧</div>
                        <div>
                            <h3 class="product-title" style="margin-bottom: 0.5rem;"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="product-description" style="margin-bottom: 1rem;"><?= htmlspecialchars($p['description']) ?></p>
                        </div>
                    </div>
                    
                    <div style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">
                                $<?= number_format($p['price'], 2) ?>
                            </span>
                            <span style="background: var(--success); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius); font-size: 0.75rem;">
                                Service
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <!-- 直接购买 -->
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn btn-primary btn-full">Book Service</button>
                        </form>
                        
                        <!-- 查看详情 -->
                        <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-outline">Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
            <p>No repair services found.</p>
            <?php if ($type === 'repair_service'): ?>
                <p class="text-sm">Try <a href="?type=all">viewing all products</a>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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
});
</script>

<?php include '../includes/footer.php'; ?>