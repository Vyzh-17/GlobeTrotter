<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/landing.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = "Username or email already exists";
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header('Location: ../dashboard/landing.php');
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Globetrotter Dashboard</title>
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
            max-width: 460px;
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
        
        /* Progress indicator */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }
        
        .step {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gray-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            position: relative;
        }
        
        .step.active {
            background: var(--primary-blue);
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -8px;
            width: 8px;
            height: 2px;
            background: var(--gray-border);
        }
        
        .step.active:not(:last-child)::after {
            background: var(--primary-blue);
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
        
        /* Success Alert */
        .success-alert {
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
            animation: slideDown 0.3s ease;
        }
        
        .success-alert i {
            color: var(--success);
            font-size: 1.125rem;
            margin-top: 1px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.875rem;
        }
        
        .form-group small {
            display: block;
            margin-top: 4px;
            color: var(--text-light);
            font-size: 0.75rem;
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
        
        /* Password strength indicator */
        .password-strength {
            margin-top: 8px;
            display: none;
        }
        
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: #ef4444;
            transition: all 0.3s ease;
        }
        
        .strength-fill.weak {
            width: 33%;
            background: #ef4444;
        }
        
        .strength-fill.medium {
            width: 66%;
            background: #f59e0b;
        }
        
        .strength-fill.strong {
            width: 100%;
            background: #10b981;
        }
        
        .strength-text {
            font-size: 0.75rem;
            color: var(--text-light);
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
        
        /* Password match indicator */
        .match-indicator {
            margin-top: 4px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .match-indicator.match {
            color: var(--success);
        }
        
        .match-indicator.mismatch {
            color: var(--error);
        }
        
        /* Terms and Conditions */
        .terms {
            margin: 24px 0;
            padding: 16px;
            background: var(--blue-bg);
            border-radius: 8px;
            border: 1px solid var(--blue-border);
        }
        
        .terms label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-dark);
        }
        
        .terms input[type="checkbox"] {
            margin-top: 3px;
            accent-color: var(--primary-blue);
        }
        
        .terms a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .terms a:hover {
            text-decoration: underline;
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
            
            .progress-steps {
                margin-bottom: 24px;
            }
        }
        
        /* Focus states for accessibility */
        .form-group input:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
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
        <div class="progress-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
        </div>
        
        <div class="auth-header">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <h1>Create Account</h1>
            <p>Join our professional travel community</p>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" required 
                           placeholder="Choose a username"
                           autocomplete="username">
                </div>
                <small>3-20 characters, letters and numbers only</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" required 
                           placeholder="your@email.com"
                           autocomplete="email">
                </div>
                <small>We'll never share your email with anyone else</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a strong password"
                           autocomplete="new-password">
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Password strength</div>
                </div>
                <small>Minimum 6 characters with letters and numbers</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Re-enter your password"
                           autocomplete="new-password">
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" aria-label="Toggle password visibility">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div class="match-indicator" id="matchIndicator"></div>
            </div>
            
            <div class="terms">
                <label>
                    <input type="checkbox" id="terms" name="terms" required>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </label>
            </div>
            
            <button type="submit" class="btn-primary" id="registerBtn" disabled>
                <span id="btnText">Create Account</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
            <p style="margin-top: 8px; font-size: 0.8125rem; color: var(--text-light);">
                By registering, you agree to our terms and privacy policy
            </p>
        </div>
    </div>
    
    <div class="copyright">
        © <?= date('Y') ?> Globetrotter. All rights reserved.
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const matchIndicator = document.getElementById('matchIndicator');
        const registerBtn = document.getElementById('registerBtn');
        const termsCheckbox = document.getElementById('terms');

        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            return strength;
        }

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0) {
                passwordStrength.style.display = 'block';
                const strength = checkPasswordStrength(password);
                
                if (strength <= 2) {
                    strengthFill.className = 'strength-fill weak';
                    strengthText.textContent = 'Weak password';
                } else if (strength <= 4) {
                    strengthFill.className = 'strength-fill medium';
                    strengthText.textContent = 'Medium strength';
                } else {
                    strengthFill.className = 'strength-fill strong';
                    strengthText.textContent = 'Strong password';
                }
            } else {
                passwordStrength.style.display = 'none';
            }
            
            checkPasswordsMatch();
        });

        function checkPasswordsMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword && password.length > 0) {
                    matchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    matchIndicator.className = 'match-indicator match';
                } else if (password.length > 0) {
                    matchIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                    matchIndicator.className = 'match-indicator mismatch';
                } else {
                    matchIndicator.innerHTML = '';
                }
            } else {
                matchIndicator.innerHTML = '';
            }
            
            updateRegisterButton();
        }

        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);

        // Password visibility toggles
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

        function setupPasswordToggle(button, input) {
            const eyeIcon = button.querySelector('i');
            
            button.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                if (type === 'password') {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    button.setAttribute('aria-label', 'Show password');
                } else {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                    button.setAttribute('aria-label', 'Hide password');
                }
            });
        }

        setupPasswordToggle(togglePassword, passwordInput);
        setupPasswordToggle(toggleConfirmPassword, confirmPasswordInput);

        // Update register button state
        function updateRegisterButton() {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const termsChecked = termsCheckbox.checked;
            
            const allFilled = username && email && password && confirmPassword;
            const passwordsMatch = password === confirmPassword && password.length > 0;
            const passwordValid = password.length >= 6;
            
            registerBtn.disabled = !(allFilled && passwordsMatch && passwordValid && termsChecked);
        }

        // Add event listeners for form validation
        document.getElementById('username').addEventListener('input', updateRegisterButton);
        document.getElementById('email').addEventListener('input', updateRegisterButton);
        termsCheckbox.addEventListener('change', updateRegisterButton);

        // Form submission handler
        const registerForm = document.getElementById('registerForm');
        const btnText = document.getElementById('btnText');
        const btnIcon = registerBtn.querySelector('.fa-arrow-right');

        registerForm.addEventListener('submit', function(e) {
            // Additional client-side validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                e.preventDefault();
                return;
            }
            
            if (!termsCheckbox.checked) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            registerBtn.disabled = true;
            registerBtn.classList.add('btn-loading');
            btnText.textContent = 'Creating account...';
            btnIcon.style.display = 'none';
            
            // Add spinner
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            registerBtn.appendChild(spinner);
        });

        // Auto-focus first input on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>