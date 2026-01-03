<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/landing.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .auth-logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo">🌍</div>
            <h1>Globetrotter</h1>
            <p>Your travel planning companion</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert error" style="background: #fed7d7; color: #e53e3e; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>