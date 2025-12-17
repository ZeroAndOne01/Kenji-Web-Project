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

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

// Mark as completed
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $sql = "UPDATE TRANSACTIONS SET STATUS='Completed' WHERE TRANSACTIONID=$id";
    sqlsrv_query($conn, $sql);
    header("Location: orders.php");
    exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pending Orders • Nukumori Zen Café</title>
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

        .card-order {
            background: linear-gradient(145deg,
                    rgba(255, 255, 255, 0.95) 0%,
                    rgba(255, 255, 255, 0.9) 100%);
            border: 2px solid var(--rose-border);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card-order:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            border-color: var(--vangogh-yellow);
        }

        .card-order h3 {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            color: var(--artistic-brown);
            border-bottom: 2px solid var(--rose-border);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .card-order p {
            color: #5a3e1b;
            margin-bottom: 0.5rem;
        }

        .card-order strong {
            color: var(--artistic-brown);
        }

        .table-dark {
            background: linear-gradient(145deg, #4B2E2E 0%, #3A1F1F 100%);
            border: 2px solid var(--vangogh-yellow);
            border-radius: 10px;
            overflow: hidden;
        }

        .table-dark th {
            background: linear-gradient(135deg, var(--starry-night), var(--vangogh-yellow));
            color: var(--artistic-brown);
            font-weight: 700;
            border: none;
            padding: 1rem;
        }

        .table-dark td {
            border-color: rgba(244, 197, 66, 0.2);
            color: var(--cafe-cream);
            padding: 0.75rem 1rem;
        }

        .btn-complete {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-complete:hover {
            background: linear-gradient(135deg, #45a049, #4CAF50);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, var(--vangogh-yellow), var(--starry-night));
            color: var(--artistic-brown);
            font-weight: 600;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(244, 197, 66, 0.3);
        }

        .btn-back:hover {
            background: linear-gradient(135deg, var(--starry-night), var(--vangogh-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 197, 66, 0.4);
        }

        .btn-light {
            background: linear-gradient(135deg, var(--soft-pink), #f8d7da);
            color: var(--artistic-brown);
            border: 2px solid var(--rose-border);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: linear-gradient(135deg, #f8d7da, var(--soft-pink));
            transform: translateY(-2px);
            border-color: var(--artistic-brown);
        }

        .alert-warning {
            background: linear-gradient(145deg,
                    var(--light-beige) 0%,
                    var(--soft-pink) 100%);
            border: 2px solid var(--vangogh-yellow);
            border-radius: 15px;
            color: var(--artistic-brown);
            font-weight: 600;
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

            .card-order {
                padding: 1rem;
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
            <a class="navbar-brand" href="index.php">Nukumori Zen Cafe</a>
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
                        <a href="logout.php" class="btn btn-outline-light me-2" style="color: var(--cafe-cream); border-color: var(--cafe-cream);">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                        <a href="admin_dashboard.php" class="btn btn-admin me-2" style="background: linear-gradient(135deg, var(--artistic-brown), #6B4226); color: white; border: none; border-radius: 50px; padding: 0.5rem 1.5rem; font-weight: 600;">
                            <i class="fas fa-crown me-1"></i>Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="welcome-card text-center">
            <h1 class="dashboard-title">Pending Orders</h1>
            <p class="welcome-text" style="font-family: 'Noto Serif JP', serif; font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto 1rem; color: var(--artistic-brown);">
                Manage and track all pending orders from customers
            </p>

            <a href="admin_dashboard.php" class="btn btn-back mb-4">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <?php
        // Fetch pending orders
        $sql = "SELECT * FROM TRANSACTIONS WHERE STATUS='Pending' ORDER BY CREATEDATE DESC";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            echo "<div class='alert alert-danger'>SQL ERROR: " . print_r(sqlsrv_errors(), true) . "</div>";
            exit;
        }

        $hasOrders = false;

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $hasOrders = true;

            $tid = $row['TRANSACTIONID'];
            $name = htmlspecialchars($row['CUSTOMERNAME']);
            $contact = htmlspecialchars($row['CONTACT']);
            $total = number_format($row['TOTALAMOUNT'], 2);
            $notes = htmlspecialchars($row['NOTES'] ?: 'No special instructions');
            $date = $row['CREATEDATE']->format("F j, Y g:i A");

            echo "<div class='card-order'>
                    <h3>Order #{$tid}</h3>
                    <p><strong>Customer:</strong> {$name}</p>
                    <p><strong>Contact:</strong> {$contact}</p>
                    <p><strong>Total Amount:</strong> <span class='price-tag' style='font-family: \"Playfair Display\", serif; font-weight: 700; color: var(--artistic-brown);'>₱{$total}</span></p>
                    <p><strong>Customer Notes:</strong> {$notes}</p>
                    <p><strong>Order Date:</strong> {$date}</p>

                    <button class='btn btn-light mb-3' data-bs-toggle='collapse' data-bs-target='#items{$tid}'>
                        <i class='fas fa-list me-2'></i>View Order Items
                    </button>

                    <div id='items{$tid}' class='collapse'>
                        <table class='table table-dark table-bordered mt-3'>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>";

            // Fetch items for this order
            $itemSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID=$tid";
            $items = sqlsrv_query($conn, $itemSql);

            if ($items === false) {
                echo "<tr><td colspan='4'>Error loading items</td></tr>";
            } else {
                $totalItems = 0;
                while ($it = sqlsrv_fetch_array($items, SQLSRV_FETCH_ASSOC)) {
                    $p = htmlspecialchars($it['PRODUCTNAME']);
                    $pr = number_format($it['PRICE'], 2);
                    $q = $it['QUANTITY'];
                    $subtotal = number_format($it['PRICE'] * $q, 2);
                    $totalItems += $q;

                    echo "<tr>
                            <td>{$p}</td>
                            <td>₱{$pr}</td>
                            <td>{$q}</td>
                            <td>₱{$subtotal}</td>
                          </tr>";
                }
                echo "<tr style='background: rgba(244, 197, 66, 0.1);'>
                        <td colspan='2'><strong>Total Items:</strong></td>
                        <td colspan='2'><strong>{$totalItems} items</strong></td>
                      </tr>";
            }

            echo "      </table>
                    </div>

                    <a href='orders.php?complete={$tid}' class='btn btn-complete mt-2'
                       onclick=\"return confirm('Mark order #{$tid} as completed?');\">
                        <i class='fas fa-check-circle me-2'></i>Mark as Completed
                    </a>
                </div>";
        }

        if (!$hasOrders) {
            echo "<div class='alert alert-warning bg-light text-dark p-3 text-center'>
                    <i class='fas fa-check-circle fa-2x mb-3' style='color: var(--vangogh-yellow);'></i>
                    <h4>No Pending Orders</h4>
                    <p>All orders have been processed. Great work!</p>
                  </div>";
        }
        ?>

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
                                &copy; <?php echo date('Y'); ?> Nukumori Zen Café. All prices in Philippine Peso (₱).
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
            const petalCount = 10;
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

            // Add smooth animation to order cards
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

            // Observe all order cards
            document.querySelectorAll('.card-order').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>
</body>

</html>