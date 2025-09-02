<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

// Get the action from POST data
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'get_dashboard_data':
        getDashboardData();
        break;
    case 'get_schedule':
        getSchedule();
        break;
    case 'save_schedule':
        saveSchedule();
        break;
    case 'delete_schedule':
        deleteSchedule();
        break;
    case 'set_attendance':
        setAttendance();
        break;
    case 'get_attendance_history':
        getAttendanceHistory();
        break;
    case 'set_status':
        setStatus();
        break;
    default:
        // If no action specified, try to handle login (for backward compatibility)
        if (isset($_POST['schoolId']) && isset($_POST['password'])) {
            handleLogin();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
}

function handleLogin() {
    global $conn;
    
    $schoolId = $_POST['schoolId'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($schoolId) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT user_id, user_firstName, user_lastName, user_schoolId, user_password FROM tbluser WHERE user_schoolId = ?");
        $stmt->execute([$schoolId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['user_password'] === $password) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_firstName'] . ' ' . $user['user_lastName'];
            $_SESSION['school_id'] = $user['user_schoolId'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['user_id'],
                    'name' => $user['user_firstName'] . ' ' . $user['user_lastName'],
                    'schoolId' => $user['user_schoolId']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid School ID or Password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function getDashboardData() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get current status
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
        
        // Get today's schedule
        $today = date('l'); // Get current day name
        $stmt = $conn->prepare("
            SELECT sched_startTime, sched_endTime
            FROM tblfacultyschedule
            WHERE sched_userId = ? AND sched_day = ?
        ");
        $stmt->execute([$user_id, $today]);
        $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $_SESSION['user_name'],
                    'schoolId' => $_SESSION['school_id']
                ],
                'current_status' => $current_status,
                'today_schedule' => $today_schedule,
                'today_day' => $today
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getSchedule() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("
            SELECT sched_id, sched_day, sched_startTime, sched_endTime
            FROM tblfacultyschedule
            WHERE sched_userId = ?
            ORDER BY FIELD(sched_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), sched_startTime
        ");
        $stmt->execute([$user_id]);
        $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $schedule]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function saveSchedule() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $day = $_POST['day'] ?? '';
    $startTime = $_POST['startTime'] ?? '';
    $endTime = $_POST['endTime'] ?? '';
    $scheduleId = $_POST['scheduleId'] ?? null;
    
    if (empty($day) || empty($startTime) || empty($endTime)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        return;
    }
    
    try {
        if ($scheduleId) {
            // Update existing schedule
            $stmt = $conn->prepare("
                UPDATE tblfacultyschedule 
                SET sched_day = ?, sched_startTime = ?, sched_endTime = ?
                WHERE sched_id = ? AND sched_userId = ?
            ");
            $stmt->execute([$day, $startTime, $endTime, $scheduleId, $user_id]);
        } else {
            // Insert new schedule
            $stmt = $conn->prepare("
                INSERT INTO tblfacultyschedule (sched_day, sched_startTime, sched_endTime, sched_userId)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$day, $startTime, $endTime, $user_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Schedule saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteSchedule() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $scheduleId = $_POST['scheduleId'] ?? '';
    
    if (empty($scheduleId)) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM tblfacultyschedule 
            WHERE sched_id = ? AND sched_userId = ?
        ");
        $stmt->execute([$scheduleId, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function setAttendance() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $current_time = date('Y-m-d H:i:s');
    
    try {
        // Check if attendance already exists for today
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            SELECT atttend_id FROM tblattendance 
            WHERE attend_userId = ? AND DATE(attend_dateTime) = ?
        ");
        $stmt->execute([$user_id, $today]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Attendance already marked for today']);
            return;
        }
        
        // Insert attendance record
        $stmt = $conn->prepare("
            INSERT INTO tblattendance (attend_userId, attend_dateTime)
            VALUES (?, ?)
        ");
        $stmt->execute([$user_id, $current_time]);
        
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getAttendanceHistory() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $limit = $_POST['limit'] ?? 30;
    
    try {
        $stmt = $conn->prepare("
            SELECT attend_dateTime
            FROM tblattendance
            WHERE attend_userId = ?
            ORDER BY attend_dateTime DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $attendance]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function setStatus() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $statusId = $_POST['statusId'] ?? '';
    $note = $_POST['note'] ?? '';
    $current_time = date('Y-m-d H:i:s');
    
    if (empty($statusId)) {
        echo json_encode(['success' => false, 'message' => 'Status is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO tblfacultystatus (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $statusId, $note, $current_time]);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>

