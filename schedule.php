<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $day = $_POST['day'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            
            if (!empty($day) && !empty($start_time) && !empty($end_time)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO tblfacultyschedule (sched_day, sched_startTime, sched_endTime, sched_userId) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$day, $start_time, $end_time, $user_id]);
                    $message = 'Schedule added successfully!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error adding schedule: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'Please fill in all fields';
                $message_type = 'warning';
            }
        } elseif ($_POST['action'] == 'delete') {
            $schedule_id = $_POST['schedule_id'] ?? '';
            if (!empty($schedule_id)) {
                try {
                    $stmt = $conn->prepare("DELETE FROM tblfacultyschedule WHERE sched_id = ? AND sched_userId = ?");
                    $stmt->execute([$schedule_id, $user_id]);
                    $message = 'Schedule deleted successfully!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error deleting schedule: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Get current schedules
try {
    $stmt = $conn->prepare("
        SELECT sched_id, sched_day, sched_startTime, sched_endTime
        FROM tblfacultyschedule
        WHERE sched_userId = ?
        ORDER BY 
            CASE sched_day
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END,
            sched_startTime
    ");
    $stmt->execute([$user_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schedules = [];
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Schedule - Faculty Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .schedule-item {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .schedule-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        .day-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
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
                <a class="nav-link" href="attendance.php">
                    <i class="fas fa-user-check me-2"></i>Attendance
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-alt me-2"></i>Set Schedule</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus me-2"></i>Add Schedule
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Current Schedules -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Current Schedules
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedules)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No schedules set yet. Add your first schedule above.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $current_day = '';
                            foreach ($schedules as $schedule):
                                if ($current_day != $schedule['sched_day']):
                                    if ($current_day != '') echo '</div>';
                                    echo '<div class="mb-4">';
                                    echo '<h6 class="day-badge d-inline-block mb-3">' . $schedule['sched_day'] . '</h6>';
                                    $current_day = $schedule['sched_day'];
                                endif;
                            ?>
                                <div class="schedule-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-clock me-3 text-primary"></i>
                                                <div>
                                                    <strong><?php echo date('g:i A', strtotime($schedule['sched_startTime'])); ?> - <?php echo date('g:i A', strtotime($schedule['sched_endTime'])); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Duration: <?php 
                                                        $start = new DateTime($schedule['sched_startTime']);
                                                        $end = new DateTime($schedule['sched_endTime']);
                                                        $diff = $start->diff($end);
                                                        echo $diff->format('%h hours %i minutes');
                                                        ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this schedule?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['sched_id']; ?>">
                                                <button type="submit" class="btn-delete">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($current_day != '') echo '</div>'; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Schedule
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="day" class="form-label">Day of Week</label>
                            <select class="form-select" id="day" name="day" required>
                                <option value="">Select a day</option>
                                <?php foreach ($days as $day): ?>
                                    <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Make sure the end time is after the start time.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Time validation
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = this.value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                document.getElementById('end_time').value = '';
            }
        });
        
        document.getElementById('end_time').addEventListener('change', function() {
            const startTime = document.getElementById('start_time').value;
            const endTime = this.value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                this.value = '';
            }
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>