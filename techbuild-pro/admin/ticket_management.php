<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

// 处理工单分配
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $technician_id = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    
    $stmt = $pdo->prepare("UPDATE repair_tickets SET assigned_technician_id = ?, status = ? WHERE id = ?");
    $new_status = $technician_id ? 'assigned' : 'new';
    $stmt->execute([$technician_id, $new_status, $ticket_id]);
    
    $_SESSION['success'] = "Ticket assigned successfully!";
    header("Location: ticket_management.php");
    exit;
}

// 获取所有工单
$stmt = $pdo->query("
    SELECT 
        rt.*,
        rb.device_type,
        rb.symptoms,
        uc.name as customer_name,
        ut.name as technician_name,
        p.name as service_name
    FROM repair_tickets rt
    JOIN repair_bookings rb ON rt.repair_booking_id = rb.id
    JOIN order_items oi ON rb.order_item_id = oi.id
    JOIN orders o ON oi.order_id = o.id
    JOIN users uc ON o.user_id = uc.id
    LEFT JOIN users ut ON rt.assigned_technician_id = ut.id
    JOIN products p ON oi.product_id = p.id
    ORDER BY 
        CASE rt.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        rt.created_at DESC
");
$tickets = $stmt->fetchAll();

$technicians = $pdo->query("
    SELECT DISTINCT id, name, email, role 
    FROM users 
    WHERE role = 'technician'
    ORDER BY name
")->fetchAll();

// 修复：正确分组工单数据
$assigned_tickets = [];
$unassigned_tickets = [];

foreach ($tickets as $ticket) {
    if (!empty($ticket['assigned_technician_id'])) {
        $tech_id = $ticket['assigned_technician_id'];
        if (!isset($assigned_tickets[$tech_id])) {
            $assigned_tickets[$tech_id] = [];
        }
        $assigned_tickets[$tech_id][] = $ticket;
    } else {
        $unassigned_tickets[] = $ticket;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - TechBuild Pro</title>
    
    <!-- 使用可靠的 CDN 源 -->
    <link rel="stylesheet" href="/techbuild-pro/assets/css/style.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://unpkg.com/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Work Order Management</h1>
        <p class="page-subtitle">Use the drag-and-drop feature to assign work orders to technicians</p>
    </div>

    <div class="admin-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total number of work orders</h3>
                <p><?= count($tickets) ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending assignment</h3>
                <p><?= count($unassigned_tickets) ?></p>
            </div>
            <div class="stat-card">
                <h3>Assigned</h3>
                <p><?= count($tickets) - count($unassigned_tickets) ?></p>
            </div>
            <div class="stat-card">
                <h3>Technician</h3>
                <p><?= count($technicians) ?></p>
            </div>
        </div>

        <!-- 拖放分配区域 -->
        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary); margin: 0;">Drag and drop assignment area</h3>
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                    <span style="display: inline-flex; align-items: center; margin-right: 1rem;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: var(--error); border-radius: 2px; margin-right: 0.5rem;"></span>
                        Urgent
                    </span>
                    <span style="display: inline-flex; align-items: center; margin-right: 1rem;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: var(--warning); border-radius: 2px; margin-right: 0.5rem;"></span>
                        High
                    </span>
                    <span style="display: inline-flex; align-items: center;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: var(--primary); border-radius: 2px; margin-right: 0.5rem;"></span>
                        Medium/Low
                    </span>
                </div>
            </div>

            <div id="techniciansList" class="technicians-grid">
                <!-- 技术人员列 -->
                <?php foreach ($technicians as $tech): ?>
                <?php 
                $tech_tickets = isset($assigned_tickets[$tech['id']]) ? $assigned_tickets[$tech['id']] : [];
                ?>
                <div class="technician-card" data-technician-id="<?= $tech['id'] ?>">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: var(--gray-800);"><?= htmlspecialchars($tech['name']) ?></h4>
                        <span class="ticket-count"><?= count($tech_tickets) ?></span>
                    </div>
                    <p class="technician-email" style="margin: 0 0 1rem 0; font-size: 0.875rem;"><?= htmlspecialchars($tech['email']) ?></p>
                    
                    <div class="ticket-list" id="tech-<?= $tech['id'] ?>" style="min-height: 120px;">
                        <?php if (!empty($tech_tickets)): ?>
                            <?php foreach ($tech_tickets as $ticket): ?>
                            <div class="ticket-item priority-<?= $ticket['priority'] ?>" 
                                 data-ticket-id="<?= $ticket['id'] ?>"
                                 style="cursor: move; background: white; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: var(--radius); border-left: 4px solid;
                                 <?php 
                                 $color = '';
                                 if ($ticket['priority'] === 'urgent') $color = 'var(--error)';
                                 elseif ($ticket['priority'] === 'high') $color = 'var(--warning)';
                                 else $color = 'var(--primary)';
                                 echo "border-left-color: $color;";
                                 ?>">
                                <div class="ticket-title" style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['service_name']) ?>
                                </div>
                                <div class="ticket-customer" style="font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.25rem;">
                                    Customer: <?= htmlspecialchars($ticket['customer_name']) ?>
                                </div>
                                <div class="ticket-priority">
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: <?= $color ?>; color: white; border-radius: var(--radius); font-size: 0.7rem; font-weight: 600;">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--gray-400); padding: 2rem; font-size: 0.875rem;">
                                <p style="margin: 0 0 0.5rem 0;">No assigned work orders</p>
                                <small>Drag and drop work orders here</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- 未分配工单列 -->
                <div class="technician-card unassigned" style="background: var(--error-light); border-color: var(--error);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: var(--gray-800);">Pending Assignment Work Order</h4>
                        <span class="ticket-count" style="background: var(--error); color: white;">
                            <?= count($unassigned_tickets) ?>
                        </span>
                    </div>
                    <p class="technician-email" style="margin: 0 0 1rem 0; font-size: 0.875rem; color: var(--error);">Technicians awaiting assignment</p>
                    
                    <div class="ticket-list" id="tech-unassigned" style="min-height: 120px; border-color: var(--error);">
                        <?php if (!empty($unassigned_tickets)): ?>
                            <?php foreach ($unassigned_tickets as $ticket): ?>
                            <div class="ticket-item priority-<?= $ticket['priority'] ?>" 
                                 data-ticket-id="<?= $ticket['id'] ?>"
                                 style="cursor: move; background: white; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: var(--radius); border-left: 4px solid;
                                 <?php 
                                 $color = '';
                                 if ($ticket['priority'] === 'urgent') $color = 'var(--error)';
                                 elseif ($ticket['priority'] === 'high') $color = 'var(--warning)';
                                 else $color = 'var(--primary)';
                                 echo "border-left-color: $color;";
                                 ?>">
                                <div class="ticket-title" style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['service_name']) ?>
                                </div>
                                <div class="ticket-customer" style="font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.25rem;">
                                    Customer: <?= htmlspecialchars($ticket['customer_name']) ?>
                                </div>
                                <div class="ticket-priority">
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: <?= $color ?>; color: white; border-radius: var(--radius); font-size: 0.7rem; font-weight: 600;">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--gray-400); padding: 2rem; font-size: 0.875rem;">
                                <p style="margin: 0 0 0.5rem 0;">No work orders pending assignment</p>
                                <small>All work orders have been assigned</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                    💡 Tip: Drag and drop the work order card to the technician area for assignment.
                </p>
            </div>
        </div>

        <!-- 所有工单表格 -->
        <div class="card-hover" style="background: white; padding: 2rem; border-radius: var(--radius-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary); margin: 0;">All Work Order List</h3>
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                    Total: <?= count($tickets) ?> work order
                </div>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>OrderID</th>
                            <th>Customer</th>
                            <th>Service Items</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assign personnel</th>
                            <th>Creation Time</th>
                            <th>Operation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong>#<?= $ticket['id'] ?></strong></td>
                            <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['service_name']) ?></td>
                            <td>
                                <span class="priority-badge priority-<?= $ticket['priority'] ?>">
                                    <?= ucfirst($ticket['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $ticket['status'] ?>">
                                    <?= ucfirst($ticket['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($ticket['technician_name']): ?>
                                    <span style="color: var(--success);"><?= htmlspecialchars($ticket['technician_name']) ?></span>
                                <?php else: ?>
                                    <em style="color: var(--gray-500);">Unassigned</em>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($ticket['created_at'])) ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="showTicketDetails(<?= $ticket['id'] ?>)" 
                                            class="btn btn-sm btn-outline">Details</button>
                                    <button onclick="testQuickAssignCall(<?= $ticket['id'] ?>)" 
                                            class="btn btn-sm btn-primary">Assign</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 加载工单管理所需库 - 使用可靠的 CDN -->
    <script src="https://unpkg.com/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://unpkg.com/sweetalert2@11/dist/sweetalert2.min.js"></script>
    
    <script>
    // 全局技术员数据
    window.techniciansData = <?= json_encode($technicians) ?>;
    window.ticketsData = <?= json_encode($tickets) ?>;
    console.log('✅ 技术员数据:', window.techniciansData);
    console.log('✅ 工单数据:', window.ticketsData);

    // 初始化拖放功能
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 初始化拖放功能...');
        
        // 检查 SortableJS 是否加载成功
        if (typeof Sortable === 'undefined') {
            console.error('❌ SortableJS 未加载');
            showDragDropFallback();
            return;
        }
        
        console.log('✅ SortableJS 加载成功');
        initializeSortable();
    });

    function initializeSortable() {
        try {
            // 获取所有技术人员元素
            const technicianElements = document.querySelectorAll('.technician-card:not(.unassigned)');
            
            technicianElements.forEach(card => {
                const technicianId = card.dataset.technicianId;
                const ticketList = document.getElementById(`tech-${technicianId}`);
                
                if (ticketList) {
                    new Sortable(ticketList, {
                        group: 'tickets',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onAdd: function(evt) {
                            const ticketId = evt.item.dataset.ticketId;
                            assignTicketToTechnician(ticketId, technicianId);
                        }
                    });
                }
            });
            
            // 初始化未分配列
            const unassignedList = document.getElementById('tech-unassigned');
            if (unassignedList) {
                new Sortable(unassignedList, {
                    group: 'tickets',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onAdd: function(evt) {
                        const ticketId = evt.item.dataset.ticketId;
                        assignTicketToTechnician(ticketId, null);
                    }
                });
            }
            
            console.log('✅ 拖放功能初始化完成');
            
        } catch (error) {
            console.error('❌ 拖放初始化错误:', error);
            showDragDropFallback();
        }
    }

    function showDragDropFallback() {
        const techniciansList = document.getElementById('techniciansList');
        if (techniciansList) {
            techniciansList.innerHTML += `
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; background: var(--warning-light); border-radius: var(--radius); margin-top: 20px;">
                    <h4 style="color: var(--warning); margin-bottom: 10px;">⚠️ Drag & Drop Unavailable</h4>
                    <p style="color: var(--gray-700); margin-bottom: 15px;">Please use the "Assign" buttons in the table below to assign tickets.</p>
                    <p style="color: var(--gray-600); font-size: 0.9em;">Drag and drop functionality requires JavaScript libraries that could not be loaded.</p>
                </div>
            `;
        }
    }

    // 分配工单到技术人员
    function assignTicketToTechnician(ticketId, technicianId) {
        console.log('分配工单:', ticketId, '给技术人员:', technicianId);
        
        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('technician_id', technicianId);
        formData.append('assign_ticket', '1');
        
        fetch('/techbuild-pro/admin/ticket_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(() => {
            showSuccessMessage('Ticket assigned successfully!');
            // 刷新页面显示更新后的分配
            setTimeout(() => {
                location.reload();
            }, 1500);
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Failed to assign ticket. Please try again.');
        });
    }

    // 显示工单详情
    function showTicketDetails(ticketId) {
        if (typeof Swal === 'undefined') {
            alert('Ticket #' + ticketId + ' Details\n\nThis would show detailed ticket information in a modal.');
            return;
        }
        
        Swal.fire({
            title: `Ticket #${ticketId} Details`,
            html: `
                <div style="text-align: left;">
                    <p><strong>Status:</strong> Loading...</p>
                    <p><strong>Priority:</strong> Loading...</p>
                    <p><strong>Assigned To:</strong> Loading...</p>
                    <p><strong>Description:</strong> Loading ticket details...</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Reassign Ticket',
            cancelButtonText: 'Close',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return quickAssign(ticketId);
            }
        });
    }

    // 快速分配功能
    function quickAssign(ticketId) {
        console.log('🔧 quickAssign 被调用, ticketId:', ticketId);
        
        if (typeof Swal === 'undefined') {
            const techName = prompt('Enter technician name for ticket #' + ticketId + ':');
            if (techName) {
                alert('Would assign ticket to: ' + techName + '\nIn live system, this would save to database.');
            }
            return false;
        }
        
        if (typeof window.techniciansData === 'undefined' || !window.techniciansData || window.techniciansData.length === 0) {
            showErrorMessage('No technicians available for assignment.');
            return false;
        }
        
        let technicianOptions = '<option value="">Unassigned</option>';
        window.techniciansData.forEach(tech => {
            technicianOptions += `<option value="${tech.id}">${tech.name} (${tech.email})</option>`;
        });
        
        return Swal.fire({
            title: 'Assign Technician',
            html: `
                <div style="text-align: left; margin-bottom: 1rem;">
                    <label for="technicianSelect" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Select Technician (${window.techniciansData.length} available):
                    </label>
                    <select id="technicianSelect" class="form-control">
                        ${technicianOptions}
                    </select>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Assign',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const technicianId = document.getElementById('technicianSelect').value;
                console.log('用户选择了技术员ID:', technicianId);
                return assignTicketToTechnician(ticketId, technicianId ? parseInt(technicianId) : null);
            }
        });
    }

    // 分配按钮调用函数
    function testQuickAssignCall(ticketId) {
        console.log('Assign按钮被点击，ticketId:', ticketId);
        if (typeof quickAssign === 'function') {
            return quickAssign(ticketId);
        } else {
            alert('分配功能加载中，请稍后重试');
        }
    }

    // 显示成功消息
    function showSuccessMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert('✓ ' + message);
        }
    }

    // 显示错误消息
    function showErrorMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert('✗ ' + message);
        }
    }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>