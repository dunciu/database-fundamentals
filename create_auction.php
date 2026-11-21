<?php include_once("header.php")?>
<?php
require_once("db.php");

$categories = $conn->query("SELECT categoryID, name FROM categories ORDER BY name");
if ($categories === false) {
  exit('<div class="text-center text-danger">Unable to load auction categories.</div>');
}
?>

<?php
  // Only logged-in sellers can create auctions. ($is_seller comes from header.php,
  // which has already printed the page top, so show a message instead of redirecting.)
  if (!$is_seller) {
    echo '<div class="container my-4"><p class="text-danger">You need to be logged in with a seller account to create an auction.</p></div>';
    include_once("footer.php");
    exit;
  }
?>

<div class="container">

<!-- Create auction form -->
<div style="max-width: 800px; margin: 10px auto">
  <h2 class="my-3">Create new auction</h2>
  <div class="card">
    <div class="card-body">
      <!-- Note: This form does not do any dynamic / client-side / 
      JavaScript-based validation of data. It only performs checking after 
      the form has been submitted, and only allows users to try once. You 
      can make this fancier using JavaScript to alert users of invalid data
      before they try to send it, but that kind of functionality should be
      extremely low-priority / only done after all database functions are
      complete. -->
      <form method="post" action="create_auction_result.php">
        <div class="form-group row">
          <label for="auctionTitle" class="col-sm-2 col-form-label text-right">Title of auction</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="auctionTitle" name="title" placeholder="e.g. Black mountain bike" required>
            <small id="titleHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> A short description of the item you're selling, which will display in listings.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionDetails" class="col-sm-2 col-form-label text-right">Details</label>
          <div class="col-sm-10">
            <textarea class="form-control" id="auctionDetails" name="description" rows="4" required></textarea>
            <small id="detailsHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Full details of the listing to help bidders decide if it's what they're looking for.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionCategory" class="col-sm-2 col-form-label text-right">Category</label>
          <div class="col-sm-10">
            <select class="form-control" id="auctionCategory" name="category_id" required>
              <option selected disabled value="">Choose...</option>
              <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?= (int)$category['categoryID'] ?>">
                  <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endwhile; ?>
            </select>
            <small id="categoryHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Select a category for this item.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionStartPrice" class="col-sm-2 col-form-label text-right">Starting price</label>
          <div class="col-sm-10">
	        <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" class="form-control" id="auctionStartPrice" name="starting_price" min="0.01" step="0.01" data-money-input required>
            </div>
            <small id="startBidHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Initial bid amount.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionReservePrice" class="col-sm-2 col-form-label text-right">Reserve price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" class="form-control" id="auctionReservePrice" name="reserve_price" min="0.01" step="0.01" data-money-input>
            </div>
            <small id="reservePriceHelp" class="form-text text-muted">Optional. Auctions that end below this price will not go through. This value is not displayed in the auction listing.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionEndDate" class="col-sm-2 col-form-label text-right">End date</label>
          <div class="col-sm-10">
            <input type="datetime-local" class="form-control" id="auctionEndDate" name="end_time" min="<?= date('Y-m-d\TH:i') ?>" required>
            <small id="endDateHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Day for the auction to end.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionCondition" class="col-sm-2 col-form-label text-right">Condition</label>
          <div class="col-sm-10">
            <select class="form-control" id="auctionCondition" name="item_condition" required>
              <option selected disabled value="">Choose...</option>
              <option value="New">New</option>
              <option value="Like new">Like new</option>
              <option value="Good">Good</option>
              <option value="Fair">Fair</option>
              <option value="Poor">Poor</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary form-control">Create Auction</button>
      </form>
    </div>
  </div>
</div>

</div>

<script>
  document.querySelectorAll('[data-money-input]').forEach(function (input) {
    input.addEventListener('input', function () {
      var parts = input.value.split('.');
      if (parts.length > 1) {
        input.value = parts[0] + '.' + parts[1].slice(0, 2);
      }
    });
  });
</script>

<?php include_once("footer.php")?>