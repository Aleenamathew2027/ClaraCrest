<?php
// Include database connection
require_once 'dbconnect.php';

// Start session
session_start();
if(!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Check if insurance ID is provided
if(!isset($_GET['id']) || !isset($_GET['policy'])) {
    header('Location: insurance.php');
    exit();
}

$insurance_id = $_GET['id'];
$policy_number = $_GET['policy'];
$user_id = $_SESSION['user_id'];

// Get insurance details
$stmt = $conn->prepare("SELECT ip.*, u.fullname, u.email, u.phone, u.address, u.city, u.state, u.pincode 
                        FROM insurance_payment ip
                        JOIN users u ON ip.user_id = u.id
                        WHERE ip.id = ? AND ip.user_id = ? AND ip.insurance_policy_number = ?");
$stmt->bind_param("iis", $insurance_id, $user_id, $policy_number);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    // Insurance not found or doesn't belong to user
    header('Location: insurance.php');
    exit();
}

$insurance = $result->fetch_assoc();
$stmt->close();

// Format dates for display
$payment_date = new DateTime($insurance['payment_date']);
$expiry_date = new DateTime($insurance['expiry_date']);

// Include header
include 'header.php';
?>

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
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .header {
        text-align: center;
        margin-bottom: 30px;
        background-color: var(--primary-color);
        padding: 30px 20px;
        color: white;
        border-radius: 10px 10px 0 0;
    }
    
    .confirmation-box {
        background-color: var(--card-bg);
        border-radius: 0 0 10px 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 40px;
    }
    
    .success-icon {
        text-align: center;
        font-size: 60px;
        color: var(--primary-color);
        margin-bottom: 20px;
    }
    
    .confirmation-title {
        text-align: center;
        font-size: 24px;
        margin-bottom: 20px;
    }
    
    .policy-details {
        margin-bottom: 30px;
    }
    
    .policy-row {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        padding: 12px 0;
    }
    
    .policy-label {
        flex: 1;
        font-weight: bold;
    }
    
    .policy-value {
        flex: 2;
    }
    
    .actions {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }
    
    .btn {
        display: inline-block;
        background-color: var(--primary-color);
        color: white;
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        background-color: #1d5c3a;
    }
    
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class="container">
    <div class="header">
        <h1>Insurance Confirmation</h1>
    </div>
    
    <div class="confirmation-box">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2 class="confirmation-title">Your Insurance Plan is Active!</h2>
        
        <div class="policy-details">
            <div class="policy-row">
                <div class="policy-label">Policy Number:</div>
                <div class="policy-value"><?php echo htmlspecialchars($insurance['insurance_policy_number']); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Customer Name:</div>
                <div class="policy-value"><?php echo htmlspecialchars($insurance['fullname']); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Plan:</div>
                <div class="policy-value"><?php echo htmlspecialchars($insurance['plan_type']); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Amount Paid:</div>
                <div class="policy-value">₹<?php 
                    // Fix the amount display - show as integer if no decimal part
                    $amount = $insurance['plan_amount'];
                    if (floor($amount) == $amount) {
                        echo number_format($amount, 0); // Show without decimals
                    } else {
                        echo number_format($amount, 2); // Show with decimals
                    }
                ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Payment Date:</div>
                <div class="policy-value"><?php echo $payment_date->format('d F Y'); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Valid Until:</div>
                <div class="policy-value"><?php echo $expiry_date->format('d F Y'); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Payment Method:</div>
                <div class="policy-value"><?php echo htmlspecialchars($insurance['payment_method']); ?></div>
            </div>
            
            <div class="policy-row">
                <div class="policy-label">Transaction ID:</div>
                <div class="policy-value"><?php echo htmlspecialchars($insurance['transaction_id']); ?></div>
            </div>
        </div>
        
        <div class="actions no-print">
            <a href="generate_pdf.php?id=<?php echo $insurance_id; ?>&policy=<?php echo urlencode($policy_number); ?>" class="btn">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </div>
    </div>
    
    <div class="text-center no-print">
        <p>For any questions, please contact our customer support.</p>
        <p><a href="index.php">Return to Homepage</a></p>
    </div>
</div>
</body>
</html> 