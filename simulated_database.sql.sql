-- ============================================================
-- SIMULATED DATA FOR EVALUATION
-- 50 lost items + 50 found items = 100 total
-- Tagged with user_id = 999 so they can be removed later
-- ------------------------------------------------------------
-- Composition:
--   25 PERFECT pairs  (all 5 attributes match)
--   15 PARTIAL pairs  (some attributes differ)
--   10 NON-MATCHES    (unrelated items)
-- ============================================================

USE lost_and_found;

-- Clear any previous simulated data
DELETE FROM lost_items  WHERE user_id = 999;
DELETE FROM found_items WHERE user_id = 999;
DELETE FROM users       WHERE user_id = 999;

-- Create simulated user (owns all test items)
INSERT INTO users (user_id, full_name, email, password, role)
VALUES (999, 'Simulated Data', 'simulated@nielsbrock.dk', 'x', 'user');

-- ============================================================
-- PERFECT PAIRS (25 pairs = 50 items)
-- Same category, color, brand, location, date
-- Expected match score: 100
-- ============================================================

-- Pair 1: MacBook Pro
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'MacBook Pro 13', 'Silver laptop with stickers', 'Silver', 'Apple', 'Library', '2026-01-10', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'MacBook Pro', 'Found in library', 'Silver', 'Apple', 'Library', '2026-01-10', 'open');

-- Pair 2: iPhone
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'iPhone 14 Pro', 'Blue case', 'Blue', 'Apple', 'Cafeteria', '2026-01-11', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'iPhone 14', 'Blue phone found', 'Blue', 'Apple', 'Cafeteria', '2026-01-11', 'open');

-- Pair 3: Wallet
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Black leather wallet', 'Contains ID', 'Black', 'Fossil', 'Lecture Hall A', '2026-01-12', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Leather wallet', 'Black wallet with cards', 'Black', 'Fossil', 'Lecture Hall A', '2026-01-12', 'open');

-- Pair 4: Keys
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Car keys Toyota', 'Red keychain', 'Silver', 'Toyota', 'Parking Lot', '2026-01-13', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Toyota keys', 'Keys with red chain', 'Silver', 'Toyota', 'Parking Lot', '2026-01-13', 'open');

-- Pair 5: Backpack
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Nike backpack', 'Black with laptop', 'Black', 'Nike', 'Gym', '2026-01-14', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Black backpack', 'Nike bag found', 'Black', 'Nike', 'Gym', '2026-01-14', 'open');

-- Pair 6: ID Card
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='ID Card'),
        'Student ID', 'Name: Asmita', 'White', 'Niels Brock', 'Library', '2026-01-15', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='ID Card'),
        'Student card', 'Found ID with name', 'White', 'Niels Brock', 'Library', '2026-01-15', 'open');

-- Pair 7: Charger
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Charger'),
        'USB-C Charger', 'For MacBook', 'White', 'Apple', 'Room 205', '2026-01-16', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Charger'),
        'Apple charger', 'White USB-C', 'White', 'Apple', 'Room 205', '2026-01-16', 'open');

-- Pair 8: AirPods
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'AirPods Pro', 'In white case', 'White', 'Apple', 'Cafeteria', '2026-01-17', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'AirPods', 'White earbuds', 'White', 'Apple', 'Cafeteria', '2026-01-17', 'open');

-- Pair 9: Glasses
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Ray-Ban sunglasses', 'Black frame', 'Black', 'Ray-Ban', 'Auditorium', '2026-01-18', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Sunglasses', 'Ray-Ban black', 'Black', 'Ray-Ban', 'Auditorium', '2026-01-18', 'open');

-- Pair 10: Watch
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Casio watch', 'Digital silver', 'Silver', 'Casio', 'Library', '2026-01-19', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Digital watch', 'Casio silver', 'Silver', 'Casio', 'Library', '2026-01-19', 'open');

-- Pair 11: Umbrella
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Black umbrella', 'Compact foldable', 'Black', 'Totes', 'Entrance', '2026-01-20', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Umbrella', 'Black foldable', 'Black', 'Totes', 'Entrance', '2026-01-20', 'open');

