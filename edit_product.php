<?php
session_start();
$serverName="LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions=[
"Database"=>"SQLJourney",
"Uid"=>"",
"PWD"=>""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: productlist.php");
    exit;
}

// Get existing product
$sql = "SELECT * FROM STRBARAKSMENU WHERE PRODUCTID = '$id'";
$stmt = sqlsrv_query($conn, $sql);
$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$product) { 
    header("Location: productlist.php");
    exit;
}

// Get all unique categories for dropdown
$catSql = "SELECT DISTINCT CATEGORY FROM STRBARAKSMENU ORDER BY CATEGORY";
$catStmt = sqlsrv_query($conn, $catSql);
$categories = [];
while ($catRow = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {
    $categories[] = $catRow['CATEGORY'];
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $newImage = $product['IMAGEPATH'];
    if (!empty($_FILES['product_image']['name'])) {
        $destination = "Uploads/";
        $filename = basename($_FILES["product_image"]["name"]);
        $targetfilepath = $destination . time() . "_" . $filename;

        $allowtypes = ['jpg', 'jpeg', 'png', 'gif'];
        $filetype = pathinfo($targetfilepath, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowtypes)) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetfilepath)) {
                $newImage = $targetfilepath;
            } else {
                $message = "Image upload failed.";
            }
        }
    }

    $sql2 = "UPDATE STRBARAKSMENU
             SET PRODUCTNAME='$name',
                 CATEGORY='$category',
                 PRICE='$price',
                 DESCRIPTION='$description',
                 IMAGEPATH='$newImage'
             WHERE PRODUCTID='$id'";

    $stmt2 = sqlsrv_query($conn, $sql2);

    if ($stmt2) {
        $message = "success|Product updated successfully!";
        // Refresh product data
        $sql = "SELECT * FROM STRBARAKSMENU WHERE PRODUCTID = '$id'";
        $stmt = sqlsrv_query($conn, $sql);
        $product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    } else {
        $message = "error|Database update error: " . print_r(sqlsrv_errors(), true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Product • Nukumori Zen Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --starry-night: #c48c39ff;
            --vangogh-yellow: #f4c542;
            --vangogh-blue: #4a8fe7;
            --cafe-cream: #f2e4b7;
            --artistic-brown: #8B4513;
            --olive-green: #6B8E23;
            --swirl-orange: #d2691e;
            --rose-border: #d89ca8;
            --soft-pink: #ffe4e1;
            --light-beige: #f7e7d7;
            --success-green: #4CAF50;
            --danger-red: #e74c3c;
        }
        
        body {
            background: linear-gradient(135deg, 
                        rgba(214, 198, 182, 0.9) 0%,  
                        rgba(199, 225, 204, 0.7) 100%),
                        url('Background/background.gif') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Raleway', sans-serif;
            min-height: 100vh;
            color: var(--artistic-brown);
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(216, 156, 168, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(196, 140, 57, 0.1) 0%, transparent 40%);
            pointer-events: none;
            z-index: -1;
        }
        
        .nukumori-navbar {
            background: linear-gradient(135deg, #4B2E2E 0%, #3A1F1F 100%) !important;
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--vangogh-yellow);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-brand {
            position: relative;
            padding-left: 90px;
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif; 
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--vangogh-yellow);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-brand::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 80px;
            height: 80px;
            background-image: url('Background/Logo.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        
        .btn-outline-light {
            color: var(--cafe-cream);
            border-color: var(--cafe-cream);
        }
        
        .btn-outline-light:hover {
            background-color: rgba(242, 228, 183, 0.1);
            border-color: var(--vangogh-yellow);
            color: var(--vangogh-yellow);
        }
        
        .user-greeting {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            color: var(--cafe-cream);
            font-weight: 600;
        }
        
        /* Edit Page Container */
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Edit Card */
        .edit-card {
            background: linear-gradient(145deg, 
                        var(--light-beige) 0%,  
                        var(--soft-pink) 100%);
            border-radius: 25px;
            border: 2px solid var(--rose-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                        inset 0 0 40px rgba(216, 156, 168, 0.1);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .edit-card::before {
            content: '🌸';
            position: absolute;
            top: -20px;
            left: 30%;
            font-size: 1.5rem;
            animation: floatPetals 15s linear infinite;
        }
        
        @keyframes floatPetals {
            0% { transform: translateY(-50px) translateX(0) rotate(0deg); opacity: 0; }
            25% { opacity: 1; }
            50% { transform: translateY(150px) translateX(20px) rotate(90deg); }
            75% { opacity: 1; }
            100% { transform: translateY(300px) translateX(-20px) rotate(180deg); opacity: 0; }
        }
        
        .edit-title {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--artistic-brown);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        /* Form Labels */
        .form-label {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 600;
            color: var(--artistic-brown);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Form Controls */
        .form-control, .form-select, .form-textarea {
            background: white;
            border: 2px solid var(--rose-border);
            border-radius: 12px;
            color: var(--artistic-brown);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(216, 156, 168, 0.1);
        }
        
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: var(--artistic-brown);
            outline: none;
        }
        
        .form-control::placeholder, .form-textarea::placeholder {
            color: rgba(139, 69, 19, 0.5);
        }
        
        /* Image Preview */
        .image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
            border: 3px solid var(--rose-border);
            margin: 1rem 0;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .image-preview:hover {
            transform: scale(1.05);
            border-color: var(--vangogh-yellow);
        }
        
        /* File Input */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin: 1rem 0;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            height: 100%;
            width: 100%;
        }
        
        .file-input-btn {
            background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
            color: var(--artistic-brown);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .file-input-btn:hover {
            background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
        }
        
        /* Buttons */
        .btn-save {
            background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 142, 35, 0.3);
            width: 100%;
            margin-top: 1.5rem;
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 142, 35, 0.4);
        }
        
        .btn-back {
            background: linear-gradient(135deg, var(--artistic-brown), #6B4226);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #6B4226, var(--artistic-brown));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
            color: white;
        }
        
        /* Message Alerts */
        .message-alert {
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 2px solid;
            background: rgba(255, 255, 255, 0.9);
            animation: slideIn 0.5s ease;
        }
        
        .message-alert.success {
            border-color: var(--success-green);
            background: linear-gradient(135deg, 
                        rgba(76, 175, 80, 0.1) 0%, 
                        rgba(255, 255, 255, 0.9) 100%);
            color: var(--artistic-brown);
        }
        
        .message-alert.error {
            border-color: var(--danger-red);
            background: linear-gradient(135deg, 
                        rgba(231, 76, 60, 0.1) 0%, 
                        rgba(255, 255, 255, 0.9) 100%);
            color: var(--artistic-brown);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Sakura Petals Animation */
        .sakura-decoration {
            position: absolute;
            width: 20px;
            height: 20px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23d89ca8'%3E%3Cpath d='M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5s1.1-2.5 2.5-2.5 2.5 1.1 2.5 2.5-1.1 2.5-2.5 2.5z'/%3E%3C/svg%3E");
            background-size: contain;
            opacity: 0;
            animation: sakura-fall 15s linear infinite;
        }
        
        @keyframes sakura-fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        @media (max-width: 768px) {
            .edit-card {
                padding: 2rem 1.5rem;
            }
            
            .edit-title {
                font-size: 2rem;
            }
            
            .navbar-brand {
                font-size: 1.5rem;
                padding-left: 70px;
            }
            
            .navbar-brand::before {
                width: 60px;
                height: 60px;
            }
        }
        
        @media (max-width: 480px) {
            .edit-card {
                padding: 1.5rem 1rem;
            }
            
            .edit-title {
                font-size: 1.8rem;
            }
            
            .image-preview {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
<div id="sakura-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark nukumori-navbar">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Nukumori Zen Cafe Admin</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <span class="user-greeting me-3">
                    <i class="fas fa-user-tie me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
                <a href="admin_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="edit-container">
    <div class="edit-card">
        <h1 class="edit-title">
            <i class="fas fa-edit me-2"></i>
            商品の編集<br>
            <small style="font-size: 1.5rem;">Edit Product</small>
        </h1>
        
        <?php 
        if ($message): 
            list($type, $msg) = explode('|', $message, 2);
        ?>
            <div class="message-alert <?php echo $type; ?>">
                <div class="d-flex align-items-center">
                    <i class="fas fa-<?php echo $type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> fa-2x me-3" 
                       style="color: <?php echo $type === 'success' ? 'var(--success-green)' : 'var(--danger-red)'; ?>;"></i>
                    <div>
                        <h4 class="mb-1"><?php echo $type === 'success' ? 'Success!' : 'Error!'; ?></h4>
                        <p class="mb-0"><?php echo htmlspecialchars($msg); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="editProductForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Product Name</label>
                    <input class="form-control" type="text" name="name" 
                           value="<?php echo htmlspecialchars($product['PRODUCTNAME']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"
                                <?php echo ($category == $product['CATEGORY']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($category)); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new" data-bs-toggle="modal" data-bs-target="#newCategoryModal">+ Add New Category</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Price (₱)</label>
                    <input class="form-control" type="number" step="0.01" name="price" 
                           value="<?php echo htmlspecialchars($product['PRICE']); ?>" required
                           min="0" max="9999.99">
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control form-textarea" name="description" 
                              rows="4" style="resize: vertical;"><?php echo htmlspecialchars($product['DESCRIPTION']); ?></textarea>
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">Current Image</label>
                    <div class="text-center">
                        <img src="<?php echo htmlspecialchars($product['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80'); ?>" 
                             class="image-preview" 
                             alt="<?php echo htmlspecialchars($product['PRODUCTNAME']); ?>"
                             id="currentImagePreview">
                    </div>
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">Replace Image (Optional)</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-input-btn">
                            <i class="fas fa-camera me-1"></i>Choose New Image
                        </button>
                        <input type="file" name="product_image" accept="image/*" 
                               id="imageInput" onchange="previewImage(this)">
                    </div>
                    <small class="text-muted d-block mt-1">Allowed: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                </div>
                
                <div class="col-12 mb-3" id="newImagePreviewContainer" style="display: none;">
                    <label class="form-label">New Image Preview</label>
                    <div class="text-center">
                        <img src="#" class="image-preview" id="newImagePreview" alt="New Image Preview">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-save">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="productlist.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i>Back to Product List
            </a>
        </div>
    </div>
</div>

<!-- New Category Modal -->
<div class="modal fade" id="newCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: linear-gradient(145deg, var(--light-beige), var(--soft-pink)); border: 2px solid var(--rose-border);">
            <div class="modal-header">
                <h5 class="modal-title" style="color: var(--artistic-brown); font-family: 'Sawarabi Mincho';">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="newCategoryInput" class="form-control" placeholder="Enter new category name" style="border: 2px solid var(--rose-border);">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-back" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-save" onclick="addNewCategory()">Add Category</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create animated sakura petals
        const sakuraContainer = document.getElementById('sakura-container');
        const petalCount = 15;
        for (let i = 0; i < petalCount; i++) {
            const petal = document.createElement('div');
            petal.classList.add('sakura-decoration');
            petal.style.left = `${Math.random() * 100}%`;
            const size = Math.random() * 20 + 10;
            petal.style.width = `${size}px`;
            petal.style.height = `${size}px`;
            petal.style.animationDelay = `${Math.random() * 15}s`;
            petal.style.animationDuration = `${Math.random() * 10 + 10}s`;
            sakuraContainer.appendChild(petal);
        }
        
        // Form submission handler
        const form = document.getElementById('editProductForm');
        form.addEventListener('submit', function(e) {
            const saveBtn = this.querySelector('.btn-save');
            const originalText = saveBtn.innerHTML;
            
            // Show loading state
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            // Re-enable button after 5 seconds if still disabled (form didn't submit)
            setTimeout(() => {
                if (saveBtn.disabled) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            }, 5000);
        });
    });
    
    // Image preview function
    function previewImage(input) {
        const previewContainer = document.getElementById('newImagePreviewContainer');
        const preview = document.getElementById('newImagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
                
                // Add animation
                preview.style.transform = 'scale(0)';
                setTimeout(() => {
                    preview.style.transform = 'scale(1)';
                    preview.style.transition = 'transform 0.5s ease';
                }, 10);
            }
            
            reader.readAsDataURL(input.files[0]);
            
            // Validate file size (5MB limit)
            if (input.files[0].size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                input.value = '';
                previewContainer.style.display = 'none';
            }
        } else {
            previewContainer.style.display = 'none';
        }
    }
    
    // Add new category function
    function addNewCategory() {
        const newCategory = document.getElementById('newCategoryInput').value.trim();
        if (newCategory) {
            const categorySelect = document.querySelector('select[name="category"]');
            
            // Add new option
            const newOption = document.createElement('option');
            newOption.value = newCategory;
            newOption.textContent = newCategory.charAt(0).toUpperCase() + newCategory.slice(1);
            newOption.selected = true;
            
            // Insert before the "Add New Category" option
            const addNewOption = categorySelect.querySelector('option[value="new"]');
            categorySelect.insertBefore(newOption, addNewOption);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newCategoryModal'));
            modal.hide();
            
            // Clear input
            document.getElementById('newCategoryInput').value = '';
            
            // Show success message
            alert(`Category "${newCategory}" added successfully!`);
        } else {
            alert('Please enter a category name');
        }
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.querySelector('.btn-save').click();
        }
        
        // Esc to go back
        if (e.key === 'Escape') {
            window.location.href = 'productlist.php';
        }
    });
</script>
</body>
</html>