<?php include_once("header.php")?>
<?php require("utilities.php")?>
<?php require_once("db.php")?>

<?php
  // Get info from the URL:
  $item_id = filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT);
  if (!$item_id || $item_id < 1) {
    http_response_code(404);
    echo '<div class="container"><p class="my-4">Auction not found.</p></div>';
    include_once("footer.php");
    exit;
  }

  // Load the auction and its current bid summary in one query.
  $stmt = $conn->prepare(
    "SELECT a.auctionID, a.title, a.description, a.startingPrice,
            a.endTime, a.status, a.winningBidID,
            COALESCE(MAX(b.amount), a.startingPrice) AS currentPrice,
            COUNT(b.bidID) AS numBids,
            wb.amount AS winningAmount,
            wu.firstName AS winnerFirstName,
            wu.lastName AS winnerLastName
     FROM auctions a
     LEFT JOIN bids b ON b.auctionID = a.auctionID
     LEFT JOIN bids wb ON wb.bidID = a.winningBidID
                       AND wb.auctionID = a.auctionID
     LEFT JOIN users wu ON wu.userID = wb.buyerID
     WHERE a.auctionID = ?
     GROUP BY a.auctionID, a.title, a.description, a.startingPrice,
              a.endTime, a.status, a.winningBidID, wb.amount,
              wu.firstName, wu.lastName");
  $stmt->bind_param('i', $item_id);
  $stmt->execute();
  $auction = $stmt->get_result()->fetch_assoc();

  if (!$auction) {
    http_response_code(404);
    echo '<div class="container"><p class="my-4">Auction not found.</p></div>';
    include_once("footer.php");
    exit;
  }

  $title         = $auction['title'];
  $description   = $auction['description'];
  $current_price = (float)$auction['currentPrice'];
  $num_bids      = (int)$auction['numBids'];
  $end_time      = new DateTime($auction['endTime']);
  $auction_status = $auction['status'];
  $winner = $auction_status === 'sold'
          && $auction['winningAmount'] !== null
          ? [
              'amount' => $auction['winningAmount'],
              'firstName' => $auction['winnerFirstName'],
              'lastName' => $auction['winnerLastName']
            ]
          : null;

  // Record this page view, including anonymous visitors.
  $view_user_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
  if ($view_user_id === null) {
    $view_stmt = $conn->prepare(
      "INSERT INTO auction_views (auctionID, userID) VALUES (?, NULL)");
    $view_stmt->bind_param('i', $item_id);
  }
  else {
    $view_stmt = $conn->prepare(
      "INSERT INTO auction_views (auctionID, userID) VALUES (?, ?)");
    $view_stmt->bind_param('ii', $item_id, $view_user_id);
  }
  $view_stmt->execute();

  // Calculate time to auction end:
  $now = new DateTime();
  
  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }
  
  // You have to login to add auction to watchlist
  // check whether it's already being watched
  $has_session = $is_buyer;
  $watching    = false;
  if ($has_session) {
    $stmt = $conn->prepare("SELECT 1 FROM watches WHERE buyerID = ? AND auctionID = ?");
    $watch_user    = (int)$_SESSION['user_id'];
    $watch_auction = (int)$item_id;
    $stmt->bind_param('ii', $watch_user, $watch_auction);
    $stmt->execute();
    $watching = $stmt->get_result()->num_rows > 0;
  }
  // All feedback info: seller deets, rating, and whether user can leave feedback
  require_once("feedback_functions.php");
  $auction_id_int = (int)$item_id;
  $seller_id      = get_auction_seller($conn, $auction_id_int);
  $seller         = $seller_id ? get_user_public($conn, $seller_id) : null;
  $seller_rating  = $seller_id ? get_user_rating($conn, $seller_id) : null;
  $can_feedback   = isset($_SESSION['user_id'])
                 && get_feedback_receiver($conn, $auction_id_int, (int)$_SESSION['user_id']) !== null;
?>


<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>
    <?php if ($seller): ?>
    <p class="text-muted">
      Seller:
      <a href="user_profile.php?user_id=<?= $seller_id ?>"><?= htmlspecialchars($seller['firstName'] . ' ' . $seller['lastName']) ?></a>
      &middot;
      <?php if ($seller_rating['numRatings'] > 0): ?>
                <a href="user_profile.php?user_id=<?= $seller_id ?>"><?= $seller_rating['avgScore'] ?>/5.0 (<?= $seller_rating['numRatings'] ?> <?= $seller_rating['numRatings'] == 1 ? 'rating' : 'ratings' ?>)</a>
      <?php else: ?>
        no ratings yet
      <?php endif; ?>
    </p>
<?php endif; ?>
  </div>
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php
  /* The following watchlist functionality uses JavaScript, but could
     just as easily use PHP as in other places in the code */
   if ($now < $end_time && $has_session):
?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <div class="itemDescription">
    <?php echo htmlspecialchars($description); ?>
    </div>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>
<?php if ($now > $end_time): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?>
      <?php if ($auction_status === 'sold' && $winner): ?>
        <p class="text-success">Sold for £<?= number_format((float)$winner['amount'], 2) ?>.</p>
      <?php else: ?>
        <p class="text-muted">This auction ended without a sale.</p>
      <?php endif; ?>
      <?php if ($can_feedback): ?>
       <p><a class="btn btn-outline-primary btn-sm" href="feedback_form.php?auction_id=<?= $auction_id_int ?>">Leave feedback</a></p>
     <?php endif; ?>
<?php else: ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 2)) ?></p>

    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
      <input type="hidden" name="auction_id" value="<?= $item_id ?>">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
	    <input type="number" class="form-control" id="bid" name="amount"
	           min="<?= number_format($num_bids > 0 ? $current_price + 0.01 : (float)$auction['startingPrice'], 2, '.', '') ?>"
	           step="0.01" data-money-input required>
      </div>
      <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php endif ?>

  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->



<?php include_once("footer.php")?>


<script> 
document.querySelectorAll('[data-money-input]').forEach(function (input) {
  input.addEventListener('input', function () {
    var parts = input.value.split('.');
    if (parts.length > 1) {
      input.value = parts[0] + '.' + parts[1].slice(0, 2);
    }
  });
});

// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist(button) {
  console.log("These print statements are helpful for debugging btw");

  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func

function removeFromWatchlist(button) {
  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func
</script>