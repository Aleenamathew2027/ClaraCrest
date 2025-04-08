<?php
session_start();
require_once 'dbconnect.php';

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Message variables
$success_message = '';
$error_message = '';

// Handle image upload
if(isset($_FILES['user_image']) && $_FILES['user_image']['error'] == 0) {
    $target_dir = "uploads/profile_images/";
    
    // Ensure the upload directory exists with proper permissions
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // For debugging
    error_log("Upload starting. Directory: " . $target_dir);
    
    // Generate a unique filename to prevent overwriting
    $file_extension = strtolower(pathinfo($_FILES["user_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    error_log("Target file: " . $target_file);
    
    // Check if image file is valid
    if(in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        if(move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file)) {
            // Update database with image path
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $query = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $target_file, $_SESSION['user_id']);
            if($stmt->execute()) {
                $success_message = "Profile image updated successfully!";
                error_log("Database updated successfully with image path: " . $target_file);
            } else {
                $error_message = "Failed to update database: " . $conn->error;
                error_log("Database update failed: " . $conn->error);
            }
        } else {
            $upload_error = error_get_last();
            $error_message = "Failed to upload image. Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error');
            error_log("Move uploaded file failed: " . $error_message);
        }
    } else {
        $error_message = "Only JPG, JPEG, PNG & GIF files are allowed. Uploaded: " . $file_extension;
        error_log("Invalid file extension: " . $file_extension);
    }
} elseif(isset($_FILES['user_image'])) {
    $error_code = $_FILES['user_image']['error'];
    $upload_errors = array(
        1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        2 => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
        3 => "The uploaded file was only partially uploaded",
        4 => "No file was uploaded",
        6 => "Missing a temporary folder",
        7 => "Failed to write file to disk",
        8 => "A PHP extension stopped the file upload"
    );
    $error_message = "Upload error: " . ($upload_errors[$error_code] ?? "Unknown error code $error_code");
    error_log("File upload error: " . $error_message);
}

// Handle profile image removal
if(isset($_POST['remove_profile_image']) && $_POST['remove_profile_image'] == 'yes') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // First get the current image path
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    // Update the database to remove the image reference
    $query = "UPDATE users SET profile_image = NULL WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if($stmt->execute()) {
        // Delete the file if it exists
        if(!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])) {
            unlink($user_data['profile_image']);
        }
        $success_message = "Profile image removed successfully!";
    } else {
        $error_message = "Failed to remove profile image: " . $conn->error;
    }
}

