<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["error" => "Verbindung fehlgeschlagen"]);
    exit;
}

$query = "SELECT name, location, gamemode, screenshot FROM maps";
$result = $conn->query($query);

$maps = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $gamemodesArray = !empty($row['gamemode']) ? array_map('trim', explode(',', $row['gamemode'])) : [];
        
        $maps[] = [
            "name" => $row['name'],
            "location" => $row['location'],
            "gamemodes" => $gamemodesArray,
            "screenshot" => $row['screenshot'] 
        ];
    }
}

echo json_encode($maps);

$conn->close();
?>