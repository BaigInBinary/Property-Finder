<?php
require_once 'backend/db.php';

echo "Starting database update...\n";

// Add listing column to properties table
$sql1 = "ALTER TABLE properties ADD COLUMN listing ENUM('approved', 'rejected', 'pending') DEFAULT 'pending' AFTER status";
if ($conn->query($sql1) === TRUE) {
    echo "✓ Added listing column to properties table\n";
} else {
    echo "✗ Error adding listing column: " . $conn->error . "\n";
}

// Add updated_at column for tracking modifications
$sql2 = "ALTER TABLE properties ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER listing";
if ($conn->query($sql2) === TRUE) {
    echo "✓ Added updated_at column\n";
} else {
    echo "✗ Error adding updated_at column: " . $conn->error . "\n";
}

// Update existing properties to have appropriate listing status based on current status
$sql3 = "UPDATE properties SET listing = 'approved' WHERE status = 'available'";
if ($conn->query($sql3) === TRUE) {
    echo "✓ Updated available properties to approved listing status\n";
} else {
    echo "✗ Error updating available properties: " . $conn->error . "\n";
}

$sql4 = "UPDATE properties SET listing = 'pending' WHERE status = 'pending' OR status IS NULL";
if ($conn->query($sql4) === TRUE) {
    echo "✓ Updated pending properties to pending listing status\n";
} else {
    echo "✗ Error updating pending properties: " . $conn->error . "\n";
}

$sql5 = "UPDATE properties SET listing = 'pending' WHERE status = 'sold'";
if ($conn->query($sql5) === TRUE) {
    echo "✓ Updated sold properties to pending listing status\n";
} else {
    echo "✗ Error updating sold properties: " . $conn->error . "\n";
}

echo "\nDatabase update completed!\n";
echo "Enhanced property approval system is now available with:\n";
echo "- listing field (approved/rejected/pending)\n";
echo "- updated_at (tracks when properties are modified)\n";

$conn->close();
?> 