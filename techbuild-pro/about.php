<?php
require_once 'config/session.php';
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">About TechBuild Pro</h1>
    <p class="page-subtitle">Your trusted partner for everything PC-related</p>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <p style="font-size: 1.125rem; line-height: 1.7;">
            <strong>TechBuild Pro</strong> is your trusted local partner for everything PC-related — whether you're a student building your first gaming rig, a freelancer needing a reliable workstation, or a small business seeking fast repair solutions.
        </p>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Our Mission</h3>
        <p style="line-height: 1.7;">
            To eliminate the frustration of incompatible parts, unreliable repairs, and fragmented tech services by offering a <strong>unified, expert-guided platform</strong> that combines retail, custom builds, and on-demand support — all in one place.
        </p>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Why Choose Us?</h3>
        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <span style="color: var(--success); font-size: 1.25rem;">✅</span>
                <div>
                    <strong>Compatibility Guaranteed</strong>
                    <p style="margin: 0.25rem 0 0; color: var(--gray-600);">Every custom build is validated by our experts to ensure all components work together.</p>
                </div>
            </div>
            <div style="display: flex; align-items: start; gap: 1rem;">
                <span style="color: var(--success); font-size: 1.25rem;">✅</span>
                <div>
                    <strong>Local & Reliable Repairs</strong>
                    <p style="margin: 0.25rem 0 0; color: var(--gray-600);">Book a technician with transparent time slots — no more waiting weeks for help.</p>
                </div>
            </div>
            <div style="display: flex; align-items: start; gap: 1rem;">
                <span style="color: var(--success); font-size: 1.25rem;">✅</span>
                <div>
                    <strong>Flexible Options</strong>
                    <p style="margin: 0.25rem 0 0; color: var(--gray-600);">Buy individual parts, order a full build, or schedule a repair — your choice, your control.</p>
                </div>
            </div>
            <div style="display: flex; align-items: start; gap: 1rem;">
                <span style="color: var(--success); font-size: 1.25rem;">✅</span>
                <div>
                    <strong>No Hidden Fees</strong>
                    <p style="margin: 0.25rem 0 0; color: var(--gray-600);">Clear pricing, no surprise charges, and post-purchase support included.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Our Story</h3>
        <p style="line-height: 1.7; margin-bottom: 1rem;">
            Founded by a local tech enthusiast, TechBuild Pro was born from real experiences: watching friends waste money on mismatched hardware, or waiting days for a simple laptop fix. We believe technology should empower — not confuse — and that everyone deserves access to honest, expert tech support.
        </p>
        <p style="line-height: 1.7;">
            While we're currently a solo project for educational purposes (ICTWEB513), our vision is to one day partner with certified local technicians to bring this service to life in our community.
        </p>
    </div>
</div>


<!-- 在about.php的合适位置添加这段代码 -->
<div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
    <h3 style="color: var(--primary); margin-bottom: 1rem;">📍 Our Location</h3>
    <p style="margin-bottom: 1.5rem; color: var(--gray-600);">
        Visit our workshop in Sydney, Australia's tech hub.
    </p>
    
    <!-- 百度地图容器 -->
    <div id="baidu-map" style="width: 100%; height: 300px; border-radius: var(--radius); overflow: hidden;"></div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
        <div>
            <p style="margin: 0.5rem 0; color: var(--gray-700);">
                <strong>Address:</strong> 123 Tech Street, Sydney NSW 2000
            </p>
            <p style="margin: 0.5rem 0; color: var(--gray-700);">
                <strong>Hours:</strong> Mon-Fri 9AM-6PM, Sat 10AM-4PM
            </p>
        </div>
        <a href="https://map.baidu.com/search/123%20Tech%20Street%20Sydney/@151.2066,-33.8671,15z" 
           target="_blank" 
           class="btn btn-outline btn-sm">
            Open in Baidu Maps →
        </a>
    </div>
</div>

<!-- 百度地图API脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 创建地图函数
    function createBaiduMap() {
        try {
            // 悉尼坐标 (经度, 纬度)
            var point = new BMap.Point(151.2066, -33.8671);
            
            // 创建地图实例
            var map = new BMap.Map("baidu-map");
            
            // 初始化地图，设置中心点坐标和缩放级别
            map.centerAndZoom(point, 15);
            
            // 启用鼠标滚轮缩放
            map.enableScrollWheelZoom(true);
            
            // 添加标记
            var marker = new BMap.Marker(point);
            map.addOverlay(marker);
            
            // 添加信息窗口
            var infoWindow = new BMap.InfoWindow(
                "<div style='padding: 10px;'>" +
                "<h4 style='margin: 0 0 5px 0;'>TechBuild Pro Workshop</h4>" +
                "<p style='margin: 0;'>123 Tech Street, Sydney</p>" +
                "<p style='margin: 5px 0 0 0;'><small>Your trusted PC building experts</small></p>" +
                "</div>"
            );
            
            marker.addEventListener("click", function() {
                this.openInfoWindow(infoWindow);
            });
            
            // 添加控件
            map.addControl(new BMap.NavigationControl());
            map.addControl(new BMap.ScaleControl());
            
            console.log('✅ Baidu Map loaded successfully');
            
        } catch (error) {
            console.error('❌ Map loading error:', error);
            // 如果地图加载失败，显示备用图片
            document.getElementById('baidu-map').innerHTML = `
                <div style="width: 100%; height: 100%; background: var(--gray-100); 
                            display: flex; align-items: center; justify-content: center; 
                            border-radius: var(--radius);">
                    <div style="text-align: center; padding: 2rem;">
                        <div style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;">📍</div>
                        <p style="color: var(--gray-600); margin: 0;">
                            Map temporarily unavailable<br>
                            <small>123 Tech Street, Sydney NSW 2000</small>
                        </p>
                    </div>
                </div>
            `;
        }
    }
    
    // 异步加载百度地图API
    function loadBaiduMapAPI() {
        // 检查是否已经加载
        if (window.BMap) {
            createBaiduMap();
            return;
        }
        
        // 创建script标签加载API
        var script = document.createElement('script');
        script.src = 'https://api.map.baidu.com/api?v=3.0&ak=pBzc4saBZ2EzEs50PGWoltZqyQWMqEVI&callback=initBaiduMap';
        script.async = true;
        script.defer = true;
        
        // 定义全局回调函数
        window.initBaiduMap = function() {
            createBaiduMap();
        };
        
        // 添加到页面
        document.head.appendChild(script);
    }
    
    // 延迟加载地图（提高页面加载速度）
    setTimeout(loadBaiduMapAPI, 1000);
});
</script>

<!-- 备用：如果没有百度地图API密钥，使用简单的静态地图 -->
<noscript>
    <div style="width: 100%; height: 300px; background: var(--gray-100); 
                border-radius: var(--radius); overflow: hidden; position: relative;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
            <div style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;">📍</div>
            <p style="color: var(--gray-700); margin: 0;">
                <strong>TechBuild Pro Workshop</strong><br>
                <small>123 Tech Street, Sydney NSW 2000</small>
            </p>
        </div>
    </div>
</noscript>


<?php include 'includes/footer.php'; ?>