<?php
include "../db.php";

$first  = $_POST['first_name'];
$last   = $_POST['last_name'];
$email  = $_POST['email'];
$mobile = $_POST['mobile'];
$city   = $_POST['city'];
$pass   = $_POST['password'];

/* Check if email already exists */
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
  echo "Email already registered. <a href='register.php'>Go back</a>";
  exit;
}

/* Insert user */
$query = "INSERT INTO users
(first_name, last_name, email, mobile, city, password)
VALUES
('$first','$last','$email','$mobile','$city','$pass')";

mysqli_query($conn, $query);

/* Redirect to login */
header("Location: login.php");
