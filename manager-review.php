<?php
session_start();
require_once 'dbconnect.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$product_filter = isset($_GET['product']) ? (int)$_GET['product'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query to fetch reviews with product and user information
$query = "SELECT pr.*, p.name as product_name, u.username 
          FROM product_reviews pr
          JOIN products p ON pr.product_id = p.id
          JOIN users u ON pr.user_id = u.id";

// Apply filters
$where_clauses = [];
$params = [];
$types = "";

if ($product_filter > 0) {
    $where_clauses[] = "pr.product_id = ?";
    $params[] = $product_filter;
    $types .= "i";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Apply sorting
switch ($sort_by) {
    case 'newest':
        $query .= " ORDER BY pr.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY pr.created_at ASC";
        break;
    case 'highest_rating':
        $query .= " ORDER BY pr.rating DESC";
        break;
    case 'lowest_rating':
        $query .= " ORDER BY pr.rating ASC";
        break;
    case 'product_name':
        $query .= " ORDER BY p.name ASC";
        break;
    default:
        $query .= " ORDER BY pr.created_at DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all products for the filter dropdown
$products_query = "SELECT id, name FROM products ORDER BY name";
$products_result = $conn->query($products_query);
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin: 15px 0;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #34495e;
            border-radius: 5px;
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background: #f5f6fa;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logout {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Manager Dashboard</h2>
            <ul>
                <li>
                    <a href="manager-dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="managerreguser.php">
                        <i class="fas fa-user-plus"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="manage-categories.php">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li>
                    <a href="add-products.php">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                </li>
                <li>
                    <a href="products.php">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </li>
                <li>
                    <a href="view-orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        View Orders
                    </a>
                </li>
                <li>
                    <a href="manager-review.php" class="bg-blue-800">
                        <i class="fas fa-star"></i>
                        Reviews
                    </a>
                </li>
                <li>
                    <a href="manager-payment.php">
                        <i class="fas fa-credit-card"></i>
                        Payment Details
                    </a>
                </li>
                
                <li class="logout">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1 class="text-2xl font-bold text-gray-800">Manage Product Reviews</h1>
                <div class="text-sm mt-2">
                    Total Reviews: <span class="font-bold"><?php echo count($reviews); ?></span>
                </div>
            </div>
    
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <h2 class="text-lg font-semibold mb-3">Filter Reviews</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <select name="product" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="0">All Products</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="highest_rating" <?php echo $sort_by === 'highest_rating' ? 'selected' : ''; ?>>Highest Rating</option>
                            <option value="lowest_rating" <?php echo $sort_by === 'lowest_rating' ? 'selected' : ''; ?>>Lowest Rating</option>
                            <option value="product_name" <?php echo $sort_by === 'product_name' ? 'selected' : ''; ?>>Product Name</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Reviews List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (empty($reviews)): ?>
                    <div class="p-6 text-center text-gray-500">
                        No reviews found matching your criteria.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start">
                                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold mr-3 flex-shrink-0">
                                                    <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($review['username']); ?></div>
                                                    <div class="text-sm text-gray-700 mt-1">
                                                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($review['product_name']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex text-yellow-500">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $review['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y, g:i a', strtotime($review['created_at'])); ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- View replies row -->
                                    <tr class="bg-gray-50">
                                        <td colspan="4" class="px-6 py-4">
                                            <?php
                                            // Fetch replies for this review
                                            $replies_query = "SELECT rr.*, u.username 
                                                            FROM review_replies rr 
                                                            JOIN users u ON rr.user_id = u.id 
                                                            WHERE rr.review_id = ? 
                                                            ORDER BY rr.created_at ASC";
                                            $reply_stmt = $conn->prepare($replies_query);
                                            $reply_stmt->bind_param("i", $review['id']);
                                            $reply_stmt->execute();
                                            $replies_result = $reply_stmt->get_result();
                                            $replies = $replies_result->fetch_all(MYSQLI_ASSOC);
                                            $reply_count = count($replies);
                                            ?>
                                            
                                            <div class="mb-2">
                                                <button type="button" 
                                                        class="text-sm flex items-center text-blue-600 hover:text-blue-800 focus:outline-none font-medium"
                                                        onclick="toggleReplies(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-comments mr-2"></i>
                                                    <?php echo $reply_count; ?> Replies - Click to view or add a reply
                                                </button>
                                            </div>
                                            
                                            <div id="replies-<?php echo $review['id']; ?>" class="hidden mt-2 pl-6 space-y-3 border-l-2 border-gray-200">
                                                <?php if (empty($replies)): ?>
                                                    <p class="text-sm text-gray-500 italic">No replies yet. Add the first reply below.</p>
                                                <?php else: ?>
                                                    <h4 class="font-medium text-gray-700">Existing Replies:</h4>
                                                    <?php foreach ($replies as $reply): ?>
                                                        <div class="bg-white p-3 rounded border border-gray-200 shadow-sm">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <span class="font-medium text-sm"><?php echo htmlspecialchars($reply['username']); ?></span>
                                                                    <span class="ml-2 text-xs text-gray-500">
                                                                        <?php echo date('M j, Y, g:i a', strtotime($reply['created_at'])); ?>
                                                                    </span>
                                                                </div>
                                                                <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                                                    <button class="text-xs text-red-500 hover:text-red-700" 
                                                                            onclick="deleteReply(<?php echo $reply['id']; ?>)">
                                                                        <i class="fas fa-trash-alt"></i> Delete
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <p class="text-sm mt-1"><?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?></p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                
                                                <!-- Enhanced reply form -->
                                                <div class="mt-3 bg-gray-50 p-3 rounded border border-gray-200">
                                                    <h4 class="font-medium text-gray-700 mb-2">Add Your Reply:</h4>
                                                    <textarea id="new-reply-<?php echo $review['id']; ?>" 
                                                              class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                                              rows="2" 
                                                              placeholder="Your professional response to this review..."></textarea>
                                                    <div class="mt-2 flex justify-end">
                                                        <button type="button" 
                                                                onclick="submitNewReply(<?php echo $review['id']; ?>)" 
                                                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors flex items-center">
                                                            <i class="fas fa-paper-plane mr-1"></i> Post Reply as Manager
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleReplies(reviewId) {
            const repliesDiv = document.getElementById('replies-' + reviewId);
            if (repliesDiv.classList.contains('hidden')) {
                repliesDiv.classList.remove('hidden');
            } else {
                repliesDiv.classList.add('hidden');
            }
        }
        
        function submitNewReply(reviewId) {
            const replyText = document.getElementById('new-reply-' + reviewId).value;
            
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
        
        function deleteReply(replyId) {
            if (confirm('Are you sure you want to delete this reply?')) {
                fetch('delete_reply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reply_id=' + replyId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload to update the page
                    } else {
                        alert('Error deleting reply: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>

