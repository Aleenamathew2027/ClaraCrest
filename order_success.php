<?php
session_start();
require_once 'dbconnect.php';
include 'header.php';

if (!isset($_GET['order_id'])) {
    header("Location: collection.php");
    exit;
}

$order_id = $_GET['order_id'];

if (isset($_GET['order_id'])) {
    $debug_query = "SELECT * FROM orders WHERE order_id = ?";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->bind_param("s", $_GET['order_id']);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    
    error_log("Order debug information:");
    while ($row = $debug_result->fetch_assoc()) {
        error_log(print_r($row, true));
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="text-green-600 mb-4">
            <i class="fas fa-check-circle text-6xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Payment Successful!</h1>
        <p class="text-gray-600 mb-4">Your order has been placed successfully.</p>
        <p class="text-gray-600 mb-6">Order ID: <?php echo htmlspecialchars($order_id); ?></p>
        <div class="flex justify-center gap-4">
            <a href="collection.php" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                Continue Shopping
            </a>
            <a href="orders.php?from_order=<?php echo htmlspecialchars($order_id); ?>" class="border-2 border-green-600 text-green-600 px-6 py-2 rounded-lg hover:bg-green-600 hover:text-white transition-colors">
                View Orders
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 