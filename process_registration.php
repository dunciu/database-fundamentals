<?php
// process_registration.php: validates the registration form and creates the account.
session_start();
require_once("db.php");

$first    = trim($_POST['firstName'] ?? '');
$last     = trim($_POST['lastName'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['passwordConfirmation'] ?? '';
$isBuyer  = isset($_POST['isBuyer'])  ? 1 : 0;
$isSeller = isset($_POST['isSeller']) ? 1 : 0;

// The form checks these too, but anyone can send a POST without using the form.
$errors = [];
if ($first === '' || $last === '')                   $errors[] = 'Enter your first and last name.';
if (mb_strlen($first) > 50 || mb_strlen($last) > 50) $errors[] = 'Names can be at most 50 characters.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'Enter a valid email address.';
if (strlen($password) < 8)                           $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm)                          $errors[] = "The two passwords don't match.";
if (!$isBuyer && !$isSeller)                         $errors[] = 'Choose buyer, seller, or both.';

if (!$errors) {
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'An account with that email already exists.';
    }
}

if (!$errors) {
    $hash = password_hash($password, PASSWORD_DEFAULT);   // never store the real password
    $stmt = $conn->prepare(
        "INSERT INTO users (email, passwordHash, firstName, lastName, isBuyer, isSeller)
         VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssii', $email, $hash, $first, $last, $isBuyer, $isSeller);
    $stmt->execute();

    // Log the new user straight in
    session_regenerate_id(true);
    $_SESSION['logged_in']  = true;
    $_SESSION['user_id']    = $conn->insert_id;
    $_SESSION['first_name'] = $first;
    $_SESSION['is_buyer']   = (bool)$isBuyer;
    $_SESSION['is_seller']  = (bool)$isSeller;
    header('Location: browse.php');
    exit;
}

include_once("header.php");
?>
<div class="container my-4">
  <h2>Registration failed</h2>
  <ul class="text-danger">
    <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
  </ul>
  <a href="register.php">Go back and try again</a>
</div>
<?php include_once("footer.php"); ?>