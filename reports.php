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

// Get filter parameters
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';
$filterType = isset($_GET['type']) ? $_GET['type'] : 'month'; // day, week, month

// Fetch sales data for charts
$salesData = [];
$labels = [];
$revenueData = [];
$transactionData = [];

if ($filterType === 'day') {
    // Get daily sales for the last 30 days
    $salesSql = "
        SELECT 
            CONVERT(VARCHAR, CREATEDATE, 23) as date,
            SUM(TOTALAMOUNT) as total,
            COUNT(*) as transactions
        FROM TRANSACTIONS 
        WHERE CREATEDATE >= DATEADD(day, -30, GETDATE())
        GROUP BY CONVERT(VARCHAR, CREATEDATE, 23)
        ORDER BY CONVERT(VARCHAR, CREATEDATE, 23)
    ";
} elseif ($filterType === 'week') {
    // Get weekly sales for the last 12 weeks - FIXED QUERY
    $salesSql = "
        SELECT 
            DATEPART(year, CREATEDATE) as year,
            DATEPART(week, CREATEDATE) as week_number,
            'Week ' + CAST(DATEPART(week, CREATEDATE) as VARCHAR) as label,
            SUM(TOTALAMOUNT) as total,
            COUNT(*) as transactions,
            MIN(CONVERT(DATE, CREATEDATE)) as start_date,
            MAX(CONVERT(DATE, CREATEDATE)) as end_date
        FROM TRANSACTIONS 
        WHERE CREATEDATE >= DATEADD(week, -12, GETDATE())
        GROUP BY DATEPART(year, CREATEDATE), DATEPART(week, CREATEDATE)
        ORDER BY DATEPART(year, CREATEDATE), DATEPART(week, CREATEDATE)
    ";
} else {
    // Get monthly sales for the last 12 months
    $salesSql = "
        SELECT 
            FORMAT(CREATEDATE, 'yyyy-MM') as month,
            FORMAT(CREATEDATE, 'MMM yyyy') as label,
            SUM(TOTALAMOUNT) as total,
            COUNT(*) as transactions
        FROM TRANSACTIONS 
        WHERE CREATEDATE >= DATEADD(month, -12, GETDATE())
        GROUP BY FORMAT(CREATEDATE, 'yyyy-MM'), FORMAT(CREATEDATE, 'MMM yyyy')
        ORDER BY FORMAT(CREATEDATE, 'yyyy-MM')
    ";
}

$salesResult = sqlsrv_query($conn, $salesSql);
if ($salesResult) {
    while ($row = sqlsrv_fetch_array($salesResult, SQLSRV_FETCH_ASSOC)) {
        $salesData[] = $row;
        
        // Format labels based on filter type
        if ($filterType === 'week') {
            // For weekly view, show date range
            if (isset($row['start_date']) && $row['start_date'] instanceof DateTime) {
                $startDate = $row['start_date']->format('M d');
                $endDate = isset($row['end_date']) && $row['end_date'] instanceof DateTime 
                          ? $row['end_date']->format('M d') 
                          : '';
                $labels[] = $row['label'] . "\n(" . $startDate . " - " . $endDate . ")";
            } else {
                $labels[] = $row['label'];
            }
        } else {
            $labels[] = $row['label'] ?? $row['date'] ?? $row['month'] ?? '';
        }
        
        $revenueData[] = floatval($row['total'] ?? 0);
        $transactionData[] = intval($row['transactions'] ?? 0);
    }
}

// Fetch statistics from database
$statsSql = "SELECT 
    COUNT(*) as total_transactions,
    SUM(TOTALAMOUNT) as total_revenue,
    AVG(TOTALAMOUNT) as avg_order_value,
    MIN(CREATEDATE) as first_sale,
    MAX(CREATEDATE) as last_sale
    FROM TRANSACTIONS";
$statsResult = sqlsrv_query($conn, $statsSql);
$stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);

// Get recent transactions
$txSql = "SELECT TOP 50 * FROM TRANSACTIONS ORDER BY CREATEDATE DESC";
$txStmt = sqlsrv_query($conn, $txSql);