-- Pair 12: Water Bottle
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Hydro Flask', 'Blue 32oz', 'Blue', 'Hydro Flask', 'Gym', '2026-01-21', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Blue bottle', 'Hydro Flask found', 'Blue', 'Hydro Flask', 'Gym', '2026-01-21', 'open');

-- Pair 13: Book
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'Calculus textbook', 'Math book', 'Blue', 'Pearson', 'Library', '2026-01-22', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'Math book', 'Blue textbook', 'Blue', 'Pearson', 'Library', '2026-01-22', 'open');

-- Pair 14: Jacket
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Clothing'),
        'North Face jacket', 'Black fleece', 'Black', 'North Face', 'Lecture Hall B', '2026-01-23', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Clothing'),
        'Black jacket', 'North Face fleece', 'Black', 'North Face', 'Lecture Hall B', '2026-01-23', 'open');

-- Pair 15: Ring
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Jewelry'),
        'Silver ring', 'Wedding band', 'Silver', 'Tiffany', 'Cafeteria', '2026-01-24', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Jewelry'),
        'Ring', 'Silver wedding band', 'Silver', 'Tiffany', 'Cafeteria', '2026-01-24', 'open');

-- Pair 16: ThinkPad
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'ThinkPad X1', 'Business laptop', 'Black', 'Lenovo', 'Room 101', '2026-01-25', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'Lenovo ThinkPad', 'Black business laptop', 'Black', 'Lenovo', 'Room 101', '2026-01-25', 'open');

-- Pair 17: Samsung
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'Galaxy S23', 'Black Samsung', 'Black', 'Samsung', 'Library', '2026-01-26', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'Samsung phone', 'Black Galaxy', 'Black', 'Samsung', 'Library', '2026-01-26', 'open');

-- Pair 18: Puma bag
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Puma duffel', 'Red sports bag', 'Red', 'Puma', 'Gym', '2026-01-27', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Red duffel bag', 'Puma', 'Red', 'Puma', 'Gym', '2026-01-27', 'open');

-- Pair 19: Wallet 2
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Brown wallet', 'Leather bifold', 'Brown', 'Levi', 'Cafeteria', '2026-01-28', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Leather wallet', 'Brown bifold', 'Brown', 'Levi', 'Cafeteria', '2026-01-28', 'open');

-- Pair 20: Bike keys
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Bike keys', 'With combination lock', 'Black', 'Kryptonite', 'Bike Rack', '2026-01-29', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Bicycle keys', 'Black lock keys', 'Black', 'Kryptonite', 'Bike Rack', '2026-01-29', 'open');

-- Pair 21: Sony headphones
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'Sony WH-1000', 'Black over-ear', 'Black', 'Sony', 'Library', '2026-01-30', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'Sony headphones', 'Black noise-cancelling', 'Black', 'Sony', 'Library', '2026-01-30', 'open');

-- Pair 22: Reading glasses
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Reading glasses', 'Brown frame', 'Brown', 'Warby', 'Room 305', '2026-01-31', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Brown glasses', 'Reading glasses', 'Brown', 'Warby', 'Room 305', '2026-01-31', 'open');

-- Pair 23: Fossil watch
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Fossil watch', 'Brown leather strap', 'Brown', 'Fossil', 'Cafeteria', '2026-02-01', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Leather watch', 'Fossil brown', 'Brown', 'Fossil', 'Cafeteria', '2026-02-01', 'open');

-- Pair 24: Water bottle 2
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Stainless bottle', 'Green 24oz', 'Green', 'Klean', 'Library', '2026-02-02', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Green bottle', 'Klean Kanteen', 'Green', 'Klean', 'Library', '2026-02-02', 'open');

-- Pair 25: Novel
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'Harry Potter book', 'Paperback', 'Red', 'Bloomsbury', 'Library', '2026-02-03', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'Red novel', 'Harry Potter paperback', 'Red', 'Bloomsbury', 'Library', '2026-02-03', 'open');

