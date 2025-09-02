// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

function loadDashboardData() {
    const formData = new FormData();
    formData.append('action', 'get_dashboard_data');
    
    fetch('operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateDashboard(data.data);
        } else {
            console.error('Error loading dashboard data:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateDashboard(data) {
    // Update user information
    const welcomeName = document.getElementById('welcomeName');
    const schoolId = document.getElementById('schoolId');
    const userName = document.getElementById('userName');
    
    if (welcomeName) welcomeName.textContent = data.user.name;
    if (schoolId) schoolId.textContent = data.user.schoolId;
    if (userName) userName.textContent = data.user.name;
    
    // Update current status
    if (data.current_status) {
        const statusText = document.getElementById('statusText');
        const statusTime = document.getElementById('statusTime');
        if (statusText) statusText.textContent = data.current_status.facStatMaster_name;
        if (statusTime) statusTime.textContent = 'Since: ' + formatDateTime(data.current_status.facStatus_dateTime);
        
        // Update status badge color based on status
        const statusBadge = document.getElementById('currentStatus');
        if (statusBadge) {
            const statusIcon = statusBadge.querySelector('i');
            if (statusIcon) {
                switch(data.current_status.facStatus_statusMId) {
                    case 1: // In office
                        statusIcon.style.color = '#28a745';
                        break;
                    case 2: // Out
                        statusIcon.style.color = '#dc3545';
                        break;
                    case 3: // In class
                        statusIcon.style.color = '#007bff';
                        break;
                }
            }
        }
    } else {
        const statusText = document.getElementById('statusText');
        const statusTime = document.getElementById('statusTime');
        if (statusText) statusText.textContent = 'No Status Set';
        if (statusTime) statusTime.textContent = '';
    }
    
    // Update today's schedule
    if (data.today_schedule && data.today_schedule.length > 0) {
        const todayDay = document.getElementById('todayDay');
        const scheduleSection = document.getElementById('scheduleSection');
        if (todayDay) todayDay.textContent = data.today_day;
        if (scheduleSection) scheduleSection.style.display = 'block';
        
        const scheduleList = document.getElementById('scheduleList');
        if (scheduleList) {
            scheduleList.innerHTML = '';
            
            data.today_schedule.forEach(schedule => {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = 'schedule-item';
                scheduleItem.innerHTML = `
                    <i class="fas fa-clock me-2"></i>
                    ${formatTime(schedule.sched_startTime)} - ${formatTime(schedule.sched_endTime)}
                `;
                scheduleList.appendChild(scheduleItem);
            });
        }
    } else {
        const scheduleSection = document.getElementById('scheduleSection');
        if (scheduleSection) scheduleSection.style.display = 'none';
    }
}

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

function logout() {
    const formData = new FormData();
    formData.append('action', 'logout');
    
    fetch('operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'index.html';
        }
    })
    .catch(error => {
        console.error('Error during logout:', error);
        window.location.href = 'index.html';
    });
}

// Auto-refresh dashboard data every 30 seconds
setInterval(function() {
    loadDashboardData();
}, 30000);

