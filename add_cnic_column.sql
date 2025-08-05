-- Add CNIC column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS cnic VARCHAR(15) AFTER location;

-- Check if the column was added successfully
DESCRIBE users; 