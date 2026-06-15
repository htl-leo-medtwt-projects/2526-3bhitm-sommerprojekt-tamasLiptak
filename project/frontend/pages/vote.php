<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$userID = $_SESSION['userData']['userID'] ?? null;
if (!$userID) {
    echo json_encode(['success' => false, 'error' => 'invalid_session']);
    exit;
}

$type  = $_POST['type']  ?? '';
$value = $_POST['value'] ?? '';

if (!in_array($type, ['hero', 'map']) || empty($value)) {
    echo json_encode(['success' => false, 'error' => 'invalid_input']);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}

// Week starts on Monday (UTC+2 / Europe/Vienna)
date_default_timezone_set('Europe/Vienna');
$weekStart = date('Y-m-d', strtotime('monday this week'));

$stmt = $conn->prepare("
    INSERT INTO weekly_votes (userID, vote_type, vote_value, week_start)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE vote_value = VALUES(vote_value), voted_at = CURRENT_TIMESTAMP
");
$stmt->bind_param('isss', $userID, $type, $value, $weekStart);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'voted_for' => $value, 'type' => $type]);
} else {
    echo json_encode(['success' => false, 'error' => 'insert_failed']);
}

$stmt->close();
$conn->close();
?>
