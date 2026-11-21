<?php include_once("header.php")?>
<?php require_once("db.php")?>

<div class="container">
<h2 class="my-3">Notifications</h2>

<?php
if (!$logged_in) {
  echo '<p>Log in to see your notifications.</p></div>';
  include_once("footer.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// Each notification with its auction, and the bid that triggered it (if any)
$stmt = $conn->prepare(
  "SELECT n.type, n.sentAt, a.auctionID, a.title, a.status, b.amount
   FROM notifications n
   JOIN auctions a  ON a.auctionID = n.auctionID
   LEFT JOIN bids b ON b.bidID = n.bidID
   WHERE n.userID = ?
   ORDER BY n.sentAt DESC, n.notificationID DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$rows) {
  echo "<p>No notifications yet.</p>";
}
else {
  echo '<ul class="list-group">';
  foreach ($rows as $n) {
    $link   = '<a href="listing.php?item_id=' . $n['auctionID'] . '">' . htmlspecialchars($n['title']) . '</a>';
    $amount = ($n['amount'] !== null) ? '£' . number_format($n['amount'], 2) : '';

    switch ($n['type']) {
      case 'outbid':
        $msg = "You've been outbid on $link. The highest bid is now $amount.";
        break;
      case 'newBid':
        $msg = "New bid of $amount on $link, which you're watching.";
        break;
      case 'auctionWon':
        $msg = "You won $link. Don't forget to leave feedback for the seller.";
        break;
      case 'auctionEnded':
        $msg = ($n['status'] === 'sold')
             ? "Your auction $link has ended and sold."
             : "Your auction $link has ended without a sale.";
        break;
      default:
        $msg = "Update on $link.";
    }

    echo '<li class="list-group-item d-flex justify-content-between">
            <span>' . $msg . '</span>
            <small class="text-muted text-nowrap ml-3">' . date('j M H:i', strtotime($n['sentAt'])) . '</small>
          </li>';
  }
  echo '</ul>';
}
?>
</div>

<?php include_once("footer.php")?>