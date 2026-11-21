<?php include_once("header.php")?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">My bids</h2>

<?php
  // Every auction the logged-in buyer has bid on, with their own highest bid,
  // the current highest bid, and whether they're winning, outbid, won or lost.
  if (!$is_buyer) {
    echo '<p>Log in with a buyer account to see your bids.</p></div>';
    include_once("footer.php");
    exit;
  }
  $user_id = (int)$_SESSION['user_id'];

  // MAX(CASE ...) picks out this user's own highest bid from all the bids on
  // each auction (conditional aggregation). The winner comes from winningBidID.
  $stmt = $conn->prepare(
    "SELECT a.auctionID, a.title, a.description, a.endTime, a.status,
            MAX(b.amount)                                     AS currentPrice,
            COUNT(b.bidID)                                    AS numBids,
            MAX(CASE WHEN b.buyerID = ? THEN b.amount END)    AS myBid,
            wb.buyerID                                        AS winnerID
     FROM auctions a
     JOIN bids b       ON b.auctionID = a.auctionID
     LEFT JOIN bids wb ON wb.bidID = a.winningBidID
     WHERE a.auctionID IN (SELECT auctionID FROM bids WHERE buyerID = ?)
     GROUP BY a.auctionID, a.title, a.description, a.endTime, a.status, wb.buyerID
     ORDER BY (a.status = 'active') DESC, a.endTime ASC");
  $stmt->bind_param('ii', $user_id, $user_id);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  if (!$rows) {
    echo '<p>You haven\'t bid on anything yet. <a href="browse.php">Browse auctions</a>.</p>';
  } else {
    echo '<ul class="list-group">';
    foreach ($rows as $r) {
      $my_bid = '£' . number_format((float)$r['myBid'], 2);
      if ($r['status'] === 'active') {
        $note = ((float)$r['myBid'] >= (float)$r['currentPrice'])
          ? "<p class=\"text-success mb-1\">You're the highest bidder ($my_bid)</p>"
          : "<p class=\"text-danger mb-1\">You've been outbid. Your highest bid: $my_bid</p>";
      } elseif ($r['status'] === 'sold' && (int)$r['winnerID'] === $user_id) {
        $note = "<p class=\"text-success mb-1\">You won this auction ($my_bid)</p>";
      } elseif ($r['status'] === 'sold') {
        $note = "<p class=\"text-muted mb-1\">Ended: another bidder won. Your highest bid: $my_bid</p>";
      } else {
        $note = "<p class=\"text-muted mb-1\">Ended without a sale (reserve not met). Your highest bid: $my_bid</p>";
      }
      print_listing_li($r['auctionID'], $r['title'], $r['description'],
                       $r['currentPrice'], $r['numBids'], new DateTime($r['endTime']), $note);
    }
    echo '</ul>';
  }
?>

</div>

<?php include_once("footer.php")?>