-- ============================================================
-- PARTIAL MATCHES (15 pairs = 30 items)
-- Same category but some attributes differ
-- Expected scores: 40-80 (MEDIUM range)
-- ============================================================

-- Partial 1: Same category, different color
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'Dell XPS', 'Red laptop', 'Red', 'Dell', 'Library', '2026-02-04', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'Dell laptop', 'Black laptop', 'Black', 'Dell', 'Library', '2026-02-04', 'open');

-- Partial 2: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'Samsung Galaxy', 'Black', 'Black', 'Samsung', 'Cafeteria', '2026-02-05', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'Android phone', 'Black', 'Black', 'Google', 'Cafeteria', '2026-02-05', 'open');

-- Partial 3: Same category+brand, different location
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Adidas bag', 'Blue', 'Blue', 'Adidas', 'Gym', '2026-02-06', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Adidas gym bag', 'Blue', 'Blue', 'Adidas', 'Library', '2026-02-06', 'open');

-- Partial 4: Same category, different date (5+ days)
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Coach wallet', 'Beige', 'Beige', 'Coach', 'Cafeteria', '2026-02-07', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Beige wallet', 'Coach', 'Beige', 'Coach', 'Cafeteria', '2026-02-14', 'open');

-- Partial 5: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'Beats headphones', 'Red', 'Red', 'Beats', 'Library', '2026-02-08', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'Red headphones', 'Sony', 'Red', 'Sony', 'Library', '2026-02-08', 'open');

-- Partial 6: Same category+color+brand, different location
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Nalgene bottle', 'Clear', 'Clear', 'Nalgene', 'Gym', '2026-02-09', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Clear bottle', 'Nalgene', 'Clear', 'Nalgene', 'Library', '2026-02-09', 'open');

-- Partial 7: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Oakley sunglasses', 'Blue lens', 'Blue', 'Oakley', 'Auditorium', '2026-02-10', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Blue sunglasses', 'Ray-Ban', 'Blue', 'Ray-Ban', 'Auditorium', '2026-02-10', 'open');

-- Partial 8: Same category, different everything else
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Rolex watch', 'Gold luxury', 'Gold', 'Rolex', 'Hotel', '2026-02-11', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Timex watch', 'Black digital', 'Black', 'Timex', 'Library', '2026-02-11', 'open');

-- Partial 9: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Charger'),
        'Anker charger', 'Black', 'Black', 'Anker', 'Room 102', '2026-02-12', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Charger'),
        'Black charger', 'Samsung', 'Black', 'Samsung', 'Room 102', '2026-02-12', 'open');

-- Partial 10: Same category+brand, different date
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Herschel backpack', 'Grey', 'Grey', 'Herschel', 'Library', '2026-02-13', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Grey backpack', 'Herschel', 'Grey', 'Herschel', 'Library', '2026-02-20', 'open');

-- Partial 11: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Clothing'),
        'Levi jeans', 'Blue denim', 'Blue', 'Levi', 'Gym', '2026-02-14', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Clothing'),
        'Blue jeans', 'Wrangler', 'Blue', 'Wrangler', 'Gym', '2026-02-14', 'open');

-- Partial 12: Same category+color+brand, different location
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='ID Card'),
        'Employee badge', 'Red lanyard', 'Red', 'Niels Brock', 'Room 101', '2026-02-15', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='ID Card'),
        'Badge with lanyard', 'Niels Brock red', 'Red', 'Niels Brock', 'Cafeteria', '2026-02-15', 'open');

-- Partial 13: Same category, different color
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Totes umbrella', 'Pink', 'Pink', 'Totes', 'Entrance', '2026-02-16', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Purple umbrella', 'Totes', 'Purple', 'Totes', 'Entrance', '2026-02-16', 'open');

-- Partial 14: Same category, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Jewelry'),
        'Silver bracelet', 'Pandora', 'Silver', 'Pandora', 'Library', '2026-02-17', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Jewelry'),
        'Bracelet', 'Silver', 'Silver', 'Swarovski', 'Library', '2026-02-17', 'open');

