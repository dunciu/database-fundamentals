<?php
// bid_functions.php: queries for the bidding section.
require_once 'db.php';

// Returns the highest bid on an auction as ['bidID' => .., 'buyerID' => .., 'amount' => ..],
// or null if nobody has bid yet. Tied amounts go to whoever bid first.
function get_highest_bid($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT bidID, buyerID, amount
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

// Returns every bid on an auction, highest first, with the bidder's first
// name and last initial (full names aren't shown to other users).
function get_bid_history($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT b.bidID, b.buyerID, b.amount, b.bidTime,
                u.firstName, SUBSTR(u.lastName, 1, 1) AS lastInitial
         FROM bids b
         JOIN users u ON u.userID = b.buyerID
         WHERE b.auctionID = ?
         ORDER BY b.amount DESC, b.bidTime ASC");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Prints the bid history table. Used by listing.php and by bid_history.php,
// which the listing page polls so new bids appear without reloading.
function print_bid_history($conn, $auction_id, $viewer_id = null) {
    $bids = get_bid_history($conn, $auction_id);
    if (!$bids) {
        echo '<p class="text-muted">No bids yet.</p>';
        return;
    }
    echo '<table class="table table-sm"><thead><tr><th>Bidder</th><th>Amount</th><th>Time</th></tr></thead><tbody>';
    foreach ($bids as $i => $b) {
        $name = htmlspecialchars($b['firstName'] . ' ' . $b['lastInitial'] . '.');
        if ($viewer_id !== null && (int)$b['buyerID'] === (int)$viewer_id) {
            $name .= ' (you)';
        }
        echo '<tr' . ($i === 0 ? ' class="table-success"' : '') . '>'
           . '<td>' . $name . '</td>'
           . '<td>£' . number_format((float)$b['amount'], 2) . '</td>'
           . '<td>' . date('j M H:i', strtotime($b['bidTime'])) . '</td></tr>';
    }
    echo '</tbody></table>';
}
