<?php
// watchlist_funcs.php: called in the background by the watch / unwatch buttons on listing.php.
// Replies with "success" or "failure".
session_start();
require_once 'db.php';

if (!isset($_POST['functionname']) || !isset($_POST['arguments'])) {
  return;
}

$res = "failure";
$auction_id = (int)($_POST['arguments'][0] ?? 0);   // the page sends the auction ID as [id]

// Only logged-in buyers can watch auctions
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['is_buyer']) && $auction_id > 0) {
  $user_id = (int)$_SESSION['user_id'];

  if ($_POST['functionname'] == "add_to_watchlist") {
    // Only active auctions can be watched. INSERT IGNORE means watching something
    // you already watch does nothing (the primary key is buyerID + auctionID).
    $stmt = $conn->prepare(
      "INSERT IGNORE INTO watches (buyerID, auctionID)
       SELECT ?, auctionID FROM auctions WHERE auctionID = ? AND status = 'active'");
    $stmt->bind_param('ii', $user_id, $auction_id);
    $stmt->execute();
    $res = "success";
  }
  else if ($_POST['functionname'] == "remove_from_watchlist") {
    $stmt = $conn->prepare("DELETE FROM watches WHERE buyerID = ? AND auctionID = ?");
    $stmt->bind_param('ii', $user_id, $auction_id);
    $stmt->execute();
    $res = "success";
  }
}

echo $res;