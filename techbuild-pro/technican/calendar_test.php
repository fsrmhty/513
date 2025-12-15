<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查权限
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'technician' && $role !== 'admin') {
    header("Location: /techbuild-pro/");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Portal - TechBuild Pro</title>
    
    <!-- 使用多个CDN源作为备用 -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- 备用CDN -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        /* 确保日历容器有明确的高度 */
        #calendar {
            height: 600px;
            background: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Technician Portal - Fixed Version</h1>
        <p class="page-subtitle">Calendar should work now</p>
    </div>

    <div class="admin-container">
        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">My Schedule Calendar</h3>
            <div id='calendar'></div>
        </div>
    </div>

    <!-- 加载 jQuery (FullCalendar 可能需要) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- 多个 CDN 备用 -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        // 如果第一个CDN失败，尝试第二个
        if (typeof FullCalendar === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.js"><\/script>');
        }
    </script>

    <script>
    // 等待所有资源加载完成
    window.addEventListener('load', function() {
        console.log('页面完全加载完成');
        initializeCalendar();
    });

    function initializeCalendar() {
        console.log('开始初始化日历...');
        
        // 检查 FullCalendar 是否可用
        if (typeof FullCalendar === 'undefined') {
            console.error('❌ FullCalendar 仍然未定义！');
            document.getElementById('calendar').innerHTML = `
                <div style="text-align: center; padding: 50px; color: red;">
                    <h3>❌ FullCalendar 加载失败</h3>
                    <p>请检查网络连接或联系管理员</p>
                    <button onclick="location.reload()" class="btn btn-primary">重试</button>
                </div>
            `;
            return;
        }
        
        var calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            console.error('❌ 找不到日历容器');
            return;
        }
        
        try {
            console.log('✅ FullCalendar 可用，版本:', FullCalendar.version);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    {
                        title: '测试维修任务 - 电脑诊断',
                        start: '2024-01-15T09:00:00',
                        end: '2024-01-15T11:00:00',
                        color: '#3B82F6'
                    },
                    {
                        title: '硬件更换 - 显卡升级',
                        start: '2024-01-16T14:00:00',
                        end: '2024-01-16T16:00:00', 
                        color: '#10B981'
                    },
                    {
                        title: '软件安装服务',
                        start: '2024-01-18T10:00:00',
                        end: '2024-01-18T12:00:00',
                        color: '#F59E0B'
                    }
                ],
                eventClick: function(info) {
                    alert(
                        '任务: ' + info.event.title + '\n' +
                        '开始: ' + info.event.start.toLocaleString() + '\n' +
                        '结束: ' + info.event.end.toLocaleString()
                    );
                }
            });
            
            calendar.render();
            console.log('✅ 日历渲染成功！');
            
        } catch (error) {
            console.error('❌ 日历初始化错误:', error);
            calendarEl.innerHTML = `
                <div style="text-align: center; padding: 50px; color: red;">
                    <h3>❌ 日历初始化错误</h3>
                    <p>${error.message}</p>
                    <details style="text-align: left; margin-top: 20px;">
                        <summary>技术详情</summary>
                        <pre>${error.stack}</pre>
                    </details>
                </div>
            `;
        }
    }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>