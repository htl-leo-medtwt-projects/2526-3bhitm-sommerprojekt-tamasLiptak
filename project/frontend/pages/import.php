<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Maps
$apiUrl = "https://overfast-api.tekrop.fr/maps";

$options = array("http" => array("header" => "User-Agent: Mozilla/5.0\r\n"));
$context = stream_context_create($options);
$response = file_get_contents($apiUrl, false, $context);

if ($response === FALSE) {
    die("Error at API Call.");
}

$maps = json_decode($response, true);

$stmt = $conn->prepare("INSERT IGNORE INTO maps (name, location, gamemode, screenshot) VALUES (?, ?, ?, ?)");
if ($stmt === FALSE) {
    die("Error at Prepared Statement: " . $conn->error);
}

$insertedCount = 0;

foreach ($maps as $map) {
    $name = $map['name'];
    $location = $map['location'] ?? ''; 
    $gamemode = isset($map['gamemodes']) ? implode(', ', $map['gamemodes']) : '';
    
    $screenshot = $map['screenshot'] ?? '';

    $stmt->bind_param("ssss", $name, $location, $gamemode, $screenshot);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $insertedCount++;
        }
    } else {
        echo "Execute error: " . $stmt->error . "<br>";
    }
}

if ($insertedCount > 0) {
    echo "<script>console.log('Done! " . $insertedCount . " new maps succesfully imported.');</script>";
} else {
    echo "<script>console.log('Database is new. No new maps needed.');</script>";
}

echo "<script>console.log('Done! " . $insertedCount . " maps succesfully imported.');</script>";

$stmt->close();


// Heroes
$heroesApiUrl = "https://overfast-api.tekrop.fr/heroes";
$heroesResponse = file_get_contents($heroesApiUrl, false, $context);

if ($heroesResponse === FALSE) {
    die("Error at Heroes API Call.");
}

$heroesList = json_decode($heroesResponse, true);

$checkStmt = $conn->prepare("SELECT heroID FROM heroes WHERE name = ?");
$insertStmt = $conn->prepare("INSERT INTO heroes (name, role, portrait, description, abilities, screenshot) VALUES (?, ?, ?, ?, ?, ?)");

$heroesInserted = 0;

foreach ($heroesList as $h) {
    $heroKey = $h['key'];
    
    $detailUrl = "https://overfast-api.tekrop.fr/heroes/" . $heroKey;
    $detailResponse = file_get_contents($detailUrl, false, $context);
    
    if ($detailResponse !== FALSE) {
        $detail = json_decode($detailResponse, true);
        
        $name = $detail['name'] ?? $h['name'];
        $role = $detail['role'] ?? $h['role'];
        $portrait = $detail['portrait'] ?? $h['portrait'];
        $description = $detail['description'] ?? '';
        
        $abilityData = [];
        if (!empty($detail['abilities'])) {
            foreach ($detail['abilities'] as $ability) {
                $abilityData[] = [
                    "name" => $ability['name'] ?? '',
                    "icon" => $ability['icon'] ?? ''
                ];
            }
        }
        $abilitiesString = json_encode($abilityData);

        $screenshot = $portrait; 
        if (!empty($detail['backgrounds'])) {
            $preferred = null;
            foreach ($detail['backgrounds'] as $bg) {
                if (in_array('xl+', $bg['sizes'])) { $preferred = $bg['url']; break; }
            }
            if (!$preferred) {
                foreach ($detail['backgrounds'] as $bg) {
                    if (in_array('lg', $bg['sizes'])) { $preferred = $bg['url']; break; }
                }
            }
            $screenshot = $preferred ?? $detail['backgrounds'][0]['url'] ?? $portrait;
        }

        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows == 0) {
            $insertStmt->bind_param("ssssss", $name, $role, $portrait, $description, $abilitiesString, $screenshot);
            if ($insertStmt->execute()) {
                $heroesInserted++;
            }
        }
    }
}

$checkStmt->close();
$insertStmt->close();
$conn->close();

echo "<script>console.log('Done! " . $heroesInserted . " new heroes successfully imported to database.');</script>";
?>