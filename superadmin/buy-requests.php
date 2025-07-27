<?php
include '../backend/db.php';
session_start();
// Optionally, check if user is admin here

$query = "SELECT r.*, p.title AS property_title, u.name AS user_name FROM property_buy_requests r
          LEFT JOIN properties p ON r.property_id = p.id
          LEFT JOIN users u ON r.user_id = u.id
          ORDER BY r.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Requests - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        th { background: #f5f5f5; }
        .btn { padding: 6px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-approve { background: #4caf50; color: #fff; }
        .btn-reject { background: #f44336; color: #fff; }
        .status-pending { color: #ff9800; font-weight: bold; }
        .status-approved { color: #4caf50; font-weight: bold; }
        .status-rejected { color: #f44336; font-weight: bold; }
        
        /* Loading styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .row-loading {
            background-color: #f8f9fa;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div id="loadingText">Processing request...</div>
        </div>
    </div>
    
    <h1>Property Buy Requests</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Property</th>
                <th>User</th>
                <th>Email</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr id="req-<?php echo $row['id']; ?>">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                <td><?php echo htmlspecialchars($row['user_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <?php if($row['status'] === 'pending'): ?>
                        <button class="btn btn-approve" onclick="handleRequest(<?php echo $row['id']; ?>, 'approved')">Approve</button>
                        <button class="btn btn-reject" onclick="handleRequest(<?php echo $row['id']; ?>, 'rejected')">Reject</button>
                    <?php else: ?>
                        <span>-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <script>
    function showLoading(message) {
        $('#loadingText').text(message || 'Processing request...');
        $('#loadingOverlay').css('display', 'flex');
    }
    
    function hideLoading() {
        $('#loadingOverlay').hide();
    }
    
    function handleRequest(id, action) {
        if (!confirm('Are you sure you want to ' + action + ' this request?')) return;
        
        // Show loading and disable buttons
        showLoading(action.charAt(0).toUpperCase() + action.slice(1) + 'ing request...');
        $('.btn').prop('disabled', true);
        $('#req-' + id).addClass('row-loading');
        
        $.ajax({
            url: '../backend/handle-buy-request.php',
            method: 'POST',
            data: { id: id, action: action },
            dataType: 'json',
            success: function(res) {
                hideLoading();
                $('.btn').prop('disabled', false);
                $('#req-' + id).removeClass('row-loading');
                
                if(res.success) {
                    $('#req-' + id + ' td.status-pending').text(action.charAt(0).toUpperCase() + action.slice(1)).removeClass('status-pending').addClass('status-' + action);
                    $('#req-' + id + ' td:last').html('<span>-</span>');
                    alert(res.message || 'Request ' + action + ' successfully.');
                    
                    // If approved, refresh the page to show updated statuses of other requests
                    if (action === 'approved') {
                        showLoading('Refreshing page to show updated statuses...');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    alert(res.message || 'Failed to update request.');
                }
            },
            error: function() { 
                hideLoading();
                $('.btn').prop('disabled', false);
                $('#req-' + id).removeClass('row-loading');
                alert('Error processing request.'); 
            }
        });
    }
    </script>
</body>
</html> 