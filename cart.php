<?php
session_start();

// Database connection
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

// Handle cart actions
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'update') {
        foreach($_POST['qty'] as $i => $q) {
            $q = max(0, (int)$q);
            if($q === 0) { 
                unset($_SESSION['cart'][$i]); 
                $_SESSION['cart_message'] = 'Item removed from cart.';
            }
            else { 
                $_SESSION['cart'][$i]['quantity'] = $q;
                $_SESSION['cart_message'] = 'Cart updated successfully!';
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart'] ?? []);
    } elseif($_POST['action'] === 'clear') {
        unset($_SESSION['cart']);
        $_SESSION['success_message'] = 'Cart cleared successfully!';
    } elseif($_POST['action'] === 'remove' && isset($_POST['item_index'])) {
        $index = (int)$_POST['item_index'];
        if(isset($_SESSION['cart'][$index])) {
            $itemName = $_SESSION['cart'][$index]['name'];
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $_SESSION['cart_message'] = "$itemName removed from cart.";
        }
    }
    header("Location: cart.php");
    exit;
}

// Calculate totals
$total = 0;
$itemCount = 0;
$cartItems = $_SESSION['cart'] ?? [];

foreach($cartItems as $item) {
    $itemCount += $item['quantity'];
    $total += $item['price'] * $item['quantity'];
}

// Check for messages
$successMessage = '';
$cartMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['cart_message'])) {
    $cartMessage = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping Cart • Nukumori Café</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
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
    
    .btn-admin {
      background: linear-gradient(135deg, var(--artistic-brown), #6B4226);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
    }
    
    .btn-cart-nav {
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
    
    .cart-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .cart-header {
      background: linear-gradient(145deg, 
                  var(--light-beige) 0%,  
                  var(--soft-pink) 100%);
      border-radius: 25px;
      border: 2px solid var(--rose-border);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                  inset 0 0 40px rgba(216, 156, 168, 0.1);
      padding: 3rem 2rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
      text-align: center;
    }
    
    .cart-header::before {
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
    
    .cart-title {
      font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
      font-weight: 700;
      font-size: 3rem;
      color: var(--artistic-brown);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      margin-bottom: 1rem;
    }
    
    .cart-subtitle {
      font-family: 'Noto Serif JP', serif;
      font-size: 1.2rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto 2rem;
      color: var(--artistic-brown);
    }
    
    .cart-stats {
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
      margin-top: 1.5rem;
    }
    
    .stat-item {
      text-align: center;
      padding: 1rem 1.5rem;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      border: 2px solid var(--rose-border);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .stat-item:hover {
      transform: translateY(-5px);
      border-color: var(--vangogh-yellow);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .stat-value {
      font-family: 'Playfair Display', serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--artistic-brown);
      margin-bottom: 0.3rem;
    }
    
    .stat-label {
      color: var(--artistic-brown);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      opacity: 0.8;
    }
    
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
    
    .message-alert.info {
      border-color: var(--vangogh-blue);
      background: linear-gradient(135deg, 
                  rgba(74, 143, 231, 0.1) 0%, 
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
    
    .empty-cart {
      text-align: center;
      padding: 4rem 2rem;
      background: linear-gradient(145deg, 
                  var(--light-beige) 0%,  
                  var(--soft-pink) 100%);
      border-radius: 25px;
      border: 2px dashed var(--rose-border);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1),
                  inset 0 0 40px rgba(216, 156, 168, 0.1);
    }
    
    .empty-cart-icon {
      font-size: 5rem;
      color: var(--rose-border);
      margin-bottom: 1.5rem;
      opacity: 0.7;
    }
    
    .empty-cart-title {
      font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
      color: var(--artistic-brown);
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    
    .btn-browse {
      background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
      color: var(--artistic-brown);
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-top: 1.5rem;
    }
    
    .btn-browse:hover {
      background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
      color: var(--artistic-brown);
    }
    
    .cart-items-container {
      background: linear-gradient(145deg, 
                  var(--light-beige) 0%,  
                  var(--soft-pink) 100%);
      border-radius: 25px;
      border: 2px solid var(--rose-border);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1),
                  inset 0 0 40px rgba(216, 156, 168, 0.1);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .cart-item {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      border: 2px solid var(--rose-border);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      display: flex;
      align-items: center;
      gap: 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .cart-item:hover {
      border-color: var(--vangogh-yellow);
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }
    
    .item-image {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 12px;
      border: 2px solid var(--rose-border);
      transition: all 0.3s ease;
    }
    
    .cart-item:hover .item-image {
      border-color: var(--artistic-brown);
      transform: scale(1.05);
    }
    
    .item-details {
      flex: 1;
    }
    
    .item-name {
      font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
      font-weight: 700;
      color: var(--artistic-brown);
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
    }
    
    .item-price {
      font-weight: 600;
      color: var(--artistic-brown);
      margin-bottom: 0.5rem;
      opacity: 0.9;
    }
    
    .item-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .quantity-input {
      background: white;
      border: 2px solid var(--rose-border);
      border-radius: 12px;
      color: var(--artistic-brown);
      width: 70px;
      text-align: center;
      padding: 0.5rem;
      font-weight: 600;
    }
    
    .quantity-input:focus {
      outline: none;
      border-color: var(--vangogh-yellow);
      box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
    }
    
    .btn-remove {
      background: linear-gradient(135deg, var(--danger-red), #c0392b);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
    }
    
    .btn-remove:hover {
      background: linear-gradient(135deg, #c0392b, var(--danger-red));
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
    }
    
    .item-subtotal {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: var(--artistic-brown);
      font-size: 1.2rem;
      min-width: 120px;
      text-align: right;
    }
    
    .cart-summary {
      background: linear-gradient(145deg, 
                  var(--light-beige) 0%,  
                  var(--soft-pink) 100%);
      border-radius: 25px;
      border: 2px solid var(--rose-border);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1),
                  inset 0 0 40px rgba(216, 156, 168, 0.1);
      padding: 2rem;
      margin-top: 2rem;
    }
    
    .summary-title {
      font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
      color: var(--artistic-brown);
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid rgba(139, 69, 19, 0.2);
    }
    
    .summary-label {
      color: var(--artistic-brown);
      opacity: 0.8;
    }
    
    .summary-value {
      font-weight: 600;
      color: var(--artistic-brown);
    }
    
    .summary-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 0;
      border-top: 2px solid var(--rose-border);
      margin-top: 1rem;
    }
    
    .total-label {
      font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
      color: var(--artistic-brown);
      font-size: 1.3rem;
      font-weight: 700;
    }
    
    .total-value {
      font-family: 'Playfair Display', serif;
      color: var(--artistic-brown);
      font-size: 2rem;
      font-weight: 900;
    }
    
    .cart-actions {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    
    .btn-update {
      background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
      color: var(--artistic-brown);
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
    }
    
    .btn-update:hover {
      background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
    }
    
    .btn-clear {
      background: linear-gradient(135deg, var(--danger-red), #c0392b);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }
    
    .btn-clear:hover {
      background: linear-gradient(135deg, #c0392b, var(--danger-red));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
    }
    
    .btn-checkout {
      background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 0.75rem 2rem;
      font-weight: 700;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(106, 142, 35, 0.3);
    }
    
    .btn-checkout:hover {
      background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(106, 142, 35, 0.4);
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
      .cart-title {
        font-size: 2.2rem;
      }
      
      .cart-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
      }
      
      .item-controls {
        justify-content: center;
      }
      
      .item-subtotal {
        text-align: center;
        min-width: auto;
      }
      
      .cart-actions {
        flex-direction: column;
      }
      
      .cart-actions button {
        width: 100%;
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
  </style>
</head>
<body>
<div id="sakura-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark nukumori-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Nukumori</a>
    
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
          <a href="register.php" class="btn btn-cart-nav me-2">
            <i class="fas fa-user-plus me-1"></i>Register
          </a>
        <?php endif; ?>
        <a href="cart.php" class="btn btn-cart-nav position-relative">
          <i class="fas fa-shopping-cart me-1"></i>Cart
          <?php if ($itemCount > 0): ?>
            <span class="cart-badge"><?php echo $itemCount; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="cart-container">
  <div class="cart-header">
    <h1 class="cart-title">
      <i class="fas fa-shopping-basket me-2"></i>
      ショッピングカート<br>
      <small style="font-size: 1.5rem;">Shopping Cart</small>
    </h1>
    <p class="cart-subtitle">Experience the warmth of your Japanese-inspired selections. Review and manage your order with care.</p>
    
    <div class="cart-stats">
      <div class="stat-item">
        <div class="stat-value"><?php echo $itemCount; ?></div>
        <div class="stat-label">Items</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">₱<?php echo number_format($total, 2); ?></div>
        <div class="stat-label">Subtotal</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?php echo count($cartItems); ?></div>
        <div class="stat-label">Unique Items</div>
      </div>
    </div>
  </div>

  <?php if ($successMessage): ?>
    <div class="message-alert success">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle fa-2x me-3" style="color: var(--success-green);"></i>
        <div>
          <h4 class="mb-1" style="color: var(--artistic-brown);">Success!</h4>
          <p class="mb-0" style="color: var(--artistic-brown);"><?php echo htmlspecialchars($successMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <?php if ($cartMessage): ?>
    <div class="message-alert info">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3" style="color: var(--vangogh-blue);"></i>
        <div>
          <h4 class="mb-1" style="color: var(--artistic-brown);">Cart Updated</h4>
          <p class="mb-0" style="color: var(--artistic-brown);"><?php echo htmlspecialchars($cartMessage); ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if(empty($cartItems)): ?>
    <div class="empty-cart">
      <div class="empty-cart-icon">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <h2 class="empty-cart-title">Your Cart is Empty</h2>
      <p class="cart-subtitle">Add some Japanese-inspired delicacies to create your perfect meal</p>
      <a href="index.php" class="btn btn-browse">
        <i class="fas fa-utensils me-2"></i>Browse Our Japanese Menu
      </a>
    </div>
  <?php else: ?>
    <form method="POST" id="cartForm">
      <input type="hidden" name="action" value="update" id="formAction">
      
      <div class="cart-items-container">
        <?php foreach($cartItems as $i => $it): 
          $subtotal = $it['price'] * $it['quantity'];
          $imageUrl = $it['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80';
        ?>
        <div class="cart-item" id="item-<?php echo $i; ?>">
          <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
               alt="<?php echo htmlspecialchars($it['name']); ?>" 
               class="item-image"
               onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80'">
          
          <div class="item-details">
            <h3 class="item-name"><?php echo htmlspecialchars($it['name']); ?></h3>
            <p class="item-price">₱<?php echo number_format($it['price'], 2); ?> each</p>
            
            <div class="item-controls">
              <input type="number" 
                     name="qty[<?php echo $i; ?>]" 
                     value="<?php echo $it['quantity']; ?>" 
                     min="0" 
                     max="99" 
                     class="quantity-input"
                     onchange="updateSubtotal(<?php echo $i; ?>, <?php echo $it['price']; ?>, this.value)">
              
              <button type="button" 
                      class="btn btn-remove" 
                      onclick="removeItem(<?php echo $i; ?>, '<?php echo addslashes($it['name']); ?>')">
                <i class="fas fa-trash me-1"></i>Remove
              </button>
            </div>
          </div>
          
          <div class="item-subtotal" id="subtotal-<?php echo $i; ?>">
            ₱<?php echo number_format($subtotal, 2); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="cart-summary">
        <h3 class="summary-title">
          <i class="fas fa-receipt me-2"></i>
          注文概要<br>
          <small style="font-size: 1rem;">Order Summary</small>
        </h3>
        
        <div class="summary-row">
          <span class="summary-label">Subtotal</span>
          <span class="summary-value">₱<?php echo number_format($total, 2); ?></span>
        </div>
        
        <div class="summary-row">
          <span class="summary-label">Service Charge (5%)</span>
          <span class="summary-value">₱<?php echo number_format($total * 0.05, 2); ?></span>
        </div>
        
        <div class="summary-total">
          <span class="total-label">Total Amount</span>
          <span class="total-value" id="grandTotal">₱<?php echo number_format($total * 1.05, 2); ?></span>
        </div>
      </div>

      <div class="cart-actions">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-update">
            <i class="fas fa-sync-alt me-2"></i>Update Cart
          </button>
          <button type="button" class="btn btn-clear" onclick="clearCart()">
            <i class="fas fa-trash-alt me-2"></i>Clear Cart
          </button>
        </div>
        
        <a href="checkout.php" class="btn btn-checkout">
          <i class="fas fa-arrow-right me-2"></i>Proceed to Checkout
        </a>
      </div>
    </form>
  <?php endif; ?>
</div>

<!-- Bootstrap JS -->
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
    
    // Update grand total
    updateGrandTotal();
    
    // Add animation to cart items
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach((item, index) => {
      item.style.animationDelay = (index * 0.1) + 's';
      item.style.animation = 'slideIn 0.5s ease forwards';
      item.style.opacity = '0';
    });
    
    // Add quantity increment/decrement buttons
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'quantity-wrapper';
      wrapper.style.display = 'flex';
      wrapper.style.alignItems = 'center';
      wrapper.style.gap = '0.5rem';
      
      // Create minus button
      const minusBtn = document.createElement('button');
      minusBtn.type = 'button';
      minusBtn.innerHTML = '<i class="fas fa-minus"></i>';
      minusBtn.className = 'btn btn-sm';
      minusBtn.style.padding = '0.25rem 0.5rem';
      minusBtn.style.background = 'var(--light-beige)';
      minusBtn.style.border = '2px solid var(--rose-border)';
      minusBtn.style.color = 'var(--artistic-brown)';
      minusBtn.style.borderRadius = '8px';
      minusBtn.onclick = function() {
        const current = parseInt(input.value);
        if (current > 0) {
          input.value = current - 1;
          input.dispatchEvent(new Event('change'));
        }
      };
      
      // Create plus button
      const plusBtn = document.createElement('button');
      plusBtn.type = 'button';
      plusBtn.innerHTML = '<i class="fas fa-plus"></i>';
      plusBtn.className = 'btn btn-sm';
      plusBtn.style.padding = '0.25rem 0.5rem';
      plusBtn.style.background = 'var(--light-beige)';
      plusBtn.style.border = '2px solid var(--rose-border)';
      plusBtn.style.color = 'var(--artistic-brown)';
      plusBtn.style.borderRadius = '8px';
      plusBtn.onclick = function() {
        const current = parseInt(input.value);
        if (current < 99) {
          input.value = current + 1;
          input.dispatchEvent(new Event('change'));
        }
      };
      
      // Wrap the input
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(minusBtn);
      wrapper.appendChild(input);
      wrapper.appendChild(plusBtn);
    });
  });
  
  // Update item subtotal
  function updateSubtotal(itemId, price, quantity) {
    const subtotal = price * quantity;
    const subtotalElement = document.getElementById(`subtotal-${itemId}`);
    if (subtotalElement) {
      subtotalElement.textContent = '₱' + subtotal.toFixed(2);
      updateGrandTotal();
    }
  }
  
  // Update grand total
  function updateGrandTotal() {
    let subtotal = 0;
    const subtotalElements = document.querySelectorAll('[id^="subtotal-"]');
    
    subtotalElements.forEach(element => {
      const value = parseFloat(element.textContent.replace('₱', '').replace(',', ''));
      if (!isNaN(value)) {
        subtotal += value;
      }
    });
    
    const serviceCharge = subtotal * 0.05;
    const grandTotal = subtotal + serviceCharge;
    
    const grandTotalElement = document.getElementById('grandTotal');
    if (grandTotalElement) {
      grandTotalElement.textContent = '₱' + grandTotal.toFixed(2);
    }
  }
  
  // Remove individual item
  function removeItem(itemId, itemName) {
    if (confirm(`Remove "${itemName}" from your cart?`)) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'remove';
      
      const indexInput = document.createElement('input');
      indexInput.type = 'hidden';
      indexInput.name = 'item_index';
      indexInput.value = itemId;
      
      form.appendChild(actionInput);
      form.appendChild(indexInput);
      document.body.appendChild(form);
      form.submit();
    }
  }
  
  // Clear entire cart
  function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?\n\nAll items will be removed.')) {
      const form = document.getElementById('cartForm');
      const actionInput = document.getElementById('formAction');
      actionInput.value = 'clear';
      form.submit();
    }
  }
  
  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Ctrl + U to update cart
    if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
      e.preventDefault();
      const updateBtn = document.querySelector('.btn-update');
      if (updateBtn) updateBtn.click();
    }
    
    // Ctrl + C to clear cart (with confirmation)
    if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
      e.preventDefault();
      clearCart();
    }
    
    // Ctrl + P to proceed to checkout
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
      e.preventDefault();
      const checkoutBtn = document.querySelector('.btn-checkout');
      if (checkoutBtn) checkoutBtn.click();
    }
  });
</script>
</body>
</html>