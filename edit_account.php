<?php
session_start();

$serverName = "LAPTOP-RVNVFIF2\SQLEXPRESS";
$connectionOptions = [
    "Database" => "SQLJourney",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$error = '';
$success = '';

$sql = "SELECT * FROM USERS WHERE USERID = ?";
$params = array($id);
$stmt = sqlsrv_query($conn, $sql, $params);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$user) {
    $error = "User not found.";
}

// Handle form submission
if (isset($_POST['save'])) {
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    // Validate inputs
    if (empty($username) || empty($role)) {
        $error = "All fields are required.";
    } else {
        // Update the user
        $update = "UPDATE USERS SET USERNAME = ?, ROLE = ? WHERE USERID = ?";
        $updateParams = array($username, $role, $id);
        $result = sqlsrv_query($conn, $update, $updateParams);

        if ($result) {
            $success = "Account updated successfully!";
            // Refresh user data
            $stmt = sqlsrv_query($conn, $sql, $params);
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $error = "Failed to update account: " . print_r(sqlsrv_errors(), true);
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Account • Nukumori Zen Café</title>
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
            background: radial-gradient(circle at 20% 20%, rgba(216, 156, 168, 0.1) 0%, transparent 40%),
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
            padding: 2rem;
            margin-bottom: 2rem;
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
            0% {
                transform: translateY(-50px) translateX(0) rotate(0deg);
                opacity: 0;
            }

            25% {
                opacity: 1;
            }

            50% {
                transform: translateY(150px) translateX(20px) rotate(90deg);
            }

            75% {
                opacity: 1;
            }

            100% {
                transform: translateY(300px) translateX(-20px) rotate(180deg);
                opacity: 0;
            }
        }

        .dashboard-title {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--artistic-brown);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .welcome-text {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 1rem;
            color: var(--artistic-brown);
        }

        .alert-message {
            border-radius: 15px;
            border: 2px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            animation: fadeIn 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(145deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05));
            border-color: #4CAF50;
            color: #2E7D32;
        }

        .alert-danger {
            background: linear-gradient(145deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.05));
            border-color: #F44336;
            color: #C62828;
        }

        .form-container {
            background: linear-gradient(145deg,
                    rgba(255, 255, 255, 0.95) 0%,
                    rgba(255, 255, 255, 0.9) 100%);
            border: 2px solid var(--rose-border);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .form-label {
            font-weight: 600;
            color: var(--artistic-brown);
            margin-bottom: 0.5rem;
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        }

        .form-control {
            background: white;
            border: 2px solid var(--rose-border);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: var(--artistic-brown);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            background: white;
            color: var(--artistic-brown);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 142, 35, 0.3);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 142, 35, 0.4);
            color: white;
        }

        .btn-outline-light {
            color: var(--cafe-cream);
            border-color: var(--cafe-cream);
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background-color: rgba(242, 228, 183, 0.1);
            border-color: var(--vangogh-yellow);
            color: var(--vangogh-yellow);
            transform: translateY(-2px);
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-title {
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

            .form-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.8rem;
            }

            .welcome-card {
                padding: 1.5rem;
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
                    <?php if (isset($_SESSION['user'])): ?>
                        <span class="user-greeting me-3" style="font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif; color: var(--cafe-cream); font-weight: 600;">
                            <i class="fas fa-user me-1"></i>
                            Hello, <?= htmlspecialchars($_SESSION['user']['username']) ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                        <a href="admin_dashboard.php" class="btn btn-admin me-2" style="background: linear-gradient(135deg, var(--artistic-brown), #6B4226); color: white; border: none; border-radius: 50px; padding: 0.5rem 1.5rem; font-weight: 600;">
                            <i class="fas fa-crown me-1"></i>Dashboard
                        </a>
                        <a href="accounts.php" class="btn btn-cart me-2" style="background: linear-gradient(135deg, var(--olive-green), #5a7d1e); color: white; border: none; border-radius: 50px; padding: 0.5rem 1.5rem; font-weight: 600;">
                            <i class="fas fa-users me-1"></i>Accounts
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="welcome-card text-center">
            <h1 class="dashboard-title">Edit Account</h1>
            <p class="welcome-text">
                Update user account information and permissions
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert-message alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-message alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="form-container">
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['USERNAME'] ?? '') ?>" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="staff" <?= ($user['ROLE'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= ($user['ROLE'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" name="save" class="btn btn-add">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="accounts.php" class="btn btn-outline-light">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert-message alert-danger text-center">
                <i class="fas fa-user-slash fa-2x mb-3"></i>
                <h4>User Not Found</h4>
                <p>The requested user account could not be found.</p>
                <a href="accounts.php" class="btn btn-outline-light mt-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Accounts
                </a>
            </div>
        <?php endif; ?>

        <footer class="footer text-center mt-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start">
                        <h4 class="footer-title mb-3">Nukumori Café</h4>
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
                                &copy; <?php echo date('Y'); ?> Nukumori Café. All rights reserved.
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
            const petalCount = 8;
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

            // Add fade-in animation to form
            const formContainer = document.querySelector('.form-container');
            if (formContainer) {
                formContainer.style.opacity = '0';
                formContainer.style.transform = 'translateY(20px)';
                formContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

                setTimeout(() => {
                    formContainer.style.opacity = '1';
                    formContainer.style.transform = 'translateY(0)';
                }, 300);
            }

            // Auto-hide success message after 5 seconds
            const successMessage = document.querySelector('.alert-success');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    successMessage.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>