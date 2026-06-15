<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

$mode = $_GET['mode'] ?? 'structure';
$hero = $_GET['hero'] ?? '';

$rarityOrder = ['Mythic' => 1, 'Legendary' => 2, 'Epic' => 3, 'Rare' => 4, 'Common' => 5, 'Unknown' => 6];

// ── Mode: structure — returns distinct source+rarity combos ──────────────────
if ($mode === 'structure') {
    if ($hero) {
        $stmt = $conn->prepare("
            SELECT source, rarity, COUNT(*) as count
            FROM skins
            WHERE hero_name = ?
            GROUP BY source, rarity
            ORDER BY source ASC
        ");
        $stmt->bind_param("s", $hero);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query("
            SELECT source, rarity, COUNT(*) as count
            FROM skins
            GROUP BY source, rarity
            ORDER BY source ASC
        ");
    }

    $structure = [];
    while ($row = $result->fetch_assoc()) {
        $src    = $row['source'];
        $rarity = $row['rarity'];
        if (!isset($structure[$src])) $structure[$src] = [];
        $structure[$src][$rarity] = (int)$row['count'];
    }

    foreach ($structure as &$rarities) {
        uksort($rarities, fn($a, $b) =>
            ($rarityOrder[$a] ?? 99) <=> ($rarityOrder[$b] ?? 99)
        );
    }

    echo json_encode($structure);
    $conn->close();
    exit;
}

// ── Mode: skins — returns skins for a hero + source + rarity ────────────────
if ($mode === 'skins') {
    $source = $_GET['source'] ?? '';
    $rarity = $_GET['rarity'] ?? '';

    if (!$source || !$rarity) {
        echo json_encode([]);
        exit;
    }

    if ($hero) {
        $stmt = $conn->prepare("
            SELECT name, rarity, cost, image_url
            FROM skins
            WHERE hero_name = ? AND source = ? AND rarity = ?
            ORDER BY name ASC
        ");
        $stmt->bind_param("sss", $hero, $source, $rarity);
    } else {
        $stmt = $conn->prepare("
            SELECT hero_name, name, cost, image_url
            FROM skins
            WHERE source = ? AND rarity = ?
            ORDER BY hero_name ASC, name ASC
        ");
        $stmt->bind_param("ss", $source, $rarity);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $skins = [];
    while ($row = $result->fetch_assoc()) {
        $skins[] = $row;
    }

    $stmt->close();
    echo json_encode($skins);
    $conn->close();
    exit;
}

echo json_encode(["error" => "Invalid mode"]);
$conn->close();
?>