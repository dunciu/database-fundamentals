<?php include_once("header.php")?>

<div class="container my-5">

<?php
require_once("db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('<div class="text-center text-danger">Invalid request.</div>');
}

// Only logged-in sellers can create auctions. Check the role in the database,
// not just the session, in case it has changed since they logged in.
if (!$logged_in) {
    exit('<div class="text-center text-danger">Please log in to create an auction.</div>');
}
$seller_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare('SELECT isSeller FROM users WHERE userID = ?');
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();
if (!$seller || !$seller['isSeller']) {
    exit('<div class="text-center text-danger">Only seller accounts can create auctions.</div>');
}

function post_value($key) {
    return trim($_POST[$key] ?? '');
}

function is_money_value($value) {
    return preg_match('/^\d+(?:\.\d{1,2})?$/D', $value) === 1;
}

$title = post_value('title');
$description = post_value('description');
$category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
$item_condition = post_value('item_condition');
$starting_input = post_value('starting_price');
$starting_price = filter_var($starting_input, FILTER_VALIDATE_FLOAT);
$reserve_input = post_value('reserve_price');
$reserve_price = $reserve_input === ''
    ? $starting_price
    : filter_var($reserve_input, FILTER_VALIDATE_FLOAT);
$end_time = str_replace('T', ' ', post_value('end_time'));
$end_datetime = DateTime::createFromFormat('Y-m-d H:i', $end_time);

if ($title === '' || strlen($title) > 100 ||
    $description === '' || !$category_id ||
    !in_array($item_condition, ['New', 'Like new', 'Good', 'Fair', 'Poor'], true) ||
    !is_money_value($starting_input) ||
    ($reserve_input !== '' && !is_money_value($reserve_input)) ||
    $starting_price === false || $starting_price <= 0 ||
    $reserve_price === false || $reserve_price < $starting_price ||
    !$end_datetime || $end_datetime <= new DateTime()) {
    exit('<div class="text-center text-danger">Please provide valid auction details. The reserve price must be at least the starting price.</div>');
}

// The category must be one that exists (otherwise the foreign key would fail).
$stmt = $conn->prepare('SELECT 1 FROM categories WHERE categoryID = ?');
$stmt->bind_param('i', $category_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    exit('<div class="text-center text-danger">Please choose a valid category.</div>');
}

$sql = 'INSERT INTO auctions
        (sellerID, categoryID, title, description, itemCondition,
         startingPrice, reservePrice, endTime)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'iisssdds',
    $seller_id,
    $category_id,
    $title,
    $description,
    $item_condition,
    $starting_price,
    $reserve_price,
    $end_time
);
$stmt->execute();
$auction_id = $conn->insert_id;

echo('<div class="text-center">Auction successfully created! <a href="listing.php?item_id=' .
     $auction_id . '">View your new listing.</a></div>');


?>

</div>


<?php include_once("footer.php")?>