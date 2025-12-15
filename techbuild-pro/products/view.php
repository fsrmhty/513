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
    header("Location: " . $_SERVER['REQUEST_URI']); // 刷新页面
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Product not found.");
}

$product_id = (int)$_GET['id'];

// 从JSON获取产品信息
$product = get_product_by_id($product_id);

if (!$product) {
    die("Product not found.");
}

// 检查产品状态
if (isset($product['status']) && $product['status'] !== 'active') {
    die("Product is not available.");
}

// 获取相关产品推荐
$all_products = get_all_products();
$related_products = array_filter($all_products, function($p) use ($product) {
    return $p['id'] != $product['id'] && 
           $p['type'] === $product['type'] && 
           (!isset($p['status']) || $p['status'] === 'active');
});

// 限制相关产品数量
$related_products = array_slice($related_products, 0, 4);
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars($product['name']) ?></h1>
    <p class="page-subtitle">Product Details</p>
</div>

<div class="admin-container">
    <!-- JSON存储状态提示 -->
    <div class="card-hover" style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Storage:</strong> JSON File 
                <span style="color: var(--success);">✓ Active</span>
            </div>
            <div>
                <strong>Product ID:</strong> <?= $product['id'] ?>
            </div>
            <div>
                <?php if (isset($product['stock'])): ?>
                    <strong>Stock:</strong> 
                    <?php if ($product['stock'] > 10): ?>
                        <span style="color: var(--success);">In Stock</span>
                    <?php elseif ($product['stock'] > 0): ?>
                        <span style="color: var(--warning);">Only <?= $product['stock'] ?> left</span>
                    <?php else: ?>
                        <span style="color: var(--error);">Out of Stock</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: start;">
            <!-- 产品图片区域 -->
            <div>
                <?php if (!empty($product['image'])): ?>
                    <div class="product-image" style="height: 400px; overflow: hidden; border-radius: var(--radius-lg);">
                        <img src="/techbuild-pro/assets/images/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="product-image-placeholder" style="background: var(--gray-100); display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <span style="color: var(--gray-400); font-size: 4rem;">
                                <?php 
                                $icons = [
                                    'component' => '💻',
                                    'build_package' => '⚙️',
                                    'repair_service' => '🔧'
                                ];
                                echo $icons[$product['type']] ?? '⚙️';
                                ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-image" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center; height: 400px; border-radius: var(--radius-lg);">
                        <span style="color: var(--gray-400); font-size: 4rem;">
                            <?php 
                            $icons = [
                                'component' => '💻',
                                'build_package' => '⚙️',
                                'repair_service' => '🔧'
                            ];
                            echo $icons[$product['type']] ?? '⚙️';
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 产品信息区域 -->
            <div>
                <div style="margin-bottom: 2rem;">
                    <h2 style="color: var(--gray-900); margin-bottom: 1rem;"><?= htmlspecialchars($product['name']) ?></h2>
                    <p style="color: var(--gray-600); line-height: 1.6; margin-bottom: 1.5rem;">
                        <?= htmlspecialchars($product['description']) ?>
                    </p>
                    
                    <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem;">
                        <div>
                            <span style="font-size: 2rem; font-weight: bold; color: var(--primary);">
                                $<?= number_format($product['price'], 2) ?>
                            </span>
                        </div>
                        <div>
                            <span class="product-type" style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: var(--radius);">
                                <?= ucfirst(str_replace('_', ' ', $product['type'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- 产品详情 -->
                    <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                        <h4 style="color: var(--gray-700); margin-bottom: 1rem;">Product Details</h4>
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                            <?php if (!empty($product['category'])): ?>
                                <strong>Category:</strong>
                                <span><?= htmlspecialchars($product['category']) ?></span>
                            <?php endif; ?>
                            
                            <?php if (isset($product['stock'])): ?>
                                <strong>Availability:</strong>
                                <span>
                                    <?php if ($product['stock'] > 10): ?>
                                        <span style="color: var(--success);">In Stock</span>
                                    <?php elseif ($product['stock'] > 0): ?>
                                        <span style="color: var(--warning);">Only <?= $product['stock'] ?> left</span>
                                    <?php else: ?>
                                        <span style="color: var(--error);">Out of Stock</span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($product['created_at'])): ?>
                                <strong>Added:</strong>
                                <span><?= date('M j, Y', strtotime($product['created_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 添加到购物车表单 -->
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label class="form-label" for="quantity">Quantity:</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <input type="number" id="quantity" name="quantity" value="1" min="1" 
                                   max="<?= isset($product['stock']) ? $product['stock'] : 99 ?>"
                                   class="form-control" style="width: 100px;">
                            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                                <span style="color: var(--gray-600); font-size: 0.875rem;">
                                    Max: <?= $product['stock'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($product['stock']) && $product['stock'] <= 0): ?>
                        <button type="button" class="btn btn-secondary btn-lg" disabled style="width: 100%;">
                            Out of Stock
                        </button>
                        <p style="color: var(--gray-600); text-align: center; margin-top: 0.5rem;">
                            Contact us for restock information
                        </p>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            Add to Cart
                        </button>
                    <?php endif; ?>
                </form>

                <!-- 快速操作 -->
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="/techbuild-pro/products/" class="btn btn-outline" style="flex: 1;">
                        ← Back to Products
                    </a>
                    <a href="/techbuild-pro/cart/" class="btn btn-outline" style="flex: 1;">
                        View Cart →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 相关产品推荐 -->
    <?php if (!empty($related_products)): ?>
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <h3 style="color: var(--primary); margin-bottom: 1.5rem;">Related Products</h3>
        <div class="products-grid">
            <?php foreach ($related_products as $related): ?>
            <div class="product-card">
                <?php if (!empty($related['image'])): ?>
                    <div class="product-image">
                        <img src="/techbuild-pro/assets/images/<?= htmlspecialchars($related['image']) ?>" 
                             alt="<?= htmlspecialchars($related['name']) ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="product-image-placeholder" style="background: var(--gray-100); display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                            <span style="color: var(--gray-400); font-size: 2rem;">
                                <?php 
                                $icons = [
                                    'component' => '💻',
                                    'build_package' => '⚙️',
                                    'repair_service' => '🔧'
                                ];
                                echo $icons[$related['type']] ?? '⚙️';
                                ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="product-image" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--gray-400); font-size: 2rem;">
                            <?php 
                            $icons = [
                                'component' => '💻',
                                'build_package' => '⚙️',
                                'repair_service' => '🔧'
                            ];
                            echo $icons[$related['type']] ?? '⚙️';
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="product-content">
                    <h4 class="product-title"><?= htmlspecialchars($related['name']) ?></h4>
                    <p class="product-description" style="font-size: 0.875rem;">
                        <?= strlen($related['description']) > 80 ? substr($related['description'], 0, 80) . '...' : $related['description'] ?>
                    </p>
                    
                    <div class="product-meta">
                        <span class="product-price">$<?= number_format($related['price'], 2) ?></span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="view.php?id=<?= $related['id'] ?>" class="btn btn-outline btn-sm" style="flex: 1;">
                            View Details
                        </a>
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="product_id" value="<?= $related['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

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

    // 数量输入框验证
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            const max = parseInt(this.getAttribute('max')) || 99;
            const min = parseInt(this.getAttribute('min')) || 1;
            let value = parseInt(this.value) || min;
            
            if (value < min) value = min;
            if (value > max) value = max;
            
            this.value = value;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>