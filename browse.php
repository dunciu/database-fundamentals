<?php include_once("header.php")?>
<?php require_once("db.php")?>
<?php require("utilities.php")?>

<?php
  // Read the search options from the URL. 
  $keyword    = trim($_GET['keyword'] ?? '');
  $category   = $_GET['cat'] ?? 'all';
  $category   = ($category === 'all') ? 'all' : (int)$category;
  $ordering   = $_GET['order_by'] ?? 'pricelow';
  $show_ended = !empty($_GET['ended']);
  $curr_page  = max(1, (int)($_GET['page'] ?? 1));
  $results_per_page = 10;

  // ORDER BY the sort must come from this fixed list, not URL
  $orderings = [
    'pricelow'  => 'currentPrice ASC, a.endTime ASC',
    'pricehigh' => 'currentPrice DESC, a.endTime ASC',
    'date'      => 'a.endTime ASC',
    'newest'    => 'a.startTime DESC',
  ];
  if (!isset($orderings[$ordering])) {
    $ordering = 'pricelow';
  }

  // Categories for the dropdown
  $categories = $conn->query("SELECT categoryID, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">

<h2 class="my-3">Browse listings</h2>

<div id="searchSpecs">
<form method="get" action="browse.php">
  <div class="row">
    <div class="col-md-5 pr-0">
      <div class="form-group">
        <label for="keyword" class="sr-only">Search keyword:</label>
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-transparent pr-0 text-muted">
              <i class="fa fa-search"></i>
            </span>
          </div>
          <input type="text" class="form-control border-left-0" id="keyword" name="keyword"
                 placeholder="Search for anything" value="<?= htmlspecialchars($keyword) ?>">
        </div>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-group">
        <label for="cat" class="sr-only">Search within:</label>
        <select class="form-control" id="cat" name="cat">
          <option value="all">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['categoryID'] ?>" <?= $category === (int)$c['categoryID'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-inline">
        <label class="mx-2" for="order_by">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by">
          <option value="pricelow"  <?= $ordering === 'pricelow'  ? 'selected' : '' ?>>Price (low to high)</option>
          <option value="pricehigh" <?= $ordering === 'pricehigh' ? 'selected' : '' ?>>Price (high to low)</option>
          <option value="date"      <?= $ordering === 'date'      ? 'selected' : '' ?>>Soonest expiry</option>
          <option value="newest"    <?= $ordering === 'newest'    ? 'selected' : '' ?>>Newly listed</option>
        </select>
      </div>
    </div>
    <div class="col-md-1 px-0">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </div>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="ended" name="ended" value="1" <?= $show_ended ? 'checked' : '' ?>>
    <label class="form-check-label" for="ended">Include ended auctions</label>
  </div>
</form>
</div> <!-- end search specs bar -->

</div>

<?php
  
  $where  = [];
  $types  = '';
  $params = [];

  if (!$show_ended) {
    $where[] = 'a.endTime > NOW()';               // running auctions only
  }
  if ($category !== 'all') {
    $where[] = 'a.categoryID = ?';
    $types  .= 'i';
    $params[] = $category;
  }

  $words = array_slice(preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY), 0, 5);
  foreach ($words as $word) {
    $like = '%' . addcslashes($word, '%_\\') . '%';   // treat % and _ as ordinary characters
    $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
    $types  .= 'ss';
    array_push($params, $like, $like);
  }
  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';


  $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM auctions a $where_sql");
  if ($types !== '') {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $num_results = (int)$stmt->get_result()->fetch_assoc()['n'];
  $max_page    = max(1, (int)ceil($num_results / $results_per_page));
  $curr_page   = min($curr_page, $max_page);
  $offset      = ($curr_page - 1) * $results_per_page;

  
  $stmt = $conn->prepare(
    "SELECT a.auctionID, a.title, a.description, a.endTime,
            COALESCE(MAX(b.amount), a.startingPrice) AS currentPrice,
            COUNT(b.bidID) AS numBids
     FROM auctions a
     LEFT JOIN bids b ON b.auctionID = a.auctionID
     $where_sql
     GROUP BY a.auctionID
     ORDER BY {$orderings[$ordering]}
     LIMIT ? OFFSET ?");
  $page_params = array_merge($params, [$results_per_page, $offset]);
  $stmt->bind_param($types . 'ii', ...$page_params);
  $stmt->execute();
  $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


  $link_params = array_filter([
    'keyword'  => $keyword,
    'cat'      => ($category === 'all') ? null : $category,
    'order_by' => $ordering,
    'ended'    => $show_ended ? 1 : null,
  ], fn($v) => $v !== null && $v !== '');
  $querystring = $link_params ? htmlspecialchars(http_build_query($link_params)) . '&amp;' : '';
?>

<div class="container mt-4">

<?php if ($num_results === 0): ?>
  <p>No auctions match your search. Try different words, another category, or tick <em>Include ended auctions</em>.</p>
<?php else: ?>
  <p class="text-muted"><?= $num_results ?> <?= $num_results === 1 ? 'auction' : 'auctions' ?> found</p>

  <ul class="list-group">
  <?php
    foreach ($results as $r) {
      print_listing_li($r['auctionID'], $r['title'], $r['description'],
                       $r['currentPrice'], $r['numBids'], new DateTime($r['endTime']));
    }
  ?>
  </ul>

  
  <nav aria-label="Search results pages" class="mt-5">
    <ul class="pagination justify-content-center">
  <?php
    $high_page_boost = max(3 - $curr_page, 0);
    $low_page_boost  = max(2 - ($max_page - $curr_page), 0);
    $low_page  = max(1, $curr_page - 2 - $low_page_boost);
    $high_page = min($max_page, $curr_page + 2 + $high_page_boost);

    if ($curr_page != 1) {
      echo('
      <li class="page-item">
        <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page - 1) . '" aria-label="Previous">
          <span aria-hidden="true"><i class="fa fa-arrow-left"></i></span>
          <span class="sr-only">Previous</span>
        </a>
      </li>');
    }

    for ($i = $low_page; $i <= $high_page; $i++) {
      echo('
      <li class="page-item' . ($i == $curr_page ? ' active' : '') . '">
        <a class="page-link" href="browse.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
      </li>');
    }

    if ($curr_page != $max_page) {
      echo('
      <li class="page-item">
        <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
          <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
          <span class="sr-only">Next</span>
        </a>
      </li>');
    }
  ?>
    </ul>
  </nav>
<?php endif; ?>

</div>

<?php include_once("footer.php")?>