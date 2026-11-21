<?php
// auction_functions.php: implemenation of general auction queries.
require_once 'db.php';
// Returns the winning buyer's userID, or null if the auction ongoingg
// The winner is decided when the auction is closed and stored in winningBidID.
function get_auction_winner($conn, $auction_id) {
    $sql = "SELECT b.buyerID
            FROM auctions a
            JOIN bids b ON b.bidID = a.winningBidID
            WHERE a.auctionID = ? AND a.status = 'sold'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['buyerID'] : null;
}

// Returns user id of selloer for given auction id, or null if non-existent.
function get_auction_seller($conn, $auction_id) {
    $stmt = $conn->prepare("SELECT sellerID FROM auctions WHERE auctionID = ?");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['sellerID'] : null;
}

// Returns one auction's details for the listing page, or null if non-existent.
function get_auction($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT auctionID, sellerID, title, description, itemCondition,
                startingPrice, startTime, endTime
         FROM auctions WHERE auctionID = ?");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}