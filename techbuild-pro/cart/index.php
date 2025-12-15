<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 处理表单提交 - 只保留购物车管理功能，移除添加商品功能
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 移除添加商品的逻辑，因为现在在产品页面处理
    /*
    if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['product_id'])) {
        cart_add($_POST['product_id'], $_POST['quantity'] ?? 1);
        header("Location: /techbuild-pro/cart/");
        exit;
    }
    */

    // 只保留更新数量和清空购物车的功能
    if (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $id => $qty) {
            cart_update($id, $qty);
        }
        header("Location: /techbuild-pro/cart/");
        exit;
    }

    if (isset($_POST['clear'])) {
        cart_clear();
        header("Location: /techbuild-pro/cart/");
        exit;
    }
}

$cart_items = cart_get_items($pdo);
$total = array_sum(array_column($cart_items, 'total'));
?>

<?php include '../includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Your Shopping Cart</h1>
    <p class="page-subtitle">Review and manage your selected items</p>
</div>

<div class="admin-container">
    <?php if (empty($cart_items)): ?>
        <div class="text-center" style="padding: 3rem;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">Your cart is empty</p>
            <a href="/techbuild-pro/products/" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                <input type="number" name="quantity[<?= $item['id'] ?>]" 
                                       value="<?= $item['quantity'] ?>" min="1" 
                                       class="form-control" style="width: 80px;">
                            </td>
                            <td>$<?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Grand Total:</td>
                            <td style="font-weight: bold;">$<?= number_format($total, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" name="update" value="1" class="btn btn-secondary">Update Quantities</button>
                <button type="submit" name="clear" value="1" class="btn btn-danger">Clear Cart</button>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <a href="/techbuild-pro/products/" class="btn btn-outline">← Continue Shopping</a>
                <a href="/techbuild-pro/cart/checkout.php" class="btn btn-primary">Proceed to Checkout →</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>