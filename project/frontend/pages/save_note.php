<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['loggedIn']) || !$_SESSION['loggedIn']) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}
$data          = json_decode(file_get_contents('php://input'), true);
$heroID        = isset($data['heroID'])        ? (int)$data['heroID']        : 0;
$counteredByHeroID = isset($data['counteredByHeroID']) ? (int)$data['counteredByHeroID'] : 0;
$severity      = isset($data['severity'])      ? (int)$data['severity']      : 1;
$counterTips   = isset($data['counterTips'])   ? trim($data['counterTips'])  : '';
$teammateHelp  = isset($data['teammateHelp'])  ? trim($data['teammateHelp']) : '';
$goodComps     = isset($data['goodComps'])     ? json_encode($data['goodComps'])     : '[]';
$dangerousComps = isset($data['dangerousComps']) ? json_encode($data['dangerousComps']) : '[]';
$isPublic      = isset($data['isPublic'])      ? (int)$data['isPublic']      : 0;
$userID        = (int)$_SESSION['userData']['userID'];
if (!$heroID || !$counterTips) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}
$conn = new mysqli("localhost", "root", "", "athena_db");
$stmt = $conn->prepare("INSERT INTO counter_notes (heroID, userID, counteredByHeroID, severity, counterTips, teammateHelp, goodComps, dangerousComps, isPublic) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("iiiissssi", $heroID, $userID, $counteredByHeroID, $severity, $counterTips, $teammateHelp, $goodComps, $dangerousComps, $isPublic);
$stmt->execute();
echo json_encode(["success" => true]);
$conn->close();
