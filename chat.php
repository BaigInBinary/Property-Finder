<?php
session_start();
require_once 'backend/db.php'; // adjust path if needed

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?message=login_required");
    exit;
}
?>
<?php include'inc/header.php'; ?>


<style>
        .dot-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

    .chat-container {
        height: 90vh;
        margin-top: 20px;
    }

    /* Style the messages area to be scrollable */
    .messages-area {
        height: 400px;
        overflow-y: auto;
        padding: 15px;
        background-color: #f8f9fa;
    }

    /* Style message bubbles */
    .message {
        margin-bottom: 15px;
        max-width: 70%;
    }

    .message-sent {
        margin-left: auto;
    }

    .message-received {
        margin-right: auto;
    }

    /* Style user list items */
    .user-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .user-item:hover {
        background-color: #f8f9fa;
    }

    /* Status badges */
    .status-badge {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .status-online {
        background-color: #28a745;
    }

    .status-offline {
        background-color: #dc3545;
    }

    /* Make the chat input stick to the bottom */
    .chat-input {
        padding: 15px;
        background-color: white;
        border-top: 1px solid #dee2e6;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .chat-container {
            height: auto;
        }
        
        .messages-area {
            height: 300px;
        }
    }
</style>

    <!-- Main Content -->
    <div class="container">
        <div class="row chat-container">
            <!-- Left Sidebar - User List -->
            <div class="col-md-4 col-12 border-end p-0">
                <!-- Sidebar Header -->
                <div class="p-3 border-bottom">
                    <h5 class="mb-3">Messages</h5>
                    <!-- Search Box -->
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search users...">
                    </div>
                </div>

                <!-- User List -->
                <ul class="nav nav-pills flex-column list-group list-group-flush" id="userList">
    <div class="text-center text-muted p-3">Loading users...</div>
</ul>


            </div>
            <div class="col-md-8 col-12 p-0 d-flex flex-column">
            <!-- Right Side - Chat Area -->
            <div class="tab-content" id="chatTabsContent">
    <!-- Chat content will be dynamically injected here based on active user -->
</div>
</div>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/chat.js"></script>
<?php 'inc/footer.php'; ?>