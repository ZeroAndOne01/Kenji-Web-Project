<?php
session_start();
$serverName="LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions=[
"Database"=>"SQLJourney",
"Uid"=>"",
"PWD"=>""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    $productId = $_POST['product_id'];
    
    // Delete from database
    $deleteSql = "DELETE FROM STRBARAKSMENU WHERE PRODUCTID = '$productId'";
    $deleteResult = sqlsrv_query($conn, $deleteSql);
    
    if ($deleteResult) {
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete product.'
        ]);
    }
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Management • Nukumori Zen Café</title>
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
  
  .btn-admin {
    background: linear-gradient(135deg, var(--artistic-brown), #6B4226);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
  }
  
  .user-greeting {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    color: var(--cafe-cream);
    font-weight: 600;
  }
  
  /* Page Header */
  .page-header {
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
    font-size: 3rem;
    color: var(--artistic-brown);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
  }
  
  .page-subtitle {
    font-family: 'Noto Serif JP', serif;
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 800px;
    margin: 0 auto 2rem;
    color: var(--artistic-brown);
  }
  
  /* Admin Stats */
  .admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid var(--rose-border);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--vangogh-yellow);
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
  
  /* Search Box */
  .search-container {
    max-width: 400px;
    position: relative;
    margin-bottom: 1.5rem;
  }
  
  .search-input {
    background: white;
    border: 2px solid var(--rose-border);
    border-radius: 50px;
    padding: 0.75rem 1.5rem 0.75rem 3rem;
    width: 100%;
    font-size: 1rem;
    color: var(--artistic-brown);
    box-shadow: 0 5px 15px rgba(216, 156, 168, 0.2);
  }
  
  .search-input:focus {
    border-color: var(--vangogh-yellow);
    box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
    outline: none;
  }
  
  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rose-border);
  }
  
  /* Action Buttons */
  .btn-add-product {
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    color: var(--artistic-brown);
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
  }
  
  .btn-add-product:hover {
    background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
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
  }
  
  .btn-back:hover {
    background: linear-gradient(135deg, #6B4226, var(--artistic-brown));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
  }
  
  /* Table Container */
  .products-table-container {
    background: linear-gradient(145deg, 
                var(--light-beige) 0%,  
                var(--soft-pink) 100%);
    border-radius: 25px;
    border: 2px solid var(--rose-border);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                inset 0 0 40px rgba(216, 156, 168, 0.1);
    padding: 2rem;
    margin-bottom: 2rem;
  }
  
  /* Table */
  .table {
    color: var(--artistic-brown);
    border-collapse: separate;
    border-spacing: 0;
  }
  
  .table thead th {
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 2px solid var(--rose-border);
    font-weight: 700;
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    padding: 1rem;
    position: sticky;
    top: 0;
    z-index: 10;
  }
  
  .table tbody tr {
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
  }
  
  .table tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.95);
  }
  
  .table tbody tr:hover {
    background: rgba(216, 156, 168, 0.15);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(216, 156, 168, 0.2);
  }
  
  /* Product Image */
  .product-image {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid var(--rose-border);
    transition: all 0.3s ease;
  }
  
  .product-image:hover {
    transform: scale(1.1);
    border-color: var(--vangogh-yellow);
  }
  
  /* Category Badges */
  .category-badge {
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
  }
  
  .category-food { background: linear-gradient(135deg, var(--starry-night), #e6b800); color: white; }
  .category-drink { background: linear-gradient(135deg, var(--vangogh-blue), #2a6fdb); color: white; }
  .category-dessert { background: linear-gradient(135deg, var(--rose-border), #e8a0b7); color: white; }
  .category-appetizer { background: linear-gradient(135deg, var(--olive-green), #5a7d1e); color: white; }
  
  /* Price Tag */
  .price-tag {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--artistic-brown);
  }
  
  .price-tag::before {
    content: '₱';
    font-weight: 700;
    margin-right: 2px;
  }
  
  /* Action Buttons */
  .btn-edit {
    background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(106, 142, 35, 0.3);
  }
  
  .btn-edit:hover {
    background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(106, 142, 35, 0.4);
  }
  
  .btn-delete {
    background: linear-gradient(135deg, var(--danger-red), #c0392b);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
  }
  
  .btn-delete:hover {
    background: linear-gradient(135deg, #c0392b, var(--danger-red));
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
  }
  
  .btn-delete:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
  }
  
  .empty-state i {
    font-size: 4rem;
    color: var(--rose-border);
    margin-bottom: 1rem;
    opacity: 0.7;
  }
  
  .empty-state h3 {
    font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    margin-bottom: 1rem;
  }
  
  .empty-state p {
    color: var(--artistic-brown);
    opacity: 0.8;
    margin-bottom: 2rem;
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
    .page-title {
      font-size: 2.2rem;
    }
    
    .admin-stats {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .navbar-brand {
      font-size: 1.5rem;
      padding-left: 70px;
    }
    
    .navbar-brand::before {
      width: 60px;
      height: 60px;
    }
    
    .action-buttons {
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .action-buttons a {
      width: 100%;
      text-align: center;
    }
  }
</style>
</head>
<body>
<div id="sakura-container"></div>
<div id="notification-container"></div>

<nav class="navbar navbar-expand-lg navbar-dark nukumori-navbar">
  <div class="container">
    <a class="navbar-brand" href="admin_dashboard.php">Nukumori Admin</a>
    
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
        <a href="index.php" class="btn btn-back">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="page-header text-center">
    <h1 class="page-title">
      商品管理<br>
      <small style="font-size: 1.5rem;">Product Management</small>
    </h1>
    <p class="page-subtitle">Manage your Japanese-inspired menu. Add, edit, and remove products with care and precision.</p>
    
    <?php
    // Get product statistics
    $statsSql = "SELECT 
        COUNT(*) as total_products,
        COUNT(DISTINCT CATEGORY) as categories,
        SUM(PRICE) as total_value,
        AVG(PRICE) as avg_price
        FROM STRBARAKSMENU";
    $statsResult = sqlsrv_query($conn, $statsSql);
    $stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);
    ?>
    
    <div class="admin-stats">
      <div class="stat-card">
        <div class="stat-value" id="totalProducts"><?php echo $stats['total_products'] ?? 0; ?></div>
        <div class="stat-label">Total Products</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="totalCategories"><?php echo $stats['categories'] ?? 0; ?></div>
        <div class="stat-label">Categories</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">₱<?php echo number_format($stats['total_value'] ?? 0, 0); ?></div>
        <div class="stat-label">Total Value</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">₱<?php echo number_format($stats['avg_price'] ?? 0, 2); ?></div>
        <div class="stat-label">Avg Price</div>
      </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="productSearch" class="search-input" placeholder="Search products...">
      </div>
      
      <div class="d-flex gap-2">
        <a href="add_product.php" class="btn btn-add-product">
          <i class="fas fa-plus-circle me-2"></i>Add New Product
        </a>
        <a href="admin_dashboard.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="products-table-container">
    <?php
    $sql = "SELECT * FROM STRBARAKSMENU ORDER BY PRODUCTNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasProducts = sqlsrv_has_rows($stmt);
    
    if (!$hasProducts) {
      echo '<div class="empty-state">
              <i class="fas fa-utensils"></i>
              <h3 class="mb-3">No Products Found</h3>
              <p>Your menu is empty. Start by adding your first Japanese-inspired creation!</p>
              <a href="add_product.php" class="btn btn-add-product mt-3">
                <i class="fas fa-plus-circle me-2"></i>Add Your First Product
              </a>
            </div>';
    } else {
      echo '<div class="table-responsive">
              <table class="table table-hover" id="productsTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th width="180">Actions</th>
                  </tr>
                </thead>
                <tbody>';
      
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $categoryClass = 'category-other';
        switch(strtolower($row['CATEGORY'])) {
          case 'food': $categoryClass = 'category-food'; break;
          case 'drink': $categoryClass = 'category-drink'; break;
          case 'dessert': $categoryClass = 'category-dessert'; break;
          case 'appetizer': $categoryClass = 'category-appetizer'; break;
        }
        
        echo '<tr id="product-row-' . $row['PRODUCTID'] . '">
                <td><strong>' . $row['PRODUCTID'] . '</strong></td>
                <td>
                  <strong>' . htmlspecialchars($row['PRODUCTNAME']) . '</strong>
                </td>
                <td>
                  <span class="category-badge ' . $categoryClass . '">
                    ' . htmlspecialchars($row['CATEGORY']) . '
                  </span>
                </td>
                <td>
                  <small style="color: var(--artistic-brown); opacity: 0.8;">
                    ' . htmlspecialchars($row['DESCRIPTION'] ?? 'No description') . '
                  </small>
                </td>
                <td>
                  <span class="price-tag">' . number_format($row['PRICE'], 2) . '</span>
                </td>
                <td>
                  <img src="' . htmlspecialchars($row['IMAGEPATH'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80') . '" 
                       class="product-image" 
                       alt="' . htmlspecialchars($row['PRODUCTNAME']) . '">
                </td>
                <td>
                  <div class="action-buttons">
                    <a href="edit_product.php?id=' . $row['PRODUCTID'] . '" class="btn btn-edit">
                      <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <button type="button" 
                            class="btn btn-delete delete-product-btn" 
                            data-product-id="' . $row['PRODUCTID'] . '" 
                            data-product-name="' . htmlspecialchars($row['PRODUCTNAME']) . '">
                      <i class="fas fa-trash me-1"></i>Delete
                    </button>
                  </div>
                </td>
              </tr>';
      }
      
      echo '</tbody>
            </table>
          </div>';
    }
    ?>
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
    
    // Product search functionality
    const searchInput = document.getElementById('productSearch');
    const productsTable = document.getElementById('productsTable');
    
    if (searchInput && productsTable) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = productsTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });
    }
    
    // Notification System
    const notificationContainer = document.getElementById('notification-container');
    
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
      `;
      
      notificationContainer.appendChild(notification);
      
      // Show with animation
      setTimeout(() => {
        notification.classList.add('show');
      }, 10);
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        notification.classList.remove('show');
        notification.classList.add('hide');
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 500);
      }, 5000);
    }
    
    // AJAX Delete Functionality
    const deleteButtons = document.querySelectorAll('.delete-product-btn');
    
    deleteButtons.forEach(button => {
      button.addEventListener('click', async function() {
        const productId = this.dataset.productId;
        const productName = this.dataset.productName;
        
        if (!confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone and will remove the product from your menu permanently.`)) {
          return;
        }
        
        // Disable button and show loading state
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
        
        try {
          // Send AJAX request
          const response = await fetch('', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              'ajax_delete': '1',
              'product_id': productId
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            // Remove row with animation
            const row = document.getElementById(`product-row-${productId}`);
            if (row) {
              row.style.transition = 'all 0.3s ease';
              row.style.opacity = '0';
              row.style.transform = 'translateX(100%)';
              
              setTimeout(() => {
                row.remove();
                
                // Update statistics
                updateStatistics();
                
                // Show success notification
                showNotification('Product Deleted', `"${productName}" has been removed successfully.`, 'success');
              }, 300);
            }
          } else {
            throw new Error(result.message);
          }
        } catch (error) {
          console.error('Error:', error);
          
          // Show error notification
          showNotification('Error', 'Failed to delete product. Please try again.', 'error');
          
          // Reset button
          setTimeout(() => {
            this.disabled = false;
            this.innerHTML = originalText;
          }, 1500);
        }
      });
    });
    
    // Function to update statistics
    function updateStatistics() {
      const totalProductsElement = document.getElementById('totalProducts');
      const totalCategoriesElement = document.getElementById('totalCategories');
      
      if (totalProductsElement) {
        const current = parseInt(totalProductsElement.textContent) || 0;
        totalProductsElement.textContent = current - 1;
        
        // Animate the change
        totalProductsElement.style.transform = 'scale(1.2)';
        setTimeout(() => {
          totalProductsElement.style.transform = 'scale(1)';
        }, 300);
      }
      
      // Note: We would need an AJAX call to update category count accurately
      // For now, we'll just update the product count
    }
    
    // Add animation to table rows
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    tableRows.forEach((row, index) => {
      row.style.opacity = '0';
      row.style.transform = 'translateY(20px)';
      row.style.transition = `opacity 0.5s ease ${index * 0.05}s, transform 0.5s ease ${index * 0.05}s`;
      
      setTimeout(() => {
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
      }, 50 + (index * 50));
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      // Ctrl + N to add product
      if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const addBtn = document.querySelector('.btn-add-product');
        if (addBtn) addBtn.click();
      }
      
      // Ctrl + / to focus search
      if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        if (searchInput) searchInput.focus();
      }
    });
    
    // Animate numeric stats on page load
    function animateStat(element, targetValue, duration = 1000) {
      const startValue = 0;
      const increment = targetValue / (duration / 16);
      let currentValue = startValue;
      
      const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
          element.textContent = targetValue;
          clearInterval(timer);
        } else {
          element.textContent = Math.floor(currentValue);
        }
      }, 16);
    }
    
    // Animate numeric stats
    const statElements = document.querySelectorAll('.stat-value');
    statElements.forEach(stat => {
      const text = stat.textContent;
      const numericValue = parseFloat(text.replace(/[^0-9.]/g, ''));
      
      if (!isNaN(numericValue)) {
        const originalText = stat.textContent;
        const isCurrency = text.includes('₱');
        
        stat.textContent = isCurrency ? '₱0' : '0';
        
        setTimeout(() => {
          if (isCurrency) {
            let current = 0;
            const increment = numericValue / 50;
            const timer = setInterval(() => {
              current += increment;
              if (current >= numericValue) {
                stat.textContent = originalText;
                clearInterval(timer);
              } else {
                stat.textContent = '₱' + Math.floor(current).toLocaleString();
              }
            }, 20);
          } else {
            animateStat(stat, numericValue);
          }
        }, 500);
      }
    });
  });
</script>
</body>
</html>