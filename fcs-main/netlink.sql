DROP DATABASE IF EXISTS netlink;
CREATE DATABASE netlink;
USE netlink;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
START TRANSACTION;

-- USERS TABLE
CREATE TABLE users_table (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username varchar(30) NOT NULL,
  full_name varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  phone_number varchar(15) NOT NULL,
  password varchar(255) NOT NULL,
  profile_picture_path varchar(255) NOT NULL,
  display_name varchar(255) NOT NULL,
  bio varchar(255) NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
  reset_token VARCHAR(64) NULL,
  reset_token_expiry DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ITEMS TABLE
CREATE TABLE items_table (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  description text NOT NULL,
  price decimal(10,2) NOT NULL,
  image_path varchar(255) NOT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES users_table (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- FOLLOWERS TABLE
CREATE TABLE followers_table (
  follow_id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  follower_id int(11) NOT NULL,
  followed_id int(11) NOT NULL,
  followed_at datetime NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (follower_id) REFERENCES users_table (id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users_table (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- POSTS TABLE
CREATE TABLE posts_table (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  image_dir varchar(255) DEFAULT NULL,
  caption varchar(2200) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_at datetime DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users_table (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- USER DOCUMENTS TABLE
CREATE TABLE user_documents (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  document_path VARCHAR(255) NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES users_table (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ADMIN USERS TABLE
CREATE TABLE admin_users (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username varchar(50) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  role ENUM('superadmin', 'moderator') NOT NULL DEFAULT 'moderator',
  created_at DATETIME NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- REPORTED USERS TABLE
CREATE TABLE reported_users (
  report_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  reported_user_id INT(11) DEFAULT NULL,
  reporter_user_id INT(11) DEFAULT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
  reported_at DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (reported_user_id) REFERENCES users_table(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (reporter_user_id) REFERENCES users_table(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- SECURITY LOGS
CREATE TABLE security_logs (
  log_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  admin_id INT(11) NOT NULL,
  action TEXT NOT NULL,
  event TEXT NOT NULL,
  user_id INT(11) NOT NULL,
  target_user_id INT(11) DEFAULT NULL,
  timestamp DATETIME NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- MESSAGES (Direct Messaging)
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  message TEXT NOT NULL, -- Assume encrypted
  timestamp DATETIME NOT NULL,
  FOREIGN KEY (sender_id) REFERENCES users_table(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users_table(id) ON DELETE CASCADE
);

-- GROUPS
CREATE TABLE `groups` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  creator_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creator_id) REFERENCES users_table(id) ON DELETE CASCADE
);

-- GROUP MEMBERS
CREATE TABLE group_members (
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users_table(id) ON DELETE CASCADE
);

-- GROUP MESSAGES
CREATE TABLE group_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users_table(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id, sender_id) REFERENCES group_members(group_id, user_id) ON DELETE CASCADE
);

COMMIT;

-- INSERT SUPERADMIN DEFAULT ACCOUNT
INSERT INTO admin_users (username, password, role) 
VALUES ('admin', '$2y$10$JvcuAMuDVX6R4BAI5AnGPOPs3oC5lhsVUx4tDQQM..0l9A14G.Gsy', 'superadmin');
ALTER TABLE users_table 
Add column verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending';
ALTER TABLE messages 
ADD COLUMN is_image BOOLEAN DEFAULT 0,
ADD COLUMN is_video BOOLEAN DEFAULT 0;