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

// Check admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = ""; // success, error, warning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $destination = "Uploads/"; 
    
    // Validate inputs
    if (empty($name) || empty($category) || $price <= 0) {
        $message = "Please fill in all required fields with valid values.";
        $messageType = "error";
    } elseif (!isset($_FILES["product_image"]) || $_FILES["product_image"]["error"] === UPLOAD_ERR_NO_FILE) {
        $message = "Please select an image for the product.";
        $messageType = "error";
    } else {
        $filename = basename($_FILES["product_image"]["name"]);
        $targetfilepath = $destination . time() . "_" . $filename;

        $allowtypes = ['jpg','jpeg','png','gif','webp'];
        $filetype = strtolower(pathinfo($targetfilepath, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowtypes)) {
            // Check file size (max 5MB)
            if ($_FILES["product_image"]["size"] > 5000000) {
                $message = "File size too large. Maximum size is 5MB.";
                $messageType = "error";
            } else {
                // Create Uploads directory if it doesn't exist
                if (!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }

                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetfilepath)) {
                    // Use prepared statement for security
                    $sql = "INSERT INTO STRBARAKSMENU (PRODUCTNAME, CATEGORY, PRICE, DESCRIPTION, IMAGEPATH)
                            VALUES (?, ?, ?, ?, ?)";
                    
                    $params = array($name, $category, $price, $description, $targetfilepath);
                    $stmt = sqlsrv_query($conn, $sql, $params);

                    if ($stmt) {
                        $message = "🎨 Product added successfully! Your new masterpiece is ready for the menu.";
                        $messageType = "success";
                        
                        // Clear form data after successful submission
                        $_POST = array();
                    } else {
                        $message = "Error adding product: " . print_r(sqlsrv_errors(), true);
                        $messageType = "error";
                    }
                } else {
                    $message = "Image upload failed. Please try again.";
                    $messageType = "error";
                }
            }
        } else {
            $message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF, WebP";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Product • Café Lumière</title>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Sawarabi Mincho Font for Japanese style -->
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
<style>
:root {
    --starry-night: #c48c39ff;
    --vangogh-yellow: #f4c542;
    --vangogh-blue: #4a8fe7;
    --cafe-cream: #f2e4b7;
    --artistic-brown: #8B4513; /* Dark brown for text */
    --olive-green: #6B8E23;
    --swirl-orange: #d2691e;
    --admin-purple: #8a2be2;
    --success-green: #28a745;
    --danger-red: #dc3545;
    --warning-orange: #fd7e14;
    --rose-border: #d89ca8;
}

/* BODY */
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

/* NAVBAR */
.admin-navbar {
    background: linear-gradient(135deg, #4B2E2E 0%, #3A1F1F 100%) !important; /* Dark brown gradient */
    backdrop-filter: blur(10px);
    border-bottom: 2px solid var(--vangogh-yellow);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.admin-brand {
    position: relative; /* needed for ::before */
    padding-left: 90px; /* same or slightly larger than logo width */
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif; 
    font-weight: 900;
    font-size: 1.8rem;
    color: var(--vangogh-yellow);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.admin-brand::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 80px;  /* increase size */
    height: 80px; /* increase size */
    background-image: url('Background/Logo.png'); /* Transparent PNG */
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
}


/* FORM CONTAINER */
.form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.price-input-group {
    display: flex;
    align-items: center;
}

.price-symbol {
    background: #fff;
    border: 2px solid var(--rose-border);
    border-right: none;
    padding: 0.75rem 1rem;
    border-radius: 12px 0 0 12px;
    color: var(--artistic-brown);
    font-weight: 600;
    font-size: 1rem;
}

.price-input-group .price-input {
    border-radius: 0 12px 12px 0;
    border-left: none;
    flex: 1;
}

/* FORM CARD */
.form-card {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%); 
    border-radius: 25px;
    border: 2px solid var(--rose-border);  
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                inset 0 0 40px rgba(216, 156, 168, 0.1); 
    padding: 3rem;
    position: relative;
    overflow: hidden;
}

/* FLOATING SAKURA PETALS */
.form-card::before,
.form-card::after {
    content: '🌸';
    position: absolute;
    top: -20px;
    font-size: 1.5rem;
    animation: floatPetals 12s linear infinite;
}

.form-card::after {
    left: 70%;
    font-size: 1.2rem;
    animation-delay: 6s;
}

@keyframes floatPetals {
  0% { transform: translateY(-50px) translateX(0) rotate(0deg); opacity: 0; }
  25% { opacity: 1; }
  50% { transform: translateY(150px) translateX(20px) rotate(90deg); }
  75% { opacity: 1; }
  100% { transform: translateY(300px) translateX(-20px) rotate(180deg); opacity: 0; }
}

/* FORM TITLE & SUBTITLE */
.form-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif; 
    font-weight: 700;
    font-size: 2.5rem;
    color: var(--artistic-brown);
    text-align: center;
    margin-bottom: 1rem;
}

.form-subtitle {
    font-family: 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    font-size: 1.1rem;
    text-align: center;
    margin-bottom: 2rem;
}

/* ALERTS */
.message-alert {
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 2px solid;
    backdrop-filter: blur(10px);
    animation: slideIn 0.5s ease;
}

.message-alert.success { color: #155724; }
.message-alert.error { color: #721c24; }
.message-alert.warning { color: #856404; }

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORM LABELS AND INPUTS */
.form-label { font-weight: 600; color: var(--artistic-brown); margin-bottom: 0.5rem; }
.form-control, .form-select, .form-textarea {
    background: #fff; 
    border: 2px solid var(--rose-border); 
    border-radius: 12px;
    color: var(--artistic-brown); 
    padding: 0.75rem 1rem; 
    font-size: 1rem;
}
.form-control:focus, .form-select:focus, .form-textarea:focus {
    border-color: #d2691e; 
    box-shadow: 0 0 0 0.25rem rgba(210, 105, 30, 0.25); 
    color: var(--artistic-brown);
    outline: none;
}
.form-control::placeholder, .form-textarea::placeholder { color: rgba(139, 69, 19, 0.5); }

/* IMAGE UPLOAD */
.image-upload-container {
    position: relative;
    border: 2px dashed var(--rose-border);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    background: #fff0f0;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-bottom: 1rem;
}
.image-upload-container:hover { background: #ffe4e1; }
.upload-icon { font-size: 3rem; color: var(--artistic-brown); margin-bottom: 1rem; }
.upload-text { color: var(--artistic-brown); margin-bottom: 0.5rem; }
.upload-hint { color: rgba(139,69,19,0.7); font-size: 0.9rem; }

/* IMAGE PREVIEW */
.image-preview { margin-top: 1rem; display: none; text-align: center; }
.preview-image {
    max-width: 100%;
    max-height: 300px;
    border-radius: 10px;
    border: 2px solid var(--rose-border);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* BUTTONS */
.btn-submit { 
    background: var(--rose-border); 
    color: var(--artistic-brown); 
    border-radius: 50px; 
    padding: 0.75rem 2rem; 
    font-weight: 700; 
    width: 100%; 
    border: none;
}
.btn-back { 
    background: #8B4513; 
    color: #fff; 
    border-radius: 50px; 
    padding: 0.75rem 2rem; 
    font-weight: 600; 
    border: none;
}

/* FORM FOOTER SPACING */
.form-footer {
    margin-top: 2rem; /* Added spacing under Add to Menu button */
}

/* MEDIA QUERIES */
@media (max-width: 768px) {
    .form-card { padding: 2rem 1.5rem; }
    .form-title { font-size: 2rem; }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
  <div class="container">
    <a class="navbar-brand admin-brand" href="admin_dashboard.php">Nukumori Cafe Admin</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto align-items-center">
        <span class="text-light me-3">
          <i class="fas fa-user-tie me-2"></i>
          <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light me-2">
          <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
        <a href="productlist.php" class="btn btn-back">
          <i class="fas fa-list me-1"></i>View Products
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="form-container">
  <div class="form-card">
    <h1 class="form-title">
      <i class="fas fa-plus-circle me-2"></i>
      Add New Product
    </h1>
    <p class="form-subtitle">"Create a new 'Nukumori' signature item for your café menu that evokes a sense of comfort and peace (Nukumori). Every product tells a story of simple, Japanese elegance."</p>
    
    <?php if ($message): ?>
      <div class="message-alert <?php echo $messageType; ?>">
        <div class="d-flex align-items-center">
          <?php if ($messageType === 'success'): ?>
            <i class="fas fa-check-circle fa-2x me-3" style="color: #28a745;"></i>
          <?php elseif ($messageType === 'error'): ?>
            <i class="fas fa-exclamation-triangle fa-2x me-3" style="color: #dc3545;"></i>
          <?php else: ?>
            <i class="fas fa-info-circle fa-2x me-3" style="color: #fd7e14;"></i>
          <?php endif; ?>
          <div>
            <h4 class="mb-1">
              <?php echo $messageType === 'success' ? 'Success!' : ($messageType === 'error' ? 'Error' : 'Notice'); ?>
            </h4>
            <p class="mb-0"><?php echo htmlspecialchars($message); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" id="addProductForm" novalidate>
      <div class="row">
        <div class="col-md-6 mb-4">
          <label class="form-label form-required">
            <i class="fas fa-utensils"></i>
            Product Name
          </label>
          <input type="text" 
                 name="name" 
                 class="form-control" 
                 value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                 required 
                 placeholder="e.g., Starry Night Latte"
                 maxlength="100">
        </div>
        
        <div class="col-md-6 mb-4">
  <label class="form-label form-required">
    <i class="fas fa-tags"></i>
    Category
  </label>
  <select name="category" class="form-select" required>
    <option value="" disabled selected>Select a category</option>
    <option value="Signature Tea Lattes" <?php if(isset($_POST['category']) && $_POST['category']=='Signature Tea Lattes') echo 'selected'; ?>>Signature Tea Lattes</option>
    <option value="Refreshing Sparkling Ades" <?php if(isset($_POST['category']) && $_POST['category']=='Refreshing Sparkling Ades') echo 'selected'; ?>>Refreshing Sparkling Ades</option>
    <option value="Specialty Coffee" <?php if(isset($_POST['category']) && $_POST['category']=='Specialty Coffee') echo 'selected'; ?>>Specialty Coffee</option>
    <option value="The White Series" <?php if(isset($_POST['category']) && $_POST['category']=='The White Series') echo 'selected'; ?>>The White Series</option>
    <option value="Modern Wagashi & Sweets" <?php if(isset($_POST['category']) && $_POST['category']=='Modern Wagashi & Sweets') echo 'selected'; ?>>Modern Wagashi & Sweets</option>
    <!-- Add more categories as needed -->
  </select>
</div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-4">
          <label class="form-label form-required">
            <i class="fas fa-tag"></i>
            Price
          </label>
          <div class="price-input-group">
            <span class="price-symbol">₱</span>
            <input type="number" 
                   name="price" 
                   class="form-control price-input" 
                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                   step="0.01" 
                   min="0.01" 
                   max="9999.99" 
                   required 
                   placeholder="0.00">
          </div>
        </div>
        
        <div class="col-md-12 mb-4">
          <label class="form-label">
            <i class="fas fa-align-left"></i>
            Description
          </label>
          <textarea name="description" 
                    class="form-control form-textarea" 
                    placeholder="Describe your product... Tell a story about its inspiration, ingredients, or unique qualities."
                    maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="form-label form-required">
          <i class="fas fa-image"></i>
          Product Image
        </label>
        
        <div class="image-upload-container" id="imageUploadContainer">
          <div class="upload-icon">
            <i class="fas fa-cloud-upload-alt"></i>
          </div>
          <div class="upload-text">
            Drag & drop your image here, or click to browse
          </div>
          <div class="upload-hint">
            JPG, PNG, GIF, or WebP • Max 5MB • Recommended: 800x600px
          </div>
          <input type="file" 
                 name="product_image" 
                 id="productImage" 
                 accept="image/*" 
                 required 
                 class="d-none"
                 onchange="previewImage(event)">
        </div>
        
        <div class="image-preview" id="imagePreview">
          <img id="previewImage" class="preview-image" src="" alt="Image preview">
          <div class="mt-2 text-center">
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="removeImage()">
              <i class="fas fa-times me-1"></i>Remove Image
            </button>
          </div>
        </div>
      </div>
      
      <button type="submit" class="btn btn-submit" id="submitBtn">
        <i class="fas fa-paint-brush me-2"></i>
        Add to Menu
      </button>
    </form>
    
    <div class="form-footer">
      <a href="productlist.php" class="btn btn-back">
        <i class="fas fa-arrow-left me-2"></i>
        Back to Product List
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const uploadContainer = document.getElementById('imageUploadContainer');
    const fileInput = document.getElementById('productImage');
    
    uploadContainer.addEventListener('click', function() {
      fileInput.click();
    });
    
    uploadContainer.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.classList.add('dragover');
    });
    
    uploadContainer.addEventListener('dragleave', function() {
      this.classList.remove('dragover');
    });
    
    uploadContainer.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
      
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        previewImage({ target: fileInput });
      }
    });
    
    // Form validation
    const form = document.getElementById('addProductForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
      let valid = true;
      const requiredFields = form.querySelectorAll('[required]');
      
      // Clear previous error states
      form.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
      });
      
      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          valid = false;
        }
      });
      
      // Check price validity
      const priceField = form.querySelector('input[name="price"]');
      if (priceField.value && parseFloat(priceField.value) <= 0) {
        priceField.classList.add('is-invalid');
        valid = false;
      }
      
      if (!valid) {
        e.preventDefault();
        showToast('Please fill in all required fields correctly.', 'error');
      } else {
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Product...';
        submitBtn.disabled = true;
      }
    });
    
    // Add real-time validation
    form.querySelectorAll('input, textarea').forEach(field => {
      field.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
          this.classList.remove('is-invalid');
        }
      });
    });
    
    // Character counter for description
    const descriptionField = form.querySelector('textarea[name="description"]');
    const descriptionCounter = document.createElement('small');
    descriptionCounter.className = 'form-text d-block mt-1 text-end';
    descriptionCounter.id = 'descriptionCounter';
    descriptionCounter.style.color = 'rgba(242, 228, 183, 0.6)';
    descriptionField.parentNode.appendChild(descriptionCounter);
    
    descriptionField.addEventListener('input', function() {
      const length = this.value.length;
      descriptionCounter.textContent = `${length}/500 characters`;
      descriptionCounter.style.color = length > 450 ? '#ffc107' : 'rgba(242, 228, 183, 0.6)';
    });
    
    // Trigger initial count
    descriptionField.dispatchEvent(new Event('input'));
  });
  
  // Category helper function
  function setCategory(category) {
    const categoryInput = document.getElementById('categoryInput');
    categoryInput.value = category;
    categoryInput.focus();
  }
  
  // Image preview function
  function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('previewImage');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      
      reader.onload = function(e) {
        preview.src = e.target.result;
        previewContainer.style.display = 'block';
      }
      
      reader.readAsDataURL(input.files[0]);
      
      // Validate file size
      if (input.files[0].size > 5000000) { // 5MB
        showToast('File size exceeds 5MB limit. Please choose a smaller image.', 'error');
        removeImage();
      }
    }
  }
  
  // Remove image function
  function removeImage() {
    const fileInput = document.getElementById('productImage');
    const previewContainer = document.getElementById('imagePreview');
    
    fileInput.value = '';
    previewContainer.style.display = 'none';
  }
  
  // Toast notification function
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
          ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;
    
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '1060';
    toastContainer.appendChild(toast);
    
    document.body.appendChild(toastContainer);
    
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
      toastContainer.remove();
    });
  }
  
  const productNameSuggestions = [
  'Nukumori Signature Matcha Latte',
  'Toasted Hojicha Comfort Tea',
  'Yuzu Honey Soothing Drink',
  'Sakura Blossom Daifuku',
  'Kinako Dusted Sweet Potato Cake',
  'Creamy Purin (Custard Pudding)',
  'Matcha Azuki Bean Pastry',
  'Seasonal Fruit Sando',
  'Classic Tamago Sando',
  'Black Sesame Kinako Milk'
];
  
  // Auto-suggest product name on focus
  const nameInput = document.querySelector('input[name="name"]');
  nameInput.addEventListener('focus', function() {
    if (!this.value) {
      const suggestion = productNameSuggestions[Math.floor(Math.random() * productNameSuggestions.length)];
      this.placeholder = `e.g., ${suggestion}`;
    }
  });
</script>
</body>
</html>