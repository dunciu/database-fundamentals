-- Online auction: sample data

USE auction;

-- ---------------------------------------------------------------------
-- users: 2 with both roles (Niamh, Yusuf), 1 seller only (Beatriz),
--        3 buyers only (Kwame, Callum, Hannah)
-- Every user's password is:  password123
-- ---------------------------------------------------------------------
INSERT INTO users (userID, email, passwordHash, firstName, lastName, isBuyer, isSeller) VALUES
 (1, 'niamh.gallagher@example.com', '$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Niamh',  'Gallagher', TRUE,  TRUE),   -- buyer + seller
 (2, 'kwame.asante@example.com',    '$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Kwame',  'Asante',    TRUE,  FALSE),  -- buyer only
 (3, 'beatriz.lopes@example.com',   '$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Beatriz','Lopes',     FALSE, TRUE),   -- seller only
 (4, 'callum.fraser@example.com',   '$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Callum', 'Fraser',    TRUE,  FALSE),  -- buyer only
 (5, 'yusuf.demir@example.com',     '$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Yusuf',  'Demir',     TRUE,  TRUE),   -- buyer + seller
 (6, 'hannah.lindqvist@example.com','$2y$10$/yY.RljCi1ByBkySvpW7H.beM.xZy.AXsGyrxXlel5FNBA3NaLr.W', 'Hannah', 'Lindqvist', TRUE,  FALSE);  -- buyer only

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
INSERT INTO categories (categoryID, name) VALUES
 (1, 'Electronics'), (2, 'Books'), (3, 'Fashion'),
 (4, 'Home & Garden'), (5, 'Sports'), (6, 'Music');

-- ---------------------------------------------------------------------
-- auctions
--   running: 1, 2, 3, 6, 8      ended: 4 (sold), 5 (unsold, below reserve), 7 (unsold, no bids)
--   reservePrice = startingPrice means "no reserve"
-- ---------------------------------------------------------------------
INSERT INTO auctions (auctionID, sellerID, categoryID, title, description, itemCondition, startingPrice, reservePrice, startTime, endTime) VALUES
 (1, 3, 1, 'Studio monitor headphones, coiled cable', 'Closed-back wired headphones with spare ear pads. Some wear on the headband.', 'Good',     50.00,  80.00, NOW() - INTERVAL 4 DAY,  NOW() + INTERVAL 3 DAY),
 (2, 1, 2, 'Bundle of three cookbooks', 'Baking, one-pot dinners and a Levantine cookbook. A few splashes on the baking one.', 'Like new', 10.00,  10.00, NOW() - INTERVAL 2 DAY,  NOW() + INTERVAL 1 DAY),
 (3, 5, 3, 'Waxed cotton field jacket, size M', 'Olive green with corduroy collar. Needs re-waxing, otherwise solid.', 'Fair',    30.00,  45.00, NOW() - INTERVAL 1 DAY,  NOW() + INTERVAL 6 HOUR),
 (4, 3, 5, 'Folding bike, 6-speed, 20-inch wheels', 'Folds down to fit under a desk. Serviced in spring, new brake pads.', 'Good',    150.00, 250.00, NOW() - INTERVAL 9 DAY,  NOW() - INTERVAL 2 DAY),
 (5, 5, 6, 'Left-handed ukulele with soft case', 'Concert size. Tuning pegs slip slightly and there is a chip on the body.', 'Poor',     40.00, 100.00, NOW() - INTERVAL 8 DAY,  NOW() - INTERVAL 1 DAY),
 (6, 1, 4, 'Cast-iron casserole dish, 4.5 litre', 'Enamelled, never used. Still has the cardboard sleeve.', 'New',      15.00,  15.00, NOW() - INTERVAL 1 DAY,  NOW() + INTERVAL 5 DAY),
 (7, 3, 1, 'Retro handheld games console', 'Comes with two cartridges and a carry pouch. Takes AA batteries.', 'Like new', 40.00,  60.00, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 3 DAY),
 (8, 5, 1, 'Refurbished 27-inch monitor', 'Matte screen, HDMI and DisplayPort. Stand height adjusts.', 'Good',     200.00, 300.00, NOW() - INTERVAL 3 DAY,  NOW() + INTERVAL 2 DAY);