// Get today's sales
$todaySql = "SELECT SUM(TOTALAMOUNT) as today_sales 
             FROM TRANSACTIONS 
             WHERE CAST(CREATEDATE AS DATE) = CAST(GETDATE() AS DATE)";
$todayResult = sqlsrv_query($conn, $todaySql);
$today = sqlsrv_fetch_array($todayResult, SQLSRV_FETCH_ASSOC);

// Get monthly sales
$monthSql = "SELECT SUM(TOTALAMOUNT) as month_sales 
             FROM TRANSACTIONS 
             WHERE MONTH(CREATEDATE) = MONTH(GETDATE()) 
             AND YEAR(CREATEDATE) = YEAR(GETDATE())";
$monthResult = sqlsrv_query($conn, $monthSql);
$month = sqlsrv_fetch_array($monthResult, SQLSRV_FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales Analytics • Nukumori Zen Cafe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Raleway:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP&family=Sawarabi+Mincho&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.0"></script>
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
    --success-green: #28a745;
    --info-blue: #17a2b8;
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
  
  .analytics-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  .page-header {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
    border-radius: 25px;
    border: 2px solid var(--rose-border);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                inset 0 0 40px rgba(216, 156, 168, 0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
  }
  
  .page-header::before {
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
  
  .page-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    font-size: 2.8rem;
    color: var(--artistic-brown);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
  }
  
  .page-subtitle {
    font-family: 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    opacity: 0.9;
  }
  
  .sales-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--rose-border);
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--starry-night);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }
  
  .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--artistic-brown);
  }
  
  .stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
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
  
  .trend-up { background: rgba(40, 167, 69, 0.2); color: #28a745; }
  .trend-down { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
  .trend-neutral { background: rgba(108, 117, 125, 0.2); color: #6c757d; }
  
  .charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
  }
  
  .chart-card {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
    border-radius: 20px;
    border: 2px solid var(--rose-border);
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }
  
  .chart-title {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    text-align: center;
  }
  
  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .chart-type-selector {
    display: flex;
    gap: 0.5rem;
  }
  
  .chart-type-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--rose-border);
    background: white;
    color: var(--artistic-brown);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Raleway', sans-serif;
  }
  
  .chart-type-btn.active {
    background: var(--rose-border);
    color: var(--artistic-brown);
    border-color: var(--starry-night);
  }
  
  .chart-type-btn:hover:not(.active) {
    background: rgba(216, 156, 168, 0.1);
  }
  
  .transactions-container {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
    border-radius: 20px;
    border: 2px solid var(--rose-border);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }
  
  .transaction-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 15px;
    border: 1px solid var(--rose-border);
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  
  .transaction-card:hover {
    transform: translateX(5px);
    border-color: var(--starry-night);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  }
  
  .transaction-header {
    background: linear-gradient(90deg, 
                rgba(216, 156, 168, 0.3) 0%, 
                rgba(196, 140, 57, 0.3) 100%);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .transaction-id {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    color: var(--artistic-brown);
    font-size: 1.2rem;
  }
  
  .transaction-amount {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-weight: 700;
    color: var(--artistic-brown);
    font-size: 1.4rem;
  }
  
  .transaction-body {
    padding: 1.5rem;
  }
  
  .transaction-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  
  .info-item {
    display: flex;
    flex-direction: column;
  }
  
  .info-label {
    font-size: 0.85rem;
    color: var(--artistic-brown);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.3rem;
    opacity: 0.7;
  }
  
  .info-value {
    font-weight: 600;
    color: var(--artistic-brown);
  }
  
  .items-table {
    width: 100%;
    color: var(--artistic-brown);
    border-collapse: separate;
    border-spacing: 0;
  }
  
  .items-table thead th {
    background: rgba(216, 156, 168, 0.2);
    color: var(--artistic-brown);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--rose-border);
  }
  
  .items-table tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(139, 69, 19, 0.1);
  }
  
  .items-table tbody tr:last-child td {
    border-bottom: none;
  }
  
  .items-table tbody tr:hover {
    background: rgba(216, 156, 168, 0.1);
  }
  
  .item-price {
    color: var(--starry-night);
    font-weight: 600;
  }
  
  .item-subtotal {
    color: var(--artistic-brown);
    font-weight: 700;
  }
  
  .grand-total-card {
    background: linear-gradient(135deg, 
                rgba(216, 156, 168, 0.2) 0%, 
                rgba(196, 140, 57, 0.2) 100%);
    border-radius: 20px;
    border: 2px solid var(--rose-border);
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
  }
  
  .grand-total-label {
    font-family: 'Noto Serif JP', serif;
    font-size: 1.2rem;
    color: var(--artistic-brown);
    margin-bottom: 0.5rem;
    opacity: 0.8;
  }
  
  .grand-total-value {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    font-size: 3rem;
    font-weight: 900;
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
  
  .date-filter-container {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
  }
  
  .date-filter, .type-filter {
    background: white;
    border: 2px solid var(--rose-border);
    border-radius: 12px;
    padding: 0.75rem;
    color: var(--artistic-brown);
    width: 200px;
    font-family: 'Raleway', sans-serif;
  }
  
  .date-filter:focus, .type-filter:focus {
    outline: none;
    border-color: var(--starry-night);
    box-shadow: 0 0 0 0.25rem rgba(196, 140, 57, 0.25);
  }
  
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--artistic-brown);
  }
  
  .empty-state i {
    font-size: 4rem;
    color: var(--starry-night);
    margin-bottom: 1rem;
    opacity: 0.7;
  }
  
  .btn-outline-light {
    border-color: var(--vangogh-yellow);
    color: var(--vangogh-yellow);
  }
  
  .btn-outline-light:hover {
    background-color: var(--vangogh-yellow);
    color: var(--artistic-brown);
  }
  
  .chart-wrapper {
    position: relative;
    height: 400px;
    width: 100%;
  }
  
  .export-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn-export {
    background: var(--rose-border);
    color: var(--artistic-brown);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    font-family: 'Raleway', sans-serif;
  }
  
  .btn-export:hover {
    background: var(--starry-night);
    color: white;
    transform: translateY(-2px);
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
    .page-title {
      font-size: 2.2rem;
    }
    
    .charts-container {
      grid-template-columns: 1fr;
    }
    
    .transaction-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .transaction-info {
      grid-template-columns: 1fr;
    }
    
    .grand-total-value {
      font-size: 2.5rem;
    }
    
    .chart-header {
      flex-direction: column;
      align-items: stretch;
    }
    
    .date-filter-container {
      flex-direction: column;
      align-items: stretch;
    }
    
    .date-filter, .type-filter {
      width: 100%;
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
</style>
</head>
<body>

<div id="sakura-container"></div>
<nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
  <div class="container">
    <a class="navbar-brand admin-brand" href="admin_dashboard.php">Nukumori Zen Cafe Admin</a>
    
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
        <a href="index.php" class="btn btn-back">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="analytics-container">
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-chart-line me-2"></i>
      Sales Analytics Dashboard
    </h1>
    <p class="page-subtitle">Track revenue, analyze trends, and monitor the financial performance of Nukumori Zen Cafe</p>
    
    <div class="sales-stats">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-value"><?php echo $stats['total_transactions'] ?? 0; ?></div>
        <div class="stat-label">Transactions</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-tag"></i>
        </div>
        <div class="stat-value">₱<?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></div>
        <div class="stat-label">Avg Order Value</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-value">₱<?php echo number_format($today['today_sales'] ?? 0, 2); ?></div>
        <div class="stat-label">Today's Sales</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-value">₱<?php echo number_format($month['month_sales'] ?? 0, 0); ?></div>
        <div class="stat-label">Monthly Revenue</div>
      </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="date-filter-container">
        <div>
          <label for="dateFilter" class="form-label" style="color: var(--artistic-brown);">Filter by Date</label>
          <input type="date" id="dateFilter" class="date-filter" value="<?php echo $filterDate; ?>">
        </div>
        
        <div>
          <label for="typeFilter" class="form-label" style="color: var(--artistic-brown);">View Period</label>
          <select id="typeFilter" class="type-filter">
            <option value="day" <?php echo $filterType === 'day' ? 'selected' : ''; ?>>Daily</option>
            <option value="week" <?php echo $filterType === 'week' ? 'selected' : ''; ?>>Weekly</option>
            <option value="month" <?php echo $filterType === 'month' ? 'selected' : ''; ?>>Monthly</option>
          </select>
        </div>
        
        <div class="export-buttons mt-4">
          <button onclick="updateChart()" class="btn-export">
            <i class="fas fa-sync-alt me-1"></i>Update Chart
          </button>
          <button onclick="exportToPDF()" class="btn-export">
            <i class="fas fa-file-pdf me-1"></i>Export PDF
          </button>
        </div>
      </div>
      
      <div class="d-flex gap-2 mt-3">
        <a href="admin_dashboard.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <!-- Sales Overview Chart -->
  <div class="chart-card">
    <div class="chart-header">
      <h3 class="chart-title mb-0">
        <i class="fas fa-chart-area me-2"></i>
        Sales Overview
      </h3>
      <div class="chart-type-selector">
        <button class="chart-type-btn <?php echo $filterType === 'day' ? 'active' : ''; ?>" onclick="changeChartType('day')">Daily</button>
        <button class="chart-type-btn <?php echo $filterType === 'week' ? 'active' : ''; ?>" onclick="changeChartType('week')">Weekly</button>
        <button class="chart-type-btn <?php echo $filterType === 'month' ? 'active' : ''; ?>" onclick="changeChartType('month')">Monthly</button>
      </div>
    </div>
    <div class="chart-wrapper">
      <canvas id="salesChart"></canvas>
    </div>
  </div>

  <div class="transactions-container">
    <h3 class="chart-title mb-4">
      <i class="fas fa-history me-2"></i>
      Recent Transactions
    </h3>
    
    <?php
    $grandTotal = 0;
    $hasTransactions = sqlsrv_has_rows($txStmt);
    
    if (!$hasTransactions) {
      echo '<div class="empty-state">
              <i class="fas fa-receipt"></i>
              <h3 class="mb-3" style="color: var(--artistic-brown);">No Transactions Found</h3>
              <p style="color: var(--artistic-brown);">Make your first sale to see data here!</p>
            </div>';
    } else {
      sqlsrv_query($conn, $txSql);
      $txStmt = sqlsrv_query($conn, $txSql);
      
      while ($tx = sqlsrv_fetch_array($txStmt, SQLSRV_FETCH_ASSOC)):
        $tid = $tx['TRANSACTIONID'];
        $cust = htmlspecialchars($tx['CUSTOMERNAME']);
        $contact = htmlspecialchars($tx['CONTACT']);
        $notes = htmlspecialchars($tx['NOTES']);
        $date = $tx['CREATEDATE']->format("F j, Y \\a\\t g:i A");
        $total = number_format($tx['TOTALAMOUNT'], 2);
        $grandTotal += $tx['TOTALAMOUNT'];
        
        // Calculate time ago
        $txDate = $tx['CREATEDATE'];
        $now = new DateTime();
        $interval = $txDate->diff($now);
        $timeAgo = '';
        
        if ($interval->y > 0) $timeAgo = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
        elseif ($interval->m > 0) $timeAgo = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
        elseif ($interval->d > 0) $timeAgo = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        elseif ($interval->h > 0) $timeAgo = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        elseif ($interval->i > 0) $timeAgo = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        else $timeAgo = 'Just now';
    ?>
    
    <div class="transaction-card">
      <div class="transaction-header">
        <div>
          <span class="transaction-id">
            <i class="fas fa-hashtag me-1"></i>
            Transaction #<?= $tid ?>
          </span>
          <span class="ms-3" style="color: var(--artistic-brown); opacity: 0.7;">
            <i class="fas fa-clock me-1"></i>
            <?= $timeAgo ?>
          </span>
        </div>
        <div class="transaction-amount">₱<?= $total ?></div>
      </div>
      
      <div class="transaction-body">
        <div class="transaction-info">
          <div class="info-item">
            <span class="info-label">Customer</span>
            <span class="info-value">
              <i class="fas fa-user me-1"></i>
              <?= $cust ?>
            </span>
          </div>
          
          <div class="info-item">
            <span class="info-label">Contact</span>
            <span class="info-value">
              <i class="fas fa-phone me-1"></i>
              <?= $contact ?>
            </span>
          </div>
          
          <div class="info-item">
            <span class="info-label">Date & Time</span>
            <span class="info-value">
              <i class="fas fa-calendar me-1"></i>
              <?= $date ?>
            </span>
          </div>
          
          <div class="info-item">
            <span class="info-label">Notes</span>
            <span class="info-value">
              <i class="fas fa-sticky-note me-1"></i>
              <?= $notes ?: 'No notes' ?>
            </span>
          </div>
        </div>
        
        <h5 style="color: var(--artistic-brown); margin-bottom: 1rem;">
          <i class="fas fa-list-alt me-2"></i>
          Items Ordered
        </h5>
        
        <table class="items-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Unit Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $itemSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID = ?";
            $itemParams = array($tid);
            $itemStmt = sqlsrv_query($conn, $itemSql, $itemParams);
            
            while ($item = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC)):
              $name = htmlspecialchars($item['PRODUCTNAME']);
              $qty = $item['QUANTITY'];
              $price = number_format($item['PRICE'], 2);
              $subtotal = number_format(($item['QUANTITY'] * $item['PRICE']), 2);
            ?>
            <tr>
              <td><?= $name ?></td>
              <td><?= $qty ?></td>
              <td class="item-price">₱<?= $price ?></td>
              <td class="item-subtotal">₱<?= $subtotal ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <?php endwhile; ?>
    
    <div class="grand-total-card">
      <div class="grand-total-label">Total Revenue from All Transactions</div>
      <div class="grand-total-value">₱<?= number_format($grandTotal, 2) ?></div>
      <div style="color: var(--artistic-brown); opacity: 0.7; margin-top: 0.5rem;">
        <i class="fas fa-chart-pie me-1"></i>
        Based on <?= $stats['total_transactions'] ?? 0 ?> transactions
      </div>
    </div>
    
    <?php } ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Create animated sakura petals
  document.addEventListener('DOMContentLoaded', function() {
    const sakuraContainer = document.getElementById('sakura-container');
    const petalCount = 10;
    for (let i = 0; i < petalCount; i++) {
      const petal = document.createElement('div');
      petal.classList.add('sakura-decoration');
      petal.style.left = `${Math.random() * 100}%`;
      const size = Math.random() * 15 + 10;
      petal.style.width = `${size}px`;
      petal.style.height = `${size}px`;
      petal.style.animationDelay = `${Math.random() * 15}s`;
      petal.style.animationDuration = `${Math.random() * 10 + 10}s`;
      sakuraContainer.appendChild(petal);
    }

    // Initialize Sales Chart with FIXED configuration
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    
    // Get data from PHP variables
    const labels = <?php echo json_encode($labels); ?>;
    const revenueData = <?php echo json_encode($revenueData); ?>;
    const transactionData = <?php echo json_encode($transactionData); ?>;
    const filterType = '<?php echo $filterType; ?>';
    
    // Create gradient for revenue line
    const gradient = salesCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(216, 156, 168, 0.3)');
    gradient.addColorStop(1, 'rgba(216, 156, 168, 0.05)');
    
    // Destroy existing chart if it exists
    if (window.salesChartInstance) {
      window.salesChartInstance.destroy();
    }
    
    // Create new chart with proper configuration
    window.salesChartInstance = new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Revenue (₱)',
            data: revenueData,
            borderColor: '#d89ca8',
            backgroundColor: gradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            yAxisID: 'y',
            pointBackgroundColor: '#d89ca8',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
          },
          {
            label: 'Transactions',
            data: transactionData,
            borderColor: '#c48c39',
            backgroundColor: 'rgba(196, 140, 57, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.4,
            yAxisID: 'y1',
            borderDash: [5, 5],
            pointBackgroundColor: '#c48c39',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              color: '#8B4513',
              font: {
                family: "'Raleway', sans-serif",
                size: 14
              },
              padding: 20,
              usePointStyle: true,
            }
          },
          tooltip: {
            backgroundColor: 'rgba(139, 69, 19, 0.9)',
            titleColor: '#f2e4b7',
            bodyColor: '#f2e4b7',
            titleFont: {
              family: "'Raleway', sans-serif"
            },
            bodyFont: {
              family: "'Raleway', sans-serif"
            },
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  if (context.datasetIndex === 0) {
                    // Revenue formatting
                    label += '₱' + context.parsed.y.toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    });
                  } else {
                    // Transaction count formatting
                    label += context.parsed.y.toLocaleString() + ' transactions';
                  }
                }
                return label;
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              color: '#8B4513',
              font: {
                family: "'Raleway', sans-serif",
                size: filterType === 'week' ? 10 : 12
              },
              maxRotation: filterType === 'week' ? 45 : 0,
              callback: function(value, index) {
                // For weekly view with date ranges, only show every other label if there are many
                if (filterType === 'week' && this.getLabels().length > 8) {
                  return index % 2 === 0 ? this.getLabels()[index] : '';
                }
                return this.getLabels()[index];
              }
            },
            grid: {
              color: 'rgba(139, 69, 19, 0.1)'
            }
          },
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Revenue (₱)',
              color: '#8B4513',
              font: {
                family: "'Raleway', sans-serif",
                size: 14,
                weight: 'bold'
              }
            },
            ticks: {
              color: '#8B4513',
              callback: function(value) {
                return '₱' + value.toLocaleString();
              },
              font: {
                family: "'Raleway', sans-serif"
              },
              padding: 10
            },
            grid: {
              color: 'rgba(139, 69, 19, 0.1)'
            },
            suggestedMin: 0 // Start from 0
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Transactions',
              color: '#8B4513',
              font: {
                family: "'Raleway', sans-serif",
                size: 14,
                weight: 'bold'
              }
            },
            ticks: {
              color: '#8B4513',
              font: {
                family: "'Raleway', sans-serif"
              },
              padding: 10,
              callback: function(value) {
                return value.toLocaleString();
              }
            },
            grid: {
              drawOnChartArea: false,
            },
            suggestedMin: 0 // Start from 0
          }
        },
        animation: {
          duration: 1000,
          easing: 'easeOutQuart'
        }
      }
    });
    
    // Add animation to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
      card.style.animationDelay = (index * 0.1) + 's';
      card.style.animation = 'fadeInUp 0.5s ease forwards';
      card.style.opacity = 0;
    });
  });
  
  // Animation for stat cards
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  `;
  document.head.appendChild(style);
  
  // Chart type selector
  function changeChartType(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
  }
  
  // Update chart based on filters
  function updateChart() {
    const dateFilter = document.getElementById('dateFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    const url = new URL(window.location.href);
    if (dateFilter) {
      url.searchParams.set('date', dateFilter);
    } else {
      url.searchParams.delete('date');
    }
    url.searchParams.set('type', typeFilter);
    
    window.location.href = url.toString();
  }
  
  // Export to PDF function
  function exportToPDF() {
    alert('PDF export feature would generate a sales report.\n\nThis would include:\n- Sales summary\n- Transaction details\n- Charts and graphs\n- Date range information');
    // In a real implementation, this would trigger a server-side PDF generation
    // window.location.href = 'export_pdf.php';
  }
  
  // Add hover effects to cards
  const cards = document.querySelectorAll('.transaction-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.zIndex = '10';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.zIndex = '';
    });
  });
</script>
</body>
</html>