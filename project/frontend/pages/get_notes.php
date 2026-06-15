<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['loggedIn']) || !$_SESSION['loggedIn']) {
    echo json_encode([]);
    exit;
}
$conn = new mysqli("localhost", "root", "", "athena_db");
$heroID = isset($_GET['heroID']) ? (int)$_GET['heroID'] : 0;
$userID = (int)$_SESSION['userData']['userID'];
$stmt = $conn->prepare("SELECT noteID, heroID, counteredByHeroID, counterTips, teammateHelp, goodComps, dangerousComps, severity, isPublic, createdAt FROM counter_notes WHERE heroID=? AND userID=? ORDER BY createdAt DESC");
$stmt->bind_param("ii", $heroID, $userID);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode($rows);
$conn->close();
