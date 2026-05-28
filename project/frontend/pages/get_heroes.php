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

$query = "SELECT heroID, name, role, portrait, description, abilities, screenshot FROM heroes ORDER BY name ASC";
$result = $conn->query($query);

$heroes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $abilitiesArray = !empty($row['abilities']) ? json_decode($row['abilities'], true) : [];
        
        $heroes[] = [
            "heroID" => $row['heroID'],
            "name" => $row['name'],
            "role" => $row['role'],
            "portrait" => $row['portrait'],
            "description" => $row['description'],
            "abilities" => $abilitiesArray,
            "screenshot" => $row['screenshot'] 
        ];
    }
}

echo json_encode($heroes);

$conn->close();
?>