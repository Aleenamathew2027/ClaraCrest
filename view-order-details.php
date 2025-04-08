<?php
session_start();
require_once 'dbconnect.php';

// Check if manager is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header("Location: manager-vieworders.php");
    exit();
}

// Create database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch order details with customer and product information
$sql = "SELECT o.*, u.fullname, u.email, u.phone, u.address,
               p.name as product_name, p.brand, p.description,
               COALESCE(pi.image_url, p.image_url) as product_image
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE o.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: manager-vieworders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add the same sidebar styles as manager-vieworders.php */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            padding-top: 20px;
            z-index: 1000;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin: 5px 0;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar .logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .container-fluid {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }

        .product-image {
            max-width: 200px;
            border-radius: 8px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            display: inline-block;
            font-weight: 600;
        }

        .detail-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar h2 {
                display: none;
            }

            .sidebar a span {
                display: none;
            }

            .sidebar a {
                padding: 15px;
                justify-content: center;
            }

            .sidebar i {
                margin: 0;
            }

            .container-fluid {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Manager Dashboard</h2>
        <ul>
            <li>
                <a href="manager-dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="managerreguser.php">
                    <i class="fas fa-user-plus"></i>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="manage-categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="add-products.php">
                    <i class="fas fa-plus"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="manager-vieworders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>View Orders</span>
                </a>
            </li>
            <li>
                <a href="manager-review.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li>
                <a href="manager-payment.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment Details</span>
                </a>
            </li>
            <li class="logout">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="container-fluid">
        <div class="mb-4">
            <a href="manager-vieworders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <h1 class="mb-4">Order Details #<?php echo $order['order_id']; ?></h1>

        <div class="row">
            <div class="col-md-6">
                <div class="detail-card">
                    <h3>Order Information</h3>
                    <hr>
                    <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('F d, Y H:i:s', strtotime($order['created_at'])); ?></p>
                    <!-- <p><strong>Status:</strong> 
                        <span class="status-badge bg-<?php 
                            echo match($order['order_status']) {
                                'pending' => 'warning',
                                'processed' => 'success',
                                'processing' => 'info',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </p> -->
                    <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['price_at_time'] * $order['quantity'], 2); ?></p>
                </div>

                <div class="detail-card">
                    <h3>Customer Information</h3>
                    <hr>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['fullname']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-card">
                    <h3>Product Information</h3>
                    <hr>
                    <div class="text-center mb-3">
                        <img src="<?php echo htmlspecialchars($order['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                             class="product-image"
                             onerror="this.src='assets/img/no-image.jpg';">
                    </div>
                    <p><strong>Product Name:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($order['brand']); ?></p>
                    <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                    <p><strong>Price per Unit:</strong> ₹<?php echo number_format($order['price_at_time'], 2); ?></p>
                    <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($order['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
