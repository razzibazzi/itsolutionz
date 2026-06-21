<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'set_status') {
            $status_id = $_POST['status_id'] ?? '';
            $note = $_POST['note'] ?? '';
            
            if (!empty($status_id)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO tblfacultystatus (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $status_id, $note]);
                    $message = 'Status updated successfully!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating status: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'Please select a status';
                $message_type = 'warning';
            }
        } elseif ($_POST['action'] == 'mark_attendance') {
            try {
                $stmt = $conn->prepare("INSERT INTO tblattendance (attend_userId, attend_dateTime) VALUES (?, NOW())");
                $stmt->execute([$user_id]);
                $message = 'Attendance marked successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error marking attendance: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Get status options
try {
    $stmt = $conn->prepare("SELECT facStatMaster_id, facStatMaster_name FROM tblfacultystatusmaster ORDER BY facStatMaster_id");
    $stmt->execute();
    $status_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_options = [];
}

// Get current status
try {
    $stmt = $conn->prepare("
        SELECT fs.facStatus_statusMId, fsm.facStatMaster_name, fs.facStatus_note, fs.facStatus_dateTime
        FROM tblfacultystatus fs
        JOIN tblfacultystatusmaster fsm ON fs.facStatus_statusMId = fsm.facStatMaster_id
        WHERE fs.facStatus_userId = ?
        ORDER BY fs.facStatus_dateTime DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $current_status = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_status = null;
}

// Get today's attendance
try {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT attend_dateTime
        FROM tblattendance
        WHERE attend_userId = ? AND DATE(attend_dateTime) = ?
        ORDER BY attend_dateTime DESC
    ");
    $stmt->execute([$user_id, $today]);
    $today_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $today_attendance = [];
}

// Get recent status history
try {
    $stmt = $conn->prepare("
        SELECT fs.facStatus_statusMId, fsm.facStatMaster_name, fs.facStatus_note, fs.facStatus_dateTime
        FROM tblfacultystatus fs
        JOIN tblfacultystatusmaster fsm ON fs.facStatus_statusMId = fsm.facStatMaster_id
        WHERE fs.facStatus_userId = ?
        ORDER BY fs.facStatus_dateTime DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_history = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Attendance - Faculty Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .status-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .attendance-card {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
        }
        .status-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .status-option:hover {
            border-color: #28a745;
            background-color: #f8f9fa;
        }
        .status-option.selected {
            border-color: #28a745;
            background-color: #e6f4ea;
        }
        .attendance-item {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .status-item {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
    </style>
    <link href="theme.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Faculty Attendance System
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="schedule.php">
                    <i class="fas fa-calendar-alt me-2"></i>Schedule
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-user-check me-2"></i>Set Attendance</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Current Status -->
            <div class="col-md-6 mb-4">
                <div class="card status-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Current Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($current_status): ?>
                            <div class="text-center">
                                <h3><?php echo htmlspecialchars($current_status['facStatMaster_name']); ?></h3>
                                <?php if ($current_status['facStatus_note']): ?>
                                    <p class="mb-2"><?php echo htmlspecialchars($current_status['facStatus_note']); ?></p>
                                <?php endif; ?>
                                <small>Since: <?php echo date('M j, Y g:i A', strtotime($current_status['facStatus_dateTime'])); ?></small>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <p>No status set yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance -->
            <div class="col-md-6 mb-4">
                <div class="card attendance-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>Today's Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($today_attendance)): ?>
                            <div class="text-center">
                                <h3><?php echo count($today_attendance); ?> Time(s)</h3>
                                <p>Last marked: <?php echo date('g:i A', strtotime($today_attendance[0]['attend_dateTime'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-clock fa-2x mb-3"></i>
                                <p>No attendance marked today</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Set Status -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Update Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="set_status">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Status:</label>
                                <?php foreach ($status_options as $status): ?>
                                    <div class="status-option" onclick="selectStatus(<?php echo $status['facStatMaster_id']; ?>, '<?php echo htmlspecialchars($status['facStatMaster_name']); ?>')">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status_id" value="<?php echo $status['facStatMaster_id']; ?>" id="status_<?php echo $status['facStatMaster_id']; ?>">
                                            <label class="form-check-label" for="status_<?php echo $status['facStatMaster_id']; ?>">
                                                <strong><?php echo htmlspecialchars($status['facStatMaster_name']); ?></strong>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Note (Optional):</label>
                                <textarea class="form-control" id="note" name="note" rows="3" placeholder="Add a note about your status..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-save me-2"></i>Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mark Attendance -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Mark Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-check fa-3x text-primary mb-3"></i>
                            <p>Click the button below to mark your attendance for today.</p>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_attendance">
                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Mark your attendance now?')">
                                <i class="fas fa-check me-2"></i>Mark Attendance
                            </button>
                        </form>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Current time: <span id="currentTime"></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Status History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($status_history)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No status history available.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($status_history as $status): ?>
                                <div class="status-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <strong><?php echo htmlspecialchars($status['facStatMaster_name']); ?></strong>
                                            <?php if ($status['facStatus_note']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($status['facStatus_note']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($status['facStatus_dateTime'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Status selection
        function selectStatus(statusId, statusName) {
            // Remove selected class from all options
            document.querySelectorAll('.status-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('status_' + statusId).checked = true;
        }
        
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString();
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Form validation
        document.querySelector('form[action=""]').addEventListener('submit', function(e) {
            const statusId = document.querySelector('input[name="status_id"]:checked');
            if (!statusId) {
                e.preventDefault();
                alert('Please select a status');
                return false;
            }
        });
    </script>
</body>
</html>