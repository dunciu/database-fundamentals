<?php include_once("header.php"); ?>
<?php require_once("feedback_functions.php"); ?>

<div class="container my-4">
<?php
if (empty($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    echo '<p>Please log in to leave feedback.</p></div>';
    include_once("footer.php");
    exit;
}

$user_id     = (int)$_SESSION['user_id'];
$auction_id  = (int)($_POST['auction_id'] ?? $_GET['auction_id'] ?? 0);
$receiver_id = get_feedback_receiver($conn, $auction_id, $user_id);

if ($receiver_id === null) {
    echo "<p>You can't leave feedback for this auction. Either it hasn't ended, it didn't sell,
          you weren't the buyer or seller, or you've already rated it.</p>";
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score   = (int)($_POST['score'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $comment = ($comment === '') ? null : $comment;

    if ($score < 1 || $score > 5) {
        echo '<p>Score must be between 1 and 5.</p>';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO feedback (auctionID, giverID, receiverID, score, comment)
             VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiis', $auction_id, $user_id, $receiver_id, $score, $comment);
        $stmt->execute();
        echo '<p>Thanks, your feedback has been saved. '
           . '<a href="listing.php?item_id=' . $auction_id . '">Back to the listing</a></p>';
    }
}
else { ?>
    <h2>Leave feedback</h2>
    <form method="POST" action="feedback_form.php">
      <input type="hidden" name="auction_id" value="<?= $auction_id ?>">
      <label for="score">Score</label>
      <select name="score" id="score" class="form-control" required>
        <option value="">Choose…</option>
        <?php for ($i = 5; $i >= 1; $i--) echo "<option value=\"$i\">$i</option>"; ?>
      </select>
      <label for="comment" class="mt-2">Comment (optional)</label>
      <textarea name="comment" id="comment" class="form-control" rows="3"></textarea>
      <button type="submit" class="btn btn-primary mt-2">Submit</button>
    </form>
<?php } ?>
</div>

<?php include_once("footer.php"); ?>