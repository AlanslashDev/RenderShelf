-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2026 at 11:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rendershelf`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `download_count` int(11) DEFAULT 0,
  `file_path` varchar(255) NOT NULL,
  `preview_path` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `creator_id`, `title`, `description`, `category_id`, `price`, `download_count`, `file_path`, `preview_path`, `thumbnail_path`, `is_approved`, `created_at`) VALUES
(24, 2, 'Chinese Lunar New Year 465871', 'Imported asset: Chinese Lunar New Year 465871', 2, 23.99, 1, 'uploads/assets/1768797355_chinese-lunar-new-year-465871.mp3', 'uploads/assets/1768797355_chinese-lunar-new-year-465871.mp3', 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif', 1, '2026-01-30 06:38:30'),
(25, 2, 'Honey Kisses 413841', 'Imported asset: Honey Kisses 413841', 2, 9.99, 0, 'uploads/assets/1768797435_honey-kisses-413841.mp3', 'uploads/assets/1768797435_honey-kisses-413841.mp3', 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif', 1, '2026-01-30 06:38:30'),
(26, 2, 'Mixkit Arcade Retro Game Over 213', 'Imported asset: Mixkit Arcade Retro Game Over 213', 2, 20.99, 1, 'uploads/assets/1768979892_mixkit_arcade_retro_game_over_213.wav', 'uploads/assets/1768979892_mixkit_arcade_retro_game_over_213.wav', 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif', 1, '2026-01-30 06:38:30'),
(27, 2, 'Mixkit Fast Rocket Whoosh 1714', 'Imported asset: Mixkit Fast Rocket Whoosh 1714', 2, 24.99, 1, 'uploads/assets/1769014092_mixkit_fast_rocket_whoosh_1714.wav', 'uploads/assets/1769014092_mixkit_fast_rocket_whoosh_1714.wav', 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif', 1, '2026-01-30 06:38:30'),
(31, 3, 'Flat geometric background', '', 8, 0.00, 0, 'uploads/assets/1769762971_6915910_Motion_Graphics_Motion_Graphic_3840x2160.mp4', 'uploads/previews/1769762971_6915910_Motion_Graphics_Motion_Graphic_3840x2160.mp4', 'uploads/thumbnails/1769762971_thumb_Screenshot_2026_01_30_140954.png', 1, '2026-01-30 08:49:31'),
(36, 2, 'Wedding Pastel Softness', 'Professional grade 3D LUT for cinematic color grading. Works with Premiere, DaVinci, and FCPX.', 3, 10.99, 0, 'uploads/assets/dummy_lut.cube', 'img/modern_zooms.png', 'img/modern_zooms.png', 1, '2026-01-30 08:53:25'),
(38, 1, 'Colorful Rolling Film-Burn', '', 1, 0.00, 0, 'uploads/assets/1772707220_Colorful_Rolling_Film_Burn_Think_Make_Push.mp4', 'uploads/previews/1772707220_Colorful_Rolling_Film_Burn_Think_Make_Push.mp4', 'uploads/thumbnails/1772707220_thumb_Screenshot_2026_03_05_152524.png', 1, '2026-03-05 10:40:20'),
(39, 1, 'PictureFX CineStill ', '', 3, 100.00, 0, 'uploads/assets/1773166118_CineStill_800_T_V1.0__N125.cube', '', 'uploads/thumbnails/1773166118_thumb_Fujifilm_X_H1_06102021_neutral.jpg', 1, '2026-03-10 18:08:38'),
(40, 1, 'Powerful cold', '', 3, 0.00, 0, 'uploads/assets/1773167955_lut_1.mountaineer_2080138_1920.cube', '', 'uploads/thumbnails/1773167955_thumb_photoslut_1.1.2.jpg', 1, '2026-03-10 18:39:15');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('asset','tutorial') NOT NULL,
  `icon_class` varchar(50) DEFAULT 'folder-outline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `icon_class`) VALUES
(1, 'Transitions', 'asset', 'shuffle-outline'),
(2, 'SFX', 'asset', 'musical-notes-outline'),
(3, 'LUTs', 'asset', 'color-palette-outline'),
(4, 'VFX', 'asset', 'flame-outline'),
(7, 'Tutorials', 'tutorial', 'folder-outline'),
(8, 'Motion Graphics', 'asset', 'film-outline');

-- --------------------------------------------------------

--
-- Table structure for table `favourites`
--

CREATE TABLE `favourites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favourites`
--

INSERT INTO `favourites` (`id`, `user_id`, `asset_id`, `created_at`) VALUES
(1, 2, 36, '2026-03-10 18:32:15');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','alert','success','warning') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 3, 'Your asset \'Color Splash Transitions\' has been deleted by an administrator. Reason: copy', 'alert', 1, '2026-01-30 07:06:51'),
(2, 5, 'Successfully purchased PictureFX CineStill ', 'success', 1, '2026-03-15 15:09:35'),
(3, 1, 'New sale! Someone purchased PictureFX CineStill ', 'info', 1, '2026-03-15 15:09:35'),
(4, 5, 'Successfully purchased Mixkit Fast Rocket Whoosh 1714', 'success', 1, '2026-03-15 15:09:35'),
(5, 2, 'New sale! Someone purchased Mixkit Fast Rocket Whoosh 1714', 'info', 0, '2026-03-15 15:09:35'),
(6, 5, 'Successfully purchased Chinese Lunar New Year 465871', 'success', 1, '2026-03-18 09:47:35'),
(7, 2, 'New sale! Someone purchased Chinese Lunar New Year 465871', 'info', 0, '2026-03-18 09:47:35'),
(8, 5, 'Successfully purchased Wedding Pastel Softness', 'success', 1, '2026-03-18 09:50:07'),
(9, 2, 'New sale! Someone purchased Wedding Pastel Softness', 'info', 0, '2026-03-18 09:50:07');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(6) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expiry`, `created_at`) VALUES
(15, 'sonatjoseph2028@mca.ajce.in', '507565', '2026-03-05 08:17:43', '2026-03-05 07:02:43'),
(21, 'alanthomas2028@mca.ajce.in', '333755', '2026-03-16 18:56:46', '2026-03-16 17:41:46');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('allow_registration', '1'),
('auto_approve_assets', '0'),
('currency_symbol', '₹'),
('maintenance_mode', '0'),
('max_upload_size', '100'),
('payment_test_mode', '1'),
('platform_fee', '10'),
('razorpay_key_id', 'rzp_test_SMeC4UXX9Pp96q'),
('razorpay_key_secret', 'fXeXJE4zs8g7gR82sm2AkCf5'),
('site_name', 'RenderShelf');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('purchase','deposit','withdrawal','sale','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `related_asset_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `description`, `related_asset_id`, `created_at`) VALUES
(1, 3, 'purchase', -14.00, 'Purchased (Card): Colorful Liquid Mix Then Reverse', NULL, '2026-02-18 08:29:12'),
(2, 2, 'sale', 12.60, 'sold: Colorful Liquid Mix Then Reverse', NULL, '2026-02-18 08:29:12'),
(3, 1, 'purchase', -14.00, 'Purchased (Card): Colorful Liquid Mix Then Reverse', NULL, '2026-03-03 04:42:45'),
(4, 2, 'sale', 12.60, 'sold: Colorful Liquid Mix Then Reverse', NULL, '2026-03-03 04:42:45'),
(5, 1, 'purchase', -20.99, 'Purchased (Card): Mixkit Arcade Retro Game Over 213', 26, '2026-03-03 04:43:42'),
(6, 2, 'sale', 18.89, 'sold: Mixkit Arcade Retro Game Over 213', 26, '2026-03-03 04:43:42'),
(7, 1, 'purchase', -23.99, 'Purchased (Razorpay): Chinese Lunar New Year 465871', 24, '2026-03-05 05:48:27'),
(8, 2, 'sale', 21.59, 'sold: Chinese Lunar New Year 465871', 24, '2026-03-05 05:48:27'),
(9, 1, 'purchase', -24.99, 'Purchased (Razorpay): Mixkit Fast Rocket Whoosh 1714', 27, '2026-03-10 07:03:50'),
(10, 2, 'sale', 22.49, 'sold: Mixkit Fast Rocket Whoosh 1714', 27, '2026-03-10 07:03:50'),
(11, 5, 'purchase', 100.00, 'Purchased (Razorpay): PictureFX CineStill ', 39, '2026-03-15 15:09:35'),
(12, 1, 'sale', 90.00, 'Sold PictureFX CineStill  (Fee: ₹10.00)', 39, '2026-03-15 15:09:35'),
(13, 5, 'purchase', 24.99, 'Purchased (Razorpay): Mixkit Fast Rocket Whoosh 1714', 27, '2026-03-15 15:09:35'),
(14, 2, 'sale', 22.49, 'Sold Mixkit Fast Rocket Whoosh 1714 (Fee: ₹2.50)', 27, '2026-03-15 15:09:35'),
(15, 5, 'purchase', 23.99, 'Purchased (Razorpay): Chinese Lunar New Year 465871', 24, '2026-03-18 09:47:35'),
(16, 2, 'sale', 21.59, 'Sold Chinese Lunar New Year 465871 (Fee: ₹2.40)', 24, '2026-03-18 09:47:35'),
(17, 5, 'purchase', 10.99, 'Purchased (Razorpay): Wedding Pastel Softness', 36, '2026-03-18 09:50:07'),
(18, 2, 'sale', 9.89, 'Sold Wedding Pastel Softness (Fee: ₹1.10)', 36, '2026-03-18 09:50:07');

-- --------------------------------------------------------

--
-- Table structure for table `tutorials`
--

CREATE TABLE `tutorials` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author_name` varchar(255) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `video_url` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `related_asset_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorials`
--

INSERT INTO `tutorials` (`id`, `title`, `author_name`, `duration`, `video_url`, `category_id`, `thumbnail_path`, `related_asset_id`, `created_at`) VALUES
(1, 'Mastering DanviciResolve', 'Resolve Cut', '', 'https://youtu.be/T3P3r4cOJN8?si=wnZmnsmkZXT1KtsX', 7, 'uploads/tutorials/697c3e8300b39.jpeg', NULL, '2026-01-30 05:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin','super_admin') DEFAULT 'user',
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `profile_pic` varchar(255) DEFAULT 'default_profile.png',
  `bio` text DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `wallet_balance`, `profile_pic`, `bio`, `google_id`, `created_at`) VALUES
(1, 'sonatjoseph', 'sonatjoseph2028@mca.ajce.in', '$2y$10$kH3bdAi2PAWSU5c/dsRF0.xemS5rUHdirOmesqUBhujQRadNC3.MK', 'user', 90.00, 'default_profile.png', '', NULL, '2026-01-29 19:18:59'),
(2, 'ALANTHOMAS', 'alanthomas2028@mca.ajce.in', '$2y$10$q0Ny678ZrxkBpStgL6TsjOZOTzAqkTFl2xPS.unyK7CAQSmTmKctG', 'admin', 142.14, 'https://lh3.googleusercontent.com/a/ACg8ocLMpF5E3zLtsZqwhsc7sqXpaXeiIqKIZVv8CYi4e6zqMzcgktM=s96-c', NULL, '108660699987086634880', '2026-01-30 04:02:23'),
(3, 'senil', 'senilcyriac65@gmail.om', '$2y$10$XMnjOmw2dp9zB9WWPobpLOI2beW6Dsj4YXsZgLCzN/8.xnCdnDLM2', 'user', 0.00, 'default_profile.png', NULL, NULL, '2026-01-30 04:31:16'),
(4, 'TestTester', 'tester3@test.com', '$2y$10$tByGJZt16PNkwL8z.4Pxz.CbdaUrZt3qD/SaE9mmHdn846I4/EMHC', 'user', 0.00, 'default_profile.png', NULL, NULL, '2026-03-15 14:31:24'),
(5, 'jinonse_cutzz', 'jinonseroy2028@mca.ajce.in', '$2y$10$3pbkBheIpST1G77f2CJemOb8jAyh2iLyaiL/ZmvSFSanfN0KYa5WW', 'user', 0.00, 'default_profile.png', NULL, NULL, '2026-03-15 15:08:01');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favourites`
--
ALTER TABLE `favourites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favourite` (`user_id`,`asset_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`asset_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_asset_id` (`related_asset_id`);

--
-- Indexes for table `tutorials`
--
ALTER TABLE `tutorials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `favourites`
--
ALTER TABLE `favourites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tutorials`
--
ALTER TABLE `tutorials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favourites`
--
ALTER TABLE `favourites`
  ADD CONSTRAINT `favourites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favourites_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`related_asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tutorials`
--
ALTER TABLE `tutorials`
  ADD CONSTRAINT `tutorials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
