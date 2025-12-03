-- TiDB Migration Script
-- Run these statements ONE BY ONE in TiDB SQL Editor
-- This adds missing columns to an existing database

-- 1. Add credits and suspended columns to users table
ALTER TABLE `users` ADD COLUMN `suspended` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `credits` int DEFAULT 20;

-- Remove status column if it exists (replaced by suspended)
-- ALTER TABLE `users` DROP COLUMN `status`;

-- 2. Add energy_type column to vehicles table
ALTER TABLE `vehicles` ADD COLUMN `energy_type` varchar(50) DEFAULT NULL AFTER `plate`;

-- 3. Add commission and completed_at columns to ride_requests table
ALTER TABLE `ride_requests` ADD COLUMN `commission` int DEFAULT 0;
ALTER TABLE `ride_requests` ADD COLUMN `completed_at` timestamp NULL DEFAULT NULL;

-- 4. Update existing vehicles with energy types
UPDATE `vehicles` SET `energy_type` = 'petrol' WHERE `energy_type` IS NULL;

-- 5. Update existing users with default credits
UPDATE `users` SET `credits` = 20 WHERE `credits` IS NULL;

-- 6. Set commission for existing ride_requests (2 credits per seat)
UPDATE `ride_requests` SET `commission` = passenger_count * 2 WHERE `commission` = 0 OR `commission` IS NULL;

-- Verify the changes
SELECT 'Migration completed!' AS status;
DESCRIBE `users`;
DESCRIBE `vehicles`;
DESCRIBE `ride_requests`;
