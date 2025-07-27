<?php
include 'db.php';
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Unknown error'];

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch total rewards and total referrals
$totalRewards = 0;
$totalReferrals = 0;

$query = "SELECT SUM(bonus_points_awarded) AS total_rewards, COUNT(*) AS total_referrals 
          FROM referrals 
          WHERE referrer_id = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalRewards = $result['total_rewards'] ?? 0;
    $totalReferrals = $result['total_referrals'] ?? 0;
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch rewards: ' . $conn->error]);
    exit;
}

// Fetch recent referrals
$recentReferrals = [];
$query = "SELECT u.name AS referred_name, r.referred_at, r.bonus_points_awarded
          FROM referrals r
          JOIN users u ON r.referred_id = u.id
          WHERE r.referrer_id = ?
          ORDER BY r.referred_at DESC
          LIMIT 5";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentReferrals = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch reward points used (reward_points column in users)
$rewardPoints = 0;
$query = "SELECT reward_points FROM users WHERE id = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($rewardPoints);
    $stmt->fetch();
    $stmt->close();
}

// Fetch user's referral code
$referralCode = '';
$query = "SELECT referral_code FROM users WHERE id = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($referralCode);
    $stmt->fetch();
    $stmt->close();
}

$response = [
    'status' => 'success',
    'total_rewards' => (int) $totalRewards,
    'total_referrals' => (int) $totalReferrals,
    'successful_rewards_used' => (int) $rewardPoints,
    'referral_code' => $referralCode,
    'recent_referrals' => $recentReferrals
];

echo json_encode($response);
$conn->close();
?>
