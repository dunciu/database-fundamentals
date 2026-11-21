<?php include_once("header.php")?>
<?php require("utilities.php")?>

<?php
  // Get info from the URL:
  $item_id = (int)($_GET['item_id'] ?? 0);

  // Message left by place_bid.php (read and cleared first, so it can't
  // linger and show up on a different page later).
  $bid_message = $_SESSION['bid_message'] ?? null;
  $bid_ok      = $_SESSION['bid_ok'] ?? false;
  unset($_SESSION['bid_message'], $_SESSION['bid_ok']);

  // Load the auction and its current price from the database.
  require_once("auction_functions.php");
  require_once("bid_functions.php");
  $auction = get_auction($conn, $item_id);
  if (!$auction) {
    echo '<div class="container my-4"><p>Auction not found.</p></div>';
    include_once("footer.php");
    exit;
  }

  $title         = $auction['title'];
  $description   = $auction['description'];
  $end_time      = new DateTime($auction['endTime']);
  $price_info    = get_current_price($conn, $item_id);
  $current_price = (float)$price_info['currentPrice'];
  $num_bids      = (int)$price_info['numBids'];
  $min_bid       = get_minimum_bid($price_info);
  $viewer_id     = $logged_in ? (int)$_SESSION['user_id'] : null;
  $is_own        = $viewer_id !== null && $viewer_id === (int)$auction['sellerID'];

  // Count this page view (guests are stored with a NULL user), then read the total.
  record_auction_view($conn, $item_id, $viewer_id);
  $view_count = get_view_count($conn, $item_id);

  // header.php has already closed this auction if its end time has passed,
  // so status tells us whether it's still running, and how it ended.
  $now      = new DateTime();
  $is_ended = $auction['status'] !== 'active' || $now >= $end_time;
  $winning  = ($auction['status'] === 'sold') ? get_winning_bid($conn, $item_id) : null;
  $reserve_met = $num_bids > 0 && $current_price >= (float)$auction['reservePrice'];

  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }
  
  // You have to login to add auction to watchlist
  // check whether it's already being watched
 require_once("db.php");
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

<?php if ($bid_message !== null): ?>
  <div class="alert <?= $bid_ok ? 'alert-success' : 'alert-danger' ?> mt-3"><?= htmlspecialchars($bid_message) ?></div>
<?php endif; ?>

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?= htmlspecialchars($title) ?></h2>
    <?php if ($seller): ?>
    <p class="text-muted">
      Seller:
      <a href="user_profile.php?user_id=<?= $seller_id ?>"><?= htmlspecialchars($seller['firstName'] . ' ' . $seller['lastName']) ?></a>
      &middot;
      <?php if ($seller_rating['numRatings'] > 0): ?>
                <a href="user_profile.php?user_id=<?= $seller_id ?>"><?= $seller_rating['avgScore'] ?>/5 (<?= $seller_rating['numRatings'] ?> <?= $seller_rating['numRatings'] == 1 ? 'rating' : 'ratings' ?>)</a>
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
   if (!$is_ended && $has_session && !$is_own):
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
    <?= nl2br(htmlspecialchars($description)) ?>
    </div>
    <p class="text-muted mt-3">
      Condition: <?= htmlspecialchars($auction['itemCondition']) ?>
      &middot; Viewed <?= $view_count ?> <?= $view_count === 1 ? 'time' : 'times' ?>
    </p>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>
<?php if ($is_ended): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?></p>
     <?php if ($winning): ?>
       <?php if ($viewer_id === (int)$winning['buyerID']): ?>
         <p class="lead text-success">You won this auction for £<?= number_format((float)$winning['amount'], 2) ?>.</p>
       <?php elseif ($is_own): ?>
         <p class="lead">Sold to <?= htmlspecialchars($winning['firstName'] . ' ' . $winning['lastName']) ?>
           for £<?= number_format((float)$winning['amount'], 2) ?>.</p>
       <?php else: ?>
         <p class="lead">Sold for £<?= number_format((float)$winning['amount'], 2) ?>.</p>
       <?php endif; ?>
     <?php elseif ($auction['status'] === 'unsold'): ?>
       <p class="lead">Ended without a sale
         (<?= $num_bids === 0 ? 'no bids' : 'reserve price not met' ?>).</p>
     <?php endif; ?>
      <?php if ($can_feedback): ?>
       <p><a class="btn btn-outline-primary btn-sm" href="feedback_form.php?auction_id=<?= $auction_id_int ?>">Leave feedback</a></p>
     <?php endif; ?>
<?php else: ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>
    <p class="lead">
      <span id="priceLabel"><?= $num_bids === 0 ? 'Starting price' : 'Current bid' ?></span>:
      £<span id="currentPrice"><?= number_format($current_price, 2) ?></span>
      <small class="text-muted">(<span id="numBids"><?= $num_bids ?> <?= $num_bids === 1 ? 'bid' : 'bids' ?></span>)</small>
    </p>
    <p class="small <?= $reserve_met ? 'text-success' : 'text-muted' ?>" id="reserveNote">
      <?= $reserve_met ? 'Reserve price met' : 'Reserve price not met yet' ?>
    </p>

<?php if (!$logged_in): ?>
    <button type="button" class="btn btn-primary form-control" data-toggle="modal" data-target="#loginModal">Log in to bid</button>
<?php elseif ($is_own): ?>
    <p class="text-muted">This is your auction, so you can't bid on it.</p>
<?php elseif (!$is_buyer): ?>
    <p class="text-muted">Only buyer accounts can place bids.</p>
<?php else: ?>
    <!-- Bidding form: sends auction_id and bid_amount to place_bid.php -->
    <form method="POST" action="place_bid.php">
      <input type="hidden" name="auction_id" value="<?= $item_id ?>">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
	    <input type="number" class="form-control" id="bid" name="bid_amount"
	           step="0.01" min="<?= number_format($min_bid, 2, '.', '') ?>" required>
      </div>
      <small class="form-text text-muted">Minimum bid: £<span id="minBid"><?= number_format($min_bid, 2) ?></span></small>
      <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php endif ?>
<?php endif ?>


  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->

<div class="row mt-4"> <!-- Row #3: every bid, highest first -->
  <div class="col-sm-8">
    <h4>Bid history</h4>
    <div id="bidHistory"><?php print_bid_history($conn, $item_id, $viewer_id); ?></div>
  </div>
</div>



<?php include_once("footer.php")?>


<script>
<?php if (!$is_ended): ?>
// Show other users' bids as they arrive: every 5 seconds, ask bid_history.php
// for the latest bids and price, and update the page without reloading it.
setInterval(function () {
  $.getJSON('bid_history.php', {item_id: <?= $item_id ?>}, function (data) {
    $('#bidHistory').html(data.html);
    $('#currentPrice').text(data.currentPrice);
    $('#numBids').text(data.numBids + (data.numBids == 1 ? ' bid' : ' bids'));
    $('#priceLabel').text(data.numBids == 0 ? 'Starting price' : 'Current bid');
    $('#minBid').text(data.minBid);
    $('#bid').attr('min', data.minBidRaw);
    $('#reserveNote').text(data.reserveMet ? 'Reserve price met' : 'Reserve price not met yet')
                     .toggleClass('text-success', data.reserveMet).toggleClass('text-muted', !data.reserveMet);
  });
}, 5000);
<?php endif; ?>

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