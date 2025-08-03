-- Add listing column to properties table for property approval
ALTER TABLE properties ADD COLUMN listing ENUM('approved', 'rejected', 'pending') DEFAULT 'pending' AFTER status;

-- Add updated_at column for tracking modifications
ALTER TABLE properties ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER listing;

-- Update existing properties to have appropriate listing status based on current status
UPDATE properties SET listing = 'approved' WHERE status = 'available';
UPDATE properties SET listing = 'pending' WHERE status = 'pending' OR status IS NULL;
UPDATE properties SET listing = 'pending' WHERE status = 'sold'; 