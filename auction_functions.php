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
                startingPrice, reservePrice, startTime, endTime, status, winningBidID
         FROM auctions WHERE auctionID = ?");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Closes every auction whose end time has passed but is still 'active':
//  - highest bid meets the reserve -> status 'sold', winningBidID set,
//    'auctionWon' to the winner and 'auctionEnded' to the seller
//  - otherwise (no bids, or reserve not met) -> status 'unsold',
//    'auctionEnded' to the seller
// header.php calls this on every page load, so auctions close as soon as
// anyone uses the site after the end time. It is one transaction, and the
// auctions being closed are locked (FOR UPDATE), so two page loads at the
// same moment can't close the same auction twice.
function close_expired_auctions($conn) {
    require_once __DIR__ . '/bid_functions.php';
    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "SELECT auctionID, sellerID, reservePrice
             FROM auctions
             WHERE status = 'active' AND endTime <= NOW()
             FOR UPDATE");
        $stmt->execute();
        $expired = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($expired as $a) {
            $auction_id = (int)$a['auctionID'];
            $seller_id  = (int)$a['sellerID'];
            $top        = get_highest_bid($conn, $auction_id);

            if ($top !== null && (float)$top['amount'] >= (float)$a['reservePrice']) {
                $bid_id   = (int)$top['bidID'];
                $winner_id = (int)$top['buyerID'];

                $stmt = $conn->prepare(
                    "UPDATE auctions SET status = 'sold', winningBidID = ? WHERE auctionID = ?");
                $stmt->bind_param('ii', $bid_id, $auction_id);
                $stmt->execute();

                $stmt = $conn->prepare(
                    "INSERT INTO notifications (userID, auctionID, type, bidID)
                     VALUES (?, ?, 'auctionWon', ?), (?, ?, 'auctionEnded', ?)");
                $stmt->bind_param('iiiiii', $winner_id, $auction_id, $bid_id,
                                            $seller_id, $auction_id, $bid_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare(
                    "UPDATE auctions SET status = 'unsold' WHERE auctionID = ?");
                $stmt->bind_param('i', $auction_id);
                $stmt->execute();

                $stmt = $conn->prepare(
                    "INSERT INTO notifications (userID, auctionID, type) VALUES (?, ?, 'auctionEnded')");
                $stmt->bind_param('ii', $seller_id, $auction_id);
                $stmt->execute();
            }
        }

        $conn->commit();
    } catch (Throwable $e) {
        // Never break the page over this; the next page load will try again.
        $conn->rollback();
        error_log('close_expired_auctions failed: ' . $e->getMessage());
    }
}

// Returns the winning bid of a sold auction as ['buyerID', 'amount',
// 'firstName', 'lastName'], or null if the auction isn't sold.
function get_winning_bid($conn, $auction_id) {
    $stmt = $conn->prepare(
        "SELECT b.buyerID, b.amount, u.firstName, u.lastName
         FROM auctions a
         JOIN bids b  ON b.bidID = a.winningBidID
         JOIN users u ON u.userID = b.buyerID
         WHERE a.auctionID = ? AND a.status = 'sold'");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Records that someone opened an auction's page (userID is null for guests).
function record_auction_view($conn, $auction_id, $user_id) {
    $stmt = $conn->prepare("INSERT INTO auction_views (auctionID, userID) VALUES (?, ?)");
    $stmt->bind_param('ii', $auction_id, $user_id);
    $stmt->execute();
}

// Returns how many times an auction's page has been viewed.
function get_view_count($conn, $auction_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM auction_views WHERE auctionID = ?");
    $stmt->bind_param('i', $auction_id);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['n'];
}