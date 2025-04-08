<?php
// Start session
session_start();

// Include database connection
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For guest users, use temporary session ID
    if (!isset($_SESSION['temp_user_id'])) {
        $_SESSION['temp_user_id'] = uniqid('guest_');
    }
    $is_guest = true;
    $session_id = $_SESSION['temp_user_id'];
} else {
    $is_guest = false;
    $user_id = $_SESSION['user_id'];
}

// Check if cart_id and quantity are provided
if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$cart_id = $_POST['cart_id'];
$quantity = intval($_POST['quantity']);

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'error' => 'Quantity must be at least 1']);
    exit;
}

// Verify cart item belongs to user
if ($is_guest) {
    $check_cart = $conn->prepare("SELECT id FROM temp_cart_items WHERE id = ? AND session_id = ?");
    $check_cart->bind_param("is", $cart_id, $session_id);
} else {
    $check_cart = $conn->prepare("SELECT id FROM cart_items WHERE id = ? AND user_id = ?");
    $check_cart->bind_param("ii", $cart_id, $user_id);
}

$check_cart->execute();
$result = $check_cart->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Cart item not found']);
    exit;
}

// Update quantity
if ($is_guest) {
    $update_cart = $conn->prepare("UPDATE temp_cart_items SET quantity = ? WHERE id = ?");
} else {
    $update_cart = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
}

$update_cart->bind_param("ii", $quantity, $cart_id);

if ($update_cart->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update cart']);
}
?> 