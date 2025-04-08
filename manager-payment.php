<?php
require_once 'dbconnect.php';

class PaymentManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all payments with customer details
    public function getAllPayments() {
        // Check if payments table exists
        $check_table = $this->db->query("SHOW TABLES LIKE 'payments'");
        if ($check_table->num_rows == 0) {
            // Table doesn't exist, return empty array
            return [];
        }
        
        $sql = "SELECT p.*, u.username, u.email 
                FROM payments p 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.payment_date DESC";
        $result = $this->db->query($sql);
        
        if ($result === false) {
            // Query failed, return empty array
            return [];
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get payment details by ID
    public function getPaymentById($id) {
        $sql = "SELECT p.*, u.username, u.email 
                FROM payments p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Get payments by date range
    public function getPaymentsByDateRange($start_date, $end_date) {
        $sql = "SELECT p.*, u.username, u.email 
                FROM payments p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.payment_date BETWEEN ? AND ? 
                ORDER BY p.payment_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get total revenue
    public function getTotalRevenue() {
        // Check if payments table exists
        $check_table = $this->db->query("SHOW TABLES LIKE 'payments'");
        if ($check_table->num_rows == 0) {
            return 0;
        }
        
        $sql = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
        $result = $this->db->query($sql);
        
        if ($result === false) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
}

// Initialize PaymentManager with database connection
$paymentManager = new PaymentManager($conn);

// Handle date range filter
$payments = [];
$filtered = false;
$start_date = '';
$end_date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $payments = $paymentManager->getPaymentsByDateRange($start_date, $end_date);
    $filtered = true;
} else {
    $payments = $paymentManager->getAllPayments();
}

// Get total revenue
$totalRevenue = $paymentManager->getTotalRevenue();

// Check if manager is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

// Fetch all payments with user details
$query = "SELECT p.*, u.fullname, u.email 
          FROM payments p
          JOIN users u ON p.user_id = u.id
          ORDER BY p.created_at DESC";
          
$result = $conn->query($query);

// Check for query execution errors
if (!$result) {
    $error_message = "Error fetching payments: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management</title>
    <!-- Include your CSS files here -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Include DataTables CSS for better table display -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            background: #212529;
        }
        
        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
        }
        
        #sidebar ul p {
            color: #fff;
            padding: 10px;
        }
        
        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #fff;
            text-decoration: none;
        }
        
        #sidebar ul li a:hover {
            color: #343a40;
            background: #fff;
        }
        
        #sidebar ul li.active > a {
            color: #fff;
            background: #007bff;
        }
        
        #content {
            width: calc(100% - 250px);
            padding: 40px 40px 40px 60px;
            min-height: 100vh;
            transition: all 0.3s;
            position: absolute;
            top: 0;
            right: 0;
            margin-left: 270px;
        }
        
        .container-fluid {
            padding-left: 50px;
            padding-right: 30px;
        }
        
        .card {
            margin-top: 20px;
            width: 100%;
            overflow-x: auto;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .table-responsive {
            padding: 0;
        }
        
        .badge {
            padding: 0.5em 0.75em;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                min-width: 80px;
                max-width: 80px;
                text-align: center;
            }
            
            #sidebar .sidebar-header h3 {
                display: none;
            }
            
            #sidebar ul li a {
                padding: 10px 5px;
                font-size: 0.85em;
            }
            
            #sidebar ul li a i {
                margin-right: 0;
                display: block;
                font-size: 1.8em;
                margin-bottom: 5px;
            }
            
            #content {
                width: calc(100% - 80px);
                margin-left: 90px;
                padding: 20px 20px 20px 30px;
            }
            
            .container-fluid {
                padding-left: 25px;
                padding-right: 15px;
            }
        }
        
        /* Add these styles to your existing styles */
        .table {
            font-size: 0.95rem;
            width: 100% !important;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 8px;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .badge {
            font-size: 0.85rem;
            padding: 0.5em 0.8em;
            border-radius: 4px;
        }
        
        .btn-info {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        /* Custom column widths */
        #paymentsTable th:nth-child(1), /* ID */
        #paymentsTable td:nth-child(1) {
            width: 5%;
        }
        
        #paymentsTable th:nth-child(2), /* Order ID */
        #paymentsTable td:nth-child(2) {
            width: 15%;
        }
        
        #paymentsTable th:nth-child(3), /* Customer */
        #paymentsTable td:nth-child(3) {
            width: 15%;
        }
        
        #paymentsTable th:nth-child(4), /* Email */
        #paymentsTable td:nth-child(4) {
            width: 15%;
        }
        
        #paymentsTable th:nth-child(5), /* Payment ID */
        #paymentsTable td:nth-child(5) {
            width: 15%;
        }
        
        #paymentsTable th:nth-child(6), /* Amount */
        #paymentsTable td:nth-child(6) {
            width: 10%;
        }
        
        #paymentsTable th:nth-child(7), /* Status */
        #paymentsTable td:nth-child(7) {
            width: 10%;
        }
        
        #paymentsTable th:nth-child(8), /* Date */
        #paymentsTable td:nth-child(8) {
            width: 10%;
        }
        
        #paymentsTable th:nth-child(9), /* Actions */
        #paymentsTable td:nth-child(9) {
            width: 5%;
            text-align: center;
        }
        
        /* DataTables customization */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            margin: 0 2px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff;
            border-color: #007bff;
            color: white !important;
        }
        
        /* Ensure page title and description are clearly visible */
        .row.mb-4 {
            margin-top: 20px;
            margin-bottom: 30px !important;
        }
        
        .row.mb-4 h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .row.mb-4 p.text-muted {
            font-size: 16px;
            margin-bottom: 0;
        }
        
        /* Improve table header visibility */
        #paymentsTable thead th {
            font-weight: 600;
            color: #333;
            padding: 12px 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>ClaraCrest Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="manager-dashboard.php">
                        <i class="fas fa-tachometer-alt sidebar-icon"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="managerreguser.php">
                        <i class="fas fa-users sidebar-icon"></i> Users
                    </a>
                </li>
                <li>
                    <a href="manage-categories.php">
                        <i class="fas fa-tags sidebar-icon"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="add-products.php">
                        <i class="fas fa-plus sidebar-icon"></i> Add Product
                    </a>
                </li>
                <li>
                    <a href="products.php">
                        <i class="fas fa-box sidebar-icon"></i> Products
                    </a>
                </li>
                <li>
                    <a href="manager-vieworders.php">
                        <i class="fas fa-shopping-cart sidebar-icon"></i> View Orders
                    </a>
                </li>
                <li>
                    <a href="manager-review.php">
                        <i class="fas fa-star sidebar-icon"></i> Reviews
                    </a>
                </li>
                <li>
                    <a href="manager-payment.php">
                        <i class="fas fa-credit-card sidebar-icon"></i> Payment Details
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt sidebar-icon"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2><i class="fas fa-credit-card me-2"></i> Payment Management</h2>
                        <p class="text-muted">View and manage all payment transactions</p>
                    </div>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <span>All Payments</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="paymentsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Payment ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($result) && $result->num_rows > 0): ?>
                                        <?php while ($payment = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $payment['id']; ?></td>
                                                <td><?php echo $payment['order_id']; ?></td>
                                                <td><?php echo htmlspecialchars($payment['fullname']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['email']); ?></td>
                                                <td><?php echo $payment['razorpay_payment_id'] ?? 'N/A'; ?></td>
                                                <td class="text-end">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $payment['status'] === 'completed' ? 'bg-success' : 
                                                            ($payment['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <a href="view-payment-details.php?payment_id=<?php echo $payment['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View Payment Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No payment records found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#paymentsTable').DataTable({
                order: [[7, 'desc']], // Sort by date column (index 7) in descending order
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                responsive: true,
                language: {
                    search: "Search payments:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ payments",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
    </script>
</body>
</html> 