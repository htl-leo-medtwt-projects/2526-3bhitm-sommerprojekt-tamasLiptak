<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$jsonFile = __DIR__ . '/overwatch_heroes_skins_hq.json';

if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile");
}

$data = json_decode(file_get_contents($jsonFile), true);

if (!$data) {
    die("Failed to parse JSON.");
}

$stmt = $conn->prepare("
    INSERT IGNORE INTO skins (hero_name, name, rarity, cost, source, image_url)
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$inserted = 0;
$skipped  = 0;

$validRarities = ['Common', 'Rare', 'Epic', 'Legendary', 'Mythic', 'Unknown'];

foreach ($data as $heroName => $skins) {
    foreach ($skins as $skin) {
        $name     = trim($skin['name']      ?? '');
        $rarity   = trim($skin['rarity']    ?? 'Unknown');
        $cost     = trim($skin['cost']      ?? 'Unknown');
        $source   = trim($skin['source']    ?? 'Base Game');
        $imageUrl = trim($skin['image_url'] ?? '');

        if (!$name || !$imageUrl) {
            $skipped++;
            continue;
        }

        if (!in_array($rarity, $validRarities)) {
            $rarity = 'Unknown';
        }

        $stmt->bind_param("ssssss", $heroName, $name, $rarity, $cost, $source, $imageUrl);
        $stmt->execute();

        if ($conn->affected_rows > 0) {
            $inserted++;
        } else {
            $skipped++;
        }
    }
}

$stmt->close();
$conn->close();

echo "<script>console.log('Done! $inserted skins imported, $skipped skipped.');</script>";
echo "Done! <strong>$inserted</strong> skins imported, <strong>$skipped</strong> skipped.";
?>