<?php
session_start();
require_once 'connection.php';

// If accessed via browser (GET), send users to the login page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

require_once 'headers.php';
header('Content-Type: application/json');

class Web_based
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function check_login()
    {
        $response = [
            'logged_in' => false,
            'user_name' => '',
            'school_id' => ''
        ];
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_name']) && isset($_SESSION['school_id'])) {
            $response['logged_in'] = true;
            $response['user_name'] = $_SESSION['user_name'];
            $response['school_id'] = $_SESSION['school_id'];
        }
        return json_encode($response);
    }

    public function login($json = null)
    {
        $schoolId = $_POST['schoolId'] ?? null;
        $password = $_POST['password'] ?? null;
        $redirect = isset($_POST['redirect']);
        $isAjax = isset($_POST['ajax']);
        if ($json) {
            $data = json_decode($json, true);
            $schoolId = $schoolId ?? ($data['schoolId'] ?? null);
            $password = $password ?? ($data['password'] ?? null);
        }
        if (empty($schoolId) || empty($password)) {
            return json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        }
        try {
            $stmt = $this->conn->prepare("SELECT user_id, user_firstName, user_lastName, user_schoolId, user_password FROM tbluser WHERE user_schoolId = ?");
            $stmt->execute([$schoolId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['user_password'] === $password) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_firstName'] . ' ' . $user['user_lastName'];
                $_SESSION['school_id'] = $user['user_schoolId'];
                if ($redirect && !$isAjax) {
                    header('Location: dashboard.html');
                    exit();
                }
                return json_encode(['success' => true]);
            }
            if ($redirect && !$isAjax) {
                header('Location: login.html?error=1');
                exit();
            }
            return json_encode(['success' => false, 'message' => 'Invalid School ID or Password']);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function logout()
    {
        session_destroy();
        return json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function get_dashboard_data()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        try {
            $stmt = $this->conn->prepare("
                SELECT fs.facStatus_statusMId, fsm.facStatMaster_name, fs.facStatus_note, fs.facStatus_dateTime
                FROM tblfacultystatus fs
                JOIN tblfacultystatusmaster fsm ON fs.facStatus_statusMId = fsm.facStatMaster_id
                WHERE fs.facStatus_userId = ?
                ORDER BY fs.facStatus_dateTime DESC
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $current_status = $stmt->fetch(PDO::FETCH_ASSOC);

            $today = date('l');
            $stmt = $this->conn->prepare("
                SELECT sched_startTime, sched_endTime
                FROM tblfacultyschedule
                WHERE sched_userId = ? AND sched_day = ?
            ");
            $stmt->execute([$user_id, $today]);
            $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode([
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
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function get_schedule()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        try {
            $stmt = $this->conn->prepare("
                SELECT sched_id, sched_day, sched_startTime, sched_endTime
                FROM tblfacultyschedule
                WHERE sched_userId = ?
                ORDER BY FIELD(sched_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), sched_startTime
            ");
            $stmt->execute([$user_id]);
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode(['success' => true, 'data' => $schedule]);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function save_schedule()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        $day = $_POST['day'] ?? '';
        $startTime = $_POST['startTime'] ?? '';
        $endTime = $_POST['endTime'] ?? '';
        $scheduleId = $_POST['scheduleId'] ?? null;
        if (empty($day) || empty($startTime) || empty($endTime)) {
            return json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        }
        try {
            if ($scheduleId) {
                $stmt = $this->conn->prepare("
                    UPDATE tblfacultyschedule 
                    SET sched_day = ?, sched_startTime = ?, sched_endTime = ?
                    WHERE sched_id = ? AND sched_userId = ?
                ");
                $stmt->execute([$day, $startTime, $endTime, $scheduleId, $user_id]);
            } else {
                $stmt = $this->conn->prepare("
                    INSERT INTO tblfacultyschedule (sched_day, sched_startTime, sched_endTime, sched_userId)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$day, $startTime, $endTime, $user_id]);
            }
            return json_encode(['success' => true, 'message' => 'Schedule saved successfully']);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function delete_schedule()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        $scheduleId = $_POST['scheduleId'] ?? '';
        if (empty($scheduleId)) {
            return json_encode(['success' => false, 'message' => 'Schedule ID is required']);
        }
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM tblfacultyschedule 
                WHERE sched_id = ? AND sched_userId = ?
            ");
            $stmt->execute([$scheduleId, $user_id]);
            if ($stmt->rowCount() > 0) {
                return json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
            }
            return json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function set_attendance()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        $current_time = date('Y-m-d H:i:s');
        try {
            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT attend_id FROM tblattendance 
                WHERE attend_userId = ? AND DATE(attend_dateTime) = ?
            ");
            $stmt->execute([$user_id, $today]);
            if ($stmt->fetch()) {
                return json_encode(['success' => false, 'message' => 'Attendance already marked for today']);
            }
            $stmt = $this->conn->prepare("
                INSERT INTO tblattendance (attend_userId, attend_dateTime)
                VALUES (?, ?)
            ");
            $stmt->execute([$user_id, $current_time]);
            return json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function get_attendance_history()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        $limit = $_POST['limit'] ?? 30;
        try {
            $stmt = $this->conn->prepare("
                SELECT attend_dateTime
                FROM tblattendance
                WHERE attend_userId = ?
                ORDER BY attend_dateTime DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode(['success' => true, 'data' => $attendance]);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function set_status()
    {
        if (!isset($_SESSION['user_id'])) {
            return json_encode(['success' => false, 'message' => 'Not logged in']);
        }
        $user_id = $_SESSION['user_id'];
        $statusId = $_POST['statusId'] ?? '';
        $note = $_POST['note'] ?? '';
        $current_time = date('Y-m-d H:i:s');
        if (empty($statusId)) {
            return json_encode(['success' => false, 'message' => 'Status is required']);
        }
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO tblfacultystatus (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $statusId, $note, $current_time]);
            return json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } catch (PDOException $e) {
            return json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

$webbased = new Web_based($conn);

// Support both styles: 'operation' with optional 'json', or legacy 'action'
$operation = $_POST['operation'] ?? ($_POST['action'] ?? '');
$json = $_POST['json'] ?? null;

switch ($operation) {
    case 'check_login':
        echo $webbased->check_login();
        break;
    case 'login':
        echo $webbased->login($json);
        break;
    case 'logout':
        echo $webbased->logout();
        break;
    case 'get_dashboard_data':
        echo $webbased->get_dashboard_data();
        break;
    case 'get_schedule':
        echo $webbased->get_schedule();
        break;
    case 'save_schedule':
        echo $webbased->save_schedule();
        break;
    case 'delete_schedule':
        echo $webbased->delete_schedule();
        break;
    case 'set_attendance':
        echo $webbased->set_attendance();
        break;
    case 'get_attendance_history':
        echo $webbased->get_attendance_history();
        break;
    case 'set_status':
        echo $webbased->set_status();
        break;
    default:
        // Fallback: attempt login for legacy forms
        if (isset($_POST['schoolId']) && isset($_POST['password'])) {
            echo $webbased->login();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid operation']);
        }
        break;
}


