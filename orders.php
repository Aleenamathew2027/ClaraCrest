<?php
session_start();
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Direct approach to fetch product images - check which image column exists
$columns_query = "SHOW COLUMNS FROM products";
$columns_result = $conn->query($columns_query);
$image_column = null;

if ($columns_result) {
    while ($row = $columns_result->fetch_assoc()) {
        $colName = $row['Field'];
        if (in_array($colName, ['image_url', 'image', 'img', 'photo', 'picture'])) {
            $image_column = $colName;
            break;
        }
    }
}

// Fall back to a default if no image column found
if (!$image_column) {
    $image_column = 'img_url'; // Set a default for the query to avoid errors
}

// Build the query using the detected image column
$query = "SELECT o.order_id, o.created_at, o.order_status, o.price_at_time, o.quantity,
          p.id as product_id, p.name as product_name, p.brand, p.{$image_column} as product_image, 
          pi.image_url as primary_image_url,
          DATE_FORMAT(o.created_at, '%M %d, %Y') as order_date 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC";

// For debugging - print the query
// echo "<pre>$query</pre>";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . " - Query: $query");
}

if (!$stmt->bind_param("i", $_SESSION['user_id'])) {
    die("Error binding parameters: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

// Group orders by order_id
$orders = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($orders[$row['order_id']])) {
        $orders[$row['order_id']] = [
            'order_id' => $row['order_id'],
            'order_date' => $row['order_date'],
            'status' => $row['order_status'],
            'items' => [],
            'total' => 0
        ];
    }
    $orders[$row['order_id']]['items'][] = $row;
    $orders[$row['order_id']]['total'] += $row['price_at_time'] * $row['quantity'];
}

$stmt->close();

// Include header
include 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">My Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="text-center py-8">
            <p class="text-gray-600 mb-4">You haven't placed any orders yet.</p>
            <a href="collection.php" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($orders as $order): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">
                                    Order #<?php echo htmlspecialchars($order['order_id']); ?>
                                </h2>
                                <p class="text-gray-600">Placed on <?php echo $order['order_date']; ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold text-green-600">
                                    ₹<?php echo number_format($order['total'], 2); ?>
                                </div>
                                <span class="inline-block px-3 py-1 rounded-full text-sm
                                    <?php
                                    switch ($order['status']) {
                                        case 'processing':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'shipped':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'delivered':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'cancelled':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="p-6 flex items-center" data-product-id="<?php echo $item['product_id']; ?>">
                                <div class="flex-shrink-0 w-24 h-24">
                                    <?php
                                        // Determine which image source to use
                                        $imageUrl = '';
                                        
                                        // Try product's direct image first
                                        if (!empty($item['product_image'])) {
                                            $imageUrl = $item['product_image'];
                                        }
                                        // Then try primary_image_url
                                        else if (!empty($item['primary_image_url'])) {
                                            $imageUrl = $item['primary_image_url'];
                                        }
                                        
                                        // If still empty, use placeholder
                                        if (empty($imageUrl)) {
                                            $imageUrl = 'placeholder.jpg';
                                        }
                                        
                                        // Add path prefix if needed - for images stored in uploads folder
                                        if (!empty($imageUrl) && strpos($imageUrl, 'http') !== 0 && strpos($imageUrl, '/') !== 0) {
                                            // Check if image contains uploads already to prevent duplication
                                            if (strpos($imageUrl, 'uploads/') === false) {
                                                $imageUrl = 'uploads/' . $imageUrl;
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="w-full h-full object-cover rounded-lg"
                                         onerror="this.src='image/placeholder.jpg'">
                                </div>
                                <div class="ml-6 flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        <?php echo htmlspecialchars($item['brand']); ?>
                                    </p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="text-gray-600">
                                            Quantity: <?php echo $item['quantity']; ?>
                                        </div>
                                        <div class="text-green-600 font-semibold">
                                            ₹<?php echo number_format($item['price_at_time'], 2); ?> each
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                        <div class="bg-gray-50 px-6 py-4 flex justify-end">
                            <button onclick="cancelOrder('<?php echo $order['order_id']; ?>')" 
                                    class="text-red-600 hover:text-red-700 font-semibold">
                                Cancel Order
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error cancelling order: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

// New review system code
document.addEventListener('DOMContentLoaded', function() {
    // Check for review opportunity
    const urlParams = new URLSearchParams(window.location.search);
    const fromOrder = urlParams.get('from_order');
    
    if (fromOrder) {
        // Find the order in the page
        const orderItems = document.querySelectorAll('.bg-white.rounded-lg.shadow-lg');
        orderItems.forEach(orderItem => {
            const orderIdElement = orderItem.querySelector('h2.text-lg.font-semibold');
            if (orderIdElement && orderIdElement.textContent.includes(fromOrder)) {
                // This is the order we're looking for
                const items = orderItem.querySelectorAll('.p-6.flex.items-center');
                if (items.length > 0) {
                    // First check if the user has already reviewed these products
                    const productIds = Array.from(items).map(item => item.getAttribute('data-product-id'));
                    
                    // Check if the user has already reviewed these products from this order
                    fetch('check_reviews.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_ids=' + JSON.stringify(productIds) + '&order_id=' + fromOrder
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.showReview) {
                            // Find first product that hasn't been reviewed
                            const productToReview = items[data.firstUnreviewedIndex];
                            const productName = productToReview.querySelector('h3').textContent.trim();
                            const productBrand = productToReview.querySelector('p').textContent.trim();
                            const productImage = productToReview.querySelector('img').src;
                            const productId = productToReview.getAttribute('data-product-id');
                            
                            openReviewModal(productName, productBrand, productImage, productId, fromOrder);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking review status:', error);
                    });
                }
            }
        });
    }
    
    // Handle stars rating
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('ratingValue').value = value;
            
            // Reset all stars
            stars.forEach(s => s.classList.replace('text-yellow-500', 'text-gray-300'));
            
            // Fill stars up to the selected one
            for (let i = 0; i < value; i++) {
                stars[i].classList.replace('text-gray-300', 'text-yellow-500');
            }
        });
    });
    
    // Handle close modal
    document.getElementById('closeReviewModal').addEventListener('click', function() {
        document.getElementById('reviewModal').classList.add('hidden');
    });
    
    // Handle review submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reviewModal').classList.add('hidden');
                alert('Thank you for your review!');
            } else {
                alert('Error submitting review: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    });
});

