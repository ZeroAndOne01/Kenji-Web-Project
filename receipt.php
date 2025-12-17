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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { 
    header('Location: index.php');
    exit;
}

// 1. Fetch Transaction Details
$sql = "SELECT * FROM TRANSACTIONS WHERE TRANSACTIONID = '$id'";
$result = sqlsrv_query($conn, $sql);
$txn = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

if (!$txn) { 
    header('Location: index.php');
    exit;
}

$itemsSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID = '$id'";
$itStmt = sqlsrv_query($conn, $itemsSql);

// Initialize calculations
$subtotal = 0;
$items = [];
while($it = sqlsrv_fetch_array($itStmt, SQLSRV_FETCH_ASSOC)) {
    $sub = $it['PRICE'] * $it['QUANTITY'];
    $subtotal += $sub;
    $items[] = $it; // Store items for display later
}

// 2. Calculate components based on the Subtotal
$serviceChargeRate = 0.05;
$seniorDiscountRate = 0.20;

$calculatedServiceCharge = $subtotal * $serviceChargeRate;

// The discount is the difference between the subtotal + service charge, and the final saved total
// If the final amount is much lower than Subtotal + SC, a discount was applied.
$expectedTotalWithoutDiscount = $subtotal + $calculatedServiceCharge;
$finalTotalSaved = $txn['TOTALAMOUNT'];

// Calculate the actual discount applied (it will be 0 if no senior discount was active)
// Note: We round to two decimal places to handle floating-point precision issues
$discountApplied = round($expectedTotalWithoutDiscount - $finalTotalSaved, 2);

