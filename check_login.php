<?php
session_start();
header('Content-Type: application/json');

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

echo json_encode($response);
?>