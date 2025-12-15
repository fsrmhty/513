// assets/js/main.js
document.addEventListener('DOMContentLoaded', function () {
    // 可在此添加“加入购物车”AJAX（Week 2 非必需，但可预留）
});

// assets/js/main.js – TechBuild Pro Frontend Enhancements

document.addEventListener('DOMContentLoaded', function () {

    // ==============
    // 1. 购物车“加入”按钮增强（适用于 products/view.php 和 products/index.php）
    // ==============
    const addToCartForms = document.querySelectorAll('form[action$="/cart/index.php"]');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // 阻止默认跳转

            const formData = new FormData(form);
            if (formData.get('action') !== 'add') return;

            // 模拟添加成功（因无 AJAX 后端，仅前端反馈）
            const productName = form.closest('.product-card')?.querySelector('h3')?.innerText ||
                                document.querySelector('h2')?.innerText || 'this item';

            // 创建提示元素
            let notice = document.getElementById('js-cart-notice');
            if (!notice) {
                notice = document.createElement('div');
                notice.id = 'js-cart-notice';
                notice.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 4px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s;
                `;
                document.body.appendChild(notice);
            }

            notice.innerText = `✅ "${productName}" added to cart!`;
            notice.style.opacity = '1';

            // 3秒后淡出
            setTimeout(() => {
                notice.style.opacity = '0';
                setTimeout(() => {
                    if (notice.parentNode) notice.parentNode.removeChild(notice);
                }, 300);
            }, 3000);

            // 更新购物车数量
            updateCartCount();
        });
    });

    // ==============
    // 2. 表单通用辅助（如 contact.php 已使用 handleSubmit）
    // ==============
    window.handleSubmit = function(e) {
        e.preventDefault();
        const name = e.target.querySelector('[name="name"]')?.value || 'there';
        let result = document.getElementById('formResult');
        if (!result) return;

        result.innerHTML = `<p style="color: green;">✅ Thank you, ${name}! In a live system, your message would be sent to our team.</p>`;
        result.style.display = 'block';
        e.target.reset();
    };

    // ==============
    // 3. 实时更新购物车数量
    // ==============
    function updateCartCount() {
        // 注意：此实现需要服务器端支持才能正常工作
        // 因为 JavaScript 无法直接访问 PHP session 数据
        try {
            // 这里保留了原始代码的结构，但在纯 JS 环境中不会正常工作
            // 需要通过 AJAX 请求获取购物车数据
            console.warn("购物车计数功能需要后端 AJAX 支持");
        } catch (error) {
            console.error("无法更新购物车计数:", error);
        }
    }

    // 初始化购物车数量
    updateCartCount();

    // ==============
    // 4. 移动端菜单切换（如需）
    // ==============
    // 可在此添加汉堡菜单逻辑（当前导航已用 flex + 响应式 CSS）

});

// 增强表单验证
function validateForm(form) {
    const email = form.querySelector('input[type="email"]');
    const password = form.querySelector('input[type="password"]');
    
    if (email && !isValidEmail(email.value)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    if (password && password.value.length < 6) {
        showError('Password must be at least 6 characters');
        return false;
    }
    
    return true;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}