// Determine if the discount was actually a Senior Discount (20% of subtotal)
$isSeniorDiscountActive = (abs($discountApplied - ($subtotal * $seniorDiscountRate)) < 0.01);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt • Nukumori Zen Café</title>
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
        padding: 20px;
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
    
    .receipt-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 1rem;
    }
    
    /* Receipt Card */
    .receipt-card {
        background: linear-gradient(145deg, 
                    var(--light-beige) 0%,  
                    var(--soft-pink) 100%);
        border-radius: 25px;
        border: 3px solid var(--rose-border);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                    inset 0 0 40px rgba(216, 156, 168, 0.1);
        padding: 3rem;
        position: relative;
        overflow: hidden;
    }
    
    .receipt-card::before {
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
    
    .receipt-title {
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        font-weight: 700;
        font-size: 2.5rem;
        color: var(--artistic-brown);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        text-align: center;
        border-bottom: 3px solid var(--rose-border);
        padding-bottom: 1rem;
    }
    
    /* Receipt Header */
    .receipt-header {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px dashed var(--rose-border);
    }
    
    .receipt-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .info-item {
        margin-bottom: 0.5rem;
    }
    
    .info-label {
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        font-weight: 600;
        color: var(--artistic-brown);
        font-size: 1rem;
        display: inline-block;
        min-width: 120px;
    }
    
    .info-value {
        color: var(--artistic-brown);
        font-weight: 500;
    }
    
    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
    }
    
    .items-table th {
        background: rgba(255, 255, 255, 0.9);
        padding: 1rem;
        text-align: left;
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        font-weight: 600;
        color: var(--artistic-brown);
        border-bottom: 2px solid var(--rose-border);
    }
    
    .items-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(216, 156, 168, 0.3);
        color: var(--artistic-brown);
    }
    
    .items-table tbody tr:hover {
        background: rgba(216, 156, 168, 0.1);
    }
    
    .text-right {
        text-align: right;
    }
    
    .text-center {
        text-align: center;
    }
    
    /* Summary Section */
    .summary-section {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid var(--rose-border);
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(216, 156, 168, 0.2);
    }
    
    .summary-row:last-child {
        border-bottom: none;
    }
    
    .summary-label {
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        color: var(--artistic-brown);
        font-weight: 600;
    }
    
    .summary-value {
        font-weight: 600;
        color: var(--artistic-brown);
    }
    
    .discount-row {
        color: var(--success-green);
        font-weight: bold;
    }
    
    .total-row {
        margin-top: 1rem;
        padding-top: 1.5rem;
        border-top: 2px solid var(--rose-border);
        font-size: 1.2rem;
    }
    
    .total-label {
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        font-size: 1.3rem;
        color: var(--artistic-brown);
        font-weight: 700;
    }
    
    .total-value {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        color: var(--artistic-brown);
        font-weight: 900;
    }
    
    /* Buttons */
    .btn-home {
        background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(106, 142, 35, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    
    .btn-home:hover {
        background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(106, 142, 35, 0.4);
        color: white;
    }
    
    .btn-print {
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
        gap: 0.5rem;
        margin-top: 2rem;
        margin-left: 1rem;
    }
    
    .btn-print:hover {
        background: linear-gradient(135deg, #6B4226, var(--artistic-brown));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
        color: white;
    }
    
    /* Footer */
    .receipt-footer {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px dashed var(--rose-border);
    }
    
    .thank-you {
        font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
        font-size: 1.2rem;
        color: var(--artistic-brown);
        margin-bottom: 0.5rem;
    }
    
    .subtext {
        color: var(--artistic-brown);
        opacity: 0.7;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    
    /* Senior Citizen Badge */
    .senior-badge {
        background: linear-gradient(135deg, var(--vangogh-yellow), var(--starry-night));
        color: white;
        border-radius: 20px;
        padding: 0.25rem 1rem;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
        margin-left: 0.5rem;
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
        .receipt-card {
            padding: 2rem 1.5rem;
        }
        
        .receipt-title {
            font-size: 2rem;
        }
        
        .items-table {
            display: block;
            overflow-x: auto;
        }
        
        .receipt-info {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .receipt-card {
            padding: 1.5rem 1rem;
        }
        
        .receipt-title {
            font-size: 1.8rem;
        }
        
        .btn-home, .btn-print {
            width: 100%;
            margin-left: 0;
            margin-top: 1rem;
        }
    }
    
    /* Print Styles */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        
        .receipt-card {
            border: 2px solid #000 !important;
            box-shadow: none !important;
            background: white !important;
        }
        
        .btn-home, .btn-print, .sakura-decoration, .receipt-card::before {
            display: none !important;
        }
        
        .receipt-card::before {
            display: none;
        }
    }
</style>
</head>
<body>
<div id="sakura-container"></div>

<div class="receipt-container">
    <div class="receipt-card">
        <h1 class="receipt-title">
            <i class="fas fa-receipt me-2"></i>
            領収書<br>
            <small style="font-size: 1.5rem;">Official Receipt</small>
        </h1>
        
        <div class="receipt-header">
            <div class="receipt-info">
                <div class="info-item">
                    <span class="info-label">Transaction #:</span>
                    <span class="info-value"><?=htmlspecialchars($txn['TRANSACTIONID'])?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date/Time:</span>
                    <span class="info-value"><?= $txn['CREATEDATE']->format('F j, Y • g:i A') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Customer:</span>
                    <span class="info-value"><?=htmlspecialchars($txn['CUSTOMERNAME'])?></span>
                </div>
                <?php if($txn['CONTACT']): ?>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span class="info-value"><?=htmlspecialchars($txn['CONTACT'])?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value badge bg-warning text-dark"><?=htmlspecialchars($txn['STATUS'])?></span>
                </div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $it):
                    $sub = $it['PRICE'] * $it['QUANTITY']; ?>
                    <tr>
                        <td><?=htmlspecialchars($it['PRODUCTNAME'])?></td>
                        <td class="text-center"><?= $it['QUANTITY'] ?></td>
                        <td class="text-right">₱<?= number_format($it['PRICE'], 2) ?></td>
                        <td class="text-right">₱<?= number_format($sub, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary-section">
            <div class="summary-row">
                <span class="summary-label">Subtotal:</span>
                <span class="summary-value">₱<?= number_format($subtotal, 2) ?></span>
            </div>
            
            <?php if($isSeniorDiscountActive): ?>
            <div class="summary-row discount-row">
                <span class="summary-label">
                    <i class="fas fa-percentage me-1"></i>
                    Senior Citizen Discount (20%)
                    <span class="senior-badge">Senior</span>
                </span>
                <span class="summary-value">-₱<?= number_format($discountApplied, 2) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="summary-row">
                <span class="summary-label">Service Charge (5%):</span>
                <span class="summary-value">+₱<?= number_format($calculatedServiceCharge, 2) ?></span>
            </div>
            
            <div class="summary-row total-row">
                <span class="summary-label total-label">TOTAL AMOUNT:</span>
                <span class="summary-value total-value">₱<?= number_format($finalTotalSaved, 2) ?></span>
            </div>
        </div>
        
        <?php if($txn['NOTES']): ?>
        <div class="mt-4 p-3" style="background: rgba(216, 156, 168, 0.1); border-radius: 12px; border-left: 4px solid var(--rose-border);">
            <h6 class="mb-2" style="color: var(--artistic-brown); font-family: 'Sawarabi Mincho';">
                <i class="fas fa-sticky-note me-2"></i>Order Notes
            </h6>
            <p class="mb-0" style="color: var(--artistic-brown);"><?=htmlspecialchars($txn['NOTES'])?></p>
        </div>
        <?php endif; ?>
        
        <div class="receipt-footer">
            <p class="thank-you">
                <i class="fas fa-heart me-1" style="color: var(--rose-border);"></i>
                Thank you for choosing Nukumori Café!
            </p>
            <p class="subtext">
                Experience the warmth of Japanese-inspired hospitality.<br>
                We hope to serve you again soon.
            </p>
            
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="index.php" class="btn-home">
                    <i class="fas fa-home me-1"></i>
                    Back to Menu
                </a>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print me-1"></i>
                    Print Receipt
                </button>
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
        
        // Add animation to receipt card
        const receiptCard = document.querySelector('.receipt-card');
        receiptCard.style.opacity = '0';
        receiptCard.style.transform = 'translateY(20px)';
        receiptCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            receiptCard.style.opacity = '1';
            receiptCard.style.transform = 'translateY(0)';
        }, 100);
        
        // Add celebration confetti effect for successful order
        setTimeout(() => {
            createConfetti();
        }, 500);
        
        function createConfetti() {
            const confettiCount = 30;
            const colors = ['#f4c542', '#c48c39', '#d89ca8', '#8B4513', '#6B8E23'];
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.top = '0';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.opacity = '0';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';
                
                document.body.appendChild(confetti);
                
                // Animation
                confetti.animate([
                    { 
                        transform: 'translateY(0) rotate(0deg)', 
                        opacity: 0 
                    },
                    { 
                        transform: `translateY(${window.innerHeight * 0.5}px) rotate(${Math.random() * 360}deg)`, 
                        opacity: 1 
                    },
                    { 
                        transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 720}deg)`, 
                        opacity: 0 
                    }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    delay: Math.random() * 500,
                    easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
                });
                
                // Remove after animation
                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.parentNode.removeChild(confetti);
                    }
                }, 3000);
            }
        }
        
        // Keyboard shortcut for printing (Ctrl + P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Esc to go home
            if (e.key === 'Escape') {
                window.location.href = 'index.php';
            }
        });
        
        // Auto-add to clipboard (transaction ID)
        function copyTransactionId() {
            const transactionId = '<?=htmlspecialchars($txn['TRANSACTIONID'])?>';
            navigator.clipboard.writeText(transactionId).then(() => {
                // Show copied notification
                const notification = document.createElement('div');
                notification.textContent = 'Transaction ID copied to clipboard!';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--olive-green);
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 10px;
                    z-index: 10000;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                    animation: slideIn 0.3s ease;
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            });
        }
        
        // Make transaction ID clickable to copy
        const transactionIdElement = document.querySelector('.info-item:first-child .info-value');
        if (transactionIdElement) {
            transactionIdElement.style.cursor = 'pointer';
            transactionIdElement.title = 'Click to copy Transaction ID';
            transactionIdElement.addEventListener('click', copyTransactionId);
        }
    });
</script>
</body>
</html>