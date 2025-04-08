<?php
// Start session
session_start();

// Include database connection
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php?redirect=bag.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Function to get cart items
function getCartItems($user_id) {
    global $conn;
    
    $items = [];
    $total = 0;
    
    // Get items from database for logged-in user
    $query = "SELECT c.id as cart_id, c.quantity, p.* 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
    
    return [
        'items' => $items,
        'total' => $total
    ];
}

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("iii", $quantity, $cart_id, $user_id);
    $update->execute();
    
    header("Location: bag.php");
    exit;
}

// Handle item removal
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    
    $remove = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $remove->bind_param("ii", $cart_id, $user_id);
    $remove->execute();
    
    header("Location: bag.php");
    exit;
}

// Get cart items
$cart = getCartItems($user_id);
$cartItems = $cart['items'];
$totalPrice = $cart['total'];

// Include header
include 'header.php';
?>

<style>
    /* Bag-specific styles */
    .bag-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .bag-empty {
        text-align: center;
        padding: 50px 0;
    }
    
    .bag-empty p {
        font-size: 18px;
        color: #666;
        margin-bottom: 30px;
    }
    
    .bag-empty a {
        display: inline-block;
        background-color: #006039;
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 4px;
    }
    
    .bag-content {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    
    .bag-items {
        flex: 2;
        min-width: 300px;
    }
    
    .bag-summary {
        flex: 1;
        min-width: 250px;
        background-color: white;
        padding: 20px;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        align-self: flex-start;
    }
    
    .bag-item {
        display: flex;
        background-color: white;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .item-image {
        width: 120px;
        margin-right: 20px;
    }
    
    .item-image img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-name {
        font-size: 18px;
        margin: 0 0 10px 0;
    }
    
    .item-price {
        font-weight: bold;
        font-size: 18px;
        margin: 10px 0;
    }
    
    .quantity-control {
        display: flex;
        align-items: center;
        margin-top: 15px;
    }
    
    .quantity-btn {
        background: #f0f0f0;
        border: none;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
    }
    
    .quantity-input {
        width: 40px;
        height: 30px;
        text-align: center;
        border: 1px solid #ddd;
        margin: 0 5px;
    }
    
    .item-actions {
        display: flex;
        align-items: center;
        margin-top: 15px;
    }
    
    .remove-item {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 0;
        font-size: 14px;
    }
    
    .remove-item:hover {
        color: #d9534f;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 16px;
    }
    
    .summary-total {
        font-weight: bold;
        font-size: 20px;
        border-top: 1px solid #ddd;
        padding-top: 15px;
        margin-top: 15px;
    }
    
    .checkout-button {
        display: block;
        width: 100%;
        background-color: #006039;
        color: white;
        border: none;
        padding: 15px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 20px;
    }
    
    .checkout-button:hover {
        background-color: #004d2e;
    }
    
    .update-cart {
        background-color: #006039;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
    }
    
    @media (max-width: 768px) {
        .bag-content {
            flex-direction: column;
        }
        
        .bag-item {
            flex-direction: column;
        }
        
        .item-image {
            width: 100%;
            margin-right: 0;
            margin-bottom: 20px;
        }
    }
</style>

<div class="container">
    <h1>Your Shopping Bag</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="bag-empty">
            <p>Your shopping bag is empty</p>
            <a href="collection.php">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="bag-content">
            <div class="bag-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="bag-item">
                        <div class="item-image">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['brand'] ?? ''); ?> <?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-model">Model: <?php echo htmlspecialchars($item['model'] ?? 'N/A'); ?></p>
                            <p class="item-price">₹ <?php echo number_format($item['price'], 2); ?></p>
                            
                            <form method="post" action="bag.php" class="quantity-control">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="button" class="quantity-btn decrease-qty">-</button>
                                <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                <button type="button" class="quantity-btn increase-qty">+</button>
                                <button type="submit" name="update_quantity" class="update-cart">Update</button>
                            </form>
                            
                            <div class="item-actions">
                                <a href="bag.php?remove=<?php echo $item['cart_id']; ?>" class="remove-item" onclick="return confirm('Are you sure you want to remove this item?')">
                                    <i class="fas fa-trash-alt"></i> Remove
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="bag-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal (<?php echo count($cartItems); ?> items)</span>
                    <span>₹ <?php echo number_format($totalPrice, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span>₹ <?php echo number_format($totalPrice, 2); ?></span>
                </div>
                <button class="checkout-button" onclick="startPayment()">PROCEED TO CHECKOUT</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Razorpay SDK before your script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle quantity adjustments
        const decreaseButtons = document.querySelectorAll('.decrease-qty');
        const increaseButtons = document.querySelectorAll('.increase-qty');
        
        decreaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        increaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                if (value < 10) {
                    input.value = value + 1;
                }
            });
        });
    });

    function startPayment() {
        // Convert amount to paise (Razorpay expects amount in smallest currency unit)
        const amount = <?php echo $totalPrice * 100; ?>;
        
        const options = {
            key: 'rzp_test_qpOnn9moti7rqv',
            amount: amount,
            currency: 'INR',
            name: 'ClaraCrest',
            description: 'Watch Purchase',
            image: 'assets/img/logo.png', // Updated to use a valid logo path
            handler: function(response) {
                // Send payment details to server
                verifyPayment(response.razorpay_payment_id, amount);
            },
            prefill: {
                name: '<?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>',
                email: '<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>',
                contact: '<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>'
            },
            theme: {
                color: '#006039'
            }
        };

        try {
            const rzp = new Razorpay(options);
            rzp.open();
        } catch (error) {
            console.error("Razorpay error:", error);
            alert("Payment initialization failed. Please try again later.");
        }
    }

    function verifyPayment(paymentId, amount) {
        fetch('process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `payment_id=${paymentId}&amount=${amount}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'order_success.php?order_id=' + data.order_id;
            } else {
                alert('Payment failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error processing payment:', error);
            alert('Error processing payment. Please try again.');
        });
    }
</script> 