<?php include_once("header.php"); ?>
<?php require_once("feedback_functions.php"); ?>

<?php
$profile_id = (int)($_GET['user_id'] ?? 0);

$user = get_user_public($conn, $profile_id);
if (!$user) {
    echo '<div class="container my-4"><p>User not found.</p></div>';
    include_once("footer.php");
    exit;
}

$rating  = get_user_rating($conn, $profile_id);
$reviews = get_feedback_for_user($conn, $profile_id);
?>

<div class="container my-4">
  <h2><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></h2>
  <p class="lead">
    <?= $rating['numRatings'] > 0
        ? "Average rating: {$rating['avgScore']} / 5 ({$rating['numRatings']} ratings)"
        : "No ratings yet" ?>
  </p>

  <ul class="list-group">
  <?php foreach ($reviews as $r): ?>
    <li class="list-group-item">
      <strong><?= $r['score'] ?>/5</strong>
      from <?= htmlspecialchars($r['giverName']) ?>
      for <a href="listing.php?item_id=<?= $r['auctionID'] ?>"><?= htmlspecialchars($r['title']) ?></a>
      <small class="text-muted">(<?= date('j M Y', strtotime($r['leftAt'])) ?>)</small>
      <?php if ($r['comment'] !== null): ?>
        <br><?= htmlspecialchars($r['comment']) ?>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>

<?php include_once("footer.php"); ?>