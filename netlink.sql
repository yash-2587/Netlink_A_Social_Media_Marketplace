DROP DATABASE IF EXISTS netlink;
CREATE DATABASE netlink;

USE netlink;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

-- Users Table
CREATE TABLE `users_table` (
  `id` int(11) NOT NULL primary key AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture_path` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `bio` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Items Table (New Table for Marketplace)
CREATE TABLE `items_table` (
  `id` int(11) NOT NULL primary key AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Followers Table
CREATE TABLE `followers_table` (
  `follow_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL,
  `followed_id` int(11) NOT NULL,
  `followed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `posts_table` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `image_dir` varchar(255) DEFAULT NULL,
  `caption` varchar(2200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `posts_table`
  ADD KEY `posts_table_ibfk_1` (`user_id`),
  ADD CONSTRAINT `posts_table_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `followers_table`
  ADD KEY `follower_id` (`follower_id`),
  ADD KEY `followed_id` (`followed_id`);

ALTER TABLE `items_table`
  ADD CONSTRAINT `items_table_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `followers_table`
  ADD CONSTRAINT `followers_table_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `followers_table_ibfk_2` FOREIGN KEY (`followed_id`) REFERENCES `users_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

use netlink;
CREATE TABLE user_documents (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  document_path VARCHAR(255) NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES users_table (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin Table
CREATE TABLE admin_table (
  admin_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL, -- Store hashed passwords
  created_at DATETIME NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `messages_table` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('sent', 'received', 'failed') DEFAULT 'sent',
    FOREIGN KEY (`sender_id`) REFERENCES `users_table` (`id`),
    FOREIGN KEY (`receiver_id`) REFERENCES `users_table` (`id`),
    INDEX sender_idx (sender_id),
    INDEX receiver_idx (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL, -- Store encrypted messages
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES users_table(id),
    FOREIGN KEY (receiver_id) REFERENCES users_table(id)
);
ALTER TABLE users_table
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_token_expiry DATETIME NULL;
