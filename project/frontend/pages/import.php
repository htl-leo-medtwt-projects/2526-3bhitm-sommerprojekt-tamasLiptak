<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$apiUrl = "https://overfast-api.tekrop.fr/maps";

$options = array("http" => array("header" => "User-Agent: Mozilla/5.0\r\n"));
$context = stream_context_create($options);
$response = file_get_contents($apiUrl, false, $context);

if ($response === FALSE) {
    die("Error at API Call.");
}

$maps = json_decode($response, true);

$stmt = $conn->prepare("INSERT IGNORE INTO maps (name, location, gamemode) VALUES (?, ?, ?)");
if ($stmt === FALSE) {
    die("Error at Prepared Statement: " . $conn->error);
}

$insertedCount = 0;

foreach ($maps as $map) {
    $name = $map['name'];
    $location = $map['location'] ?? ''; 
    $gamemode = isset($map['gamemodes']) ? implode(', ', $map['gamemodes']) : '';

    $stmt->bind_param("sss", $name, $location, $gamemode);
    
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
$conn->close();
?>