<?php
require_once 'db.php';
require_once 'auction_functions.php';


// Returns user's public details for profile page (no email)
function get_user_public($conn, $user_id) {
    $stmt = $conn->prepare("SELECT firstName, lastName FROM users WHERE userID = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Returns the userID to be rated by $user_id for this $auction_id,
// or null (not involved, auction unsold, or already rated).
function get_feedback_receiver($conn, $auction_id, $user_id) {
    $stmt = $conn->prepare("SELECT sellerID FROM auctions WHERE auctionID = ?");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return null;

    $seller_id = (int)$row['sellerID'];
    $winner_id = get_auction_winner($conn, $auction_id);
    if ($winner_id === null) return null;

    if ($user_id === $seller_id)      $receiver_id = $winner_id;   // if user_id is seller, rates buyer
    elseif ($user_id === $winner_id)  $receiver_id = $seller_id;   // if user_id is buyer, rates seller
    else return null;

    $stmt = $conn->prepare("SELECT 1 FROM feedback WHERE auctionID = ? AND giverID = ?");
    $stmt->bind_param('ii', $auction_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return null;

    return $receiver_id;
}

// Returs average score and number of ratings a $user_id has received.
function get_user_rating($conn, $user_id) {
    $stmt = $conn->prepare(
        "SELECT ROUND(AVG(score), 1) AS avgScore, COUNT(*) AS numRatings
         FROM feedback WHERE receiverID = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Returns all feedback received by $user_id, most to least recent.
function get_feedback_for_user($conn, $user_id) {
    $stmt = $conn->prepare(
        "SELECT f.score, f.comment, f.leftAt, a.auctionID, a.title,
                u.firstName AS giverName
         FROM feedback f
         JOIN auctions a ON a.auctionID = f.auctionID
         JOIN users u    ON u.userID    = f.giverID
         WHERE f.receiverID = ?
         ORDER BY f.leftAt DESC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}