<?php include "../db.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Register | GlobeTrotter</title>
</head>
<body>

<h2>Create Account</h2>

<form action="auth_register.php" method="POST">
  <input name="first_name" placeholder="First Name" required><br><br>
  <input name="last_name" placeholder="Last Name" required><br><br>
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input name="mobile" placeholder="Mobile" required><br><br>
  <input name="city" placeholder="City" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>

  <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login</a></p>

</body>
</html>
