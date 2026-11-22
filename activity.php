<?php include_once("header.php")?>
<?php require_once("db.php")?>
<?php require_once("feedback_functions.php")?>

<div class="container">
<h2 class="my-3">My activity</h2>

<?php
if (!$logged_in) {
  echo '<p>Log in to see your activity.</p></div>';
  include_once("footer.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// Create activity row
function activity_row($conn, $sql, $placeholders = 1) {
  global $user_id;
  $stmt = $conn->prepare($sql);
  $ids  = array_fill(0, $placeholders, $user_id);
  $stmt->bind_param(str_repeat('i', $placeholders), ...$ids);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

// Prints a summary box: 
function stat_box($label, $value) {
  echo '<div class="col-6 col-md-3 mb-3"><div class="card h-100"><div class="card-body text-center py-3">
          <div style="font-size:1.6em">' . $value . '</div>
          <div class="text-muted small">' . $label . '</div>
        </div></div></div>';
}

function money($amount) {
  return ($amount === null) ? '–' : '£' . number_format((float)$amount, 2);
}
?>

<?php if ($is_buyer):
  // Bids placed and auctions 
  $bids = activity_row($conn,
    "SELECT COUNT(*) AS numBids, COUNT(DISTINCT auctionID) AS numAuctions
     FROM bids WHERE buyerID = ?");

  // Lists the auctions won, aggregate paid for them
  $won = activity_row($conn,
    "SELECT COUNT(*) AS numWon, SUM(b.amount) AS totalSpent
     FROM auctions a
     JOIN bids b ON b.bidID = a.winningBidID
     WHERE a.status = 'sold' AND b.buyerID = ?");

  // Auctions where user currently has the highest bid
  $winning = activity_row($conn,
    "SELECT COUNT(*) AS n
     FROM auctions a
     WHERE a.status = 'active' AND a.endTime > NOW()
       AND (SELECT b.buyerID FROM bids b
            WHERE b.auctionID = a.auctionID
            ORDER BY b.amount DESC, b.bidTime ASC
            LIMIT 1) = ?");

  $watching = activity_row($conn, "SELECT COUNT(*) AS n FROM watches WHERE buyerID = ?");
?>
  <h4 class="mt-4">As a buyer</h4>
  <div class="row">
    <?php
      stat_box('bids placed', $bids['numBids']);
      stat_box('auctions bid on', $bids['numAuctions']);
      stat_box('currently winning', $winning['n']);
      stat_box('auctions won', $won['numWon']);
      stat_box('spent on won items', money($won['totalSpent'] ?? 0));
      stat_box('auctions watched', $watching['n']);
    ?>
  </div>
<?php endif; ?>

<?php if ($is_seller):
  // Auctions listed as seller: how each ended, and earnings from sales
  $sales = activity_row($conn,
    "SELECT COUNT(*)                                                  AS listed,
            SUM(a.status = 'active')                                  AS running,
            SUM(a.status = 'sold')                                    AS sold,
            SUM(a.status = 'unsold')                                  AS unsold,
            SUM(CASE WHEN a.status = 'sold' THEN b.amount END)        AS earned,
            AVG(CASE WHEN a.status = 'sold' THEN b.amount END)        AS avgSale
     FROM auctions a
     LEFT JOIN bids b ON b.bidID = a.winningBidID
     WHERE a.sellerID = ?");

  // Aggregate page views across all of seller's auctions
  $views = activity_row($conn,
    "SELECT COUNT(*) AS n
     FROM auction_views v
     JOIN auctions a ON a.auctionID = v.auctionID
     WHERE a.sellerID = ?");
?>
  <h4 class="mt-4">As a seller</h4>
  <div class="row">
    <?php
      stat_box('auctions listed', $sales['listed']);
      stat_box('still running', (int)$sales['running']);
      stat_box('sold', (int)$sales['sold']);
      stat_box('ended unsold', (int)$sales['unsold']);
      stat_box('total earned', money($sales['earned'] ?? 0));
      stat_box('average sale price', money($sales['avgSale']));
      stat_box('views of your auctions', $views['n']);
    ?>
  </div>
<?php endif; ?>

<?php
  // Reputation: ratings received and ratings given
  $rating = get_user_rating($conn, $user_id);
  $given  = activity_row($conn, "SELECT COUNT(*) AS n FROM feedback WHERE giverID = ?");
?>
  <h4 class="mt-4">Reputation</h4>
  <div class="row">
    <?php
      stat_box('average rating', $rating['numRatings'] > 0 ? $rating['avgScore'] . ' / 5' : '–');
      stat_box('ratings received', $rating['numRatings']);
      stat_box('ratings given', $given['n']);
    ?>
  </div>
  <p><a href="user_profile.php?user_id=<?= $user_id ?>">See your public feedback page</a></p>

<?php
  // Recent activity: One timeline with wins, bids and new listings, newest first
  $stmt = $conn->prepare(
    "(SELECT 'bid' AS kind, b.bidTime AS happenedAt, a.auctionID, a.title, b.amount
      FROM bids b JOIN auctions a ON a.auctionID = b.auctionID
      WHERE b.buyerID = ?)
     UNION ALL
     (SELECT 'won', a.endTime, a.auctionID, a.title, b.amount
      FROM auctions a JOIN bids b ON b.bidID = a.winningBidID
      WHERE a.status = 'sold' AND b.buyerID = ?)
     UNION ALL
     (SELECT 'listed', a.startTime, a.auctionID, a.title, a.startingPrice
      FROM auctions a
      WHERE a.sellerID = ?)
     ORDER BY happenedAt DESC
     LIMIT 10");
  $stmt->bind_param('iii', $user_id, $user_id, $user_id);
  $stmt->execute();
  $recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
  <h4 class="mt-4">Recent activity</h4>
<?php if (!$recent): ?>
  <p>Nothing yet.</p>
<?php else: ?>
  <ul class="list-group mb-4">
  <?php foreach ($recent as $e):
    $link = '<a href="listing.php?item_id=' . $e['auctionID'] . '">' . htmlspecialchars($e['title']) . '</a>';
    switch ($e['kind']) {
      case 'bid':    $msg = 'You bid ' . money($e['amount']) . ' on ' . $link; break;
      case 'won':    $msg = 'You won ' . $link . ' for ' . money($e['amount']); break;
      case 'listed': $msg = 'You listed ' . $link . ', starting at ' . money($e['amount']); break;
    }
  ?>
    <li class="list-group-item d-flex justify-content-between">
      <span><?= $msg ?></span>
      <small class="text-muted text-nowrap ml-3"><?= date('j M H:i', strtotime($e['happenedAt'])) ?></small>
    </li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>

</div>

<?php include_once("footer.php")?>