<?php
// place_bid.php: receives the bid form from listing.php, checks the bid,
// saves it, adds notifications, then sends the user back to the listing.
// Nothing is printed here: the result is shown on listing.php via $_SESSION.
session_start();
require_once 'bid_functions.php';
require_once 'utilities.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: browse.php');
    exit;
}

$auction_id = (int)($_POST['auction_id'] ?? 0);
$amount     = trim($_POST['bid_amount'] ?? '');
$back       = 'listing.php?item_id=' . $auction_id;

// Undo anything half-done, leave a message, and go back to the listing.
// (rollback() does nothing if no transaction has started yet.)
function reject($conn, $msg, $back) {
    $conn->rollback();
    $_SESSION['bid_message'] = $msg;
    $_SESSION['bid_ok']      = false;
    header("Location: $back");
    exit;
}

// ---- Gate 1: who are you? ----------------------------------------------
if (!isset($_SESSION['user_id'])) {
    reject($conn, 'Please log in to bid.', $back);
}
$user_id = (int)$_SESSION['user_id'];

// Check the role in the database rather than trusting the session.
$stmt = $conn->prepare("SELECT isBuyer FROM users WHERE userID = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user || !$user['isBuyer']) {
    reject($conn, 'Only buyer accounts can place bids.', $back);
}

// ---- Gate 2: is it a real amount? ----------------------------------------
// The form has min/required, but anyone can send a POST without the form.
if (!is_money_value($amount) || (float)$amount <= 0) {
    reject($conn, 'Enter your bid in pounds, for example 12.50.', $back);
}
$amount = round((float)$amount, 2);
if ($amount > 99999999.99) {                       // biggest value DECIMAL(10,2) holds
    reject($conn, 'That bid is too large.', $back);
}

// ---- Gates 3 and 4 run inside one transaction ---------------------------
$conn->begin_transaction();

// FOR UPDATE locks this auction's row. Anyone else bidding on the same
// auction waits here until we commit or roll back, so two people can't
// both "beat" the same old price at the same moment.
// The open/closed check uses MySQL's NOW(), the same clock the data uses,
// and also requires status = 'active' (it becomes 'sold' or 'unsold' once
// the auction has been closed).
$stmt = $conn->prepare(
    "SELECT sellerID, startingPrice,
            (status = 'active' AND NOW() >= startTime AND NOW() < endTime) AS isOpen
     FROM auctions
     WHERE auctionID = ?
     FOR UPDATE");
$stmt->bind_param('i', $auction_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();

// ---- Gate 3: can you bid on this auction? -------------------------------
if (!$auction) {
    reject($conn, 'That auction does not exist.', $back);
}
if (!$auction['isOpen']) {
    reject($conn, 'This auction is not open for bids.', $back);
}
if ((int)$auction['sellerID'] === $user_id) {
    reject($conn, "You can't bid on your own auction.", $back);
}

// ---- Gate 4: is it high enough? -----------------------------------------
$top = get_highest_bid($conn, $auction_id);       // null if no bids yet
if ($top !== null && $amount <= (float)$top['amount']) {
    reject($conn, 'Your bid must be more than the current bid of £'
                . number_format((float)$top['amount'], 2) . '.', $back);
}
if ($top === null && $amount < (float)$auction['startingPrice']) {
    reject($conn, 'The first bid must be at least the starting price of £'
                . number_format((float)$auction['startingPrice'], 2) . '.', $back);
}

// ---- Passed every gate: save the bid -------------------------------------
$stmt = $conn->prepare("INSERT INTO bids (auctionID, buyerID, amount) VALUES (?, ?, ?)");
$stmt->bind_param('iid', $auction_id, $user_id, $amount);
$stmt->execute();
$bid_id = (int)$conn->insert_id;   // the new bid's ID, stored on its notifications

// Tell whoever was winning that they've been outbid
// (unless they're just raising their own bid).
if ($top !== null && (int)$top['buyerID'] !== $user_id) {
    $previous_leader = (int)$top['buyerID'];
    $stmt = $conn->prepare(
        "INSERT INTO notifications (userID, auctionID, type, bidID) VALUES (?, ?, 'outbid', ?)");
    $stmt->bind_param('iii', $previous_leader, $auction_id, $bid_id);
    $stmt->execute();
}

// Tell everyone watching this auction, except the bidder, that there's a new bid.
$stmt = $conn->prepare(
    "INSERT INTO notifications (userID, auctionID, type, bidID)
     SELECT buyerID, auctionID, 'newBid', ?
     FROM watches
     WHERE auctionID = ? AND buyerID <> ?");
$stmt->bind_param('iii', $bid_id, $auction_id, $user_id);
$stmt->execute();

$conn->commit();

$_SESSION['bid_message'] = 'Bid placed: £' . number_format($amount, 2) . '. You are the highest bidder.';
$_SESSION['bid_ok']      = true;
header("Location: $back");
exit;
