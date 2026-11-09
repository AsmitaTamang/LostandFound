-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 09, 2026 at 12:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lost_and_found`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `is_active`, `created_at`) VALUES
(1, 'Laptop', 1, '2026-09-20 22:43:11'),
(2, 'Phone', 1, '2026-09-20 22:43:11'),
(3, 'Wallet', 1, '2026-09-20 22:43:11'),
(4, 'Keys', 1, '2026-09-20 22:43:11'),
(5, 'Bag', 1, '2026-09-20 22:43:11'),
(6, 'ID Card', 1, '2026-09-20 22:43:11'),
(7, 'Charger', 1, '2026-09-20 22:43:11'),
(8, 'Headphones', 1, '2026-09-20 22:43:11'),
(9, 'Glasses', 1, '2026-09-20 22:43:11'),
(10, 'Watch', 1, '2026-09-20 22:43:11'),
(11, 'Umbrella', 1, '2026-09-20 22:43:11'),
(12, 'Water Bottle', 1, '2026-09-20 22:43:11'),
(13, 'Books', 1, '2026-09-20 22:43:11'),
(14, 'Clothing', 1, '2026-09-20 22:43:11'),
(15, 'Jewelry', 1, '2026-09-20 22:43:11'),
(16, 'jacket', 1, '2026-09-28 09:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `claim_id` int(11) NOT NULL,
  `lost_id` int(11) NOT NULL,
  `found_id` int(11) NOT NULL,
  `claimant_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`claim_id`, `lost_id`, `found_id`, `claimant_id`, `status`, `created_at`) VALUES
(1, 1, 1, 2, 'pending', '2026-10-09 10:39:44');

-- --------------------------------------------------------

--
-- Table structure for table `found_items`
--