-- Partial 15: Same category+color, different brand
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Honda car keys', 'Black remote', 'Black', 'Honda', 'Parking', '2026-02-18', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'Car keys', 'Black remote Toyota', 'Black', 'Toyota', 'Parking', '2026-02-18', 'open');

-- ============================================================
-- NON-MATCHES (10 pairs = 20 items)
-- Completely unrelated items
-- Expected scores: 0 (no category match)
-- ============================================================

-- Non-match 1: Laptop vs Umbrella
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'HP Laptop', 'Black HP', 'Black', 'HP', 'Library', '2026-02-19', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Blue Umbrella', 'Found near door', 'Blue', NULL, 'Entrance', '2026-02-19', 'open');

-- Non-match 2: Phone vs Book
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'Pixel 7', 'White Google', 'White', 'Google', 'Cafeteria', '2026-02-20', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'Physics book', 'Green cover', 'Green', 'McGraw', 'Library', '2026-02-20', 'open');

-- Non-match 3: Wallet vs Charger
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Wallet'),
        'Yellow wallet', 'Bright leather', 'Yellow', 'Herschel', 'Room 101', '2026-02-21', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Charger'),
        'iPhone charger', 'Lightning cable', 'White', 'Apple', 'Room 101', '2026-02-21', 'open');

-- Non-match 4: Keys vs Glasses
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Keys'),
        'House keys', 'Green keychain', 'Green', 'Yale', 'Entrance', '2026-02-22', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Glasses'),
        'Prescription glasses', 'Thin frame', 'Silver', 'Oakley', 'Entrance', '2026-02-22', 'open');

-- Non-match 5: Book vs Watch
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Books'),
        'History textbook', 'Thick paperback', 'White', 'Oxford', 'Library', '2026-02-23', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Watch'),
        'Digital watch', 'Black Casio', 'Black', 'Casio', 'Library', '2026-02-23', 'open');

-- Non-match 6: Bag vs ID Card
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Pink handbag', 'Small purse', 'Pink', 'MK', 'Cafeteria', '2026-02-24', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='ID Card'),
        'Student ID', 'Blue card', 'Blue', 'Niels Brock', 'Cafeteria', '2026-02-24', 'open');

-- Non-match 7: Headphones vs Jewelry
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Headphones'),
        'JBL earbuds', 'Red wireless', 'Red', 'JBL', 'Gym', '2026-02-25', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Jewelry'),
        'Gold earrings', 'Small studs', 'Gold', NULL, 'Gym', '2026-02-25', 'open');

-- Non-match 8: Clothing vs Water Bottle
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Clothing'),
        'Hoodie', 'Grey zip-up', 'Grey', 'Champion', 'Lecture Hall C', '2026-02-26', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Water Bottle'),
        'Metal bottle', 'Black insulated', 'Black', 'Yeti', 'Lecture Hall C', '2026-02-26', 'open');

-- Non-match 9: Umbrella vs Phone
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Umbrella'),
        'Red umbrella', 'Large', 'Red', 'Totes', 'Entrance', '2026-02-27', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Phone'),
        'OnePlus phone', 'Blue Android', 'Blue', 'OnePlus', 'Entrance', '2026-02-27', 'open');

-- Non-match 10: Laptop vs Bag
INSERT INTO lost_items (user_id, category_id, item_name, description, color, brand, location, date_lost, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Laptop'),
        'Asus laptop', 'Silver gaming', 'Silver', 'Asus', 'Library', '2026-02-28', 'open');
INSERT INTO found_items (user_id, category_id, item_name, description, color, brand, location, date_found, status)
VALUES (999, (SELECT category_id FROM categories WHERE category_name='Bag'),
        'Grey tote bag', 'Canvas', 'Grey', NULL, 'Library', '2026-02-28', 'open');

-- ============================================================
-- VERIFICATION
-- ============================================================
SELECT 'Simulated data imported!' AS Status,
       (SELECT COUNT(*) FROM lost_items  WHERE user_id=999) AS Lost_Count,
       (SELECT COUNT(*) FROM found_items WHERE user_id=999) AS Found_Count;