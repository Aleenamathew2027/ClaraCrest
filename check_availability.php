<?php
require_once 'dbconnect.php';

$response = array('status' => 'available');

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['status'] = 'exists';
    }
}

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['status'] = 'exists';
    }
}

if (isset($_POST['number'])) {
    $number = trim($_POST['number']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['status'] = 'exists';
    }
}

echo json_encode($response);
?> 