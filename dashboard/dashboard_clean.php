<?php
session_start();
require_once '../backend/db.php'; // adjust path if needed

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?message=login_required");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user name from database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$userName = 'User';
if ($row = mysqli_fetch_assoc($result)) {
    $userName = $row['name'];
    $userEmail = $row['email'];
    $userCNIC = $row['cnic'];
    $userRole = $row['role'];
    $userCreated = $row['created_at'];
    $picture = $row['picture'];
}

// Fetch revenue and expenditure for the user
$revenue = 0;
$expenditure = 0;
$stmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE seller_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($revenue);
$stmt->fetch();
$stmt->close();
$stmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE buyer_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($expenditure);
$stmt->fetch();
$stmt->close();
// Fetch transaction history for the user
$transactions = [];
$stmt = $conn->prepare("SELECT t.*, p.title AS property_title, bu.name AS buyer_name, se.name AS seller_name FROM transactions t
    LEFT JOIN properties p ON t.property_id = p.id
    LEFT JOIN users bu ON t.buyer_id = bu.id
    LEFT JOIN users se ON t.seller_id = se.id
    WHERE t.buyer_id = ? OR t.seller_id = ? ORDER BY t.created_at DESC");
$stmt->bind_param('ii', $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
?>
<style>
    .navbar {
        z-index: 1030;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .main-content {
        padding-top: 76px;
        /* Adjust based on your navbar height */
    }

    .sidebar {
        top: 76px;
        /* Should match the padding-top of main-content */
        height: calc(100vh - 76px);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    }

    .notifications-dropdown .dropdown-menu {
        min-width: 300px;
    }

    .notification-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem;
    }
    