// Handle password change
if(isset($_POST['change_password']) && $_POST['change_password'] == 'yes') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } else if($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else if(strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Verify current password
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if(password_verify($current_password, $user_data['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if($update_stmt->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
        } else {
            $error_message = "Current password is incorrect.";
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

// Include header
include 'header.php';
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
            height: 100vh;
            overflow: auto; /* Changed to auto to allow scrolling */
        }

        .background-slideshow {
            position: fixed; /* Fixed instead of absolute to keep it in place when scrolling */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            animation: slide 30s infinite;
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
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .profile-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .tab-active {
            background-color: #f97316;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="background-slideshow"></div>
    <div class="profile-container mt-8 mb-8">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-8">Profile</h2>
                <nav class="space-y-4">
                    <a href="home.php" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-home"></i>
                        <span>Back to Home</span>
                    </a>
                    <a href="userprofile.php" class="flex items-center space-x-3 w-full p-2 rounded bg-orange-100 text-orange-500">
                        <i class="fas fa-user"></i>
                        <span>User Info</span>
                    </a>
                    <a href="favorites.php" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-heart"></i>
                        <span>Favorites</span>
                    </a>
                    <a href="orders.php" class="flex items-center space-x-3 w-full p-2 rounded text-gray-600 hover:bg-orange-100 hover:text-orange-500">
                        <i class="fas fa-list"></i>
                        <span>Orders</span>
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
                    <!-- Success/Error Messages -->
                    <?php if(!empty($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                    <?php endif; ?>
                
                    <div class="flex items-start space-x-8 mb-8">
                        <div class="relative">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-orange-200">
                                <img 
                                    src="<?php echo !empty($user['profile_image']) && file_exists($user['profile_image']) 
                                        ? htmlspecialchars($user['profile_image']) 
                                        : 'https://via.placeholder.com/150'; ?>"
                                    alt="Profile"
                                    class="w-32 h-32 object-cover"
                                    id="profile-image-preview"
                                />
                            </div>
                            <div class="flex absolute bottom-0 right-0">
                                <form id="image-upload-form" action="" method="POST" enctype="multipart/form-data">
                                    <label class="bg-orange-500 text-white p-2 rounded-full cursor-pointer hover:bg-orange-600 mr-2">
                                        <input 
                                            type="file" 
                                            class="hidden" 
                                            name="user_image"
                                            accept="image/*"
                                            id="profile-image-input"
                                        />
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <!-- Add a hidden submit button that we'll trigger via JS -->
                                    <button type="submit" id="submit-image" class="hidden">Upload</button>
                                </form>
                                <?php if(!empty($user['profile_image'])): ?>
                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to remove your profile image?');">
                                    <input type="hidden" name="remove_profile_image" value="yes">
                                    <button type="submit" class="bg-red-500 text-white p-2 rounded-full cursor-pointer hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($user['fullname']); ?></h1>
                            <p class="text-gray-600">Manage your account settings and preferences</p>
                        </div>
                    </div>

                    <!-- Tabs for different sections -->
                    <div class="border-b border-gray-200 mb-6">
                        <ul class="flex flex-wrap -mb-px">
                            <li class="mr-2">
                                <button id="profile-tab" class="inline-block p-4 rounded-t-lg tab-active" onclick="switchTab('profile')">
                                    <i class="fas fa-user-circle mr-2"></i>Profile Details
                                </button>
                            </li>
                            <li class="mr-2">
                                <button id="password-tab" class="inline-block p-4 rounded-t-lg text-gray-500 hover:text-orange-500" onclick="switchTab('password')">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Profile Details Tab -->
                    <div id="profile-content" class="tab-content">
                        <form id="profile-form" action="update_profile.php" method="POST" class="grid grid-cols-2 gap-6">
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

                            <!-- Address Fields (Reordered) -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">State</label>
                                <select name="state" id="state" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                    <option value="">Select State</option>
                                    <?php
                                    $states = [
                                        "Andhra Pradesh",
                                        "Tamil Nadu",
                                        "Kerala",
                                        "Karnataka",
                                        "Maharashtra",
                                        "Gujarat"
                                    ];
                                    foreach ($states as $state) {
                                        $selected = ($user['state'] == $state) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($state) . "\" $selected>" . htmlspecialchars($state) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">District</label>
                                <select name="district" id="district" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                    <option value="">Select District</option>
                                    <!-- Districts will be populated by JavaScript -->
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" 
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" 
                                       pattern="[A-Za-z\s]+" title="Please enter a valid city name"
                                       required>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Pin Code</label>
                                <input type="text" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" 
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" 
                                       pattern="[0-9]{6}" title="Please enter a valid 6-digit pincode"
                                       required>
                            </div>

                            <div class="col-span-2">
                                <button type="submit" class="w-full bg-orange-500 text-black py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors border border-black" id="save-button">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Change Tab -->
                    <div id="password-content" class="tab-content hidden">
                        <form id="password-form" action="" method="POST" class="space-y-6">
                            <input type="hidden" name="change_password" value="yes">
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                <p class="text-xs text-gray-500">Enter your current password to verify your identity</p>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                <p class="text-xs text-gray-500">Password must be at least 8 characters long</p>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="w-full p-2 border rounded focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                                <p id="password-match" class="text-xs text-gray-500">Passwords must match</p>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full bg-orange-500 text-black py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors border border-black">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle tab switching
        function switchTab(tabName) {
            const tabs = ['profile', 'password'];
            tabs.forEach(tab => {
                document.getElementById(`${tab}-content`).classList.add('hidden');
                document.getElementById(`${tab}-tab`).classList.remove('tab-active');
                document.getElementById(`${tab}-tab`).classList.add('text-gray-500');
            });
            
            document.getElementById(`${tabName}-content`).classList.remove('hidden');
            document.getElementById(`${tabName}-tab`).classList.add('tab-active');
            document.getElementById(`${tabName}-tab`).classList.remove('text-gray-500');
        }
        
        // Handle image preview and auto upload
        document.getElementById('profile-image-input').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-image-preview').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
                
                console.log('File selected, submitting form...');
                // Auto submit the form when image is selected
                document.getElementById('image-upload-form').submit();
            }
        });

        // Password matching validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchDisplay = document.getElementById('password-match');
            
            if (confirmPassword === '') {
                matchDisplay.textContent = 'Passwords must match';
                matchDisplay.className = 'text-xs text-gray-500';
            } else if (newPassword === confirmPassword) {
                matchDisplay.textContent = 'Passwords match!';
                matchDisplay.className = 'text-xs text-green-500';
            } else {
                matchDisplay.textContent = 'Passwords do not match!';
                matchDisplay.className = 'text-xs text-red-500';
            }
        });
        
        // Also check when new password changes
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value !== '') {
                // Trigger the confirm password input event to update matching status
                const event = new Event('input');
                confirmPassword.dispatchEvent(event);
            }
        });

        // Add this before your existing JavaScript code
        const stateDistricts = {
            "Andhra Pradesh": [
                "Anantapur", "Chittoor", "East Godavari", "Guntur", "Krishna", "Kurnool", "Prakasam", 
                "Srikakulam", "Visakhapatnam", "Vizianagaram", "West Godavari", "YSR Kadapa"
            ],
            "Tamil Nadu": [
                "Ariyalur", "Chennai", "Coimbatore", "Cuddalore", "Dharmapuri", "Dindigul", "Erode", 
                "Kanchipuram", "Kanyakumari", "Karur", "Krishnagiri", "Madurai", "Nagapattinam", 
                "Namakkal", "Nilgiris", "Perambalur", "Pudukkottai", "Ramanathapuram", "Salem", 
                "Sivaganga", "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", 
                "Tiruppur", "Tiruvallur", "Tiruvannamalai", "Tiruvarur", "Vellore", "Viluppuram", 
                "Virudhunagar"
            ],
            "Kerala": [
                "Alappuzha", "Ernakulam", "Idukki", "Kannur", "Kasaragod", "Kollam", "Kottayam", 
                "Kozhikode", "Malappuram", "Palakkad", "Pathanamthitta", "Thiruvananthapuram", 
                "Thrissur", "Wayanad"
            ],
            "Karnataka": [
                "Bagalkot", "Ballari", "Belagavi", "Bengaluru Rural", "Bengaluru Urban", "Bidar", 
                "Chamarajanagar", "Chikballapur", "Chikkamagaluru", "Chitradurga", "Dakshina Kannada", 
                "Davanagere", "Dharwad", "Gadag", "Hassan", "Haveri", "Kalaburagi", "Kodagu", "Kolar", 
                "Koppal", "Mandya", "Mysuru", "Raichur", "Ramanagara", "Shivamogga", "Tumakuru", 
                "Udupi", "Uttara Kannada", "Vijayapura", "Yadgir"
            ],
            "Maharashtra": [
                "Ahmednagar", "Akola", "Amravati", "Aurangabad", "Beed", "Bhandara", "Buldhana", 
                "Chandrapur", "Dhule", "Gadchiroli", "Gondia", "Hingoli", "Jalgaon", "Jalna", "Kolhapur", 
                "Latur", "Mumbai City", "Mumbai Suburban", "Nagpur", "Nanded", "Nandurbar", "Nashik", 
                "Osmanabad", "Palghar", "Parbhani", "Pune", "Raigad", "Ratnagiri", "Sangli", "Satara", 
                "Sindhudurg", "Solapur", "Thane", "Wardha", "Washim", "Yavatmal"
            ],
            "Gujarat": [
                "Ahmedabad", "Amreli", "Anand", "Aravalli", "Banaskantha", "Bharuch", "Bhavnagar", 
                "Botad", "Chhota Udaipur", "Dahod", "Dang", "Devbhoomi Dwarka", "Gandhinagar", 
                "Gir Somnath", "Jamnagar", "Junagadh", "Kheda", "Kutch", "Mahisagar", "Mehsana", 
                "Morbi", "Narmada", "Navsari", "Panchmahal", "Patan", "Porbandar", "Rajkot", 
                "Sabarkantha", "Surat", "Surendranagar", "Tapi", "Vadodara", "Valsad"
            ]
        };

        // Function to update districts based on selected state
        function updateDistricts() {
            const stateSelect = document.getElementById('state');
            const districtSelect = document.getElementById('district');
            const selectedState = stateSelect.value;
            const currentDistrict = '<?php echo htmlspecialchars($user['district'] ?? ''); ?>';
            
            // Clear existing options
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            // Add new options based on selected state
            if (selectedState && stateDistricts[selectedState]) {
                stateDistricts[selectedState].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    if (district === currentDistrict) {
                        option.selected = true;
                    }
                    districtSelect.appendChild(option);
                });
            }
        }

        // Add this to your existing document.addEventListener('DOMContentLoaded', ...)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize districts dropdown
            updateDistricts();
            
            // Add event listener for state change
            document.getElementById('state').addEventListener('change', updateDistricts);
        });
    </script>
</body>
</html>