<?php
// Include database connection
require_once 'dbconnect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=insurance.php');
    exit();
}

// Check if insurance_id is provided
if (!isset($_GET['insurance_id'])) {
    header('Location: insurance.php');
    exit();
}

$insurance_id = $_GET['insurance_id'];

// Get insurance details
$stmt = $conn->prepare("SELECT * FROM insurance_payment WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $insurance_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insurance record not found or doesn't belong to current user
    header('Location: insurance.php');
    exit();
}

$insurance = $result->fetch_assoc();
$stmt->close();

// Get user details for the payment form
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Convert amount to paise/cents (Razorpay expects amount in smallest currency unit)
$amount_in_paise = $insurance['plan_amount'] * 100;

// Handle payment success callback
if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_order_id']) && isset($_POST['razorpay_signature'])) {
    // Verify the payment signature - in a production environment, you'd verify this server-side
    // For demo purposes, we'll assume the payment is valid
    
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $razorpay_order_id = $_POST['razorpay_order_id'];
    
    // Update insurance payment record
    $stmt = $conn->prepare("UPDATE insurance_payment SET 
                          payment_status = 'completed', 
                          transaction_id = ? 
                          WHERE id = ?");
    $stmt->bind_param("si", $razorpay_payment_id, $insurance_id);
    
    if ($stmt->execute()) {
        // Redirect to success page
        header('Location: insurance-success.php?policy=' . $insurance['insurance_policy_number']);
        exit();
    } else {
        $payment_error = "Error updating payment status: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Insurance Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2E8B57;
            --dark-bg: #ffffff;
            --card-bg: #f8f9fa;
            --text-light: #333333;
            --text-muted: #666666;
            --border-color: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .payment-details {
            margin-bottom: 30px;
        }
        
        .payment-details p {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
        }
        
        .payment-summary {
            background-color: #f8f8f8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #1a6b3d;
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .payment-options {
            margin-top: 20px;
        }
        
        .razorpay-logo {
            display: block;
            margin: 20px auto;
            max-width: 120px;
        }
        
        .secure-badge {
            text-align: center;
            color: var(--text-muted);
            margin-top: 20px;
            font-size: 14px;
        }
        
        .secure-badge i {
            color: var(--primary-color);
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Complete Your Insurance Purchase</h1>
        
        <?php if (isset($payment_error)): ?>
            <div class="error"><?php echo $payment_error; ?></div>
        <?php endif; ?>
        
        <div class="payment-details">
            <h2>Plan Details</h2>
            <p><strong>Plan:</strong> <?php echo htmlspecialchars($insurance['plan_type']); ?></p>
            <p><strong>Policy Number:</strong> <?php echo htmlspecialchars($insurance['insurance_policy_number']); ?></p>
            <p><strong>Coverage Period:</strong> 1 Year (<?php echo date('F j, Y', strtotime($insurance['expiry_date'])); ?>)</p>
        </div>
        
        <div class="payment-summary">
            <h2>Payment Summary</h2>
            <div class="summary-row">
                <span>Plan Cost:</span>
                <span>$<?php echo htmlspecialchars($insurance['plan_amount']); ?></span>
            </div>
            <div class="summary-row">
                <span>Taxes & Fees:</span>
                <span>$0.00</span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span>$<?php echo htmlspecialchars($insurance['plan_amount']); ?></span>
            </div>
        </div>
        
        <div class="payment-options">
            <img src="https://razorpay.com/assets/razorpay-logo.svg" alt="Razorpay" class="razorpay-logo">
            <button id="rzp-button1" class="btn">Pay with Razorpay</button>
            <div class="secure-badge">
                <i class="fas fa-lock"></i> Secure Payment Powered by Razorpay
            </div>
        </div>
    </div>
    
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    var options = {
        "key": "rzp_test_qpOnn9moti7rqv", // Enter the Key ID generated from the Dashboard
        "amount": "<?php echo $amount_in_paise; ?>", // Amount in paisa
        "currency": "INR",
        "name": "Clara Crest Watches",
        "description": "<?php echo htmlspecialchars($insurance['plan_type']); ?> Insurance",
        "image": "image/logo.png", // Adjust the path to your logo
        "order_id": "", // This is generated by your backend after order creation
        "handler": function (response){
            // Handle the success callback
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            document.getElementById('payment-form').submit();
        },
        "prefill": {
            "name": "<?php echo htmlspecialchars($user['fullname']); ?>",
            "email": "<?php echo htmlspecialchars($user['email']); ?>",
            "contact": "<?php echo htmlspecialchars($user['phone']); ?>"
        },
        "notes": {
            "address": "Clara Crest Watches",
            "insurance_id": "<?php echo $insurance_id; ?>",
            "policy_number": "<?php echo htmlspecialchars($insurance['insurance_policy_number']); ?>"
        },
        "theme": {
            "color": "#2E8B57"
        }
    };
    var rzp1 = new Razorpay(options);
    document.getElementById('rzp-button1').onclick = function(e){
        rzp1.open();
        e.preventDefault();
    }
    </script>
    
    <!-- Hidden form to submit payment details -->
    <form id="payment-form" action="" method="POST" style="display: none;">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    </form>
</body>
</html> 