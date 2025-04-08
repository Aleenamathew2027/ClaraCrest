<?php
// Include database connection
require_once 'dbconnect.php';

// Ensure user is logged in
session_start();
if(!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Get user details from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Include header
include 'header.php';
?>

<style>
    :root {
        --primary-color: #2E8B57;  /* Sea Green */
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
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    header {
        text-align: center;
        margin-bottom: 50px;
        background-color: var(--primary-color);
        padding: 25px 20px;
        color: white;
        margin: -40px -20px 50px -20px;
    }
    
    h1 {
        font-size: 36px;
        margin-bottom: 15px;
        font-weight: 300;
        letter-spacing: 2px;
    }
    
    .header-desc {
        color: var(--text-muted);
        max-width: 700px;
        margin: 0 auto;
        font-size: 16px;
    }
    
    .cta-button {
        display: inline-block;
        background-color: var(--primary-color);
        color: white;
        padding: 12px 30px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: bold;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .cta-button:hover {
        background-color: #1a6b3d;
        transform: translateY(-2px);
    }
    
    .insurance-plans {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 40px;
    }
    
    .plan-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }
    
    .plan-card:hover {
        transform: translateY(-10px);
    }
    
    .plan-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
        background-color: var(--primary-color);
        color: white;
    }
    
    .plan-title {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    
    .plan-price {
        font-size: 18px;
        color: white;
        font-weight: bold;
        margin-top: 10px;
    }
    
    .plan-features {
        padding: 20px;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .feature-icon {
        color: var(--primary-color);
        margin-right: 10px;
        font-size: 16px;
    }
    
    .plan-footer {
        padding: 20px;
        text-align: center;
    }
    
    .plan-button {
        display: block;
        background-color: var(--primary-color);
        color: white;
        padding: 12px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        border: none;
        width: 100%;
        cursor: pointer;
    }
    
    .plan-button:hover {
        background-color: #1a6b3d;
        transform: translateY(-2px);
    }
    
    .renew-section {
        margin-top: 60px;
        text-align: center;
        background-color: #f8f9fa;
        padding: 40px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }
    
    .watch-image {
        max-width: 150px;
        display: block;
        margin: 0 auto 20px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .insurance-plans {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
    }
    
    @media (max-width: 576px) {
        .insurance-plans {
            grid-template-columns: 1fr;
        }
    }
    
    /* Add header-specific styles */
    .auth-button {
        transition: all 0.3s ease;
        padding: 0.5rem 1.5rem;
        border-radius: 9999px;
    }
    
    .cart-button, .heart-button {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #2d3748;
        border-radius: 0.5rem;
        width: 150px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }
    
    .dropdown-item {
        padding: 10px 15px;
        color: white;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: #4a5568;
    }
    
    .profile-container:hover .dropdown {
        display: block;
    }
</style>

<div class="container">
    <header>
        <h1>Watch Insurance Plans</h1>
        <p class="header-desc">Protect your investment with our comprehensive insurance coverage for luxury timepieces</p>
    </header>
    
    <div class="insurance-plans" id="plans">
        <!-- Basic Protection Plan -->
        <div class="plan-card">
            <div class="plan-header">
                <div class="plan-title">BASIC PROTECTION</div>
                <div class="plan-price">₹7,499 / year</div>
            </div>
            
            <div class="plan-features">
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Accidental Damage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Theft Protection</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>90-Day Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Up to $1,000 Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-times-circle"></i></span>
                    <span>No International Coverage</span>
                </div>
            </div>
            
            <div class="plan-footer">
                <button class="plan-button">BUY NOW</button>
            </div>
        </div>
        
        <!-- Classic Protection Plan -->
        <div class="plan-card">
            <div class="plan-header">
                <div class="plan-title">CLASSIC PROTECTION</div>
                <div class="plan-price">₹14,999 / year</div>
            </div>
            
            <div class="plan-features">
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Accidental Damage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Theft Protection</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>1-Year Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Up to $5,000 Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Basic Servicing</span>
                </div>
            </div>
            
            <div class="plan-footer">
                <button class="plan-button">BUY NOW</button>
            </div>
        </div>
        
        <!-- Premium Coverage Plan -->
        <div class="plan-card">
            <div class="plan-header">
                <div class="plan-title">PREMIUM COVERAGE</div>
                <div class="plan-price">₹24,999 / year</div>
            </div>
            
            <div class="plan-features">
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Full Damage Protection</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Theft & Loss Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>1-Year Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Up to $10,000 Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Annual Professional Service</span>
                </div>
            </div>
            
            <div class="plan-footer">
                <button class="plan-button">BUY NOW</button>
            </div>
        </div>
        
        <!-- Ultimate Assurance Plan -->
        <div class="plan-card">
            <div class="plan-header">
                <div class="plan-title">ULTIMATE ASSURANCE</div>
                <div class="plan-price">₹49,999 / year</div>
            </div>
            
            <div class="plan-features">
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Complete Protection</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Global Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>1-Year Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>Up to $50,000 Coverage</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="fas fa-check-circle"></i></span>
                    <span>VIP Support & Service</span>
                </div>
            </div>
            
            <div class="plan-footer">
                <button class="plan-button">BUY NOW</button>
            </div>
        </div>
    </div>
    
    <div class="renew-section">
        <h2>Already Have Insurance?</h2>
        <p>Renew your insurance plan to keep your timepieces protected</p>
        <img src="image/insu.jpg" alt="Luxury Watch" class="watch-image">
        <a href="renew-insurance.php" class="cta-button">RENEW NOW</a>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    // JavaScript for handling plan selection and payment
    document.querySelectorAll('.plan-button').forEach(button => {
        button.addEventListener('click', function() {
            const planTitle = this.closest('.plan-card').querySelector('.plan-title').textContent;
            const planPrice = this.closest('.plan-card').querySelector('.plan-price').textContent;
            
            // Extract price in number format for rupees
            const priceMatch = planPrice.match(/₹([\d,]+)/);
            const priceInRupees = priceMatch ? parseInt(priceMatch[1].replace(/,/g, '')) : 0;
            const priceInPaise = priceInRupees * 100; // Convert to paise for Razorpay
            
            // Calculate expiry date (1 year from now)
            const today = new Date();
            const expiryDate = new Date(today);
            expiryDate.setFullYear(today.getFullYear() + 1);
            const formattedExpiryDate = expiryDate.toISOString().split('T')[0]; // YYYY-MM-DD format
            
            // Razorpay payment integration
            const options = {
                key: 'rzp_test_qpOnn9moti7rqv',
                amount: priceInPaise, // Amount in smallest currency unit (paise)
                currency: "INR", // Changed from USD to INR
                name: "Luxury Watch Insurance",
                description: planTitle,
                handler: function(response) {
                    // On successful payment
                    const paymentId = response.razorpay_payment_id;
                    
                    // Send payment data to server to store in database
                    fetch('store_insurance_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            payment_id: paymentId,
                            plan_name: planTitle,
                            amount: priceInRupees,
                            status: 'completed',
                            payment_method: 'Razorpay',
                            expiry_date: formattedExpiryDate,
                            is_renewal: 0 // 0 for new policy, 1 for renewal
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            // Redirect to confirmation page with insurance ID
                            window.location.href = 'insurance_confirmation.php?id=' + data.insurance_id + '&policy=' + data.policy_number;
                        } else {
                            alert('Payment recorded but there was an issue with activation. Please contact support.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('There was an error processing your payment. Please try again.');
                    });
                },
                prefill: {
                    name: "<?php echo htmlspecialchars($user['fullname']); ?>",
                    email: "<?php echo htmlspecialchars($user['email']); ?>",
                    contact: "<?php echo htmlspecialchars($user['phone']); ?>"
                },
                theme: {
                    color: "#2E8B57"
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        });
    });
    
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    });
    
    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Get cart from localStorage (if you're using localStorage)
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        document.querySelector('.cart-count').textContent = totalItems;
    });
</script> 