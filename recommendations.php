<?php include_once("header.php")?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">Recommendations for you</h2>

<?php
  if (!$is_buyer) {
    echo '<p>Log in with a buyer account to see recommendations.</p></div>';
    include_once("footer.php");
    exit;
  }
  $user_id = (int)$_SESSION['user_id'];

  // Collaborative filtering:
  //   mine  = the auctions I have bid on
  //   peer  = other people who bid on those same auctions ("similar bidders")
  //   other = the auctions those similar bidders have bid on
  // Each candidate auction scores the number of similar bidders on it. We only
  // keep auctions that are still running, that I haven't bid on, and that
  // aren't my own.
  $stmt = $conn->prepare(
    "SELECT a.auctionID, a.title, a.description, a.endTime, rec.similarBidders,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auctionID = a.auctionID), a.startingPrice) AS currentPrice,
            (SELECT COUNT(*) FROM bids WHERE auctionID = a.auctionID)                                AS numBids
     FROM (
       SELECT other.auctionID, COUNT(DISTINCT other.buyerID) AS similarBidders
       FROM bids mine
       JOIN bids peer  ON peer.auctionID = mine.auctionID AND peer.buyerID <> mine.buyerID
       JOIN bids other ON other.buyerID = peer.buyerID
       WHERE mine.buyerID = ?
       GROUP BY other.auctionID
     ) rec
     JOIN auctions a ON a.auctionID = rec.auctionID
     WHERE a.status = 'active' AND a.endTime > NOW()
       AND a.sellerID <> ?
       AND NOT EXISTS (SELECT 1 FROM bids x WHERE x.auctionID = a.auctionID AND x.buyerID = ?)
     ORDER BY rec.similarBidders DESC, a.endTime ASC
     LIMIT 10");
  $stmt->bind_param('iii', $user_id, $user_id, $user_id);
  $stmt->execute();
  $recs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  if ($recs) {
    echo '<p class="text-muted">People who bid on the same things as you are also bidding on these.</p>';
    echo '<ul class="list-group">';
    foreach ($recs as $r) {
      $n    = (int)$r['similarBidders'];
      $note = '<p class="text-muted mb-1 small">' . $n . ' similar ' . ($n === 1 ? 'bidder is' : 'bidders are') . ' bidding on this</p>';
      print_listing_li($r['auctionID'], $r['title'], $r['description'],
                       $r['currentPrice'], $r['numBids'], new DateTime($r['endTime']), $note);
    }
    echo '</ul>';
  } else {
    // No bidding history to compare yet: fall back to the most viewed running
    // auctions (from auction_views) that the user isn't selling or bidding on.
    $stmt = $conn->prepare(
      "SELECT a.auctionID, a.title, a.description, a.endTime,
              COALESCE((SELECT MAX(amount) FROM bids WHERE auctionID = a.auctionID), a.startingPrice) AS currentPrice,
              (SELECT COUNT(*) FROM bids WHERE auctionID = a.auctionID)                                AS numBids,
              (SELECT COUNT(*) FROM auction_views WHERE auctionID = a.auctionID)                       AS numViews
       FROM auctions a
       WHERE a.status = 'active' AND a.endTime > NOW() AND a.sellerID <> ?
         AND NOT EXISTS (SELECT 1 FROM bids x WHERE x.auctionID = a.auctionID AND x.buyerID = ?)
       ORDER BY numViews DESC, a.endTime ASC
       LIMIT 10");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $popular = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($popular) {
      echo '<p class="text-muted">Nothing to recommend from similar bidders right now (this needs you and others to have bid on some of the same items). Here are the most viewed auctions instead.</p>';
      echo '<ul class="list-group">';
      foreach ($popular as $r) {
        $note = '<p class="text-muted mb-1 small">' . (int)$r['numViews'] . ' views</p>';
        print_listing_li($r['auctionID'], $r['title'], $r['description'],
                         $r['currentPrice'], $r['numBids'], new DateTime($r['endTime']), $note);
      }
      echo '</ul>';
    } else {
      echo '<p>No recommendations right now. <a href="browse.php">Browse all auctions</a>.</p>';
    }
  }
?>

</div>

<?php include_once("footer.php")?>
