// Ticket Management JavaScript
// Uses SortableJS for drag & drop and SweetAlert2 for modals

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
    populateTicketColumns();
    initializeEventListeners();
});

// Initialize SortableJS for drag and drop functionality
function initializeSortable() {
    // Get all technician elements
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
    
    // Initialize for unassigned column
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
}


// Populate tickets into their respective columns
function populateTicketColumns() {
    console.log('✅ 拖放区域已由PHP生成，无需JavaScript处理');
    // 什么都不做，保持PHP生成的内容
}

// Initialize event listeners for buttons and interactions
function initializeEventListeners() {
    // Add any additional event listeners here
    console.log('Event listeners initialized');
}

// Assign ticket to technician via AJAX
function assignTicketToTechnician(ticketId, technicianId) {
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
        // Refresh the page to show updated assignments
        setTimeout(() => {
            location.reload();
        }, 1500);
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to assign ticket. Please try again.');
    });
}

// Show ticket details in a modal
function showTicketDetails(ticketId) {
    Swal.fire({
        title: `Ticket #${ticketId} Details`,
        html: `
            <div style="text-align: left;">
                <p><strong>Status:</strong> <span class="status-badge status-new">Loading...</span></p>
                <p><strong>Priority:</strong> <span class="priority-badge priority-medium">Loading...</span></p>
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

// Quick assign functionality with SweetAlert2 - 修复版本
function quickAssign(ticketId) {
    console.log('🔧 quickAssign 被调用');
    console.log('📊 window.techniciansData:', window.techniciansData);
    console.log('🏷️ ticketId:', ticketId);
    
    // 检查数据是否就绪
    if (typeof window.techniciansData === 'undefined') {
        console.error('❌ techniciansData 未定义');
        showErrorMessage('Technician data is not loaded yet. Please wait and try again.');
        return false;
    }
    
    if (!window.techniciansData || window.techniciansData.length === 0) {
        console.error('❌ 没有技术员数据', window.techniciansData);
        showErrorMessage('No technicians available for assignment.');
        return false;
    }
    
    console.log('✅ 技术员数据正常，数量:', window.techniciansData.length);
    
    // 生成选项
    let technicianOptions = '<option value="">Unassigned</option>';
    window.techniciansData.forEach(tech => {
        console.log(`添加技术员: ${tech.name} (ID: ${tech.id})`);
        technicianOptions += `<option value="${tech.id}">${tech.name}</option>`;
    });
    
    console.log('生成的选项HTML:', technicianOptions);
    
    // 显示模态框
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
        didOpen: () => {
            console.log('✅ 模态框已打开');
            const select = document.getElementById('technicianSelect');
            console.log('实际选项数量:', select.options.length);
        },
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

// 显示分配模态框的辅助函数
function showQuickAssignModal(ticketId, technicianOptions) {
    return Swal.fire({
        title: 'Assign Technician',
        html: `
            <div style="text-align: left; margin-bottom: 1rem;">
                <label for="technicianSelect" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Select Technician:
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
            if (technicianId === "") {
                return assignTicketToTechnician(ticketId, null);
            } else {
                return assignTicketToTechnician(ticketId, parseInt(technicianId));
            }
        }
    });
}

// Generate technician options for select dropdown - 动态版本
function getTechnicianOptions() {
    let options = '<option value="">Unassigned</option>';
    techniciansData.forEach(tech => {
        options += `<option value="${tech.id}">${tech.name}</option>`;
    });
    return options;
}

// Show success message
function showSuccessMessage(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Show error message
function showErrorMessage(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Update ticket counts (for future real-time updates)
function updateTicketCounts() {
    // This would make an API call to get updated counts
    console.log('Updating ticket counts...');
}

// Export functions for global access (if needed)
window.ticketManagement = {
    initializeSortable,
    populateTicketColumns,
    assignTicketToTechnician,
    showTicketDetails,
    quickAssign
};