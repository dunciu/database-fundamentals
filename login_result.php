<?php
// cross references email, password against the DB -> logs the user in.
session_start();
require_once("db.php");

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare(
    "SELECT userID, passwordHash, firstName, isBuyer, isSeller
     FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['passwordHash'])) {
    session_regenerate_id(true);   // fresh session ID on login (stops session fixation)
    $_SESSION['logged_in']  = true;
    $_SESSION['user_id']    = (int)$user['userID'];
    $_SESSION['first_name'] = $user['firstName'];
    $_SESSION['is_buyer']   = (bool)$user['isBuyer'];
    $_SESSION['is_seller']  = (bool)$user['isSeller'];
    header('Location: browse.php');
    exit;
}

// Ensurees same message regardless of whether email or the password was wrong
include_once("header.php");
?>
<div class="container my-4 text-center">
  <p class="text-danger">Incorrect email or password.</p>
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#loginModal">Try again</button>
  or <a href="register.php">create an account</a>
</div>
<?php include_once("footer.php"); ?>