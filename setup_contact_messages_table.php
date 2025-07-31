<?php
require_once 'backend/db.php';

// SQL to create contact_messages table
$sql = "
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    sender_phone VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)";

// Execute the SQL
if ($conn->query($sql) === TRUE) {
    echo "✅ contact_messages table created successfully\n";
    
    // Create indexes (with error handling)
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_contact_messages_property_id ON contact_messages(property_id)",
        "CREATE INDEX IF NOT EXISTS idx_contact_messages_created_at ON contact_messages(created_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        if ($conn->query($index_sql) === TRUE) {
            echo "✅ Index created successfully\n";
        } else {
            // Check if index already exists
            if (strpos($conn->error, "Duplicate key name") !== false || strpos($conn->error, "already exists") !== false) {
                echo "ℹ️ Index already exists\n";
            } else {
                echo "⚠️ Index creation failed: " . $conn->error . "\n";
            }
        }
    }
    
    echo "\n🎉 Contact messages table setup complete!\n";
    echo "The contact agent form will now send emails to property creators.\n";
    
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?> 