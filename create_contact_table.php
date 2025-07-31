<?php
require_once 'backend/db.php';

// Drop table if exists and recreate
$drop_sql = "DROP TABLE IF EXISTS contact_messages";
$conn->query($drop_sql);

// Create contact_messages table
$sql = "
CREATE TABLE contact_messages (
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
    
    // Create indexes
    $conn->query("CREATE INDEX idx_contact_messages_property_id ON contact_messages(property_id)");
    $conn->query("CREATE INDEX idx_contact_messages_created_at ON contact_messages(created_at)");
    
    echo "✅ Indexes created successfully\n";
    echo "\n🎉 Contact messages table setup complete!\n";
    echo "The contact agent form will now send emails to property creators.\n";
    
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?> 