// Function to open review modal
function openReviewModal(productName, productBrand, productImage, productId, orderId) {
    console.log("Opening review modal for product ID:", productId);
    
    document.getElementById('reviewProductName').textContent = productName;
    document.getElementById('reviewProductBrand').textContent = productBrand;
    document.getElementById('reviewProductImage').src = productImage;
    document.getElementById('reviewProductId').value = productId;
    document.getElementById('reviewOrderId').value = orderId;
    
    document.getElementById('reviewModal').classList.remove('hidden');
}
</script>

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-lg w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Rate & Review Your Purchase</h3>
            <button id="closeReviewModal" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="reviewProduct" class="mb-4">
            <div class="flex items-center mb-4">
                <div class="w-20 h-20 mr-4">
                    <img id="reviewProductImage" src="" alt="Product" class="w-full h-full object-cover rounded">
                </div>
                <div>
                    <h4 id="reviewProductName" class="font-semibold text-gray-800"></h4>
                    <p id="reviewProductBrand" class="text-gray-600"></p>
                </div>
            </div>
        </div>
        
        <form id="reviewForm" class="space-y-4">
            <input type="hidden" id="reviewProductId" name="product_id">
            <input type="hidden" id="reviewOrderId" name="order_id">
            
            <div>
                <label class="block text-gray-700 mb-2">Rating</label>
                <div class="flex text-2xl">
                    <span class="star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="1">★</span>
                    <span class="star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="2">★</span>
                    <span class="star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="3">★</span>
                    <span class="star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="4">★</span>
                    <span class="star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="5">★</span>
                </div>
                <input type="hidden" id="ratingValue" name="rating" value="0">
            </div>
            
            <div>
                <label for="reviewText" class="block text-gray-700 mb-2">Your Review</label>
                <textarea id="reviewText" name="review_text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                    Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>