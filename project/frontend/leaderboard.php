<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch all users with a battletag
$stmt = $conn->prepare("SELECT username, battletag FROM users WHERE battletag IS NOT NULL AND battletag != '' ORDER BY userID ASC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();

$players = [];
while ($row = $result->fetch_assoc()) {
    $playerId = str_replace('#', '-', $row['battletag']);

    // --- Summary (rank, endorsement) ---
    $summaryUrl  = "https://overfast-api.tekrop.fr/players/{$playerId}/summary";
    $summaryJson = @file_get_contents($summaryUrl);
    $summary     = $summaryJson ? json_decode($summaryJson, true) : null;

    // --- Career stats ---
    $statsUrl  = "https://overfast-api.tekrop.fr/players/{$playerId}/stats/career?gamemode=quickplay";
    $statsJson = @file_get_contents($statsUrl);
    $stats     = $statsJson ? json_decode($statsJson, true) : null;

    // Parse heroes: $stats is keyed directly by hero name at top level
    $mostPlayedHero = null;
    $maxTime        = 0;
    $allHeroStats   = null;
    $heroName       = null;
    $heroPortrait   = null;

    if ($stats) {
        $allHeroStats = $stats['all-heroes'] ?? null;

        foreach ($stats as $heroKey => $heroData) {
            if ($heroKey === 'all-heroes') continue;
            $timePlayed = $heroData['game']['time_played'] ?? 0;
            if ($timePlayed > $maxTime) {
                $maxTime        = $timePlayed;
                $mostPlayedHero = $heroKey;
            }
        }
    }

    // Fetch hero portrait + display name
    if ($mostPlayedHero) {
        $heroUrl    = "https://overfast-api.tekrop.fr/heroes/{$mostPlayedHero}";
        $heroJson   = @file_get_contents($heroUrl);
        $heroDetail = $heroJson ? json_decode($heroJson, true) : null;
        $heroPortrait = $heroDetail['portrait'] ?? null;
        $heroName     = $heroDetail['name']     ?? ucfirst($mostPlayedHero);
    }

    // Build competitive rank: find the highest division across roles
    $competitive   = $summary['competitive']['pc'] ?? null;
    $rankInfo      = null;
    $divisionOrder = ['Grandmaster', 'Master', 'Diamond', 'Platinum', 'Gold', 'Silver', 'Bronze'];

    if ($competitive) {
        $roles = ['tank', 'damage', 'support', 'open'];
        foreach ($divisionOrder as $division) {
            foreach ($roles as $role) {
                if (isset($competitive[$role]) && $competitive[$role] !== null) {
                    $tier = $competitive[$role]['division'] ?? '';
                    if (stripos($tier, $division) !== false) {
                        $rankInfo         = $competitive[$role];
                        $rankInfo['role'] = $role;
                        break 2;
                    }
                }
            }
        }
        // Fallback: just grab the first non-null role
        if (!$rankInfo) {
            foreach ($roles as $role) {
                if (isset($competitive[$role]) && $competitive[$role] !== null) {
                    $rankInfo         = $competitive[$role];
                    $rankInfo['role'] = $role;
                    break;
                }
            }
        }
    }

    $players[] = [
        'username'       => $row['username'],
        'battletag'      => $row['battletag'],
        'endorsement'    => $summary['endorsement'] ?? null,
        'rank'           => $rankInfo,
        'mostPlayedHero' => $mostPlayedHero,
        'heroName'       => $heroName,
        'heroPortrait'   => $heroPortrait,
        'heroTimeSecs'   => $maxTime,
        'allStats'       => $allHeroStats,
        'profilePrivate' => ($summary === null),
    ];
}

$conn->close();
echo json_encode($players);
