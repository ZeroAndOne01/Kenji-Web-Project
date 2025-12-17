<?php
session_start();

$serverName="LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions=[
"Database"=>"SQLJourney",
"Uid"=>"",
"PWD"=>""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Account Management • Nukumori Cafe</title>
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
    --success-green: #28a745;
    --danger-red: #dc3545;
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
  
  .management-container {
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
    padding: 2.5rem;
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
    margin-bottom: 1rem;
  }
  
  .page-subtitle {
    font-family: 'Noto Serif JP', serif;
    color: var(--artistic-brown);
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    opacity: 0.9;
  }
  
  .user-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid var(--rose-border);
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--starry-night);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
  
  .btn-add-user {
    background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
    color: var(--artistic-brown);
    border: none;
    border-radius: 50px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
  }
  
  .btn-add-user:hover {
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
  
  .users-table-container {
    background: linear-gradient(145deg, 
                #f7e7d7 0%,  
                #ffe4e1 100%);
    border-radius: 20px;
    border: 2px solid var(--rose-border);
    padding: 2rem;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  }
  
  .table {
    color: var(--artistic-brown);
    margin-bottom: 0;
  }
  
  .table thead th {
    background: rgba(216, 156, 168, 0.2);
    border-bottom: 2px solid var(--artistic-brown);
    color: var(--artistic-brown);
    font-weight: 700;
    padding: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
  }
  
  .table tbody td {
    border-color: rgba(139, 69, 19, 0.1);
    padding: 1rem;
    vertical-align: middle;
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
  }
  
  .table tbody tr:hover {
    background: rgba(216, 156, 168, 0.1);
    transform: translateX(5px);
  }
  
  .role-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .role-admin { 
    background: rgba(139, 69, 19, 0.2); 
    color: #8B4513; 
    border: 1px solid #8B4513; 
  }
  
  .role-staff { 
    background: rgba(196, 140, 57, 0.2); 
    color: var(--starry-night); 
    border: 1px solid var(--starry-night); 
  }
  
  .role-user { 
    background: rgba(216, 156, 168, 0.2); 
    color: var(--rose-border); 
    border: 1px solid var(--rose-border); 
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn-edit {
    background: linear-gradient(135deg, var(--starry-night), #e6b800);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-edit:hover {
    background: linear-gradient(135deg, #e6b800, var(--starry-night));
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(196, 140, 57, 0.3);
    color: white;
  }
  
  .btn-delete {
    background: linear-gradient(135deg, var(--danger-red), #c82333);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-delete:hover {
    background: linear-gradient(135deg, #c82333, var(--danger-red));
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
    color: white;
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
  
  .search-container {
    position: relative;
    max-width: 400px;
    margin-bottom: 1.5rem;
  }
  
  .search-input {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid var(--rose-border);
    border-radius: 50px;
    color: var(--artistic-brown);
    padding: 0.75rem 1.5rem 0.75rem 3rem;
    width: 100%;
    font-size: 1rem;
    transition: all 0.3s ease;
  }
  
  .search-input:focus {
    background: white;
    border-color: var(--starry-night);
    box-shadow: 0 0 0 0.25rem rgba(196, 140, 57, 0.25);
    outline: none;
  }
  
  .search-input::placeholder {
    color: rgba(139, 69, 19, 0.5);
  }
  
  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--starry-night);
  }
  
  .form-control, .form-select {
    background: #fff;
    border: 2px solid var(--rose-border);
    border-radius: 12px;
    color: var(--artistic-brown);
    padding: 0.75rem 1rem;
  }
  
  .form-control:focus, .form-select:focus {
    border-color: var(--starry-night);
    box-shadow: 0 0 0 0.25rem rgba(196, 140, 57, 0.25);
    color: var(--artistic-brown);
    outline: none;
  }
  
  .btn-outline-light {
    border-color: var(--vangogh-yellow);
    color: var(--vangogh-yellow);
  }
  
  .btn-outline-light:hover {
    background-color: var(--vangogh-yellow);
    color: var(--artistic-brown);
  }
  
  @media (max-width: 768px) {
    .page-title {
      font-size: 2.2rem;
    }
    
    .page-header {
      padding: 1.5rem;
    }
    
    .table thead {
      display: none;
    }
    
    .table tbody tr {
      display: block;
      margin-bottom: 1rem;
      background: rgba(255, 255, 255, 0.7);
      border-radius: 10px;
      padding: 1rem;
      border: 1px solid var(--rose-border);
    }
    
    .table tbody td {
      display: block;
      text-align: right;
      border: none;
      padding: 0.5rem;
      position: relative;
    }
    
    .table tbody td::before {
      content: attr(data-label);
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-weight: 700;
      color: var(--artistic-brown);
    }
    
    .action-buttons {
      justify-content: flex-end;
    }
    
    .users-table-container {
      padding: 1rem;
    }
  }
  
  .stat-icon {
    font-size: 2rem;
    color: var(--rose-border);
    margin-bottom: 0.5rem;
  }
  
  .form-footer {
    margin-top: 2rem;
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
        <a href="index.php" class="btn btn-back">
          <i class="fas fa-coffee me-1"></i>Back to Café
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="management-container">
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-users-cog me-2"></i>
      Team Management
    </h1>
    <p class="page-subtitle">Manage your Nukumori team members and their roles with care and attention.</p>
    
    <?php
    // Get user statistics
    $statsSql = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN ROLE = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN ROLE = 'staff' THEN 1 ELSE 0 END) as staff_count,
        SUM(CASE WHEN ROLE = 'user' THEN 1 ELSE 0 END) as user_count
        FROM USERS";
    $statsStmt = sqlsrv_query($conn, $statsSql);
    $stats = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC);
    ?>
    
    <div class="user-stats">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
        <div class="stat-label">Total Team</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-crown"></i>
        </div>
        <div class="stat-value"><?php echo $stats['admin_count'] ?? 0; ?></div>
        <div class="stat-label">Administrators</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-value"><?php echo $stats['staff_count'] ?? 0; ?></div>
        <div class="stat-label">Staff Members</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user"></i>
        </div>
        <div class="stat-value"><?php echo $stats['user_count'] ?? 0; ?></div>
        <div class="stat-label">Regular Users</div>
      </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="userSearch" class="search-input" placeholder="Search team members...">
      </div>
      
      <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-add-user">
          <i class="fas fa-user-plus me-2"></i>Add Team Member
        </a>
        <a href="admin_dashboard.php" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="users-table-container">
    <?php
    $sql = "SELECT * FROM USERS ORDER BY USERNAME";
    $stmt = sqlsrv_query($conn, $sql);
    $hasUsers = sqlsrv_has_rows($stmt);
    
    if (!$hasUsers) {
      echo '<div class="empty-state">
              <i class="fas fa-users"></i>
              <h3 class="mb-3" style="color: var(--artistic-brown);">No Team Members Found</h3>
              <p style="color: var(--artistic-brown);">Begin building your Nukumori team by adding the first member!</p>
              <a href="register.php" class="btn btn-add-user mt-3">
                <i class="fas fa-user-plus me-2"></i>Add First Team Member
              </a>
            </div>';
    } else {
      echo '<div class="table-responsive">
              <table class="table table-hover" id="usersTable">
                <thead>
                  <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Join Date</th>
                    <th width="180">Actions</th>
                  </tr>
                </thead>
                <tbody>';
      
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $roleClass = 'role-user';
        $roleIcon = 'fa-user';
        switch(strtolower($row['ROLE'])) {
          case 'admin': 
            $roleClass = 'role-admin'; 
            $roleIcon = 'fa-crown';
            break;
          case 'staff': 
            $roleClass = 'role-staff'; 
            $roleIcon = 'fa-user-tie';
            break;
        }
        
        $createdDate = '';
        if (isset($row['CREATEDAT']) && $row['CREATEDAT'] instanceof DateTime) {
          $createdDate = $row['CREATEDAT']->format('M d, Y');
        }
        
        echo '<tr>
                <td data-label="User ID"><strong>' . $row['USERID'] . '</strong></td>
                <td data-label="Username">
                  <strong>' . htmlspecialchars($row['USERNAME']) . '</strong>
                </td>
                <td data-label="Full Name">
                  ' . htmlspecialchars($row['FULLNAME'] ?? 'Not set') . '
                </td>
                <td data-label="Role">
                  <span class="role-badge ' . $roleClass . '">
                    <i class="fas ' . $roleIcon . ' me-1"></i>
                    ' . htmlspecialchars($row['ROLE']) . '
                  </span>
                </td>
                <td data-label="Join Date">
                  ' . ($createdDate ? '<small>' . $createdDate . '</small>' : 'N/A') . '
                </td>
                <td data-label="Actions">
                  <div class="action-buttons">
                    <a href="edit_account.php?id=' . $row['USERID'] . '" class="btn btn-edit">
                      <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <a href="delete_account.php?id=' . $row['USERID'] . '" 
                       class="btn btn-delete" 
                       onclick="return confirmDelete(\'' . htmlspecialchars(addslashes($row['USERNAME'])) . '\', \'' . htmlspecialchars($row['ROLE']) . '\')">
                      <i class="fas fa-trash me-1"></i>Delete
                    </a>
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
    const searchInput = document.getElementById('userSearch');
    const usersTable = document.getElementById('usersTable');
    
    if (searchInput && usersTable) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = usersTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });
    }
    
    window.confirmDelete = function(username, role) {
      if (role === 'admin') {
        return confirm(`重要 (Important): Deleting administrator "${username}"!\n\nThis team member has full system access. Are you sure you want to proceed?\n\nこの操作は元に戻せません (This action cannot be undone).`);
      }
      return confirm(`Are you sure you want to remove "${username}" from the team?\n\nこの操作は元に戻せません (This action cannot be undone).`);
    };
    
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
      });
      
      row.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
      });
    });
    
    // Add some interactive effects
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        const icon = this.querySelector('.stat-icon i');
        icon.style.transform = 'scale(1.1)';
        icon.style.transition = 'transform 0.3s ease';
      });
      
      card.addEventListener('mouseleave', function() {
        const icon = this.querySelector('.stat-icon i');
        icon.style.transform = 'scale(1)';
      });
    });
  });
</script>
</body>
</html>