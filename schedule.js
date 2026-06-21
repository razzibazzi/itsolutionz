// Schedule functionality
document.addEventListener('DOMContentLoaded', function() {
    loadSchedule();
});

function loadSchedule() {
    const formData = new FormData();
    formData.append('operation', 'get_schedule');
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySchedule(data.data);
        } else {
            console.error('Error loading schedule:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function displaySchedule(scheduleData) {
    const container = document.getElementById('scheduleContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (scheduleData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Schedule Found</h4>
                <p class="text-muted">Click "Add Schedule" to create your first schedule entry.</p>
            </div>
        `;
        return;
    }
    
    // Group schedule by day
    const scheduleByDay = {};
    const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    scheduleData.forEach(schedule => {
        if (!scheduleByDay[schedule.sched_day]) {
            scheduleByDay[schedule.sched_day] = [];
        }
        scheduleByDay[schedule.sched_day].push(schedule);
    });
    
    // Display schedule by day
    daysOrder.forEach(day => {
        if (scheduleByDay[day]) {
            const dayContainer = document.createElement('div');
            dayContainer.className = 'mb-4';
            
            const dayHeader = document.createElement('div');
            dayHeader.className = 'day-header';
            dayHeader.innerHTML = `<h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>${day}</h5>`;
            
            dayContainer.appendChild(dayHeader);
            
            scheduleByDay[day].forEach(schedule => {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = 'schedule-item';
                scheduleItem.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-1">
                                <i class="fas fa-clock me-2"></i>
                                ${formatTime(schedule.sched_startTime)} - ${formatTime(schedule.sched_endTime)}
                            </h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editSchedule(${schedule.sched_id}, '${schedule.sched_day}', '${schedule.sched_startTime}', '${schedule.sched_endTime}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteSchedule(${schedule.sched_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                dayContainer.appendChild(scheduleItem);
            });
            
            container.appendChild(dayContainer);
        }
    });
}

function openAddModal() {
    const modalTitle = document.getElementById('modalTitle');
    const scheduleForm = document.getElementById('scheduleForm');
    const scheduleId = document.getElementById('scheduleId');
    
    if (modalTitle) modalTitle.textContent = 'Add Schedule';
    if (scheduleForm) scheduleForm.reset();
    if (scheduleId) scheduleId.value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}

function editSchedule(scheduleId, day, startTime, endTime) {
    const modalTitle = document.getElementById('modalTitle');
    const scheduleIdInput = document.getElementById('scheduleId');
    const daySelect = document.getElementById('day');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');
    
    if (modalTitle) modalTitle.textContent = 'Edit Schedule';
    if (scheduleIdInput) scheduleIdInput.value = scheduleId;
    if (daySelect) daySelect.value = day;
    if (startTimeInput) startTimeInput.value = startTime;
    if (endTimeInput) endTimeInput.value = endTime;
    
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}

function saveSchedule() {
    const form = document.getElementById('scheduleForm');
    if (!form) return;
    
    const formData = new FormData(form);
    
    // Validate form
    if (!formData.get('day') || !formData.get('startTime') || !formData.get('endTime')) {
        alert('Please fill in all fields');
        return;
    }
    
    // Validate time
    const startTime = formData.get('startTime');
    const endTime = formData.get('endTime');
    if (startTime >= endTime) {
        alert('End time must be after start time');
        return;
    }
    
    // Add action
    formData.append('operation', 'save_schedule');
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
            if (modal) modal.hide();
            
            // Reload schedule
            loadSchedule();
            
            // Show success message
            showAlert('Schedule saved successfully!', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function deleteSchedule(scheduleId) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        const formData = new FormData();
        formData.append('operation', 'delete_schedule');
        formData.append('scheduleId', scheduleId);
        
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadSchedule();
                showAlert('Schedule deleted successfully!', 'success');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

function logout() {
    const formData = new FormData();
    formData.append('operation', 'logout');

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .finally(() => {
        window.location.href = 'login.html';
    });
}