-- ---------------------------------------------------------------------
-- bids
-- ---------------------------------------------------------------------
INSERT INTO bids (auctionID, buyerID, amount, bidTime) VALUES
 -- auction 1 (seller Beatriz): current 85.00, reserve met
 (1, 2,  55.00, NOW() - INTERVAL 2 DAY),
 (1, 4,  60.00, NOW() - INTERVAL 1 DAY),
 (1, 2,  70.00, NOW() - INTERVAL 20 HOUR),
 (1, 1,  85.00, NOW() - INTERVAL 5 HOUR),
 -- auction 2 (seller Niamh): current 15.00
 (2, 2,  12.00, NOW() - INTERVAL 10 HOUR),
 (2, 6,  15.00, NOW() - INTERVAL 3 HOUR),
 -- auction 3 (seller Yusuf): one bid, reserve not met yet
 (3, 4,  32.00, NOW() - INTERVAL 2 HOUR),
 -- auction 4 (seller Beatriz, ENDED): Hannah wins at 260.00 >= reserve 250.00
 (4, 1, 160.00, NOW() - INTERVAL 8 DAY),
 (4, 6, 200.00, NOW() - INTERVAL 6 DAY),
 (4, 1, 230.00, NOW() - INTERVAL 4 DAY),
 (4, 6, 260.00, NOW() - INTERVAL 3 DAY),
 -- auction 5 (seller Yusuf, ENDED): highest 70.00 < reserve 100.00, so unsold
 (5, 2,  45.00, NOW() - INTERVAL 6 DAY),
 (5, 4,  60.00, NOW() - INTERVAL 4 DAY),
 (5, 2,  70.00, NOW() - INTERVAL 2 DAY),
 -- auction 8 (seller Yusuf): current 240.00, reserve not met yet
 (8, 2, 210.00, NOW() - INTERVAL 1 DAY),
 (8, 1, 225.00, NOW() - INTERVAL 12 HOUR),
 (8, 4, 240.00, NOW() - INTERVAL 1 HOUR);
 -- auctions 6 and 7 have no bids

-- ---------------------------------------------------------------------
-- watches
-- ---------------------------------------------------------------------
INSERT INTO watches (buyerID, auctionID, watchedSince) VALUES
 (2, 1, NOW() - INTERVAL 3 DAY),
 (2, 3, NOW() - INTERVAL 20 HOUR),
 (2, 8, NOW() - INTERVAL 2 DAY),
 (4, 1, NOW() - INTERVAL 2 DAY),
 (6, 2, NOW() - INTERVAL 1 DAY),
 (1, 8, NOW() - INTERVAL 1 DAY);

-- ---------------------------------------------------------------------
-- auction_views: NULL userID = visitor who wasn't logged in
-- ---------------------------------------------------------------------
INSERT INTO auction_views (auctionID, userID, viewedAt) VALUES
 (1, 2,    NOW() - INTERVAL 3 DAY),  (1, 4,    NOW() - INTERVAL 2 DAY),
 (1, NULL, NOW() - INTERVAL 1 DAY),  (1, 1,    NOW() - INTERVAL 6 HOUR),
 (1, NULL, NOW() - INTERVAL 2 HOUR), (2, 2,    NOW() - INTERVAL 11 HOUR),
 (2, 6,    NOW() - INTERVAL 4 HOUR), (3, 4,    NOW() - INTERVAL 3 HOUR),
 (3, NULL, NOW() - INTERVAL 1 HOUR), (4, 1,    NOW() - INTERVAL 8 DAY),
 (4, 6,    NOW() - INTERVAL 7 DAY),  (5, 2,    NOW() - INTERVAL 6 DAY),
 (6, NULL, NOW() - INTERVAL 5 HOUR), (8, 2,    NOW() - INTERVAL 1 DAY),
 (8, 1,    NOW() - INTERVAL 13 HOUR),(8, 4,    NOW() - INTERVAL 2 HOUR);

