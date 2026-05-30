<?php
// save_counter.php
// Upserts a counter relationship via POST (JSON body).
// Intended for admin/import use — protect with a session/role check in production.

header('Content-Type: application/json');

session_start();
// Uncomment in production:
// if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
//     echo json_encode(["error" => "Unauthorised"]);
//     exit;
// }

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$heroID            = isset($body['heroID'])            ? (int)$body['heroID']            : 0;
$counteredByHeroID = isset($body['counteredByHeroID']) ? (int)$body['counteredByHeroID'] : 0;
$counterTips       = $body['counterTips']       ?? '';
$teammateHelp      = $body['teammateHelp']      ?? '';
$goodComps         = isset($body['goodComps'])         ? json_encode($body['goodComps'])         : '[]';
$dangerousComps    = isset($body['dangerousComps'])    ? json_encode($body['dangerousComps'])    : '[]';
$severity          = isset($body['severity'])          ? (int)$body['severity']                 : 2;

if (!$heroID || !$counteredByHeroID) {
    echo json_encode(["error" => "heroID and counteredByHeroID are required"]);
    exit;
}

// Check if this relationship already exists
$check = $conn->prepare("SELECT counterID FROM counters WHERE heroID = ? AND counteredByHeroID = ?");
$check->bind_param("ii", $heroID, $counteredByHeroID);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->bind_result($existingID);
    $check->fetch();
    $check->close();

    $upd = $conn->prepare("
        UPDATE counters
        SET counterTips = ?, teammateHelp = ?, goodComps = ?, dangerousComps = ?, severity = ?
        WHERE counterID = ?
    ");
    $upd->bind_param("ssssii", $counterTips, $teammateHelp, $goodComps, $dangerousComps, $severity, $existingID);
    $upd->execute();
    echo json_encode(["success" => true, "action" => "updated", "counterID" => $existingID]);
    $upd->close();
} else {
    $check->close();
    $ins = $conn->prepare("
        INSERT INTO counters (heroID, counteredByHeroID, counterTips, teammateHelp, goodComps, dangerousComps, severity)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("iissssi", $heroID, $counteredByHeroID, $counterTips, $teammateHelp, $goodComps, $dangerousComps, $severity);
    $ins->execute();
    echo json_encode(["success" => true, "action" => "inserted", "counterID" => $conn->insert_id]);
    $ins->close();
}

$conn->close();
?>
