<?php
require_once 'dbconnect.php';

// Check if manager is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

// Check if payment_id is provided
if (!isset($_GET['payment_id'])) {
    header("Location: manager-payment.php");
    exit();
}

$payment_id = $_GET['payment_id'];

// Fetch payment details with related information
$query = "SELECT p.*, u.fullname, u.email, u.phone, u.address,
                 o.order_id, o.quantity, o.price_at_time,
                 pr.name as product_name, pr.description as product_description,
                 COALESCE(pi.image_url, pr.image_url, 'assets/img/no-image.jpg') as product_image
          FROM payments p
          JOIN users u ON p.user_id = u.id
          JOIN orders o ON p.order_id = o.order_id
          JOIN products pr ON o.product_id = pr.id
          LEFT JOIN product_images pi ON pr.id = pi.product_id AND pi.is_primary = 1
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manager-payment.php");
    exit();
}

$payment_details = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
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
            z-index: 1000;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #212529;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
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

        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        #content {
            width: calc(100% - 250px);
            padding: 40px 40px 40px 80px;
            min-height: 100vh;
            transition: all 0.3s;
            position: absolute;
            top: 0;
            right: 0;
            margin-left: 290px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1.25rem;
        }

        .product-image {
            max-width: 200px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }

        .detail-row {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .back-button {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            #sidebar {
                min-width: 80px;
                max-width: 80px;
            }

            #content {
                width: calc(100% - 80px);
                margin-left: 90px;
                padding: 20px;
            }
        }

        /* Add these new styles */
        .container-fluid {
            padding-left: 50px;
            padding-right: 30px;
        }

        .back-button {
            margin-bottom: 30px;
            margin-top: 20px;
        }

        .card {
            margin-bottom: 30px;
        }

        .card-header {
            padding: 1.25rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .detail-row {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            margin-bottom: 5px;
            color: #495057;
            font-weight: 600;
        }

        .product-image {
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar {
                min-width: 80px;
                max-width: 80px;
            }

            #sidebar .sidebar-header h3 {
                display: none;
            }

            #sidebar ul li a {
                padding: 10px 5px;
                text-align: center;
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
                padding: 20px;
            }

            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        /* Page Header Styling */
        .page-header {
            background-color: #fff;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .header-content {
            position: relative;
            padding: 0 20px;
        }

        .header-content h1 {
            font-size: 24px;
            color: #2c3e50;
            margin: 20px 0 10px 0;
        }

        .header-content p {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 0;
        }

        /* Back Button Styling */
        .back-btn {
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: #3498db;
            border: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background-color: #2980b9;
            transform: translateX(-3px);
        }

        .back-btn i {
            margin-right: 8px;
        }

        /* Card Improvements */
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 30px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .card-header {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
        }

        .card-header h5 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        /* Detail Row Improvements */
        .detail-row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7f8c8d;
            margin-bottom: 8px;
        }

        .detail-content {
            font-size: 16px;
            color: #2c3e50;
        }

        /* Badge Improvements */
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Product Image Improvements */
        .product-image-container {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }

        .product-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            .page-header {
                margin-bottom: 20px;
            }

            .header-content h1 {
                font-size: 20px;
            }

            .card-header h5 {
                font-size: 16px;
            }

            .detail-label {
                font-size: 13px;
            }

            .detail-content {
                font-size: 15px;
            }
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
                <div class="page-header">
                    <div class="header-content">
                        <button onclick="window.location.href='manager-payment.php'" class="btn btn-primary back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Payments
                        </button>
                        <h1><i class="fas fa-info-circle"></i> Payment Details</h1>
                        <p class="text-muted">View detailed information about the payment</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="detail-row">
                                    <div class="detail-label">Payment ID</div>
                                    <div class="detail-content">
                                        <?php echo htmlspecialchars($payment_details['razorpay_payment_id'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Order ID</div>
                                    <div><?php echo htmlspecialchars($payment_details['order_id']); ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Amount</div>
                                    <div>₹<?php echo number_format($payment_details['amount'], 2); ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Status</div>
                                    <div>
                                        <span class="badge <?php 
                                            echo $payment_details['status'] === 'completed' ? 'bg-success' : 
                                                ($payment_details['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                        ?>">
                                            <?php echo ucfirst($payment_details['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Date</div>
                                    <div><?php echo date('d M Y, h:i A', strtotime($payment_details['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="detail-row">
                                    <div class="detail-label">Name</div>
                                    <div><?php echo htmlspecialchars($payment_details['fullname']); ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email</div>
                                    <div><?php echo htmlspecialchars($payment_details['email']); ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone</div>
                                    <div><?php echo htmlspecialchars($payment_details['phone']); ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Shipping Address</div>
                                    <div><?php echo nl2br(htmlspecialchars($payment_details['address'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Product Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="product-image-container">
                                            <img src="<?php echo htmlspecialchars($payment_details['product_image']); ?>" 
                                                 alt="Product Image" 
                                                 class="product-image img-fluid"
                                                 onerror="this.src='assets/img/no-image.jpg';">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="detail-row">
                                            <div class="detail-label">Product Name</div>
                                            <div><?php echo htmlspecialchars($payment_details['product_name']); ?></div>
                                        </div>
                                        <div class="detail-row">
                                            <div class="detail-label">Description</div>
                                            <div><?php echo htmlspecialchars($payment_details['product_description']); ?></div>
                                        </div>
                                        <div class="detail-row">
                                            <div class="detail-label">Quantity</div>
                                            <div><?php echo $payment_details['quantity']; ?></div>
                                        </div>
                                        <div class="detail-row">
                                            <div class="detail-label">Price at Time of Purchase</div>
                                            <div>₹<?php echo number_format($payment_details['price_at_time'], 2); ?></div>
                                        </div>
                                        <div class="detail-row">
                                            <div class="detail-label">Total Amount</div>
                                            <div>₹<?php echo number_format($payment_details['price_at_time'] * $payment_details['quantity'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
