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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch statistics from database
$todaySales = 0;
$totalProducts = 0;
$pendingOrders = 0;
$monthRevenue = 0;

try {
    // Get today's sales
    $todaySql = "SELECT SUM(TOTALAMOUNT) as today_sales 
                 FROM TRANSACTIONS 
                 WHERE CAST(CREATEDATE AS DATE) = CAST(GETDATE() AS DATE)";
    $todayResult = sqlsrv_query($conn, $todaySql);
    if ($todayResult !== false) {
        $todayRow = sqlsrv_fetch_array($todayResult, SQLSRV_FETCH_ASSOC);
        $todaySales = $todayRow['today_sales'] ?? 0;
    }
    
    // Get total products
    $productsSql = "SELECT COUNT(*) as total_products FROM STRBARAKSMENU";
    $productsResult = sqlsrv_query($conn, $productsSql);
    if ($productsResult !== false) {
        $productsRow = sqlsrv_fetch_array($productsResult, SQLSRV_FETCH_ASSOC);
        $totalProducts = $productsRow['total_products'] ?? 0;
    }
    
    // Get pending orders (transactions with 'Pending' status)
    $pendingSql = "SELECT COUNT(*) as pending_orders 
                   FROM TRANSACTIONS 
                   WHERE STATUS = 'Pending'";
    $pendingResult = sqlsrv_query($conn, $pendingSql);
    if ($pendingResult !== false) {
        $pendingRow = sqlsrv_fetch_array($pendingResult, SQLSRV_FETCH_ASSOC);
        $pendingOrders = $pendingRow['pending_orders'] ?? 0;
    }
    
    // Get month revenue
    $monthSql = "SELECT SUM(TOTALAMOUNT) as month_revenue 
                 FROM TRANSACTIONS 
                 WHERE MONTH(CREATEDATE) = MONTH(GETDATE()) 
                 AND YEAR(CREATEDATE) = YEAR(GETDATE())";
    $monthResult = sqlsrv_query($conn, $monthSql);
    if ($monthResult !== false) {
        $monthRow = sqlsrv_fetch_array($monthResult, SQLSRV_FETCH_ASSOC);
        $monthRevenue = $monthRow['month_revenue'] ?? 0;
    }
} catch (Exception $e) {
    // Silently fail - statistics will show 0
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard • Nukumori Cafe</title>
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
    --admin-purple: #8a2be2;
    --warning-orange: #fd7e14;
    --rose-border: #d89ca8;
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
  
  .admin-navbar {
    background: linear-gradient(135deg, #4B2E2E 0%, #3A1F1F 100%) !important;
    backdrop-filter: blur(10px);
    border-bottom: 2px solid var(--vangogh-yellow);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  }
  
  .admin-brand {
    position: relative;
    padding-left: 90px;
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
    width: 80px;
    height: 80px;
    background-image: url('Background/Logo.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
  }
  
  .dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  .welcome-card {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
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
  
  .admin-greeting {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
  }
  
  .admin-role {
    color: var(--artistic-brown);
    font-weight: 600;
    font-size: 1.1rem;
    opacity: 0.9;
  }
  
  .dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
  }
  
  .admin-card {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
    border-radius: 20px;
    padding: 2rem;
    border: 2px solid var(--rose-border);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }
  
  .admin-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rose-border), var(--starry-night));
    opacity: 0.7;
    transition: opacity 0.3s ease;
  }
  
  .admin-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(216, 156, 168, 0.3);
    border-color: var(--starry-night);
  }
  
  .admin-card:hover::before {
    opacity: 1;
  }
  
  .card-icon {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    color: var(--artistic-brown);
    background: none;
    -webkit-background-clip: unset;
    background-clip: unset;
  }
  
  .card-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    color: var(--artistic-brown);
    font-size: 1.5rem;
    margin-bottom: 1rem;
  }
  
  .card-description {
    color: var(--artistic-brown);
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    min-height: 80px;
    opacity: 0.9;
  }
  
  .btn-admin {
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    color: var(--artistic-brown);
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
  }
  
  .btn-admin:hover {
    background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
    color: var(--artistic-brown);
  }
  
  .btn-back {
    background: linear-gradient(135deg, #8B4513, #a0522d);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
  }
  
  .btn-back:hover {
    background: linear-gradient(135deg, #a0522d, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
    color: white;
  }
  
  .btn-outline-light {
    border-color: var(--vangogh-yellow);
    color: var(--vangogh-yellow);
  }
  
  .btn-outline-light:hover {
    background-color: var(--vangogh-yellow);
    color: var(--artistic-brown);
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid var(--rose-border);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--starry-night);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--artistic-brown);
    margin-bottom: 0.5rem;
  }
  
  .stat-label {
    color: var(--artistic-brown);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
  }
  
  .stat-icon {
    font-size: 2rem;
    color: var(--rose-border);
    margin-bottom: 0.5rem;
  }
  
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
    .dashboard-title {
      font-size: 2.5rem;
    }
    
    .dashboard-cards {
      grid-template-columns: 1fr;
    }
    
    .welcome-card {
      padding: 2rem 1rem;
    }
    
    .admin-brand {
      font-size: 1.5rem;
      padding-left: 70px;
    }
    
    .admin-brand::before {
      width: 60px;
      height: 60px;
    }
  }
  
  @media (max-width: 480px) {
    .dashboard-title {
      font-size: 2rem;
    }
    
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>
</head>

<body>
<div id="sakura-container"></div>
<nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
  <div class="container">
    <a class="navbar-brand admin-brand" href="index.php">Nukumori Cafe Admin</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto align-items-center">
        <span class="admin-greeting me-3">
          <i class="fas fa-user-tie me-2"></i>
          <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light me-2">
          <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
        <a href="index.php" class="btn btn-back">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>
<div class="dashboard-container">
  <div class="welcome-card text-center">
    <h1 class="dashboard-title">管理ダッシュボード<br><small style="font-size: 1.5rem;">Admin Dashboard</small></h1>
    <p class="welcome-text">温もりの空間を管理してください。売上を監視し、メニューを更新し、お客様の体験を大切にしましょう。</p>
    <p class="welcome-text">Manage your space of warmth. Monitor sales, update menus, and cherish each customer's experience.</p>
    
    <div class="admin-greeting">
      <i class="fas fa-user-tie me-2"></i>
      ようこそ、<?php echo htmlspecialchars($_SESSION['user']['username']); ?>さん
    </div>
    <div class="admin-role">
      <i class="fas fa-crown me-1"></i>
      管理者 (Administrator)
    </div>
    
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-coins"></i>
        </div>
        <div class="stat-value" id="todaySales">₱<?php echo number_format($todaySales, 2); ?></div>
        <div class="stat-label">今日の売上<br>Today's Sales</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-utensils"></i>
        </div>
        <div class="stat-value" id="totalProducts"><?php echo $totalProducts; ?></div>
        <div class="stat-label">メニューアイテム<br>Menu Items</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value" id="pendingOrders"><?php echo $pendingOrders; ?></div>
        <div class="stat-label">保留中の注文<br>Pending Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-value" id="monthRevenue">₱<?php echo number_format($monthRevenue, 2); ?></div>
        <div class="stat-label">月間収入<br>Month Revenue</div>
      </div>
    </div>
  </div>

  <div class="dashboard-cards">
    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-utensils"></i>
      </div>
      <h3 class="card-title">メニュー管理<br><small>Menu Management</small></h3>
      <p class="card-description">
        メニューアイテムを閲覧、追加、編集、削除します。お客様に新鮮で魅力的な温もり体験を提供しましょう。
      </p>
      <a href="productlist.php" class="btn btn-admin">
        <i class="fas fa-edit me-1"></i>商品を管理<br>Manage Products
      </a>
    </div>

    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <h3 class="card-title">売上分析<br><small>Sales Analytics</small></h3>
      <p class="card-description">
        詳細な売上レポートを確認し、トレンドを分析し、データに基づいた意思決定を行いましょう。
      </p>
      <a href="reports.php" class="btn btn-admin">
        <i class="fas fa-chart-bar me-1"></i>レポートを見る<br>View Reports
      </a>
    </div>

    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-shopping-cart"></i>
      </div>
      <h3 class="card-title">注文管理<br><small>Order Management</small></h3>
      <p class="card-description">
        お客様の注文を追跡・管理し、注文状況を更新して円滑な注文履行を確保しましょう。
      </p>
      <a href="orders.php" class="btn btn-admin">
        <i class="fas fa-list-alt me-1"></i>注文を管理<br>Manage Orders
      </a>
    </div>

    <div class="admin-card">
      <div class="card-icon">
        <i class="fas fa-users-cog"></i>
      </div>
      <h3 class="card-title">スタッフ管理<br><small>Staff Management</small></h3>
      <p class="card-description">
        スタッフアカウントを管理し、役割を割り当て、チームのパフォーマンスを監視してカフェの卓越性を維持しましょう。
      </p>
      <a href="accounts.php" class="btn btn-admin">
        <i class="fas fa-user-friends me-1"></i>スタッフを管理<br>Manage Staff
      </a>
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
    
    // Add hover effects to cards
    const cards = document.querySelectorAll('.admin-card');
    cards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.zIndex = '10';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.zIndex = '';
      });
    });
    
    // Add hover effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        const icon = this.querySelector('.stat-icon i');
        icon.style.transform = 'scale(1.2)';
        icon.style.transition = 'transform 0.3s ease';
      });
      
      card.addEventListener('mouseleave', function() {
        const icon = this.querySelector('.stat-icon i');
        icon.style.transform = 'scale(1)';
      });
    });
    
    // Animate the statistics numbers
    function animateValue(element, start, end, duration, prefix = '', suffix = '') {
      let startTimestamp = null;
      const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        if (prefix.includes('₱')) {
          element.textContent = prefix + value.toLocaleString(undefined, {minimumFractionDigits: 2}) + suffix;
        } else {
          element.textContent = prefix + value.toLocaleString() + suffix;
        }
        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };
      window.requestAnimationFrame(step);
    }
    
    // Get current values from the page
    const todaySalesEl = document.getElementById('todaySales');
    const totalProductsEl = document.getElementById('totalProducts');
    const pendingOrdersEl = document.getElementById('pendingOrders');
    const monthRevenueEl = document.getElementById('monthRevenue');
    
    // Extract numeric values from the text
    const todaySales = parseFloat(todaySalesEl.textContent.replace('₱', '').replace(',', '')) || 0;
    const totalProducts = parseInt(totalProductsEl.textContent) || 0;
    const pendingOrders = parseInt(pendingOrdersEl.textContent) || 0;
    const monthRevenue = parseFloat(monthRevenueEl.textContent.replace('₱', '').replace(',', '')) || 0;
    
    // Animate the values (start from 0)
    setTimeout(() => {
      // Reset to 0 for animation
      todaySalesEl.textContent = '₱0';
      totalProductsEl.textContent = '0';
      pendingOrdersEl.textContent = '0';
      monthRevenueEl.textContent = '₱0';
      
      // Animate to actual values
      animateValue(todaySalesEl, 0, todaySales, 1500, '₱');
      animateValue(totalProductsEl, 0, totalProducts, 1500);
      animateValue(pendingOrdersEl, 0, pendingOrders, 1500);
      animateValue(monthRevenueEl, 0, monthRevenue, 2000, '₱');
    }, 1000);
    
    // Add subtle animation to card icons
    const cardIcons = document.querySelectorAll('.card-icon');
    cardIcons.forEach(icon => {
      icon.addEventListener('mouseenter', function() {
        this.style.transform = 'rotate(10deg) scale(1.1)';
        this.style.transition = 'transform 0.3s ease';
      });
      
      icon.addEventListener('mouseleave', function() {
        this.style.transform = 'rotate(0deg) scale(1)';
      });
    });
  });
</script>
</body>
</html>