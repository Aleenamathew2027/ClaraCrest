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

// Check if cart_id is provided
if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'error' => 'Cart ID is required']);
    exit;
}

$cart_id = $_POST['cart_id'];

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

// Remove item from cart
if ($is_guest) {
    $remove_item = $conn->prepare("DELETE FROM temp_cart_items WHERE id = ?");
} else {
    $remove_item = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
}

$remove_item->bind_param("i", $cart_id);

if ($remove_item->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to remove item from cart']);
}
?> 