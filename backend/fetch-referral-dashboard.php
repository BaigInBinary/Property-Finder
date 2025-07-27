<?php
include 'db.php';
session_start();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Total Points Earned
$query = "SELECT SUM(bonus_points_awarded) AS total_points FROM referrals WHERE referrer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($totalPoints);
$stmt->fetch();
$stmt->close();

$totalPoints = $totalPoints ?? 0;

// Milestone logic
$nextMilestone = 500;
$pointsToNext = max(0, $nextMilestone - $totalPoints);
$progressPercent = min(100, ($totalPoints / $nextMilestone) * 100);

// Rewards History
$query = "SELECT r.referred_at AS date, u.name AS referred_name, r.bonus_points_awarded AS points, 'Credited' AS status
          FROM referrals r
          JOIN users u ON r.referred_id = u.id
          WHERE r.referrer_id = ?
          ORDER BY r.referred_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'date' => date('Y-m-d', strtotime($row['date'])),
        'activity' => 'Referral Signup - ' . $row['referred_name'],
        'points' => '+' . $row['points'],
        'status' => $row['status']
    ];
}
$stmt->close();

// Fetch Referral Code
$query = "SELECT referral_code FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($referralCode);
$stmt->fetch();
$stmt->close();

$response = [
    'status' => 'success',
    'total_points' => (int)$totalPoints,
    'next_milestone' => $nextMilestone,
    'points_to_next' => $pointsToNext,
    'progress_percent' => round($progressPercent),
    'referral_code' => $referralCode ?? '',
    'rewards_history' => $history
];

echo json_encode($response);

$conn->close();
?>
