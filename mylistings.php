<?php include_once("header.php")?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">My listings</h2>

<?php
  // Every auction the logged-in seller has created, with its current price,
  // number of bids, page views and outcome.
  if (!$is_seller) {
    echo '<p>Log in with a seller account to see your listings.</p></div>';
    include_once("footer.php");
    exit;
  }
  $user_id = (int)$_SESSION['user_id'];

  // Bids and views are counted in subqueries rather than joins, so the two
  // counts don't multiply each other.
  $stmt = $conn->prepare(
    "SELECT a.auctionID, a.title, a.description, a.endTime, a.status, a.reservePrice,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auctionID = a.auctionID), a.startingPrice) AS currentPrice,
            (SELECT COUNT(*) FROM bids WHERE auctionID = a.auctionID)                                AS numBids,
            (SELECT COUNT(*) FROM auction_views WHERE auctionID = a.auctionID)                       AS numViews
     FROM auctions a
     WHERE a.sellerID = ?
     ORDER BY (a.status = 'active') DESC, a.endTime ASC");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  if (!$rows) {
    echo '<p>You haven\'t listed anything yet. <a href="create_auction.php">Create an auction</a>.</p>';
  } else {
    echo '<ul class="list-group">';
    foreach ($rows as $r) {
      $views = (int)$r['numViews'] . ((int)$r['numViews'] === 1 ? ' view' : ' views');
      if ($r['status'] === 'sold') {
        $outcome = '<span class="text-success">Sold</span>';
      } elseif ($r['status'] === 'unsold') {
        $outcome = '<span class="text-muted">Ended without a sale</span>';
      } elseif ((int)$r['numBids'] > 0 && (float)$r['currentPrice'] >= (float)$r['reservePrice']) {
        $outcome = '<span class="text-success">Running, reserve met</span>';
      } else {
        $outcome = '<span class="text-muted">Running, reserve not met yet</span>';
      }
      $note = "<p class=\"mb-1\">$outcome &middot; $views</p>";
      print_listing_li($r['auctionID'], $r['title'], $r['description'],
                       $r['currentPrice'], $r['numBids'], new DateTime($r['endTime']), $note);
    }
    echo '</ul>';
  }
?>

</div>

<?php include_once("footer.php")?>
