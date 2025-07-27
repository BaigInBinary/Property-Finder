<?php
// Include database configuration
require_once 'backend/db.php';

// Test query
$query = "SHOW TABLES";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<h2>Database Connection Successful!</h2>";
    echo "<h3>Tables in propfind_db:</h3>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 