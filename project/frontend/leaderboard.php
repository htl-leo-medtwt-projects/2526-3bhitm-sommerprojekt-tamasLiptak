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
    $summaryUrl = "https://overfast-api.tekrop.fr/players/{$playerId}/summary";
    $summaryJson = @file_get_contents($summaryUrl);
    $summary = $summaryJson ? json_decode($summaryJson, true) : null;

    // --- Career stats: quickplay hero time ---
    $statsUrl = "https://overfast-api.tekrop.fr/players/{$playerId}/stats/career?gamemode=quickplay";
    $statsJson = @file_get_contents($statsUrl);
    $stats = $statsJson ? json_decode($statsJson, true) : null;

    // Find most-played hero by time_played_video_game
    $mostPlayedHero = null;
    $maxTime = 0;

    if ($stats && isset($stats['heroes'])) {
        foreach ($stats['heroes'] as $heroData) {
            // Skip 'all-heroes' aggregate
            if (!isset($heroData['hero']) || $heroData['hero'] === 'all-heroes') continue;

            $timePlayed = 0;
            // Navigate: heroData -> stats -> game -> time_played_video_game (seconds)
            if (isset($heroData['stats']['game']['time_played_video_game'])) {
                $timePlayed = $heroData['stats']['game']['time_played_video_game'];
            }
            if ($timePlayed > $maxTime) {
                $maxTime = $timePlayed;
                $mostPlayedHero = $heroData['hero'];
            }
        }
    }

    // Build competitive rank: find the highest division across roles
    $competitive = $summary['competitive']['pc'] ?? null;
    $rankInfo = null;
    $divisionOrder = ['Grandmaster', 'Master', 'Diamond', 'Platinum', 'Gold', 'Silver', 'Bronze'];

    if ($competitive) {
        $roles = ['tank', 'damage', 'support', 'open'];
        foreach ($divisionOrder as $division) {
            foreach ($roles as $role) {
                if (isset($competitive[$role]) && $competitive[$role] !== null) {
                    $tier = $competitive[$role]['division'] ?? '';
                    if (stripos($tier, $division) !== false || $tier === $division) {
                        $rankInfo = $competitive[$role];
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
                    $rankInfo = $competitive[$role];
                    $rankInfo['role'] = $role;
                    break;
                }
            }
        }
    }

    // Endorsement
    $endorsement = $summary['endorsement'] ?? null;

    // Hero portrait from OverFast heroes endpoint (we cache key->portrait mapping via inline call)
    $heroPortrait = null;
    if ($mostPlayedHero) {
        $heroUrl = "https://overfast-api.tekrop.fr/heroes/{$mostPlayedHero}";
        $heroJson = @file_get_contents($heroUrl);
        $heroDetail = $heroJson ? json_decode($heroJson, true) : null;
        $heroPortrait = $heroDetail['portrait'] ?? null;
        $heroName = $heroDetail['name'] ?? ucfirst($mostPlayedHero);
    }

    // Build all-hero stats for tooltip (eliminations, deaths, wins etc from all-heroes)
    $allHeroStats = null;
    if ($stats && isset($stats['heroes'])) {
        foreach ($stats['heroes'] as $heroData) {
            if (($heroData['hero'] ?? '') === 'all-heroes') {
                $allHeroStats = $heroData['stats'] ?? null;
                break;
            }
        }
    }

    $players[] = [
        'username'        => $row['username'],
        'battletag'       => $row['battletag'],
        'endorsement'     => $endorsement,
        'rank'            => $rankInfo,
        'mostPlayedHero'  => $mostPlayedHero ?? null,
        'heroName'        => $heroName ?? null,
        'heroPortrait'    => $heroPortrait,
        'heroTimeSecs'    => $maxTime,
        'allStats'        => $allHeroStats,
        'profilePrivate'  => ($summary === null),
    ];
}

$conn->close();
echo json_encode($players);
