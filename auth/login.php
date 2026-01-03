<?php include "../db.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Login | GlobeTrotter</title>
</head>
<body>

<h2>Login</h2>

<form action="auth_login.php" method="POST">
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>

  <button type="submit">Login</button>
</form>

<p><a href="#">Forgot Password?</a></p>
<p>New user? <a href="register.php">Register</a></p>

</body>
</html>
