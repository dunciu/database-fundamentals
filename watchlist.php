<?php include_once("header.php")?>
<?php require_once("db.php")?>
<?php require("utilities.php")?>

<div class="container">
<h2 class="my-3">My watchlist</h2>

<?php
if (!$is_buyer) {
  echo '<p>Log in as a buyer to see your watchlist.</p></div>';
  include_once("footer.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// The Remove buttons below post back to this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
  $remove_id = (int)$_POST['remove_id'];
  $stmt = $conn->prepare("DELETE FROM watches WHERE buyerID = ? AND auctionID = ?");
  $stmt->bind_param('ii', $user_id, $remove_id);
  $stmt->execute();
}

// Each watched auction, with its current price (highest bid, or the starting price
// if there are no bids yet) and number of bids. Ending soonest first.
$stmt = $conn->prepare(
  "SELECT a.auctionID, a.title, a.endTime,
          COALESCE(MAX(b.amount), a.startingPrice) AS currentPrice,
          COUNT(b.bidID) AS numBids
   FROM watches w
   JOIN auctions a  ON a.auctionID = w.auctionID
   LEFT JOIN bids b ON b.auctionID = a.auctionID
   WHERE w.buyerID = ?
   GROUP BY a.auctionID, a.title, a.endTime, a.startingPrice
   ORDER BY a.endTime ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$rows) {
  echo "<p>You aren't watching any auctions yet. Use <em>+ Add to watchlist</em> on an auction's page.</p>";
}
else {
  echo '<ul class="list-group">';
  $now = new DateTime();
  foreach ($rows as $r) {
    $end  = new DateTime($r['endTime']);
    $when = ($now < $end) ? 'Ends in ' . display_time_remaining(date_diff($now, $end)) : 'Ended';
    $bids = $r['numBids'] . ($r['numBids'] == 1 ? ' bid' : ' bids');
    echo '<li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1"><a href="listing.php?item_id=' . $r['auctionID'] . '">' . htmlspecialchars($r['title']) . '</a></h5>
              <small class="text-muted">£' . number_format($r['currentPrice'], 2) . ' &middot; ' . $bids . ' &middot; ' . $when . '</small>
            </div>
            <form method="POST" class="mb-0">
              <input type="hidden" name="remove_id" value="' . $r['auctionID'] . '">
              <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
            </form>
          </li>';
  }
  echo '</ul>';
}
?>
</div>

<?php include_once("footer.php")?>