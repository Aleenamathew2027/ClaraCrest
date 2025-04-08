<?php
session_start();
require_once 'dbconnect.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: collection.php");
    exit();
}

// Fetch product details with category and subcategory names
$query = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN subcategories s ON p.subcategory_id = s.id 
          WHERE p.id = ? AND p.status = 'active'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: collection.php");
    exit();
}

// Fetch all images for the product
$images_query = "SELECT * FROM product_images 
                WHERE product_id = ? 
                ORDER BY is_primary DESC, order_number ASC";
$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$images_result = $stmt->get_result();
$product_images = $images_result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'header.php';
?>

<!-- Add custom styles specific to product details -->
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f9f9f9;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .product-container {
        display: grid;
        grid-template-columns: 60% 40%;
        gap: 40px;
        margin-top: 20px;
        margin-bottom: 40px;
    }

    /* Image Gallery Styles - Enhanced */
    .image-gallery {
        position: relative;
        background-color: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .main-image-container {
        position: relative;
        width: 100%;
        height: 0;
        padding-bottom: 100%; /* Create a perfect square */
        overflow: hidden;
        margin-bottom: 20px;
        border-radius: 8px;
        background-color: #f5f5f5;
    }

    .main-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain; /* Changed from cover to contain to avoid image cropping */
        border-radius: 8px;
        transition: transform 0.3s ease;
    }

    .main-image:hover {
        transform: scale(1.02);
    }

    .thumbnail-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 12px;
        padding: 10px 0;
    }

    .thumbnail {
        width: 100%;
        height: 80px;
        object-fit: cover;
        cursor: pointer;
        border-radius: 6px;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .thumbnail:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .thumbnail.active {
        border-color: #006039;
    }

    /* Product Info Styles - Enhanced */
    .product-info {
        padding: 30px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .product-title {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
        color: #006039;
        letter-spacing: -0.5px;
    }

    .product-category {
        color: #666;
        margin-bottom: 20px;
        font-size: 15px;
    }

    .product-price {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #006039;
    }

    .product-description {
        margin-bottom: 30px;
        line-height: 1.8;
        color: #555;
        font-size: 16px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .specifications {
        margin-bottom: 30px;
    }

    .spec-title {
        font-size: 20px;
        margin-bottom: 15px;
        color: #006039;
        font-weight: 600;
    }

    .spec-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .spec-item {
        padding: 15px;
        background-color: #f8f8f8;
        border-radius: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .spec-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .spec-label {
        font-weight: bold;
        margin-bottom: 5px;
        color: #666;
    }

    .add-to-cart {
        width: 100%;
        padding: 16px;
        background-color: #006039;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }

    .add-to-cart:hover {
        background-color: #004c2d;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 96, 57, 0.2);
    }

    .wishlist-btn {
        width: 100%;
        padding: 16px;
        background-color: white;
        color: #006039;
        border: 2px solid #006039;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .wishlist-btn:hover {
        background-color: #f0f8f4;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Responsive Design - Enhanced */
    @media (max-width: 992px) {
        .product-container {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .main-image-container {
            padding-bottom: 75%; /* Adjust aspect ratio for tablets */
        }
    }

    @media (max-width: 768px) {
        .spec-grid {
            grid-template-columns: 1fr;
        }
        
        .thumbnail-container {
            grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        }
        
        .thumbnail {
            height: 70px;
        }
    }

    @media (max-width: 480px) {
        .main-image-container {
            padding-bottom: 100%; /* Back to square for mobile */
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 24px;
        }
    }

    /* Back Button - Enhanced */
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background-color: #f0f8f4;
        color: #006039;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        font-weight: 600;
        border: 1px solid #e0f0e9;
    }

    .back-button:hover {
        background-color: #e0f0e9;
        transform: translateX(-3px);
    }

    /* Stock Status - Enhanced */
    .stock-status {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 14px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .in-stock {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .out-of-stock {
        background-color: #ffebee;
        color: #c62828;
    }

    /* Image Zoom feature */
    .zoom-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.9);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        cursor: zoom-out;
    }

    .zoomed-image {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }

    .zoom-close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 30px;
        cursor: pointer;
    }
</style>

<div class="container mx-auto px-4">
    <a href="collection.php" class="back-button inline-flex items-center gap-2 py-2 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors mb-6">
        <i class="fas fa-arrow-left"></i>
        Back to Collection
    </a>

    <div class="product-container grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- Image Gallery - 3 columns -->
        <div class="lg:col-span-3">
            <div class="image-gallery">
                <div class="main-image-container">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? $product_images[0]['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="main-image" 
                         id="mainImage">
                </div>
                
                <div class="thumbnail-container">
                    <?php 
                    // First add the main product image if it exists and is different from the first product_image
                    if (!empty($product['image_url']) && 
                        (!isset($product_images[0]['image_url']) || $product['image_url'] != $product_images[0]['image_url'])): 
                    ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?> - Main" 
                             class="thumbnail border-green-600"
                             onclick="changeMainImage(this.src, this)">
                    <?php endif; ?>
                    
                    <?php foreach ($product_images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?> - Image <?php echo $index+1; ?>" 
                             class="thumbnail <?php echo (empty($product['image_url']) && $index === 0) ? 'border-green-600' : 'border-transparent'; ?>"
                             onclick="changeMainImage(this.src, this)">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Product Info - 2 columns -->
        <div class="lg:col-span-2">
            <div class="product-info">
                <h1 class="product-title">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <div class="product-category">
                    <?php echo htmlspecialchars($product['category_name']); ?> / 
                    <?php echo htmlspecialchars($product['subcategory_name']); ?>
                </div>

                <div class="product-price">
                    ₹<?php echo number_format($product['price'], 2); ?>
                </div>

                <div class="stock-status <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                    <?php echo $product['stock_quantity'] > 0 
                          ? '<i class="fas fa-check-circle"></i> In Stock (' . $product['stock_quantity'] . ' available)' 
                          : '<i class="fas fa-times-circle"></i> Out of Stock'; ?>
                </div>

                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <div class="specifications">
                    <h2 class="spec-title">Specifications</h2>
                    <div class="spec-grid">
                        <?php
                        $specs = [
                            'Brand' => $product['brand'],
                            'Watch Type' => $product['watch_type'],
                            'Movement' => $product['movement'],
                            'Water Resistance' => $product['water_resistance'],
                            'Dial Color' => $product['dial_color'],
                            'Warranty' => $product['warranty']
                        ];

                        foreach ($specs as $label => $value):
                            if (!empty($value)):
                        ?>
                            <div class="spec-item">
                                <div class="spec-label"><?php echo $label; ?></div>
                                <div><?php echo htmlspecialchars($value); ?></div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <button class="add-to-cart <?php echo $product['stock_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        data-product-id="<?php echo $product['id']; ?>"
                        <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-shopping-bag"></i>
                    Add to Bag
                </button>

                <button class="wishlist-btn"
                        data-product-id="<?php echo $product['id']; ?>">
                    <i class="far fa-heart"></i>
                    Add to Wishlist
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reviews section for product-details.php -->
<div class="container mx-auto px-4 py-8 mt-10">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Customer Reviews</h2>
    
    <?php
    // Fetch ALL reviews for this product without status filter
    $reviews_query = "SELECT r.*, u.username, 
                      (SELECT COUNT(*) FROM review_replies WHERE review_id = r.id) as reply_count
                      FROM product_reviews r 
                      JOIN users u ON r.user_id = u.id 
                      WHERE r.product_id = ? 
                      ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($reviews_query);
    if ($stmt === false) {
        echo "<div class='text-red-600 bg-red-100 p-4 rounded-lg mb-6'>Error preparing reviews query: " . $conn->error . "</div>";
        $reviews = [];
    } else {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $reviews_result = $stmt->get_result();
        $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    // Calculate average rating - include all reviews
    $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ?";
    $stmt = $conn->prepare($avg_query);
    if ($stmt === false) {
        echo "<div class='text-red-600 bg-red-100 p-4 rounded-lg mb-6'>Error preparing rating query: " . $conn->error . "</div>";
        $avg_rating = 0;
        $total_reviews = 0;
    } else {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $avg_result = $stmt->get_result();
        $rating_data = $avg_result->fetch_assoc();
        $stmt->close();
        
        $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
        $total_reviews = $rating_data['total_reviews'] ?? 0;
    }
    ?>
    
    <!-- Reviews Summary -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center">
            <div class="text-3xl font-bold text-yellow-500 mr-4"><?php echo $avg_rating; ?></div>
            <div>
                <div class="flex text-yellow-500 mb-1">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <?php if($i <= $avg_rating): ?>
                            <i class="fas fa-star"></i>
                        <?php elseif($i <= $avg_rating + 0.5): ?>
                            <i class="fas fa-star-half-alt"></i>
                        <?php else: ?>
                            <i class="far fa-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <div class="text-gray-500">Based on <?php echo $total_reviews; ?> reviews</div>
            </div>
            <div class="ml-auto">
                <button id="writeReviewBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                    Write a Review
                </button>
            </div>
        </div>
    </div>
    
    <!-- Reviews List -->
    <div class="space-y-6">
        <?php if(empty($reviews)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-500 mb-4">There are no reviews for this product yet.</p>
                <p class="text-gray-500">Be the first to review this product!</p>
            </div>
        <?php else: ?>
            <?php foreach($reviews as $review): ?>
                <div class="bg-white rounded-lg shadow-md p-6" id="review-<?php echo $review['id']; ?>">
                    <div class="flex justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold mr-3">
                                <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-semibold"><?php echo htmlspecialchars($review['username']); ?></div>
                                <div class="text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex text-yellow-500">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-gray-700">
                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                    </div>
                    
                    <!-- Replies Section -->
                    <?php
                    $replies_query = "SELECT rr.*, u.username FROM review_replies rr JOIN users u ON rr.user_id = u.id WHERE rr.review_id = ? ORDER BY rr.created_at ASC";
                    $stmt = $conn->prepare($replies_query);
                    if ($stmt === false) {
                        echo "<div class='text-red-600 text-sm'>Error preparing replies query</div>";
                        $replies = [];
                    } else {
                        $stmt->bind_param("i", $review['id']);
                        $stmt->execute();
                        $replies_result = $stmt->get_result();
                        $replies = $replies_result->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();
                    }
                    ?>
                    
                    <?php if(!empty($replies)): ?>
                        <div class="mt-4 pl-8 border-l-2 border-gray-200">
                            <button class="text-sm font-semibold text-gray-500 mb-2 focus:outline-none" 
                                    onclick="toggleRepliesForReview(<?php echo $review['id']; ?>)">
                                <i class="fas fa-comments mr-1"></i>
                                <?php echo count($replies); ?> Replies
                            </button>
                            
                            <div id="review-replies-<?php echo $review['id']; ?>" class="hidden space-y-2">
                                <?php foreach($replies as $reply): ?>
                                    <div class="bg-gray-50 rounded p-3 mb-2">
                                        <div class="flex items-center">
                                            <div class="text-sm font-semibold">
                                                <?php echo htmlspecialchars($reply['username']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 ml-2">
                                                <?php echo date('M j, Y', strtotime($reply['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="mt-1 text-gray-700">
                                            <?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="mt-3">
                            <button onclick="showReplyForm(<?php echo $review['id']; ?>)" class="text-sm text-blue-600 hover:text-blue-800">
                                Reply to this review
                            </button>
                            <div id="reply-form-<?php echo $review['id']; ?>" class="mt-2 hidden">
                                <textarea id="reply-text-<?php echo $review['id']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" rows="2" placeholder="Write your reply..."></textarea>
                                <div class="mt-2 flex justify-end">
                                    <button onclick="submitReply(<?php echo $review['id']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded-md text-sm hover:bg-green-700 transition-colors">
                                        Submit Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Write Review Modal -->
<div id="reviewFormModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-lg w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Write a Review</h3>
            <button id="closeReviewFormModal" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="productReviewForm" class="space-y-4">
            <input type="hidden" id="productReviewProductId" name="product_id" value="<?php echo $product_id; ?>">
            
            <div>
                <label class="block text-gray-700 mb-2">Rating</label>
                <div class="flex text-2xl">
                    <span class="review-star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="1">★</span>
                    <span class="review-star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="2">★</span>
                    <span class="review-star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="3">★</span>
                    <span class="review-star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="4">★</span>
                    <span class="review-star cursor-pointer text-gray-300 hover:text-yellow-500" data-value="5">★</span>
                </div>
                <input type="hidden" id="productReviewRating" name="rating" value="0">
            </div>
            
            <div>
                <label for="reviewTextArea" class="block text-gray-700 mb-2">Your Review</label>
                <textarea id="reviewTextArea" name="review_text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                    Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Image Zoom Overlay -->
<div class="zoom-overlay" id="zoomOverlay">
    <div class="zoom-close" onclick="closeZoom()">&times;</div>
    <img src="" alt="Zoomed product image" class="zoomed-image" id="zoomedImage">
</div>

<script>
    function changeMainImage(src, thumbnail) {
        document.getElementById('mainImage').src = src;
        // Remove active class from all thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('border-green-600');
            thumb.classList.add('border-transparent');
        });
        // Add active class to clicked thumbnail
        thumbnail.classList.remove('border-transparent');
        thumbnail.classList.add('border-green-600');
    }

    // Image zoom functionality
    document.getElementById('mainImage').addEventListener('click', function() {
        const zoomOverlay = document.getElementById('zoomOverlay');
        const zoomedImage = document.getElementById('zoomedImage');
        
        zoomedImage.src = this.src;
        zoomOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    });

    function closeZoom() {
        document.getElementById('zoomOverlay').style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    }

    // Add to Cart functionality
    document.querySelector('.add-to-cart').addEventListener('click', function() {
        if(this.disabled) return;
        
        const productId = this.getAttribute('data-product-id');
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.innerHTML = '<i class="fas fa-check"></i> Added to Bag';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-shopping-bag"></i> Add to Bag';
                }, 2000);
            } else {
                alert('Error adding product to cart: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    });

    // Wishlist functionality
    document.querySelector('.wishlist-btn').addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        const heartIcon = this.querySelector('i');
        
        fetch('update_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&action=add'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
                this.innerHTML = '<i class="fas fa-heart"></i> Added to Wishlist';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-heart"></i> In Wishlist';
                }, 2000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    });

    // Review related functions
    document.getElementById('writeReviewBtn')?.addEventListener('click', function() {
        document.getElementById('reviewFormModal').classList.remove('hidden');
    });
    
    document.getElementById('closeReviewFormModal')?.addEventListener('click', function() {
        document.getElementById('reviewFormModal').classList.add('hidden');
    });
    
    // Handle stars rating for product review form
    const reviewStars = document.querySelectorAll('.review-star');
    reviewStars.forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('productReviewRating').value = value;
            
            // Reset all stars
            reviewStars.forEach(s => s.classList.replace('text-yellow-500', 'text-gray-300'));
            
            // Fill stars up to the selected one
            for (let i = 0; i < value; i++) {
                reviewStars[i].classList.replace('text-gray-300', 'text-yellow-500');
            }
        });
    });
    
    // Handle review submission
    document.getElementById('productReviewForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reviewFormModal').classList.add('hidden');
                alert('Thank you for your review!');
                location.reload(); // Reload to see the new review
            } else {
                alert('Error submitting review: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    });
    
    // Handle review replies
    function showReplyForm(reviewId) {
        const replyForm = document.getElementById('reply-form-' + reviewId);
        replyForm.classList.toggle('hidden');
    }
    
    function submitReply(reviewId) {
        const replyText = document.getElementById('reply-text-' + reviewId).value;
        
        if (!replyText.trim()) {
            alert('Please enter a reply');
            return;
        }
        
        fetch('submit_reply.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'review_id=' + reviewId + '&reply_text=' + encodeURIComponent(replyText)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to see the new reply
            } else {
                alert('Error submitting reply: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }

    // Add this function to toggle replies for reviews
    function toggleRepliesForReview(reviewId) {
        const repliesDiv = document.getElementById('review-replies-' + reviewId);
        if (repliesDiv.classList.contains('hidden')) {
            repliesDiv.classList.remove('hidden');
        } else {
            repliesDiv.classList.add('hidden');
        }
    }
</script>

<?php
// Include footer
include 'footer.php';
?> 