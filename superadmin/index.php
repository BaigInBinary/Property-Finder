<?php
require_once '../backend/db.php';

// Total Users
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];

// Total Properties
$totalProperties = $conn->query("SELECT COUNT(*) AS count FROM properties")->fetch_assoc()['count'];

// Approved Properties
$approvedProperties = $conn->query("SELECT COUNT(*) AS count FROM properties WHERE status = 'available'")->fetch_assoc()['count'];

// Rejected Properties
$rejectedProperties = $conn->query("SELECT COUNT(*) AS count FROM properties WHERE status = 'rejected'")->fetch_assoc()['count'];
?>

<?php include 'inc/header.php'; ?>

<div class="main-content container-fluid p-4">
    <h2 class="mb-4">Admin Dashboard</h2>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo $totalProperties; ?></h3>
                    <p class="text-muted mb-0">Total Properties</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo $approvedProperties; ?></h3>
                    <p class="text-success mb-0">Approved Properties</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3><?php echo $rejectedProperties; ?></h3>
                    <p class="text-danger mb-0">Rejected Properties</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">Property Status Overview</div>
                <div class="card-body">
                    <canvas id="propertyStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">Users vs Properties</div>
                <div class="card-body">
                    <canvas id="userPropertyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Property Status Pie Chart
const propertyStatusChart = new Chart(document.getElementById('propertyStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Rejected', 'Pending'],
        datasets: [{
            data: [<?php echo $approvedProperties; ?>, <?php echo $rejectedProperties; ?>, <?php echo $totalProperties - ($approvedProperties + $rejectedProperties); ?>],
            backgroundColor: ['#28a745', '#dc3545', '#ffc107']
        }]
    }
});

// Users vs Properties Bar Chart
const userPropertyChart = new Chart(document.getElementById('userPropertyChart'), {
    type: 'bar',
    data: {
        labels: ['Users', 'Properties'],
        datasets: [{
            label: 'Count',
            data: [<?php echo $totalUsers; ?>, <?php echo $totalProperties; ?>],
            backgroundColor: ['#007bff', '#17a2b8']
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'inc/footer.php'; ?>
