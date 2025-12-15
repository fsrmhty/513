<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/json_functions.php'; // 新增JSON函数
require_admin();

// 处理新增/编辑/删除 - 改为JSON操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $product_data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'type' => $_POST['type'],
                    'category' => $_POST['category'] ?? '',
                    'image' => $_POST['image'] ?? ''
                ];
                
                $new_id = add_product($product_data);
                if ($new_id) {
                    // 创建备份
                    backup_products_json();
                    header("Location: products.php?msg=created");
                } else {
                    header("Location: products.php?msg=error");
                }
                exit;
                
            case 'update':
                $product_id = (int)$_POST['product_id'];
                $product_data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'type' => $_POST['type'],
                    'category' => $_POST['category'] ?? '',
                    'image' => $_POST['image'] ?? ''
                ];
                
                if (update_product($product_id, $product_data)) {
                    // 创建备份
                    backup_products_json();
                    header("Location: products.php?msg=updated");
                } else {
                    header("Location: products.php?msg=error");
                }
                exit;
                
            case 'delete':
                $product_id = (int)$_POST['product_id'];
                if (delete_product($product_id)) {
                    // 创建备份
                    backup_products_json();
                    header("Location: products.php?msg=deleted");
                } else {
                    header("Location: products.php?msg=error");
                }
                exit;
        }
    }
}

// 获取所有产品 - 改为从JSON读取
$products = get_all_products();

// 按类型和名称排序
usort($products, function($a, $b) {
    if ($a['type'] == $b['type']) {
        return strcmp($a['name'], $b['name']);
    }
    return strcmp($a['type'], $b['type']);
});
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Manage Products & Services</h1>
    <p class="page-subtitle">Add, edit, or remove products and services (JSON Storage)</p>
</div>

<div class="admin-container">
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'created'): ?>
            <div class="alert alert-success">Product created successfully!</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Product updated successfully!</div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Product deleted successfully!</div>
        <?php elseif ($_GET['msg'] === 'error'): ?>
            <div class="alert alert-error">Error processing request. Please try again.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- JSON文件信息 -->
    <div class="card-hover" style="background: var(--primary-50); padding: 1rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Storage:</strong> JSON File 
                <span style="color: var(--success);">✓ Active</span>
            </div>
            <div>
                <strong>Total Products:</strong> <?= count($products) ?>
            </div>
            <div>
                <a href="/techbuild-pro/data/products.json" target="_blank" class="btn btn-sm btn-outline">View JSON File</a>
            </div>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Add New Product/Service</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="product_id" value="">
            
            <div class="form-group">
                <label class="form-label">Name:</label>
                <input type="text" name="name" class="form-control" placeholder="Product name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description:</label>
                <textarea name="description" class="form-control" placeholder="Product description" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Price:</label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Type:</label>
                <select name="type" class="form-control" required>
                    <option value="component">Component</option>
                    <option value="build_package">Build Package</option>
                    <option value="repair_service">Repair Service</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category:</label>
                <input type="text" name="category" class="form-control" placeholder="Category (e.g., GPU, RAM)">
            </div>
            
            <div class="form-group">
                <label class="form-label">Image (optional):</label>
                <input type="text" name="image" class="form-control" placeholder="Image filename (e.g., product.jpg)">
            </div>
            
            <button type="submit" class="btn btn-primary">Add Product</button>
        </form>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Existing Items (<?= count($products) ?>)</h3>
        
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                <p>No products found. Add your first product above.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>
                                <span class="product-type">
                                    <?= ucfirst(str_replace('_', ' ', $p['type'])) ?>
                                </span>
                            </td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                            <td>
                                <button onclick="editProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', '<?= htmlspecialchars($p['description']) ?>', <?= $p['price'] ?>, '<?= $p['type'] ?>', '<?= htmlspecialchars($p['category'] ?? '') ?>', '<?= htmlspecialchars($p['image'] ?? '') ?>')" 
                                        class="btn btn-sm btn-outline">Edit</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete this product?')" 
                                            class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function editProduct(id, name, description, price, type, category, image) {
    document.querySelector('input[name="action"]').value = 'update';
    document.querySelector('input[name="product_id"]').value = id;
    document.querySelector('input[name="name"]').value = name;
    document.querySelector('textarea[name="description"]').value = description;
    document.querySelector('input[name="price"]').value = price;
    document.querySelector('select[name="type"]').value = type;
    document.querySelector('input[name="category"]').value = category;
    document.querySelector('input[name="image"]').value = image;
    
    // 更改按钮文本
    document.querySelector('button[type="submit"]').textContent = 'Update Product';
    
    // 滚动到表单
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include '../includes/footer.php'; ?>