CREATE TABLE `found_items` (
  `found_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `date_found` date DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('open','claimed','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `found_items`
--

INSERT INTO `found_items` (`found_id`, `user_id`, `category_id`, `item_name`, `description`, `color`, `brand`, `location`, `date_found`, `image_path`, `status`, `created_at`) VALUES
(1, 3, 1, 'Silver Laptop Apple', '', 'Silver', 'Apple', 'Canteen', '2026-09-28', NULL, 'open', '2026-09-27 22:57:36'),
(2, 999, 1, 'MacBook Pro', 'Silver Apple', 'Silver', 'Apple', 'Library', '2026-03-01', NULL, 'open', '2026-10-07 11:52:16'),
(3, 999, 1, 'Dell laptop', 'Silver Dell', 'Silver', 'Dell', 'Cafeteria', '2026-03-02', NULL, 'open', '2026-10-07 11:52:16'),
(4, 999, 1, 'ROG laptop', 'Asus black found', 'Black', 'Asus', 'Library', '2026-03-03', NULL, 'open', '2026-10-07 11:52:16'),
(5, 999, 1, 'HP laptop', 'HP silver', 'Silver', 'HP', 'Room 101', '2026-03-15', NULL, 'open', '2026-10-07 11:52:16'),
(6, 999, 1, 'Lenovo laptop', 'Black Lenovo', 'Black', 'Lenovo', 'Cafeteria', '2026-03-20', NULL, 'open', '2026-10-07 11:52:16'),
(7, 999, 2, 'iPhone', 'Apple blue found', 'Blue', 'Apple', 'Cafeteria', '2026-03-06', NULL, 'open', '2026-10-07 11:52:16'),
(8, 999, 2, 'Galaxy phone', 'Samsung black', 'Black', 'Samsung', 'Room 205', '2026-03-07', NULL, 'open', '2026-10-07 11:52:16'),
(9, 999, 2, 'Google phone', 'Pixel white', 'White', 'Google', 'Library', '2026-03-20', NULL, 'open', '2026-10-07 11:52:16'),
(10, 999, 2, 'Blue phone', 'OnePlus found', 'Blue', 'OnePlus', 'Library', '2026-03-09', NULL, 'open', '2026-10-07 11:52:16'),
(11, 999, 2, 'Green mobile', 'Nokia green found', 'Green', 'Nokia', 'Library', '2026-04-01', NULL, 'open', '2026-10-07 11:52:16'),
(12, 999, 3, 'Wallet', 'Brown Fossil', 'Brown', 'Fossil', 'Cafeteria', '2026-03-11', NULL, 'open', '2026-10-07 11:52:16'),
(13, 999, 3, 'Cardholder', 'Black Gucci', 'Black', 'Gucci', 'Library', '2026-03-12', NULL, 'open', '2026-10-07 11:52:16'),
(14, 999, 3, 'Brown wallet', 'Brown leather', 'Brown', NULL, 'Cafeteria', '2026-03-13', NULL, 'open', '2026-10-07 11:52:16'),
(15, 999, 3, 'Beige wallet', 'Coach', 'Beige', 'Coach', 'Library', '2026-03-14', NULL, 'open', '2026-10-07 11:52:16'),
(16, 999, 3, 'Red purse', 'Red Fossil', 'Red', 'Fossil', 'Cafeteria', '2026-03-30', NULL, 'open', '2026-10-07 11:52:16'),
(17, 999, 5, 'Backpack', 'Black Nike', 'Black', 'Nike', 'Gym', '2026-03-16', NULL, 'open', '2026-10-07 11:52:16'),
(18, 999, 5, 'Red bag', 'Puma', 'Red', 'Puma', 'Gym', '2026-03-17', NULL, 'open', '2026-10-07 11:52:16'),
(19, 999, 5, 'Blue duffel', 'Adidas', 'Blue', 'Adidas', 'Gym', '2026-03-18', NULL, 'open', '2026-10-07 11:52:16'),
(20, 999, 5, 'Grey backpack', 'Herschel', 'Grey', 'Herschel', 'Library', '2026-04-01', NULL, 'open', '2026-10-07 11:52:16'),
(21, 999, 5, 'Brown tote', 'Brown Fossil', 'Brown', 'Fossil', 'Library', '2026-04-05', NULL, 'open', '2026-10-07 11:52:16'),
(22, 999, 10, 'Silver watch', 'Casio', 'Silver', 'Casio', 'Library', '2026-03-21', NULL, 'open', '2026-10-07 11:52:16'),
(23, 999, 10, 'Brown watch', 'Fossil', 'Brown', 'Fossil', 'Cafeteria', '2026-03-22', NULL, 'open', '2026-10-07 11:52:16'),
(24, 999, 10, 'Sports watch', 'Garmin', 'Black', 'Garmin', 'Gym', '2026-04-05', NULL, 'open', '2026-10-07 11:52:16'),
(25, 999, 10, 'Black watch', 'Digital', 'Black', NULL, 'Library', '2026-03-24', NULL, 'open', '2026-10-07 11:52:16'),
(26, 999, 10, 'Gold watch', 'Gold Rolex', 'Gold', 'Rolex', 'Library', '2026-04-10', NULL, 'open', '2026-10-07 11:52:16'),
(27, 999, 8, 'AirPods', 'White', 'White', 'Apple', 'Cafeteria', '2026-03-26', NULL, 'open', '2026-10-07 11:52:16'),
(28, 999, 8, 'Sony cans', 'Black', 'Black', 'Sony', 'Library', '2026-03-27', NULL, 'open', '2026-10-07 11:52:16'),
(29, 999, 8, 'Red earbuds', 'JBL', 'Red', 'JBL', 'Gym', '2026-04-10', NULL, 'open', '2026-10-07 11:52:16'),
(30, 999, 8, 'Red headphones', 'Beats', 'Red', 'Beats', 'Cafeteria', '2026-03-29', NULL, 'open', '2026-10-07 11:52:16'),
(31, 999, 8, 'Black earbuds', 'Bose', 'Black', 'Bose', 'Library', '2026-04-15', NULL, 'open', '2026-10-07 11:52:16'),
(32, 999, 9, 'Black sunglasses', 'Ray-Ban', 'Black', 'Ray-Ban', 'Auditorium', '2026-03-31', NULL, 'open', '2026-10-07 11:52:16'),
(33, 999, 9, 'Blue glasses', 'Oakley', 'Blue', 'Oakley', 'Cafeteria', '2026-04-01', NULL, 'open', '2026-10-07 11:52:16'),
(34, 999, 9, 'Black glasses', 'Designer', 'Black', NULL, 'Library', '2026-04-02', NULL, 'open', '2026-10-07 11:52:16'),
(35, 999, 9, 'Brown glasses', 'Gucci', 'Brown', 'Gucci', 'Cafeteria', '2026-04-15', NULL, 'open', '2026-10-07 11:52:16'),
(36, 999, 9, 'Blue spectacles', 'Warby', 'Blue', 'Warby', 'Gym', '2026-04-20', NULL, 'open', '2026-10-07 11:52:16'),
(37, 999, 4, 'Car keys', 'Toyota', 'Silver', 'Toyota', 'Parking', '2026-04-05', NULL, 'open', '2026-10-07 11:52:16'),
(38, 999, 4, 'Bicycle keys', 'Kryptonite', 'Black', 'Kryptonite', 'Bike Rack', '2026-04-06', NULL, 'open', '2026-10-07 11:52:16'),
(39, 999, 4, 'Silver keys', 'Honda', 'Silver', 'Honda', 'Library', '2026-04-07', NULL, 'open', '2026-10-07 11:52:16'),
(40, 999, 4, 'Green keys', 'Yale', 'Green', 'Yale', 'Entrance', '2026-04-20', NULL, 'open', '2026-10-07 11:52:16'),
(41, 999, 4, 'Black keys', 'Ford', 'Black', 'Ford', 'Library', '2026-04-25', NULL, 'open', '2026-10-07 11:52:16'),
(42, 999, 1, 'Unknown laptop', 'Generic', 'Silver', NULL, 'Library', '2026-03-05', NULL, 'open', '2026-10-07 11:52:16'),
(43, 999, 1, 'Red laptop', 'Generic', 'Red', NULL, 'Cafeteria', '2026-03-10', NULL, 'open', '2026-10-07 11:52:16'),
(44, 999, 2, 'Unknown phone', 'Generic', 'Silver', NULL, 'Entrance', '2026-03-10', NULL, 'open', '2026-10-07 11:52:16'),
(45, 999, 2, 'White phone', 'Generic', 'White', NULL, 'Library', '2026-03-15', NULL, 'open', '2026-10-07 11:52:16'),
(46, 999, 3, 'Unknown wallet', 'Generic', 'Black', NULL, 'Library', '2026-03-15', NULL, 'open', '2026-10-07 11:52:16'),
(47, 999, 5, 'Unknown bag', 'Generic', 'Grey', NULL, 'Cafeteria', '2026-03-20', NULL, 'open', '2026-10-07 11:52:16'),
(48, 999, 10, 'Unknown watch', 'Generic', 'Silver', NULL, 'Cafeteria', '2026-03-25', NULL, 'open', '2026-10-07 11:52:16'),
(49, 999, 8, 'Unknown earbuds', 'Generic', 'White', NULL, 'Auditorium', '2026-03-30', NULL, 'open', '2026-10-07 11:52:16'),
(50, 999, 9, 'Unknown glasses', 'Generic', 'Black', NULL, 'Library', '2026-04-04', NULL, 'open', '2026-10-07 11:52:16'),
(51, 999, 4, 'Unknown keys', 'Generic', 'Silver', NULL, 'Parking', '2026-04-09', NULL, 'open', '2026-10-07 11:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `lost_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `date_lost` date DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('open','claimed','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`lost_id`, `user_id`, `category_id`, `item_name`, `description`, `color`, `brand`, `location`, `date_lost`, `image_path`, `status`, `created_at`) VALUES
(1, 2, 1, 'Macbook Pro', '', 'Silver', 'Apple', 'Canteen', '2026-09-28', NULL, 'open', '2026-09-27 22:44:50'),
(2, 999, 1, 'MacBook Pro 13', 'Silver Apple', 'Silver', 'Apple', 'Library', '2026-03-01', NULL, 'open', '2026-10-07 11:52:16'),
(3, 999, 1, 'Dell XPS 13', 'Silver Dell', 'Silver', 'Dell', 'Cafeteria', '2026-03-02', NULL, 'open', '2026-10-07 11:52:16'),
(4, 999, 1, 'Gaming laptop', 'Black Asus ROG', 'Black', 'Asus', 'Room 205', '2026-03-03', NULL, 'open', '2026-10-07 11:52:16'),
(5, 999, 1, 'HP EliteBook', 'HP work', 'Silver', 'HP', 'Room 101', '2026-03-04', NULL, 'open', '2026-10-07 11:52:16'),
(6, 999, 1, 'Lenovo ThinkPad', 'Black Lenovo', 'Black', 'Lenovo', 'Library', '2026-03-05', NULL, 'open', '2026-10-07 11:52:16'),
(7, 999, 2, 'iPhone 14', 'Apple blue', 'Blue', 'Apple', 'Cafeteria', '2026-03-06', NULL, 'open', '2026-10-07 11:52:16'),
(8, 999, 2, 'Samsung Galaxy', 'Black Samsung', 'Black', 'Samsung', 'Room 205', '2026-03-07', NULL, 'open', '2026-10-07 11:52:16'),
(9, 999, 2, 'Pixel 7', 'Google white', 'White', 'Google', 'Library', '2026-03-08', NULL, 'open', '2026-10-07 11:52:16'),
(10, 999, 2, 'OnePlus phone', 'OnePlus blue', 'Blue', 'OnePlus', 'Cafeteria', '2026-03-09', NULL, 'open', '2026-10-07 11:52:16'),
(11, 999, 2, 'Nokia phone', 'Nokia green', 'Green', 'Nokia', 'Entrance', '2026-03-10', NULL, 'open', '2026-10-07 11:52:16'),
(12, 999, 3, 'Leather wallet', 'Brown Fossil', 'Brown', 'Fossil', 'Cafeteria', '2026-03-11', NULL, 'open', '2026-10-07 11:52:16'),
(13, 999, 3, 'Black cardholder', 'Black Gucci', 'Black', 'Gucci', 'Library', '2026-03-12', NULL, 'open', '2026-10-07 11:52:16'),
(14, 999, 3, 'Levi wallet', 'Levi brown', 'Brown', 'Levi', 'Cafeteria', '2026-03-13', NULL, 'open', '2026-10-07 11:52:16'),
(15, 999, 3, 'Coach wallet', 'Beige Coach', 'Beige', 'Coach', 'Cafeteria', '2026-03-14', NULL, 'open', '2026-10-07 11:52:16'),
(16, 999, 3, 'Red wallet', 'Red leather', 'Red', 'Fossil', 'Library', '2026-03-15', NULL, 'open', '2026-10-07 11:52:16'),
(17, 999, 5, 'Nike backpack', 'Black Nike', 'Black', 'Nike', 'Gym', '2026-03-16', NULL, 'open', '2026-10-07 11:52:16'),
(18, 999, 5, 'Puma duffel', 'Red Puma', 'Red', 'Puma', 'Gym', '2026-03-17', NULL, 'open', '2026-10-07 11:52:16'),
(19, 999, 5, 'Adidas bag', 'Blue Adidas', 'Blue', 'Adidas', 'Library', '2026-03-18', NULL, 'open', '2026-10-07 11:52:16'),
(20, 999, 5, 'Herschel bag', 'Grey Herschel', 'Grey', 'Herschel', 'Library', '2026-03-19', NULL, 'open', '2026-10-07 11:52:16'),
(21, 999, 5, 'Leather bag', 'Brown Fossil', 'Brown', 'Fossil', 'Cafeteria', '2026-03-20', NULL, 'open', '2026-10-07 11:52:16'),
(22, 999, 10, 'Casio watch', 'Casio silver', 'Silver', 'Casio', 'Library', '2026-03-21', NULL, 'open', '2026-10-07 11:52:16'),
(23, 999, 10, 'Fossil watch', 'Fossil brown', 'Brown', 'Fossil', 'Cafeteria', '2026-03-22', NULL, 'open', '2026-10-07 11:52:16'),
(24, 999, 10, 'Garmin watch', 'Garmin black', 'Black', 'Garmin', 'Gym', '2026-03-23', NULL, 'open', '2026-10-07 11:52:16'),
(25, 999, 10, 'Timex watch', 'Timex black', 'Black', 'Timex', 'Library', '2026-03-24', NULL, 'open', '2026-10-07 11:52:16'),
(26, 999, 10, 'Rolex watch', 'Gold Rolex', 'Gold', 'Rolex', 'Cafeteria', '2026-03-25', NULL, 'open', '2026-10-07 11:52:16'),
(27, 999, 8, 'AirPods Pro', 'Apple white', 'White', 'Apple', 'Cafeteria', '2026-03-26', NULL, 'open', '2026-10-07 11:52:16'),
(28, 999, 8, 'Sony headphones', 'Sony black', 'Black', 'Sony', 'Library', '2026-03-27', NULL, 'open', '2026-10-07 11:52:16'),
(29, 999, 8, 'JBL earbuds', 'JBL red', 'Red', 'JBL', 'Gym', '2026-03-28', NULL, 'open', '2026-10-07 11:52:16'),
(30, 999, 8, 'Beats headphones', 'Beats red', 'Red', 'Beats', 'Library', '2026-03-29', NULL, 'open', '2026-10-07 11:52:16'),
(31, 999, 8, 'Bose earbuds', 'Bose black', 'Black', 'Bose', 'Auditorium', '2026-03-30', NULL, 'open', '2026-10-07 11:52:16'),
(32, 999, 9, 'Ray-Ban sunglasses', 'Ray-Ban black', 'Black', 'Ray-Ban', 'Auditorium', '2026-03-31', NULL, 'open', '2026-10-07 11:52:16'),
(33, 999, 9, 'Oakley sunglasses', 'Oakley blue', 'Blue', 'Oakley', 'Cafeteria', '2026-04-01', NULL, 'open', '2026-10-07 11:52:16'),
(34, 999, 9, 'Prada glasses', 'Prada black', 'Black', 'Prada', 'Library', '2026-04-02', NULL, 'open', '2026-10-07 11:52:16'),
(35, 999, 9, 'Gucci glasses', 'Gucci brown', 'Brown', 'Gucci', 'Cafeteria', '2026-04-03', NULL, 'open', '2026-10-07 11:52:16'),
(36, 999, 9, 'Warby Parker glasses', 'Warby blue', 'Blue', 'Warby', 'Library', '2026-04-04', NULL, 'open', '2026-10-07 11:52:16'),
(37, 999, 4, 'Toyota car keys', 'Toyota silver', 'Silver', 'Toyota', 'Parking', '2026-04-05', NULL, 'open', '2026-10-07 11:52:16'),
(38, 999, 4, 'Bike keys', 'Kryptonite black', 'Black', 'Kryptonite', 'Bike Rack', '2026-04-06', NULL, 'open', '2026-10-07 11:52:16'),
(39, 999, 4, 'Honda keys', 'Honda silver', 'Silver', 'Honda', 'Parking', '2026-04-07', NULL, 'open', '2026-10-07 11:52:16'),
(40, 999, 4, 'House keys', 'Yale green', 'Green', 'Yale', 'Entrance', '2026-04-08', NULL, 'open', '2026-10-07 11:52:16'),
(41, 999, 4, 'Ford keys', 'Ford black', 'Black', 'Ford', 'Parking', '2026-04-09', NULL, 'open', '2026-10-07 11:52:16'),
(42, 1, 8, 'airpod pro', '', 'white', 'Apple', 'Canteen', '2026-11-02', NULL, 'open', '2026-11-02 13:56:25');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 999, 'Someone reported losing an item that might match your found \'AirPods\'.', 'browse.php', 0, '2026-11-02 13:56:25'),
(2, 999, 'Someone reported losing an item that might match your found \'Sony cans\'.', 'browse.php', 0, '2026-11-02 13:56:25'),
(3, 999, 'Someone reported losing an item that might match your found \'Red earbuds\'.', 'browse.php', 0, '2026-11-02 13:56:25'),
(4, 999, 'Someone reported losing an item that might match your found \'Red headphones\'.', 'browse.php', 0, '2026-11-02 13:56:25'),
(5, 999, 'Someone reported losing an item that might match your found \'Black earbuds\'.', 'browse.php', 0, '2026-11-02 13:56:25'),
(6, 999, 'Someone reported losing an item that might match your found \'Unknown earbuds\'.', 'browse.php', 0, '2026-11-02 13:56:25');

-- --------------------------------------------------------

--
-- Table structure for table `pending_categories`
--

CREATE TABLE `pending_categories` (
  `pending_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `suggested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_categories`
--

INSERT INTO `pending_categories` (`pending_id`, `user_id`, `category_name`, `reason`, `status`, `suggested_at`, `reviewed_at`) VALUES
(1, 2, 'jacket', 'many students wear jacket in denmark', 'approved', '2026-09-28 09:33:41', '2026-09-28 09:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@nielsbrock.dk', '$2y$10$z8IjlLkGkj0eVQg/BAchWeuLVdh2Fm09ThxwPp.vKpWwlfuQU.256', 'admin', '2026-09-20 22:43:11'),
(2, 'Test User', 'test@test.com', '$2y$10$AO4bKiMQueDl6UX0RGGyuuKnSMfbH64KmzHuBX7jOFGxY5MKVDh9u', 'user', '2026-09-20 23:05:26'),
(3, 'Test User2', 'test1@test.com', '$2y$10$BcpIUhmXJIjCDVivyrnCBevpA0iaN1UnWdztnEXWoPhqWi0PtQJoq', 'user', '2026-09-27 22:56:00'),
(999, 'Simulated Data', 'simulated@nielsbrock.dk', 'x', 'user', '2026-10-07 11:52:16'),
(1000, 'test user 3', 'test3@test.com', '$2y$10$89eyU3L.HvgfnRvp8T32xuOViFfMeLRMYzvPbSnUl8vlOx4N1/2OW', 'user', '2026-10-07 13:53:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD KEY `lost_id` (`lost_id`),
  ADD KEY `found_id` (`found_id`),
  ADD KEY `claimant_id` (`claimant_id`);

--
-- Indexes for table `found_items`
--
ALTER TABLE `found_items`
  ADD PRIMARY KEY (`found_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`lost_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pending_categories`
--
ALTER TABLE `pending_categories`
  ADD PRIMARY KEY (`pending_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `found_items`
--
ALTER TABLE `found_items`
  MODIFY `found_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `lost_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pending_categories`
--
ALTER TABLE `pending_categories`
  MODIFY `pending_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1001;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`lost_id`) REFERENCES `lost_items` (`lost_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`found_id`) REFERENCES `found_items` (`found_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `claims_ibfk_3` FOREIGN KEY (`claimant_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `found_items`
--
ALTER TABLE `found_items`
  ADD CONSTRAINT `found_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `found_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD CONSTRAINT `lost_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lost_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_categories`
--
ALTER TABLE `pending_categories`
  ADD CONSTRAINT `pending_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
