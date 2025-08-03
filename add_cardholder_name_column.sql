-- Add cardholder_name column to property_buy_requests table
-- Run this script if the column doesn't exist

ALTER TABLE property_buy_requests 
ADD COLUMN cardholder_name VARCHAR(255) AFTER email;

-- Update existing records with a placeholder if needed
-- UPDATE property_buy_requests SET cardholder_name = 'Not Provided' WHERE cardholder_name IS NULL; 