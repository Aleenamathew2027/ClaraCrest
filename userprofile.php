<?php
session_start();
require_once 'dbconnect.php';

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle image upload
if(isset($_FILES['user_image'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["user_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is valid
    if(in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        if(move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file)) {
            // Update database with image path
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $query = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $target_file, $_SESSION['user_id']);
            $stmt->execute();
        }
    }
}

// Fetch user details
$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            height: 100vh; /* Full height */
            overflow: hidden; /* Prevent scrolling */
        }

        .background-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Place behind other content */
            animation: slide 30s infinite; /* Adjust duration as needed */
        }

        @keyframes slide {
            0% { background-image: url("image/bg1.jpg"); }
            20% { background-image: url("image/bg1.jpg"); }
            20% { background-image: url("image/bg2.jpg"); }
            40% { background-image: url("image/bg2.jpg"); }
            40% { background-image: url("image/bg3.jpg"); }
            60% { background-image: url("image/bg3.jpg"); }
            60% { background-image: url("image/bg4.jpg"); }
            80% { background-image: url("image/bg4.jpg"); }
            80% { background-image: url("image/bg5.jpg"); }
            100% { background-image: url("image/bg5.jpg"); }
        }

        .background-slideshow {
            background-size: cover; /* Cover the entire viewport */
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Prevent repeating the image */
        }

        .profile-container {
            background-color: rgba(255, 255, 255, 0.8); /* White background with transparency */
            border-radius: 10px; /* Rounded corners */
            padding: 20px; /* Padding around the content */
            max-width: 800px; /* Increased maximum width */
            margin: auto; /* Center the container */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add a subtle shadow */
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="background-slideshow"></div>
    <div class="profile-container">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-8">User Profile</h2>
                <nav class="space-y-4">
                    <a href="home.php" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-home"></i>
                        <span>Back to Home</span>
                    </a>
                    <a href="#user-info" class="flex items-center space-x-3 w-full p-2 rounded bg-orange-100 text-orange-500">
                        <i class="fas fa-user"></i>
                        <span>User Info</span>
                    </a>
                    <a href="#favorites" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-heart"></i>
                        <span>Favorites</span>
                    </a>
                    <a href="#orders" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-list"></i>
                        <span>Orders</span>
                    </a>
                    <a href="#settings" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </nav>
                <a href="logout.php" class="flex items-center space-x-3 w-full p-2 text-red-500 absolute bottom-6">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-8">
                <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-start space-x-8 mb-8">
                        <div class="relative">
                            <img 
                                src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '/api/placeholder/150/150'; ?>"
                                alt="  Profile"
                                class="w-32 h-32 rounded-full object-cover"
                            />
                            <label class="absolute bottom-0 right-0 bg-orange-500 text-white p-2 rounded-full cursor-pointer hover:bg-orange-600">
                                <input 
                                    type="file" 
                                    class="hidden" 
                                    name="user_image"
                                    form="profile-form"
                                    accept="image/*"
                                />
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-2">User Profile</h1>
                            <p class="text-gray-600">Manage your account settings and preferences</p>
                        </div>
                    </div>

                    <form id="profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <select name="gender" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                <option value="male" <?php echo ($user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Mobile</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">District</label>
                            <input type="text" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Pin Code</label>
                            <input type="text" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                        </div>

                        <!--<div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                             Removed Password Input 
                             <input type="password" name="password" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500"> 
                        </div>-->

                         <!--<div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            Removed Confirm Password Input 
                            <input type="password" name="confirmpassword" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500"> 
                        </div>-->

                        <div class="col-span-2">
                            <button type="submit" class="w-full bg-orange-500 text-black py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors border border-black" id="save-button">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle image preview
        document.querySelector('input[name="user_image"]').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('img').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Ensure form submission works
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default submission for validation
            // You can add any validation here if needed
            this.submit(); // Submit the form
        });
    </script>
</body>
</html>