// Attendance functionality
let selectedStatusId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentStatus();
    loadStatusOptions();
    loadAttendanceHistory();
    checkTodayAttendance();
    
    // Set up event listeners
    const statusForm = document.getElementById('statusForm');
    if (statusForm) {
        statusForm.addEventListener('submit', handleStatusSubmit);
    }
});

function loadCurrentStatus() {
    const formData = new FormData();
    formData.append('operation', 'get_dashboard_data');
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCurrentStatus(data.data.current_status);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function displayCurrentStatus(currentStatus) {
    const container = document.getElementById('currentStatusDisplay');
    if (!container) return;
    
    if (currentStatus) {
        container.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-2">${currentStatus.facStatMaster_name}</h4>
                    ${currentStatus.facStatus_note ? `<p class="mb-2"><strong>Note:</strong> ${currentStatus.facStatus_note}</p>` : ''}
                    <p class="mb-0"><small>Since: ${formatDateTime(currentStatus.facStatus_dateTime)}</small></p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6 p-3">
                        <i class="fas fa-circle me-2" style="color: ${getStatusColor(currentStatus.facStatus_statusMId)};"></i>
                        Active
                    </span>
                </div>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h5>No Status Set</h5>
                <p class="mb-0">Please update your status below</p>
            </div>
        `;
    }
}

function getStatusColor(statusId) {
    switch(statusId) {
        case 1: return '#2d9f5f'; // In office
        case 2: return '#6b8e6b'; // Out
        case 3: return '#20c997'; // In class
        default: return '#6c757d';
    }
}

function loadStatusOptions() {
    // Hardcoded status options based on the database
    const statusOptions = [
        { id: 1, name: 'In office', icon: 'fas fa-building', color: '#2d9f5f' },
        { id: 2, name: 'Out', icon: 'fas fa-sign-out-alt', color: '#6b8e6b' },
        { id: 3, name: 'In class', icon: 'fas fa-chalkboard-teacher', color: '#20c997' }
    ];
    
    const container = document.getElementById('statusOptions');
    if (!container) return;
    
    container.innerHTML = '';
    
    statusOptions.forEach(status => {
        const option = document.createElement('div');
        option.className = 'status-option border';
        option.onclick = () => selectStatus(status.id);
        option.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="${status.icon} fa-2x me-3" style="color: ${status.color};"></i>
                <div>
                    <h6 class="mb-1">${status.name}</h6>
                    <small class="text-muted">Click to select</small>
                </div>
            </div>
        `;
        container.appendChild(option);
    });
}

function selectStatus(statusId) {
    // Remove previous selection
    document.querySelectorAll('.status-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selection to clicked option
    event.currentTarget.classList.add('selected');
    
    // Store selected status
    selectedStatusId = statusId;
}

function checkTodayAttendance() {
    const formData = new FormData();
    formData.append('operation', 'get_attendance_history');
    formData.append('limit', 1);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            const today = new Date().toDateString();
            const lastAttendance = new Date(data.data[0].attend_dateTime).toDateString();
            
            if (today === lastAttendance) {
                // Attendance already marked today
                const attendanceBtn = document.getElementById('attendanceBtn');
                const attendanceStatus = document.getElementById('attendanceStatus');
                
                if (attendanceBtn) {
                    attendanceBtn.disabled = true;
                    attendanceBtn.innerHTML = '<i class="fas fa-check me-2"></i>Attendance Marked';
                }
                
                if (attendanceStatus) {
                    attendanceStatus.innerHTML = `
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>Attendance marked for today
                        </div>
                    `;
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function markAttendance() {
    const formData = new FormData();
    formData.append('operation', 'set_attendance');
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const attendanceBtn = document.getElementById('attendanceBtn');
            const attendanceStatus = document.getElementById('attendanceStatus');
            
            if (attendanceBtn) {
                attendanceBtn.disabled = true;
                attendanceBtn.innerHTML = '<i class="fas fa-check me-2"></i>Attendance Marked';
            }
            
            if (attendanceStatus) {
                attendanceStatus.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>${data.message}
                    </div>
                `;
            }
            
            // Reload attendance history
            loadAttendanceHistory();
        } else {
            const attendanceStatus = document.getElementById('attendanceStatus');
            if (attendanceStatus) {
                attendanceStatus.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>${data.message}
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const attendanceStatus = document.getElementById('attendanceStatus');
        if (attendanceStatus) {
            attendanceStatus.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-times-circle me-2"></i>An error occurred. Please try again.
                </div>
            `;
        }
    });
}

function loadAttendanceHistory() {
    const formData = new FormData();
    formData.append('operation', 'get_attendance_history');
    formData.append('limit', 30);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAttendanceHistory(data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function displayAttendanceHistory(attendanceData) {
    const container = document.getElementById('attendanceHistory');
    if (!container) return;
    
    if (attendanceData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                <h5 class="text-muted">No Attendance Records</h5>
                <p class="text-muted">Your attendance history will appear here</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    attendanceData.forEach(record => {
        const item = document.createElement('div');
        item.className = 'attendance-history-item';
        item.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">
                        <i class="fas fa-calendar-check me-2"></i>
                        ${formatDate(record.attend_dateTime)}
                    </h6>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        ${formatTime(record.attend_dateTime)}
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-success">Present</span>
                </div>
            </div>
        `;
        container.appendChild(item);
    });
}

// Handle status form submission
function handleStatusSubmit(e) {
    e.preventDefault();
    
    if (!selectedStatusId) {
        alert('Please select a status');
        return;
    }
    
    const note = document.getElementById('statusNote');
    const noteValue = note ? note.value.trim() : '';
    
    const formData = new FormData();
    formData.append('operation', 'set_status');
    formData.append('statusId', selectedStatusId);
    formData.append('note', noteValue);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form
            const statusForm = document.getElementById('statusForm');
            const statusNote = document.getElementById('statusNote');
            
            if (statusForm) statusForm.reset();
            if (statusNote) statusNote.value = '';
            
            document.querySelectorAll('.status-option').forEach(option => {
                option.classList.remove('selected');
            });
            selectedStatusId = null;
            
            // Reload current status
            loadCurrentStatus();
            
            // Show success message
            showAlert('Status updated successfully!', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Utility functions
function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    }) + ' ' + date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

function formatDate(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
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