-- ---------------------------------------------------------------------
-- feedback: only on auction 4, the one that sold (winner Hannah, seller Beatriz)
-- ---------------------------------------------------------------------
INSERT INTO feedback (auctionID, giverID, receiverID, score, comment, leftAt) VALUES
 (4, 6, 3, 5, 'Bike folds exactly as described. Easy collection.', NOW() - INTERVAL 1 DAY),
 (4, 3, 6, 4, 'Paid quickly, a bit late to collect but friendly.',  NOW() - INTERVAL 1 DAY);

-- ---------------------------------------------------------------------
-- notifications: the emails that would have been sent for the bids above
-- ---------------------------------------------------------------------
INSERT INTO notifications (userID, auctionID, type, sentAt) VALUES
 -- auction 1
 (2, 1, 'outbid',       NOW() - INTERVAL 1 DAY),    -- Kwame outbid by Callum
 (2, 1, 'newBid',       NOW() - INTERVAL 1 DAY),    -- Kwame watches auction 1
 (4, 1, 'outbid',       NOW() - INTERVAL 20 HOUR),  -- Callum outbid by Kwame
 (4, 1, 'newBid',       NOW() - INTERVAL 20 HOUR),  -- Callum watches auction 1
 (2, 1, 'outbid',       NOW() - INTERVAL 5 HOUR),   -- Kwame outbid by Niamh
 (2, 1, 'newBid',       NOW() - INTERVAL 5 HOUR),
 (4, 1, 'newBid',       NOW() - INTERVAL 5 HOUR),
 -- auction 2
 (2, 2, 'outbid',       NOW() - INTERVAL 3 HOUR),   -- Kwame outbid by Hannah
 -- auction 4 (ended, sold)
 (6, 4, 'auctionWon',   NOW() - INTERVAL 2 DAY),    -- to the winner, Hannah
 (3, 4, 'auctionEnded', NOW() - INTERVAL 2 DAY),    -- to the seller, Beatriz
 -- auction 5 (ended, unsold)
 (5, 5, 'auctionEnded', NOW() - INTERVAL 1 DAY),    -- to the seller, Yusuf
 -- auction 7 (ended, no bids)
 (3, 7, 'auctionEnded', NOW() - INTERVAL 3 DAY),    -- to the seller, Beatriz
 -- auction 8
 (2, 8, 'outbid',       NOW() - INTERVAL 12 HOUR),  -- Kwame outbid by Niamh
 (2, 8, 'newBid',       NOW() - INTERVAL 1 HOUR),   -- Kwame watches auction 8
 (1, 8, 'outbid',       NOW() - INTERVAL 1 HOUR),   -- Niamh outbid by Callum
 -- newBid emails to watchers (everyone watching, except the bidder)
 (4, 1, 'newBid',       NOW() - INTERVAL 2 DAY),    -- Callum watches auction 1; Kwame bid
 (6, 2, 'newBid',       NOW() - INTERVAL 10 HOUR),  -- Hannah watches auction 2; Kwame bid
 (2, 3, 'newBid',       NOW() - INTERVAL 2 HOUR),   -- Kwame watches auction 3; Callum bid
 (1, 8, 'newBid',       NOW() - INTERVAL 1 DAY),    -- Niamh watches auction 8; Kwame bid
 (2, 8, 'newBid',       NOW() - INTERVAL 12 HOUR),  -- Kwame watches auction 8; Niamh bid
 (1, 8, 'newBid',       NOW() - INTERVAL 1 HOUR);   -- Niamh watches auction 8; Callum bid
