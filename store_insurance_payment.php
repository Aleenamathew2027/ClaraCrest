<?php
// Include database connection
require_once 'dbconnect.php';

// Start session to get user_id
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get JSON data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Log the received data for debugging
error_log("Received data: " . print_r($data, true));

// Validate required data
if (!isset($data['payment_id']) || !isset($data['plan_name']) || !isset($data['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment data']);
    exit();
}

try {
    // Generate a unique insurance policy number
    $policy_number = 'INS-' . uniqid() . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
    
    // Prepare the query to insert insurance payment data
    $stmt = $conn->prepare("INSERT INTO insurance_payment 
                            (user_id, plan_type, plan_amount, expiry_date, payment_method, 
                             transaction_id, payment_status, insurance_policy_number, is_renewal) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_id = $_SESSION['user_id'];
    $plan_name = $data['plan_name'];
    $amount = $data['amount'];
    $expiry_date = $data['expiry_date'];
    $payment_method = $data['payment_method'];
    $transaction_id = $data['payment_id'];
    $status = $data['status'];
    $is_renewal = $data['is_renewal'];
    
    // Corrected bind_param - 9 parameters needed
    $stmt->bind_param("isdsssssi", 
        $user_id, 
        $plan_name, 
        $amount, 
        $expiry_date, 
        $payment_method, 
        $transaction_id, 
        $status,
        $policy_number,
        $is_renewal
    );
    
    // Execute the query
    if ($stmt->execute()) {
        $insurance_id = $conn->insert_id;
        // Success - payment recorded
        echo json_encode([
            'success' => true, 
            'policy_number' => $policy_number,
            'insurance_id' => $insurance_id
        ]);
    } else {
        // Error inserting payment record
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error processing payment: ' . $e->getMessage()]);
}
?> 