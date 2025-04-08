<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    // Update order status
    $update_order = $conn->prepare("UPDATE orders SET order_status = 'cancelled' 
                                  WHERE order_id = ? AND user_id = ? 
                                  AND order_status NOT IN ('delivered', 'cancelled')");
    $update_order->bind_param("si", $order_id, $user_id);
    
    if (!$update_order->execute()) {
        throw new Exception("Error cancelling order");
    }

    // Restore product stock
    $restore_stock = $conn->prepare("UPDATE products p 
                                   JOIN orders o ON p.id = o.product_id 
                                   SET p.stock_quantity = p.stock_quantity + o.quantity 
                                   WHERE o.order_id = ? AND o.user_id = ?");
    $restore_stock->bind_param("si", $order_id, $user_id);
    
    if (!$restore_stock->execute()) {
        throw new Exception("Error restoring stock");
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>