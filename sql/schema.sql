-- COMP0178 Database schema


CREATE DATABASE IF NOT EXISTS auction
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auction;


DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS auction_views;
DROP TABLE IF EXISTS watches;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS auctions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;


CREATE TABLE users (
  userID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(255) NOT NULL,
  passwordHash  VARCHAR(255) NOT NULL,
  firstName     VARCHAR(50)  NOT NULL,
  lastName      VARCHAR(50)  NOT NULL,
  isBuyer       BOOLEAN      NOT NULL DEFAULT FALSE,
  isSeller      BOOLEAN      NOT NULL DEFAULT FALSE,
  PRIMARY KEY (userID),
  UNIQUE KEY uq_users_email (email),
  CONSTRAINT chk_users_role CHECK (isBuyer OR isSeller)
) ENGINE=InnoDB;

CREATE TABLE categories (
  categoryID  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50)  NOT NULL,
  PRIMARY KEY (categoryID),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;


CREATE TABLE auctions (
  auctionID      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  sellerID       INT UNSIGNED  NOT NULL,
  categoryID     INT UNSIGNED  NOT NULL,
  title          VARCHAR(100)  NOT NULL,
  description    TEXT          NOT NULL,
  itemCondition  ENUM('New', 'Like new', 'Good', 'Fair', 'Poor') NOT NULL,
  startingPrice  DECIMAL(10,2) NOT NULL,
  reservePrice   DECIMAL(10,2) NULL,          -- NULL = no reserve
  startTime      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  endTime        DATETIME      NOT NULL,
  PRIMARY KEY (auctionID),
  CONSTRAINT fk_auctions_seller   FOREIGN KEY (sellerID)   REFERENCES users (userID),
  CONSTRAINT fk_auctions_category FOREIGN KEY (categoryID) REFERENCES categories (categoryID),
  CONSTRAINT chk_auctions_start   CHECK (startingPrice > 0),
  CONSTRAINT chk_auctions_reserve CHECK (reservePrice IS NULL OR reservePrice >= startingPrice),
  CONSTRAINT chk_auctions_times   CHECK (endTime > startTime),
  KEY idx_auctions_endTime (endTime)
) ENGINE=InnoDB;

CREATE TABLE bids (
  bidID      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  auctionID  INT UNSIGNED  NOT NULL,
  buyerID    INT UNSIGNED  NOT NULL,
  amount     DECIMAL(10,2) NOT NULL,
  bidTime    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (bidID),
  CONSTRAINT fk_bids_auction FOREIGN KEY (auctionID) REFERENCES auctions (auctionID),
  CONSTRAINT fk_bids_buyer   FOREIGN KEY (buyerID)   REFERENCES users (userID),
  CONSTRAINT chk_bids_amount CHECK (amount > 0),
  KEY idx_bids_auction_amount (auctionID, amount)
) ENGINE=InnoDB;


CREATE TABLE watches (
  buyerID       INT UNSIGNED NOT NULL,
  auctionID     INT UNSIGNED NOT NULL,
  watchedSince  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (buyerID, auctionID),
  CONSTRAINT fk_watches_buyer   FOREIGN KEY (buyerID)   REFERENCES users (userID)       ON DELETE CASCADE,
  CONSTRAINT fk_watches_auction FOREIGN KEY (auctionID) REFERENCES auctions (auctionID) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE auction_views (
  viewID     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  auctionID  INT UNSIGNED NOT NULL,
  userID     INT UNSIGNED NULL,
  viewedAt   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (viewID),
  CONSTRAINT fk_views_auction FOREIGN KEY (auctionID) REFERENCES auctions (auctionID) ON DELETE CASCADE,
  CONSTRAINT fk_views_user    FOREIGN KEY (userID)    REFERENCES users (userID)       ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE feedback (
  feedbackID  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  auctionID   INT UNSIGNED     NOT NULL,
  giverID     INT UNSIGNED     NOT NULL,
  receiverID  INT UNSIGNED     NOT NULL,
  score       TINYINT UNSIGNED NOT NULL,
  comment     TEXT             NULL,
  leftAt      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (feedbackID),
  UNIQUE KEY uq_feedback_auction_giver (auctionID, giverID),
  CONSTRAINT fk_feedback_auction  FOREIGN KEY (auctionID)  REFERENCES auctions (auctionID),
  CONSTRAINT fk_feedback_giver    FOREIGN KEY (giverID)    REFERENCES users (userID),
  CONSTRAINT fk_feedback_receiver FOREIGN KEY (receiverID) REFERENCES users (userID),
  CONSTRAINT chk_feedback_score   CHECK (score BETWEEN 1 AND 5),
  CONSTRAINT chk_feedback_people  CHECK (giverID <> receiverID)
) ENGINE=InnoDB;
