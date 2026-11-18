<?php
// auction_functions.php: implemenation of general auction queries.
require_once 'db.php';
// Returns the userID of winner bidder, or null if auction unconcluded,
// reserve not met, no bids .
// Tied bids go to whoever bid first.
function get_auction_winner($conn, $auction_id) {
    $sql = "SELECT b.buyerID
            FROM bids b
            JOIN auctions a ON a.auctionID = b.auctionID
            WHERE b.auctionID = ?
              AND a.endTime < NOW()
              AND b.amount >= a.reservePrice
            ORDER BY b.amount DESC, b.bidTime ASC
            LIMIT 1";
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