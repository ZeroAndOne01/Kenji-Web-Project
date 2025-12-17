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

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if(empty($_SESSION['cart'])) { 
    header('Location: index.php'); 
    exit; 
}

// Check if Senior Citizen discount is active
$isSenior = $_SESSION['is_senior'] ?? false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = [
        'name' => $_POST['name'] ?? 'Guest',
        'contact' => $_POST['contact'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    $subtotal = 0;
    foreach($_SESSION['cart'] as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
    }

    // APPLY DISCOUNT LOGIC FOR DATABASE
    $discount = $isSenior ? ($subtotal * 0.20) : 0;
    $serviceCharge = $subtotal * 0.05;
    $finalTotal = ($subtotal - $discount) + $serviceCharge;

    $customerName = $customer['name'];
    $contact = $customer['contact'];
    $notes = $customer['notes'];
    
    // Save the FINAL total (after discount + service charge)
    $totalAmount = $finalTotal;

    $insertSql = "
        INSERT INTO TRANSACTIONS (CUSTOMERNAME, CONTACT, TOTALAMOUNT, NOTES, CREATEDATE, STATUS)
        OUTPUT INSERTED.TRANSACTIONID
        VALUES ('$customerName', '$contact', '$totalAmount', '$notes', GETDATE(), 'Pending')
    ";

    $insertResult = sqlsrv_query($conn, $insertSql);
    if ($insertResult === false) {
        die("Insert failed: " . print_r(sqlsrv_errors(), true));
    }

    $transactionId = null;
    if (sqlsrv_has_rows($insertResult)) {
        $row = sqlsrv_fetch_array($insertResult, SQLSRV_FETCH_ASSOC);
        if ($row !== false && isset($row['TRANSACTIONID'])) {
            $transactionId = $row['TRANSACTIONID'];
        }
    }
    
    if (!$transactionId) {
        $getIdSql = "SELECT SCOPE_IDENTITY() AS TRANSACTIONID";
        $getIdResult = sqlsrv_query($conn, $getIdSql);
        if ($getIdResult !== false) {
            $idRow = sqlsrv_fetch_array($getIdResult, SQLSRV_FETCH_ASSOC);
            if ($idRow !== false && isset($idRow['TRANSACTIONID'])) {
                $transactionId = $idRow['TRANSACTIONID'];
            }
        }
    }

    foreach($_SESSION['cart'] as $it) {
        $pid = $it['id'];
        $pname = $it['name'];
        $price = $it['price'];
        $qty = $it['quantity'];

        $itemSql = "
            INSERT INTO TRANSACTIONITEMS 
            (TRANSACTIONID, PRODUCTID, PRODUCTNAME, PRICE, QUANTITY)
            VALUES ('$transactionId', '$pid', '$pname', '$price', '$qty')
        ";
        sqlsrv_query($conn, $itemSql);
    }

    $_SESSION['last_txn'] = $transactionId;
    unset($_SESSION['cart']);
    unset($_SESSION['is_senior']); // Clear discount after order is placed

    header("Location: receipt.php?id={$transactionId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout • Nukumori Zen Café</title>
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
        
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Checkout Card */
        .checkout-card {
            background: linear-gradient(145deg, 
                        var(--light-beige) 0%,  
                        var(--soft-pink) 100%);
            border-radius: 25px;
            border: 2px solid var(--rose-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2),
                        inset 0 0 40px rgba(216, 156, 168, 0.1);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .checkout-card::before {
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
        
        .checkout-title {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--artistic-brown);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        /* Order Summary */
        .order-summary {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            border: 2px solid var(--rose-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .summary-title {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            color: var(--artistic-brown);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--rose-border);
            padding-bottom: 0.5rem;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(216, 156, 168, 0.3);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--artistic-brown);
        }
        
        .item-quantity {
            color: var(--artistic-brown);
            opacity: 0.8;
        }
        
        .item-price {
            font-weight: 700;
            color: var(--artistic-brown);
        }
        
        /* Calculation Section */
        .calculation-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--rose-border);
        }
        
        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .calc-label {
            color: var(--artistic-brown);
            opacity: 0.8;
        }
        
        .calc-value {
            font-weight: 600;
            color: var(--artistic-brown);
        }
        
        .discount-row {
            color: var(--success-green);
            font-weight: bold;
        }
        
        .total-row {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--rose-border);
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .total-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--artistic-brown);
            font-weight: 900;
        }
        
        /* Form Styles */
        .form-label {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 600;
            color: var(--artistic-brown);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .form-control, .form-textarea {
            background: white;
            border: 2px solid var(--rose-border);
            border-radius: 12px;
            color: var(--artistic-brown);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(216, 156, 168, 0.1);
        }
        
        .form-control:focus, .form-textarea:focus {
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: var(--artistic-brown);
            outline: none;
        }
        
        .form-control::placeholder, .form-textarea::placeholder {
            color: rgba(139, 69, 19, 0.5);
        }
        
        /* Buttons */
        .btn-checkout {
            background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 2rem;
            box-shadow: 0 4px 15px rgba(106, 142, 35, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-checkout:hover {
            background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 142, 35, 0.4);
        }
        
        .btn-checkout:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-back {
            background: linear-gradient(135deg, var(--artistic-brown), #6B4226);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #6B4226, var(--artistic-brown));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
            color: white;
        }
        
        /* Senior Citizen Badge */
        .senior-badge {
            background: linear-gradient(135deg, var(--vangogh-yellow), var(--starry-night));
            color: white;
            border-radius: 20px;
            padding: 0.25rem 1rem;
            font-weight: 600;
            font-size: 0.9rem;
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
            .checkout-card {
                padding: 2rem 1.5rem;
            }
            
            .checkout-title {
                font-size: 2rem;
            }
            
            .order-summary {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .checkout-card {
                padding: 1.5rem 1rem;
            }
            
            .checkout-title {
                font-size: 1.8rem;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .item-price {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
<div id="sakura-container"></div>

<div class="checkout-container">
    <div class="checkout-card">
        <h1 class="checkout-title">
            <i class="fas fa-receipt me-2"></i>
            チェックアウト<br>
            <small style="font-size: 1.5rem;">Checkout</small>
        </h1>
        
        <div class="order-summary">
            <h3 class="summary-title">
                <i class="fas fa-shopping-basket me-2"></i>
                Order Summary
            </h3>
            
            <?php 
            $subtotal = 0;
            foreach($_SESSION['cart'] as $item): 
                $itemTotal = $item['price'] * $item['quantity'];
                $subtotal += $itemTotal;
            ?>
                <div class="cart-item">
                    <div>
                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                        <span class="item-quantity ms-2">× <?= $item['quantity'] ?></span>
                    </div>
                    <div class="item-price">₱<?= number_format($itemTotal, 2) ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="calculation-section">
                <div class="calc-row">
                    <span class="calc-label">Subtotal</span>
                    <span class="calc-value">₱<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <?php 
                $discountVal = 0;
                if($isSenior): 
                    $discountVal = $subtotal * 0.20;
                ?>
                    <div class="calc-row discount-row">
                        <span class="calc-label">
                            <i class="fas fa-percentage me-1"></i>
                            Senior Citizen Discount (20%)
                            <span class="senior-badge">Senior</span>
                        </span>
                        <span class="calc-value">-₱<?= number_format($discountVal, 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="calc-row">
                    <span class="calc-label">Service Charge (5%)</span>
                    <span class="calc-value">₱<?= number_format($subtotal * 0.05, 2) ?></span>
                </div>
                
                <?php 
                $finalTotal = ($subtotal - $discountVal) + ($subtotal * 0.05);
                ?>
                <div class="calc-row total-row">
                    <span class="calc-label">Total Amount</span>
                    <span class="calc-value total-value">₱<?= number_format($finalTotal, 2) ?></span>
                </div>
            </div>
        </div>
        
        <form method="POST" id="checkoutForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>
                        Customer Name
                    </label>
                    <input type="text" 
                           name="name" 
                           class="form-control" 
                           required 
                           placeholder="Enter customer name" 
                           value="<?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>
                        Contact Number
                    </label>
                    <input type="text" 
                           name="contact" 
                           class="form-control" 
                           placeholder="Enter contact number"
                           pattern="[0-9+\s()-]*"
                           title="Enter a valid phone number">
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">
                        <i class="fas fa-sticky-note me-2"></i>
                        Special Instructions / Notes
                    </label>
                    <textarea name="notes" 
                              class="form-control form-textarea" 
                              rows="3" 
                              placeholder="Any special requests or notes for this order..."></textarea>
                </div>
                
                <?php if(!$isSenior): ?>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="senior_citizen" id="seniorCitizen">
                        <label class="form-check-label" for="seniorCitizen" style="color: var(--artistic-brown);">
                            <i class="fas fa-user-check me-1"></i>
                            Apply Senior Citizen Discount (20%)
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn-checkout" id="checkoutButton">
                <i class="fas fa-check-circle me-2"></i>
                Place Order - ₱<?= number_format($finalTotal, 2) ?>
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="cart.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Cart
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
        
        // Form submission handler
        const checkoutForm = document.getElementById('checkoutForm');
        const checkoutButton = document.getElementById('checkoutButton');
        
        checkoutForm.addEventListener('submit', function(e) {
            const nameInput = document.querySelector('input[name="name"]');
            
            // Basic validation
            if (!nameInput.value.trim()) {
                e.preventDefault();
                nameInput.focus();
                showError('Please enter customer name.');
                return;
            }
            
            // Show loading state
            const originalText = checkoutButton.innerHTML;
            checkoutButton.disabled = true;
            checkoutButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Order...';
            
            // Re-enable button after 5 seconds if still disabled
            setTimeout(() => {
                if (checkoutButton.disabled) {
                    checkoutButton.disabled = false;
                    checkoutButton.innerHTML = originalText;
                }
            }, 5000);
        });
        
        // Senior citizen discount toggle
        const seniorCheckbox = document.getElementById('seniorCitizen');
        if (seniorCheckbox) {
            seniorCheckbox.addEventListener('change', function() {
                // Create a form to set the senior discount session
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'apply_senior_discount.php';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'apply_discount';
                input.value = this.checked ? '1' : '0';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        // Function to show error message
        function showError(message) {
            // Remove existing error
            const existingError = document.querySelector('.error-alert');
            if (existingError) {
                existingError.remove();
            }
            
            // Create new error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-alert';
            errorDiv.style.cssText = `
                background: linear-gradient(135deg, 
                    rgba(231, 76, 60, 0.1) 0%, 
                    rgba(255, 255, 255, 0.9) 100%);
                border: 2px solid var(--danger-red);
                border-radius: 12px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.5rem;
                color: var(--artistic-brown);
                animation: slideIn 0.5s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            `;
            
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: var(--danger-red);"></i>
                ${message}
            `;
            
            // Insert before form
            checkoutForm.parentNode.insertBefore(errorDiv, checkoutForm);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
        
        // Add focus effects to form inputs
        const formInputs = document.querySelectorAll('.form-control, .form-textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(216, 156, 168, 0.2)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(216, 156, 168, 0.1)';
            });
        });
        
        // Auto-focus first field
        document.querySelector('input[name="name"]').focus();
        
        // Add animation to card on load
        const checkoutCard = document.querySelector('.checkout-card');
        checkoutCard.style.opacity = '0';
        checkoutCard.style.transform = 'translateY(20px)';
        checkoutCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            checkoutCard.style.opacity = '1';
            checkoutCard.style.transform = 'translateY(0)';
        }, 100);
    });
</script>
</body>
</html>