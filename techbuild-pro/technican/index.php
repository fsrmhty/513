<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查是否是技术人员
if (!isset($_SESSION['user_id'])) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

// 查询角色
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'technician' && $role !== 'admin') {
    header("Location: /techbuild-pro/");
    exit;
}

// 获取技术人员的任务
$stmt = $pdo->prepare("
    SELECT id, title, start_datetime, end_datetime, status, description
    FROM technician_schedule 
    WHERE technician_id = ?
    ORDER BY start_datetime
");
$stmt->execute([$_SESSION['user_id']]);
$schedule = $stmt->fetchAll();

// 统计数量
$scheduled_count = count(array_filter($schedule, fn($s) => $s['status'] === 'scheduled'));
$in_progress_count = count(array_filter($schedule, fn($s) => $s['status'] === 'in_progress'));
$completed_count = count(array_filter($schedule, fn($s) => $s['status'] === 'completed'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Portal - TechBuild Pro</title>
    
    <!-- FullCalendar CSS -->
    <link href="https://unpkg.com/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/techbuild-pro/assets/css/style.css">
    
    <style>
        #calendar {
            min-height: 700px;
            background: white;
            margin-bottom: 20px;
        }
        .fc-toolbar {
            padding: 10px;
            background: var(--gray-50);
            border-radius: var(--radius);
        }
        .fc-event {
            cursor: pointer;
            border: none;
            font-weight: 500;
        }
        .calendar-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-badge {
            background: var(--gray-100);
            padding: 8px 12px;
            border-radius: var(--radius);
            text-align: center;
            font-size: 0.9em;
        }
        .stat-badge.urgent {
            background: var(--error-light);
            color: var(--error);
        }
        .stat-badge.today {
            background: var(--primary-light);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Technician Portal</h1>
        <p class="page-subtitle">Manage your repair schedule with full calendar features</p>
    </div>

    <div class="admin-container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Scheduled</h3>
                <p><?= $scheduled_count ?></p>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <p><?= $in_progress_count ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <p><?= $completed_count ?></p>
            </div>
        </div>

        <!-- 日历统计 -->
        <div class="calendar-stats">
            <div class="stat-badge today" id="todayCount">Today: 0</div>
            <div class="stat-badge" id="weekCount">This Week: 0</div>
            <div class="stat-badge urgent" id="urgentCount">Urgent: 0</div>
            <div class="stat-badge" id="totalCount">Total: <?= count($schedule) ?></div>
        </div>

        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 1rem;">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">My Schedule Calendar</h3>
            <div id="calendar"></div>
        </div>

        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem;">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">Calendar Features</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <h4>🔄 Drag & Drop</h4>
                    <p>Drag events to reschedule them</p>
                </div>
                <div>
                    <h4>📅 Multiple Views</h4>
                    <p>Month, Week, Day, and List views</p>
                </div>
                <div>
                    <h4>⏰ Click to Create</h4>
                    <p>Click on empty slots to create new appointments</p>
                </div>
                <div>
                    <h4>📊 Real-time Stats</h4>
                    <p>See today's and this week's tasks</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://unpkg.com/fullcalendar@5.11.3/main.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Starting FullCalendar with full features...');
        
        var calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            console.error('❌ Calendar container element not found!');
            return;
        }
        
        if (typeof FullCalendar === 'undefined') {
            console.error('❌ FullCalendar is not defined');
            return;
        }
        
        // 准备事件数据
        var events = [
            <?php 
            $events_js = [];
            foreach ($schedule as $event) {
                $events_js[] = "{
                    id: '" . $event['id'] . "',
                    title: '" . addslashes($event['title']) . "',
                    start: '" . $event['start_datetime'] . "',
                    end: '" . $event['end_datetime'] . "',
                    color: '" . ($event['status'] === 'completed' ? '#10B981' : ($event['status'] === 'in_progress' ? '#F59E0B' : '#3B82F6')) . "',
                    extendedProps: {
                        description: '" . addslashes($event['description'] ?? '') . "',
                        status: '" . $event['status'] . "',
                        technician_id: '" . $_SESSION['user_id'] . "'
                    },
                    editable: true, // 允许拖拽编辑
                    durationEditable: true // 允许调整时长
                }";
            }
            echo implode(",\n            ", $events_js);
            ?>
        ];
        
        // 计算统计数据
        function updateCalendarStats() {
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            
            const todayEvents = events.filter(event => {
                const eventDate = new Date(event.start);
                return eventDate.toDateString() === today.toDateString();
            });
            
            const weekEvents = events.filter(event => {
                const eventDate = new Date(event.start);
                return eventDate >= startOfWeek;
            });
            
            const urgentEvents = events.filter(event => 
                event.extendedProps.status === 'in_progress'
            );
            
            document.getElementById('todayCount').textContent = `Today: ${todayEvents.length}`;
            document.getElementById('weekCount').textContent = `This Week: ${weekEvents.length}`;
            document.getElementById('urgentCount').textContent = `Urgent: ${urgentEvents.length}`;
        }
        
        try {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                // 基础配置
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                initialDate: new Date(),
                navLinks: true, // 可点击的周/日导航
                editable: true, // 允许事件拖拽
                selectable: true, // 允许选择时间段
                dayMaxEvents: true, // 限制每天显示的事件数量
                nowIndicator: true, // 显示当前时间线
                
                // 事件配置
                events: events,
                eventColor: '#3788d8',
                
                // 点击日期创建新事件
                dateClick: function(info) {
                    const title = prompt('Enter new repair appointment title:');
                    if (title) {
                        calendar.addEvent({
                            title: title,
                            start: info.date,
                            end: new Date(info.date.getTime() + 60 * 60 * 1000), // 1小时默认时长
                            color: '#3B82F6',
                            extendedProps: {
                                status: 'scheduled',
                                description: 'New appointment',
                                technician_id: <?= $_SESSION['user_id'] ?>
                            }
                        });
                        showTemporaryMessage('New appointment created! (Demo)');
                    }
                },
                
                // 选择时间段创建事件
                select: function(info) {
                    const title = prompt('Enter new repair appointment title:');
                    if (title) {
                        calendar.addEvent({
                            title: title,
                            start: info.start,
                            end: info.end,
                            allDay: info.allDay,
                            color: '#3B82F6',
                            extendedProps: {
                                status: 'scheduled',
                                description: 'New appointment',
                                technician_id: <?= $_SESSION['user_id'] ?>
                            }
                        });
                        showTemporaryMessage('New appointment created! (Demo)');
                    }
                    calendar.unselect();
                },
                
                // 事件拖拽完成
                eventDrop: function(info) {
                    console.log('Event moved:', info.event.title, 'to', info.event.start);
                    showTemporaryMessage('Appointment rescheduled! (Demo)');
                    // 在实际系统中，这里应该更新数据库
                    // updateEventInDatabase(info.event);
                },
                
                // 事件调整大小完成
                eventResize: function(info) {
                    console.log('Event resized:', info.event.title, info.event.start, 'to', info.event.end);
                    showTemporaryMessage('Appointment duration updated! (Demo)');
                    // 在实际系统中，这里应该更新数据库
                    // updateEventInDatabase(info.event);
                },
                
                // 事件点击
                eventClick: function(info) {
                    const event = info.event;
                    const description = event.extendedProps.description || 'No description';
                    const status = event.extendedProps.status || 'scheduled';
                    
                    const action = confirm(
                        'Repair: ' + event.title + '\n' +
                        'Time: ' + event.start.toLocaleString() + '\n' +
                        'Status: ' + status + '\n' +
                        'Description: ' + description + '\n\n' +
                        'Click OK to edit, Cancel to view details.'
                    );
                    
                    if (action) {
                        const newTitle = prompt('Edit appointment title:', event.title);
                        if (newTitle) {
                            event.setProp('title', newTitle);
                            showTemporaryMessage('Appointment updated! (Demo)');
                        }
                    }
                },
                
                // 事件鼠标悬停
                eventMouseEnter: function(info) {
                    info.el.style.opacity = '0.8';
                    info.el.style.transform = 'scale(1.02)';
                    info.el.style.transition = 'all 0.2s ease';
                },
                
                eventMouseLeave: function(info) {
                    info.el.style.opacity = '1';
                    info.el.style.transform = 'scale(1)';
                },
                
                // 视图改变
                viewDidMount: function(info) {
                    console.log('View changed to:', info.view.type);
                    updateCalendarStats();
                },
                
                // 事件渲染完成
                eventDidMount: function(info) {
                    // 添加自定义样式或内容
                    if (info.event.extendedProps.status === 'urgent') {
                        info.el.style.fontWeight = 'bold';
                    }
                }
            });
            
            calendar.render();
            console.log('✅ FullCalendar rendered with full features');
            updateCalendarStats();
            
        } catch (error) {
            console.error('❌ Calendar initialization error:', error);
        }
    });

    function showTemporaryMessage(message) {
        // 创建临时消息显示
        const messageEl = document.createElement('div');
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 12px 20px;
            border-radius: var(--radius);
            z-index: 1000;
            box-shadow: var(--shadow-md);
        `;
        messageEl.textContent = message;
        document.body.appendChild(messageEl);
        
        setTimeout(() => {
            messageEl.remove();
        }, 3000);
    }

    function addDemoEvent() {
        const calendar = document.calendar;
        if (calendar) {
            calendar.addEvent({
                title: 'Demo Repair Appointment',
                start: new Date(),
                end: new Date(new Date().getTime() + 60 * 60 * 1000),
                color: '#8B5CF6',
                extendedProps: {
                    status: 'scheduled',
                    description: 'This is a demo appointment'
                }
            });
            showTemporaryMessage('Demo appointment added!');
        }
    }
    
    // 保存日历实例到全局变量以便其他函数访问
    document.calendar = calendar;
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>