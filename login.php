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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM USERS WHERE USERNAME = '$username' AND PASSWORDHASH = '$password'";
    $result = sqlsrv_query($conn, $sql);

    if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $_SESSION['user'] = [
            'id' => $row['USERID'],
            'username' => $row['USERNAME'],
            'role' => $row['ROLE']
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login • Nukumori Zen Café</title>
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
            display: flex;
            align-items: center;
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
        
        .login-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Login Card */
        .login-card {
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
        
        .login-card::before {
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
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-title {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 700;
            font-size: 3rem;
            color: var(--artistic-brown);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            font-family: 'Noto Serif JP', serif;
            font-size: 1.2rem;
            opacity: 0.9;
            color: var(--artistic-brown);
        }
        
        /* Form Labels */
        .form-label {
            font-family: 'Sawarabi Mincho', 'Noto Serif JP', serif;
            font-weight: 600;
            color: var(--artistic-brown);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Form Controls */
        .form-control {
            background: white;
            border: 2px solid var(--rose-border);
            border-radius: 12px;
            color: var(--artistic-brown);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(216, 156, 168, 0.1);
        }
        
        .form-control:focus {
            border-color: var(--vangogh-yellow);
            box-shadow: 0 0 0 0.25rem rgba(244, 197, 66, 0.25);
            color: var(--artistic-brown);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(139, 69, 19, 0.5);
        }
        
        /* Input Groups */
        .input-group-text {
            background: var(--light-beige);
            border: 2px solid var(--rose-border);
            border-right: none;
            color: var(--artistic-brown);
            font-size: 1rem;
        }
        
        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--artistic-brown);
            cursor: pointer;
            z-index: 10;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .toggle-password:hover {
            opacity: 1;
            color: var(--vangogh-yellow);
        }
        
        /* Error Alert */
        .error-alert {
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
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, var(--rose-border), #e8a0b7);
            color: var(--artistic-brown);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            box-shadow: 0 4px 15px rgba(216, 156, 168, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #e8a0b7, var(--rose-border));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(216, 156, 168, 0.4);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid rgba(216, 156, 168, 0.3);
        }
        
        .register-link p {
            color: var(--artistic-brown);
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .register-link a {
            background: linear-gradient(135deg, var(--olive-green), #5a7d1e);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 142, 35, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .register-link a:hover {
            background: linear-gradient(135deg, #5a7d1e, var(--olive-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 142, 35, 0.4);
            color: white;
        }
        
        /* Back Button */
        .back-home {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 10;
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
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #6B4226, var(--artistic-brown));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
            color: white;
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
        
        /* Loading Spinner */
        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        @media (max-width: 768px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 2.2rem;
            }
            
            .back-home {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
                text-align: center;
            }
            
            .btn-back {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem 1rem;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
            
            .login-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div id="sakura-container"></div>

<div class="back-home">
    <a href="index.php" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>
        Back to Café
    </a>
</div>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1 class="login-title">
                <i class="fas fa-sign-in-alt me-2"></i>
                ログイン<br>
                <small style="font-size: 1.5rem;">Login</small>
            </h1>
            <p class="login-subtitle">Welcome back to Nukumori Zen Café. Experience warmth in every visit.</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-triangle" style="color: var(--danger-red);"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <div class="mb-4">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>
                    Username
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user-circle"></i>
                    </span>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           class="form-control" 
                           required 
                           placeholder="Enter your username"
                           autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-key me-2"></i>
                    Password
                </label>
                <div class="password-wrapper">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               required 
                               placeholder="Enter your password"
                               autocomplete="current-password">
                    </div>
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="loginButton">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login to Nukumori
            </button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account yet?</p>
            <a href="register.php">
                <i class="fas fa-user-plus me-1"></i>
                Create an Account
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
        
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' 
                ? '<i class="fas fa-eye"></i>' 
                : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Form submission handler
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Basic validation
            if (!username || !password) {
                e.preventDefault();
                showError('Please fill in all fields.');
                return;
            }
            
            // Show loading state
            const originalText = loginButton.innerHTML;
            loginButton.disabled = true;
            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            
            // Re-enable button after 5 seconds if still disabled
            setTimeout(() => {
                if (loginButton.disabled) {
                    loginButton.disabled = false;
                    loginButton.innerHTML = originalText;
                }
            }, 5000);
        });
        
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
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: var(--danger-red);"></i>
                ${message}
            `;
            
            // Insert after header
            const header = document.querySelector('.login-header');
            header.parentNode.insertBefore(errorDiv, header.nextSibling);
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Add focus effects to form inputs
        const formInputs = document.querySelectorAll('.form-control');
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
        
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Enter key to submit form
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !loginButton.disabled) {
                const focused = document.activeElement;
                if (focused.tagName === 'INPUT' && focused.type !== 'submit') {
                    loginForm.requestSubmit();
                }
            }
        });
        
        // Add animation to card on load
        const loginCard = document.querySelector('.login-card');
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(20px)';
        loginCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0)';
        }, 100);
    });
</script>
</body>
</html>