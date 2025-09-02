<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$school_id = $_SESSION['school_id'];

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

// Get today's schedule
try {
    $today = date('l'); // Get current day name
    $stmt = $conn->prepare("
        SELECT sched_startTime, sched_endTime
        FROM tblfacultyschedule
        WHERE sched_userId = ? AND sched_day = ?
    ");
    $stmt->execute([$user_id, $today]);
    $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $today_schedule = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Faculty Attendance System</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .module-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .module-card:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 20px;
        }
        .schedule-item {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>Faculty Attendance System
            </a>
            <div class="navbar-nav ms-auto">
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card status-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h4>
                                <p class="mb-0">School ID: <?php echo htmlspecialchars($school_id); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($current_status): ?>
                                    <span class="status-badge bg-light text-dark">
                                        <i class="fas fa-circle me-2" style="color: #28a745;"></i>
                                        <?php echo htmlspecialchars($current_status['facStatMaster_name']); ?>
                                    </span>
                                    <br>
                                    <small class="text-light">Since: <?php echo date('M j, Y g:i A', strtotime($current_status['facStatus_dateTime'])); ?></small>
                                <?php else: ?>
                                    <span class="status-badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No Status Set
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Schedule -->
        <?php if (!empty($today_schedule)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>Today's Schedule (<?php echo $today; ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($today_schedule as $schedule): ?>
                            <div class="schedule-item">
                                <i class="fas fa-clock me-2"></i>
                                <?php echo date('g:i A', strtotime($schedule['sched_startTime'])); ?> - 
                                <?php echo date('g:i A', strtotime($schedule['sched_endTime'])); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modules -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card module-card h-100" onclick="location.href='schedule.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Set Schedule</h5>
                        <p class="card-text">Manage your weekly class schedule and working hours.</p>
                        <button class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Go to Schedule
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card module-card h-100" onclick="location.href='attendance.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Set Attendance</h5>
                        <p class="card-text">Mark your attendance and update your current status.</p>
                        <button class="btn btn-success">
                            <i class="fas fa-arrow-right me-2"></i>Go to Attendance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh status every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>