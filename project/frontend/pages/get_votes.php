<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['error' => 'db_error']);
    exit;
}

date_default_timezone_set('Europe/Vienna');
$weekStart = date('Y-m-d', strtotime('monday this week'));

// Top voted hero this week
$heroResult = $conn->query("
    SELECT vote_value, COUNT(*) as votes
    FROM weekly_votes
    WHERE vote_type = 'hero' AND week_start = '$weekStart'
    GROUP BY vote_value
    ORDER BY votes DESC
    LIMIT 1
");
$topHero = $heroResult && $heroResult->num_rows > 0 ? $heroResult->fetch_assoc() : null;

// Top voted map this week
$mapResult = $conn->query("
    SELECT vote_value, COUNT(*) as votes
    FROM weekly_votes
    WHERE vote_type = 'map' AND week_start = '$weekStart'
    GROUP BY vote_value
    ORDER BY votes DESC
    LIMIT 1
");
$topMap = $mapResult && $mapResult->num_rows > 0 ? $mapResult->fetch_assoc() : null;

// Current user's existing votes (so we can highlight them in the UI)
$userVotes = ['hero' => null, 'map' => null];
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    $userID = $_SESSION['userData']['userID'] ?? null;
    if ($userID) {
        $stmt = $conn->prepare("
            SELECT vote_type, vote_value
            FROM weekly_votes
            WHERE userID = ? AND week_start = ?
        ");
        $stmt->bind_param('is', $userID, $weekStart);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $userVotes[$row['vote_type']] = $row['vote_value'];
        }
        $stmt->close();
    }
}

// Fetch hero screenshot for the top hero
$topHeroData = null;
if ($topHero) {
    $heroName = $conn->real_escape_string($topHero['vote_value']);
    $hRes = $conn->query("SELECT name, portrait, screenshot FROM heroes WHERE name = '$heroName' LIMIT 1");
    if ($hRes && $hRes->num_rows > 0) {
        $topHeroData = array_merge($topHero, $hRes->fetch_assoc());
    }
}

// Fetch map screenshot for the top map
$topMapData = null;
if ($topMap) {
    $mapName = $conn->real_escape_string($topMap['vote_value']);
    $mRes = $conn->query("SELECT name, screenshot FROM maps WHERE name = '$mapName' LIMIT 1");
    if ($mRes && $mRes->num_rows > 0) {
        $topMapData = array_merge($topMap, $mRes->fetch_assoc());
    }
}

echo json_encode([
    'week_start'  => $weekStart,
    'top_hero'    => $topHeroData,
    'top_map'     => $topMapData,
    'user_votes'  => $userVotes,
    'logged_in'   => isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true,
]);

$conn->close();
?>
