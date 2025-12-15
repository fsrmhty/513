    </main>
    
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>TechBuild Pro</h3>
                <p>Your trusted partner for custom PC builds and reliable repair services. Building dreams, one component at a time.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="/techbuild-pro/products/">Products & Services</a></p>
                <p><a href="/techbuild-pro/about.php">About Us</a></p>
                <p><a href="/techbuild-pro/contact.php">Contact</a></p>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>📧 support@techbuildpro.local</p>
                <p>📞 (02) 9999 8888</p>
                <p>📍 123 Tech Street, Sydney NSW 2000</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 TechBuild Pro. All rights reserved. | ICTWEB513 Project</p>
        </div>
    </footer>

    <script>
    function toggleMenu() {
        document.getElementById('main-nav').classList.toggle('show');
    }
    
    // 实时更新购物车数量
    function updateCartCount(count) {
        const cartBadge = document.querySelector('.cart-badge');
        const cartLink = document.getElementById('cart-link');
        
        if (count > 0) {
            if (cartBadge) {
                cartBadge.textContent = count;
            } else {
                const badge = document.createElement('span');
                badge.className = 'cart-badge';
                badge.textContent = count;
                cartLink.appendChild(badge);
            }
        } else if (cartBadge) {
            cartBadge.remove();
        }
    }
    </script>
</body>
</html>