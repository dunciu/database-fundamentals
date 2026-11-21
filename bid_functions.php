<?php
// bid_functions.php: queries for the bidding section.
require_once 'db.php';

// Returns the highest bid on an auction as ['buyerID' => .., 'amount' => ..],
// or null if nobody has bid yet. Tied amounts go to whoever bid first.
function get_highest_bid($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT buyerID, amount
         FROM bids
         WHERE auctionID = ?
         ORDER BY amount DESC, bidTime ASC
         LIMIT 1");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Returns ['currentPrice' => .., 'numBids' => ..] for the listing page.
// The current price is the highest bid, or the starting price if there are no bids.
function get_current_price($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT COALESCE(MAX(b.amount), a.startingPrice) AS currentPrice,
                COUNT(b.bidID) AS numBids
         FROM auctions a
         LEFT JOIN bids b ON b.auctionID = a.auctionID
         WHERE a.auctionID = ?
         GROUP BY a.auctionID, a.startingPrice");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Returns the lowest amount the next bid can be: the starting price if there
// are no bids yet, otherwise 1p more than the current highest bid.
function get_minimum_bid($price_info) {
    $current = (float)$price_info['currentPrice'];
    return ((int)$price_info['numBids'] === 0) ? $current : $current + 0.01;
}
