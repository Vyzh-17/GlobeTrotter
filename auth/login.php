<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/landing.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../dashboard/landing.php');
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please fill in all fields";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Globetrotter Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --blue-dark: #1d4ed8;
            --blue-light: #3b82f6;
            --blue-bg: #eff6ff;
            --blue-border: #dbeafe;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --gray-light: #f8fafc;
            --gray-border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            background-color: var(--gray-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        /* Professional background pattern */
        .background-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(37, 99, 235, 0.03) 0%, transparent 55%),
                radial-gradient(circle at 85% 30%, rgba(37, 99, 235, 0.03) 0%, transparent 55%);
            z-index: -1;
        }
        
        /* Main container */
        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--white);
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 10px 15px -3px rgba(0, 0, 0, 0.05),
                0 20px 25px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-border);
            position: relative;
            overflow: hidden;
        }
        
        /* Accent line */
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--blue-light));
        }
        
        /* Header */
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-blue), var(--blue-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        
        .auth-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        
        .auth-header p {
            color: var(--text-light);
            font-size: 0.9375rem;
            font-weight: 400;
            line-height: 1.5;
        }
        
        /* Error Alert */
        .error-alert {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-left: 4px solid var(--error);
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }
        
        .error-alert i {
            color: var(--error);
            font-size: 1.125rem;
            margin-top: 1px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.875rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.125rem;
            pointer-events: none;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1.5px solid var(--gray-border);
            border-radius: 10px;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background-color: var(--white);
            color: var(--text-dark);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-group input:hover:not(:focus) {
            border-color: #94a3b8;
        }
        
        .form-group input::placeholder {
            color: #94a3b8;
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.125rem;
            padding: 4px;
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary-blue);
        }
        
        /* Submit Button */
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-blue), var(--blue-light));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
            margin-top: 8px;
            letter-spacing: 0.01em;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--blue-dark), var(--primary-blue));
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.15);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Footer Links */
        .auth-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-border);
        }
        
        .auth-footer p {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 8px;
        }
        
        .auth-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .auth-footer a:hover {
            color: var(--blue-dark);
        }
        
        .auth-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--primary-blue);
            transition: width 0.2s ease;
        }
        
        .auth-footer a:hover::after {
            width: 100%;
        }
        
        .forgot-password {
            display: inline-block;
            margin-top: 8px;
            font-size: 0.8125rem;
        }
        
        /* Loading state */
        .btn-loading .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .auth-container {
                padding: 40px 24px;
                margin: 0 16px;
            }
            
            .auth-header h1 {
                font-size: 1.625rem;
            }
            
            .logo-icon {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
            }
        }
        
        /* Focus states for accessibility */
        .form-group input:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
        
        /* Success message (if added later) */
        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            border-left: 4px solid var(--success);
            color: #166534;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
        }
        
        /* Copyright notice */
        .copyright {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: var(--text-light);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <i class="fas fa-globe-americas"></i>
                </div>
            </div>
            <h1>Globetrotter</h1>
            <p>Sign in to your account to continue</p>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username or email address"
                           autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password"
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" id="loginBtn">
                <span id="btnText">Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create account</a></p>
            <a href="forgot-password.php" class="forgot-password">
                <i class="fas fa-key"></i> Forgot your password?
            </a>
        </div>
    </div>
    
    <div class="copyright">
        © <?= date('Y') ?> Globetrotter. All rights reserved.
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = togglePassword.querySelector('i');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle eye icon
            if (type === 'password') {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                togglePassword.setAttribute('aria-label', 'Show password');
            } else {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                togglePassword.setAttribute('aria-label', 'Hide password');
            }
        });
        
        // Form submission handler
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');
        const btnIcon = loginBtn.querySelector('.fa-arrow-right');
        
        loginForm.addEventListener('submit', function(e) {
            // Client-side validation
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.classList.add('btn-loading');
            btnText.textContent = 'Signing in...';
            btnIcon.style.display = 'none';
            
            // Add spinner
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            loginBtn.appendChild(spinner);
        });
        
        // Input focus effects
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            const wrapper = input.closest('.input-wrapper');
            
            input.addEventListener('focus', function() {
                wrapper.style.transform = 'translateY(-1px)';
            });
            
            input.addEventListener('blur', function() {
                wrapper.style.transform = 'translateY(0)';
            });
        });
        
        // Enter key support
        loginForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !loginBtn.disabled) {
                // Submit is handled by form submit event
            }
        });
        
        // Auto-focus first input on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>