-- setup.sql

CREATE DATABASE IF NOT EXISTS rendershelf;
USE rendershelf;

-- 1. Users Table (Enhanced)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    username VARCHAR(50),
    profile_pic VARCHAR(255) DEFAULT 'default_avatar.png',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attempt to update existing users table if it lacks new columns
-- These might fail if columns exist, strictly speaking, but on a fresh run they are harmless or will just error out on duplicates which we can ignore for this setup format.
-- Better approach for XAMPP/MariaDB: Use simple checks or just let the user know to import.
-- We will use a stored procedure to safely add columns if not exists, or just keep it simple.
-- For simplicity in this environment, we assume we can just run these. If you have an existing DB, some might fail.

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('asset', 'tutorial') NOT NULL, -- To distinguish between asset categories and tutorial categories
    icon_class VARCHAR(50), -- For UI icons (Ionicon name)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Assets Table
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10, 2) DEFAULT 0.00, -- 0.00 for free assets
    file_path VARCHAR(255) NOT NULL,
    preview_path VARCHAR(255), -- Image or Video preview
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 4. Tutorials Table
CREATE TABLE IF NOT EXISTS tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    video_url VARCHAR(255) NOT NULL, -- YouTube embed or local file
    thumbnail_path VARCHAR(255),
    category_id INT,
    duration VARCHAR(20), -- e.g., "12:04"
    author_name VARCHAR(100), -- Could be linked to a user or just text
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 5. Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Favourites Table
CREATE TABLE IF NOT EXISTS favourites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    UNIQUE(user_id, asset_id)
);

-- 7. Transactions Table (Wallet & Purchases)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('deposit', 'withdrawal', 'purchase', 'sale', 'commission') NOT NULL,
    description VARCHAR(255),
    related_asset_id INT, -- If it was a purchase/sale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Withdrawal Requests Table
CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 9. Password Resets (Existing)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (token)
);

-- Seed some default Categories
INSERT IGNORE INTO categories (name, type, icon_class) VALUES 
('VFX', 'asset', 'aperture-outline'),
('LUTs', 'asset', 'color-filter-outline'),
('Transitions', 'asset', 'swap-horizontal-outline'),
('SFX', 'asset', 'musical-notes-outline'),
('Premiere Pro', 'tutorial', 'logo-youtube'),
('After Effects', 'tutorial', 'film-outline'),
('DaVinci Resolve', 'tutorial', 'color-wand-outline');
