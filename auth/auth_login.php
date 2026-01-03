<?php
include "../db.php";

$email = $_POST['email'];
$pass  = $_POST['password'];

$query = "SELECT * FROM users WHERE email='$email' AND password='$pass'";
$result = mysqli_query($conn, $query);

$user = mysqli_fetch_assoc($result);

if ($user) {
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['name']    = $user['first_name'];

  /* Redirect to dashboard */
  header("Location: ../dashboard/landing.php");
} else {
  echo "Invalid login. <a href='login.php'>Try again</a>";
}
