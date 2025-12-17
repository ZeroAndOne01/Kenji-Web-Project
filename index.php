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

// Handle add to cart via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    $productId = $_POST['product_id'];
    $productName = $_POST['product_name'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add product to cart or increment quantity
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += 1;
    } else {
        // Fetch product details from database
        $sql = "SELECT PRODUCTNAME, PRICE, IMAGEPATH FROM STRBARAKSMENU WHERE PRODUCTID = '$productId'";
        $stmt = sqlsrv_query($conn, $sql);
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $_SESSION['cart'][$productId] = [
                'name' => $row['PRODUCTNAME'],
                'price' => $row['PRICE'],
                'image' => $row['IMAGEPATH'],
                'quantity' => 1
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'cart_count' => count($_SESSION['cart']),
        'message' => "Added $productName to cart!"
    ]);
    exit;
}

// Get all categories for tabs
$catSql = "SELECT DISTINCT CATEGORY FROM STRBARAKSMENU ORDER BY CATEGORY";
$catStmt = sqlsrv_query($conn, $catSql);
$categories = [];
while ($catRow = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {
    $categories[] = $catRow['CATEGORY'];
}

// Get search query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
// Get selected tab
$selectedTab = isset($_GET['tab']) ? $_GET['tab'] : (count($categories) > 0 ? $categories[0] : '');

// Function to get items for a category
function getCategoryItems($conn, $category, $searchQuery = '') {
    if ($searchQuery !== '') {
        $like = "%$searchQuery%";
        $sql = "SELECT * FROM STRBARAKSMENU
                WHERE CATEGORY = '$category' 
                AND (PRODUCTNAME LIKE '$like' OR DESCRIPTION LIKE '$like')";
    } else {
        $sql = "SELECT * FROM STRBARAKSMENU
                WHERE CATEGORY = '$category'";
    }
    $stmt = sqlsrv_query($conn, $sql);
    
    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    return $items;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nukumori • Japanese Inspired Café</title>
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
  
  .welcome-card {
    background: linear-gradient(145deg, 
                var(--light-beige) 0%,  
                var(--soft-pink) 100%);
    border-radius: 25px;
    border: 2px solid var(--rose-border);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                inset 0 0 40px rgba(216, 156, 168, 0.1);
    padding: 3rem 2rem;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
  }
  
  .welcome-card::before {
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
  
  .dashboard-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    font-size: 3.5rem;
    color: var(--artistic-brown);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
  }
  
  .welcome-text {
    font-family: 'Noto Serif JP', serif;
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 2rem;
    color: var(--artistic-brown);
  }
  
  /* Category Tabs Container */
  .tabs-container {
    background: linear-gradient(145deg, 
                var(--light-beige) 0%,  
                var(--soft-pink) 100%);
    border-radius: 20px;
    padding: 2rem;
    border: 2px solid var(--rose-border);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    position: sticky;
    top: 20px;
    z-index: 100;
  }
  
  /* Tabs Navigation */
  .tabs-nav {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
    scrollbar-width: thin;
    scrollbar-color: var(--rose-border) transparent;
  }
  
  .tabs-nav::-webkit-scrollbar {
    height: 6px;
  }
  
  .tabs-nav::-webkit-scrollbar-track {
    background: transparent;
  }
  
  .tabs-nav::-webkit-scrollbar-thumb {
    background-color: var(--rose-border);
    border-radius: 3px;
  }
  
  .tab-button {
    flex: 0 0 auto;
    background: white;
    border: 2px solid var(--rose-border);
    border-radius: 50px;
    padding: 0.8rem 2rem;
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 600;
    color: var(--artistic-brown);
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
  }
  
  .tab-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
  }
  
  .tab-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(216, 156, 168, 0.3);
  }
  
  .tab-button.active {
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    border-color: transparent;
    color: var(--artistic-brown);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(216, 156, 168, 0.4);
  }
  
  .tab-button.active::before {
    opacity: 1;
  }
  
  /* Tab Content */
  .tab-content {
    min-height: 500px;
  }
  
  .category-content {
    display: none;
    animation: fadeIn 0.5s ease;
  }
  
  .category-content.active {
    display: block;
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Category Header */
  .category-header {
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
  }
  
  .category-header h2 {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-size: 2.5rem;
    color: var(--artistic-brown);
    display: inline-block;
    padding: 0 2rem;
    position: relative;
  }
  
  .category-header h2::before,
  .category-header h2::after {
    content: '• • •';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rose-border);
    font-size: 1.5rem;
  }
  
  .category-header h2::before {
    left: -40px;
  }
  
  .category-header h2::after {
    right: -40px;
  }
  
  /* Menu Cards */
  .menu-card {
    background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.9) 0%, 
                rgba(255, 255, 255, 0.8) 100%);
    border-radius: 20px;
    padding: 1.5rem;
    border: 2px solid var(--rose-border);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }
  
  .menu-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rose-border), var(--starry-night));
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .menu-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(216, 156, 168, 0.3);
    border-color: var(--vangogh-yellow);
  }
  
  .menu-card:hover::before {
    opacity: 1;
  }
  
  .menu-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid var(--rose-border);
    transition: all 0.3s ease;
  }
  
  .menu-card:hover .menu-img {
    transform: scale(1.05);
    border-color: var(--artistic-brown);
  }
  
  .menu-card h5 {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    color: var(--artistic-brown);
    margin: 1rem 0 0.5rem;
    font-size: 1.3rem;
  }
  
  .menu-card .description {
    color: #5a3e1b;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    min-height: 60px;
  }
  
  .price-tag {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--artistic-brown);
    position: relative;
  }
  
  .price-tag::before {
    content: '₱';
    font-weight: 700;
    margin-right: 2px;
  }
  
  .btn-add-to-cart {
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    color: var(--artistic-brown);
    border: none;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
    position: relative;
    overflow: hidden;
  }
  
  .btn-add-to-cart:hover {
    background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
  }
  
  .btn-add-to-cart:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  .btn-add-to-cart.adding {
    background: linear-gradient(135deg, var(--vangogh-yellow), var(--starry-night));
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
  
  .btn-admin {
    background: linear-gradient(135deg, var(--artistic-brown), #6B4226);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
  }
  
  .btn-cart {
    background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    position: relative;
  }
  
  .user-greeting {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    color: var(--cafe-cream);
    font-weight: 600;
  }
  
  .cart-badge {
    background: linear-gradient(135deg, var(--vangogh-yellow), var(--starry-night));
    color: var(--artistic-brown);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    position: absolute;
    top: -5px;
    right: -5px;
  }
  
  /* Search Box */
  .search-box {
    max-width: 600px;
    margin: 0 auto;
    position: relative;
  }
  
  .search-box input {
    background: white;
    border: 2px solid var(--rose-border);
    border-radius: 50px;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    color: var(--artistic-brown);
    box-shadow: 0 5px 15px rgba(216, 156, 168, 0.2);
  }
  
  .search-box input:focus {
    border-color: var(--vangogh-yellow);
    box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
  }
  
  .search-box button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    color: var(--artistic-brown);
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .search-box button:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 8px 20px rgba(216, 156, 168, 0.4);
  }
  
  /* Footer */
  .footer {
    background: linear-gradient(135deg, #4B2E2E 0%, #3A1F1F 100%);
    border-top: 2px solid var(--vangogh-yellow);
    padding: 2rem 0;
    margin-top: 4rem;
    color: var(--cafe-cream);
  }
  
  .footer-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-size: 1.8rem;
    color: var(--vangogh-yellow);
    margin-bottom: 1.5rem;
  }
  
  /* Notification System */
  #notification-container {
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 9999;
    max-width: 350px;
  }
  
  .notification {
    background: linear-gradient(145deg, 
                var(--light-beige) 0%,  
                var(--soft-pink) 100%);
    border: 2px solid var(--rose-border);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    transform: translateX(400px);
    transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    overflow: hidden;
  }
  
  .notification.show {
    transform: translateX(0);
  }
  
  .notification.hide {
    transform: translateX(400px);
  }
  
  .notification::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 5px;
    background: linear-gradient(to bottom, var(--rose-border), var(--vangogh-yellow));
  }
  
  .notification-icon {
    font-size: 2rem;
    color: var(--artistic-brown);
    flex-shrink: 0;
  }
  
  .notification-content {
    flex-grow: 1;
  }
  
  .notification-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    color: var(--artistic-brown);
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
  }
  
  .notification-message {
    color: var(--artistic-brown);
    font-size: 0.9rem;
    opacity: 0.9;
  }
  
  .notification-close {
    background: transparent;
    border: none;
    color: var(--artistic-brown);
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
    flex-shrink: 0;
  }
  
  .notification-close:hover {
    background: rgba(139, 69, 19, 0.1);
  }
  
  .no-results {
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    border: 2px dashed var(--rose-border);
    color: var(--artistic-brown);
  }
  
  .no-results i {
    font-size: 3rem;
    color: var(--artistic-brown);
    margin-bottom: 1rem;
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
  
  /* Responsive */
  @media (max-width: 768px) {
    .dashboard-title {
      font-size: 2.5rem;
    }
    
    .tabs-nav {
      padding-bottom: 0.5rem;
    }
    
    .tab-button {
      padding: 0.6rem 1.5rem;
      font-size: 0.9rem;
    }
    
    .category-header h2 {
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
    
    #notification-container {
      top: 80px;
      right: 10px;
      left: 10px;
      max-width: none;
    }
  }
  
  @media (max-width: 480px) {
    .dashboard-title {
      font-size: 2rem;
    }
    
    .tabs-container {
      padding: 1rem;
    }
    
    .notification {
      padding: 1rem;
    }
  }
</style>
</head>
<body>
<div id="sakura-container"></div>
<div id="notification-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark nukumori-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Nukumori Zen Cafe</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto align-items-center">
        <?php if(isset($_SESSION['user'])): ?>
          <span class="user-greeting me-3">
            <i class="fas fa-user me-1"></i>
            Hello, <?=htmlspecialchars($_SESSION['user']['username'])?>
          </span>
          <a href="logout.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
          </a>
          <?php if($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="btn btn-admin me-2">
              <i class="fas fa-crown me-1"></i>Admin
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-light me-2">
            <i class="fas fa-sign-in-alt me-1"></i>Login
          </a>
          <a href="register.php" class="btn btn-add-to-cart me-2">
            <i class="fas fa-user-plus me-1"></i>Register
          </a>
        <?php endif; ?>
        <a href="cart.php" class="btn btn-cart position-relative">
          <i class="fas fa-shopping-cart me-1"></i>Cart
          <span id="cart-count" class="cart-badge">
            <?=isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0?>
          </span>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="welcome-card text-center">
    <h1 class="dashboard-title">Nukumori Zen Café</h1>
    <p class="welcome-text">Experience the warmth and comfort of Japanese-inspired cuisine. Where every dish tells a story of simple elegance and traditional flavors.</p>
    
    <div class="search-box d-flex">
      <form method="GET" action="index.php" class="w-100">
        <input type="hidden" name="tab" value="<?=htmlspecialchars($selectedTab)?>">
        <input name="q" class="form-control" 
               placeholder="Search our menu for Japanese delights..."
               value="<?=htmlspecialchars($q)?>">
        <button class="btn btn-add-to-cart" type="submit">
          <i class="fas fa-search me-1"></i>Search
        </button>
      </form>
    </div>
  </div>

  <!-- Category Tabs -->
  <div class="tabs-container">
    <div class="tabs-nav">
      <?php foreach($categories as $index => $category): 
        // Icon mapping for categories
        $icons = [
          'modern wagashi & sweets' => '🍱',
          'signature tea lattes' => '🍵',
          'dessert' => '🍡',
          'specialty coffee' => '🍵',
          'main' => '🍜',
          'the white series' => '🥗',
          'refreshing sparklig ades' => '🧋'
        ];
        $icon = $icons[strtolower($category)] ?? '🍽️';
      ?>
        <div class="tab-button <?=$category === $selectedTab ? 'active' : ''?>" 
             data-category="<?=htmlspecialchars($category)?>">
          <span class="me-2"><?=$icon?></span>
          <?=htmlspecialchars(ucfirst($category))?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tab Content Area -->
  <div class="tab-content">
    <?php 
    $hasAnyResults = false;
    
    foreach($categories as $category): 
      $items = getCategoryItems($conn, $category, $q);
      if(empty($items) && $q !== '') continue;
      
      $hasAnyResults = true;
    ?>
      <div class="category-content <?=$category === $selectedTab ? 'active' : ''?>" 
           id="content-<?=htmlspecialchars($category)?>">
        <div class="category-header">
          <h2><?=htmlspecialchars(ucfirst($category))?></h2>
        </div>
        
        <?php if(empty($items)): ?>
          <div class="text-center py-5">
            <i class="fas fa-utensils fa-3x mb-3" style="color: var(--rose-border);"></i>
            <h4 style="color: var(--artistic-brown);">No items in this category yet</h4>
            <p style="color: var(--artistic-brown);">Check back soon for new additions!</p>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach($items as $row): 
              $id = $row['PRODUCTID'];
              $name = htmlspecialchars($row['PRODUCTNAME']);
              $price = number_format($row['PRICE'], 2);
              $img = htmlspecialchars($row['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80');
              $desc = htmlspecialchars($row['DESCRIPTION'] ?: 'A traditional Japanese delicacy prepared with care and authenticity.');
            ?>
              <div class="col-lg-4 col-md-6">
                <div class="menu-card h-100">
                  <img src="<?=$img?>" class="menu-img" alt="<?=$name?>">
                  <h5><?=$name?></h5>
                  <p class="description"><?=$desc?></p>
                  <div class="d-flex align-items-center justify-content-between mt-auto">
                    <div class="d-flex align-items-center">
                      <span class="price-tag"><?=$price?></span>
                    </div>
                    <button type="button" class="btn btn-add-to-cart add-to-cart-btn" 
                            data-product-id="<?=$id?>" 
                            data-product-name="<?=htmlspecialchars($name)?>">
                      <i class="fas fa-plus me-1"></i>Add to Cart
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    
    <?php if(!$hasAnyResults && $q !== ''): ?>
      <div class="no-results my-5">
        <i class="fas fa-search"></i>
        <h3 class="mb-3" style="color: var(--artistic-brown);">No Results Found</h3>
        <p>We couldn't find any menu items matching "<?=htmlspecialchars($q)?>".</p>
        <p>Try searching for something else or <a href="index.php" style="color: var(--artistic-brown); font-weight: 600;">browse all items</a>.</p>
      </div>
    <?php endif; ?>
  </div>

  <footer class="footer text-center">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6 text-md-start">
          <h4 class="footer-title mb-3">Nukumori Zen Café</h4>
          <p class="mb-0">Experience the warmth of Japanese hospitality and culinary tradition.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
          <p class="mb-0">
            <i class="fas fa-map-marker-alt text-warning me-2"></i>
            JP Laurel St. Nasugbu, Batangas
          </p>
          <p class="mb-0">
            <i class="fas fa-clock text-warning me-2"></i>
            Open Daily: 7AM - 10PM
          </p>
        </div>
      </div>
      <hr class="my-3" style="border-color: rgba(244, 197, 66, 0.2);">
      <div class="row">
        <div class="col-12">
          <p class="mb-0">
            <small>
              &copy; <?php echo date('Y'); ?> Nukumori ZenCafé. All prices in Philippine Peso (₱).
            </small>
          </p>
        </div>
      </div>
    </div>
  </footer>
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
    
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const categoryContents = document.querySelectorAll('.category-content');
    const tabInput = document.querySelector('input[name="tab"]');
    
    // Function to switch tabs
    function switchTab(category) {
      // Update active tab
      tabButtons.forEach(tab => {
        if (tab.dataset.category === category) {
          tab.classList.add('active');
        } else {
          tab.classList.remove('active');
        }
      });
      
      // Update active content
      categoryContents.forEach(content => {
        if (content.id === `content-${category}`) {
          content.classList.add('active');
        } else {
          content.classList.remove('active');
        }
      });
      
      // Update hidden input for form submission
      if (tabInput) {
        tabInput.value = category;
      }
      
      // Update URL without reloading
      const url = new URL(window.location);
      url.searchParams.set('tab', category);
      history.replaceState({}, '', url);
    }
    
    // Add click event to tabs
    tabButtons.forEach(tab => {
      tab.addEventListener('click', function() {
        const category = this.dataset.category;
        switchTab(category);
      });
    });
    
    // Initialize with first tab if no tab is active
    if (!document.querySelector('.tab-button.active') && tabButtons.length > 0) {
      switchTab(tabButtons[0].dataset.category);
    }
    
    // Notification System
    const notificationContainer = document.getElementById('notification-container');
    let notificationTimeout;
    
    function showNotification(title, message, type = 'success') {
      // Clear existing notifications after animation
      const existingNotifications = notificationContainer.querySelectorAll('.notification');
      if (existingNotifications.length >= 3) {
        existingNotifications[0].remove();
      }
      
      // Create notification
      const notification = document.createElement('div');
      notification.className = 'notification';
      
      const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
      };
      
      notification.innerHTML = `
        <div class="notification-icon">
          <i class="${icons[type] || 'fas fa-bell'}"></i>
        </div>
        <div class="notification-content">
          <div class="notification-title">${title}</div>
          <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close">
          <i class="fas fa-times"></i>
        </button>
      `;
      
      notificationContainer.appendChild(notification);
      
      // Add close button functionality
      const closeBtn = notification.querySelector('.notification-close');
      closeBtn.addEventListener('click', function() {
        notification.classList.remove('show');
        notification.classList.add('hide');
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 500);
      });
      
      // Show with animation
      setTimeout(() => {
        notification.classList.add('show');
      }, 10);
      
      // Auto-hide after 5 seconds
      const hideTimeout = setTimeout(() => {
        notification.classList.remove('show');
        notification.classList.add('hide');
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 500);
      }, 5000);
      
      // Store timeout reference for cleanup
      notification.hideTimeout = hideTimeout;
    }
    
    // Add to Cart functionality with AJAX
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const cartCount = document.getElementById('cart-count');
    
    addToCartButtons.forEach(button => {
      button.addEventListener('click', async function() {
        const productId = this.dataset.productId;
        const productName = this.dataset.productName;
        const originalText = this.innerHTML;
        const originalClass = this.className;
        
        // Disable button and show loading state
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
        this.className = 'btn btn-add-to-cart adding';
        
        try {
          // Send AJAX request
          const response = await fetch('', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              'ajax_add_to_cart': '1',
              'product_id': productId,
              'product_name': productName
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            // Update cart count
            cartCount.textContent = result.cart_count;
            
            // Show success notification
            showNotification('Added to Cart!', result.message, 'success');
            
            // Add visual feedback to button
            this.innerHTML = '<i class="fas fa-check me-1"></i>Added!';
            
            // Reset button after 1.5 seconds
            setTimeout(() => {
              this.disabled = false;
              this.innerHTML = originalText;
              this.className = originalClass;
            }, 1500);
          }
        } catch (error) {
          console.error('Error:', error);
          
          // Show error notification
          showNotification('Error', 'Failed to add item to cart. Please try again.', 'error');
          
          // Reset button
          setTimeout(() => {
            this.disabled = false;
            this.innerHTML = originalText;
            this.className = originalClass;
          }, 1500);
        }
      });
    });
    
    // Make tabs container sticky on scroll
    const tabsContainer = document.querySelector('.tabs-container');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      
      if (scrollTop > 200) {
        tabsContainer.style.position = 'sticky';
        tabsContainer.style.top = '0';
        tabsContainer.style.zIndex = '1000';
      } else {
        tabsContainer.style.position = 'relative';
      }
      
      lastScrollTop = scrollTop;
    });
    
    // Add animation to menu cards on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);
    
    // Observe all menu cards
    document.querySelectorAll('.menu-card').forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(card);
    });
    
    // Handle keyboard navigation for tabs
    document.addEventListener('keydown', function(e) {
      const activeTab = document.querySelector('.tab-button.active');
      if (!activeTab) return;
      
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        e.preventDefault();
        const tabs = Array.from(tabButtons);
        const currentIndex = tabs.indexOf(activeTab);
        let nextIndex;
        
        if (e.key === 'ArrowRight') {
          nextIndex = (currentIndex + 1) % tabs.length;
        } else {
          nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
        }
        
        const nextCategory = tabs[nextIndex].dataset.category;
        switchTab(nextCategory);
      }
    });
  });
</script>
</body>
</html>