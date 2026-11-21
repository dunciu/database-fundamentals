<?php
// bid_history.php: returns the latest bids and price for one auction as JSON.
// listing.php calls this every few seconds so new bids appear as they arrive.
session_start();
require_once 'auction_functions.php';
require_once 'bid_functions.php';

$auction_id = (int)($_GET['item_id'] ?? 0);
$viewer_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$auction = get_auction($conn, $auction_id);
if (!$auction) {
    http_response_code(404);
    exit;
}

$price_info = get_current_price($conn, $auction_id);
$min_bid    = get_minimum_bid($price_info);
$num_bids   = (int)$price_info['numBids'];
$current    = (float)$price_info['currentPrice'];

ob_start();
print_bid_history($conn, $auction_id, $viewer_id);
$html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
    'html'         => $html,
    'currentPrice' => number_format($current, 2),
    'numBids'      => $num_bids,
    'minBid'       => number_format($min_bid, 2),
    'minBidRaw'    => number_format($min_bid, 2, '.', ''),
    'reserveMet'   => $num_bids > 0 && $current >= (float)$auction['reservePrice'],
]);
