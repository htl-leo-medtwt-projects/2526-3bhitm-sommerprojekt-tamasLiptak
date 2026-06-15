<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Best maps per hero (OW2, matched to common map pool)
// Map names must exactly match what is stored in your maps table
$heroBestMaps = [
    // TANK
    "D.Va"          => ["Busan", "Ilios", "Lijiang Tower"],
    "Doomfist"      => ["Eichenwalde", "King's Row", "Midtown"],
    "Hazard"        => ["Colosseo", "Esperança", "New Junk City"],
    "Junker Queen"  => ["Junkertown", "Shambali Monastery", "Samoa"],
    "Mauga"         => ["Paraíso", "Midtown", "Havana"],
    "Orisa"         => ["Colosseo", "Esperança", "Rialto"],
    "Ramattra"      => ["Shambali Monastery", "Eichenwalde", "King's Row"],
    "Reinhardt"     => ["King's Row", "Eichenwalde", "Midtown"],
    "Roadhog"       => ["Junkertown", "Havana", "Circuit Royal"],
    "Sigma"         => ["Colosseo", "Esperança", "Ilios"],
    "Winston"       => ["Busan", "Lijiang Tower", "Nepal"],
    "Wrecking Ball" => ["Busan", "Lijiang Tower", "Samoa"],
    "Zarya"         => ["King's Row", "Eichenwalde", "Havana"],

    // DAMAGE
    "Ashe"          => ["Circuit Royal", "Havana", "Junkertown"],
    "Bastion"       => ["King's Row", "Eichenwalde", "Midtown"],
    "Cassidy"       => ["Havana", "Circuit Royal", "Rialto"],
    "Echo"          => ["Busan", "Ilios", "Lijiang Tower"],
    "Genji"         => ["Hanamura", "Busan", "Lijiang Tower"],
    "Hanzo"         => ["Hanamura", "King's Row", "Eichenwalde"],
    "Junkrat"       => ["Junkertown", "King's Row", "Rialto"],
    "Mei"           => ["Busan", "Nepal", "Ilios"],
    "Pharah"        => ["Paraíso", "Rialto", "Circuit Royal"],
    "Reaper"        => ["King's Row", "Eichenwalde", "Numbani"],
    "Sojourn"       => ["Circuit Royal", "Midtown", "Havana"],
    "Soldier: 76"   => ["Havana", "Circuit Royal", "Rialto"],
    "Sombra"        => ["Busan", "Numbani", "Paraíso"],
    "Symmetra"      => ["Hanamura", "King's Row", "Eichenwalde"],
    "Torbjörn"      => ["King's Row", "Eichenwalde", "Hanamura"],
    "Tracer"        => ["King's Row", "Numbani", "Busan"],
    "Venture"       => ["Colosseo", "Esperança", "New Junk City"],
    "Widowmaker"    => ["Circuit Royal", "Havana", "Rialto"],

    // SUPPORT
    "Ana"           => ["King's Row", "Eichenwalde", "Hanamura"],
    "Baptiste"      => ["Rialto", "Havana", "Circuit Royal"],
    "Brigitte"      => ["Eichenwalde", "King's Row", "Midtown"],
    "Illari"        => ["Ilios", "Busan", "Nepal"],
    "Juno"          => ["Ilios", "Lijiang Tower", "Busan"],
    "Kiriko"        => ["Busan", "Hanamura", "Lijiang Tower"],
    "Lifeweaver"    => ["Rialto", "Havana", "Colosseo"],
    "Lucio"         => ["Busan", "Lijiang Tower", "Ilios"],
    "Mercy"         => ["Circuit Royal", "Rialto", "Havana"],
    "Moira"         => ["King's Row", "Eichenwalde", "Numbani"],
    "Zenyatta"      => ["King's Row", "Hanamura", "Ilios"],

    // Newer / seasonal heroes (add as they release)
    "Freja"         => ["Circuit Royal", "Havana", "Rialto"],
];

$updated = 0;
$notFound = [];

foreach ($heroBestMaps as $heroName => $maps) {
    $mapsJson = $conn->real_escape_string(json_encode($maps));
    $heroNameEscaped = $conn->real_escape_string($heroName);

    $check = $conn->query("SELECT heroID FROM heroes WHERE name = '$heroNameEscaped'");

    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE heroes SET best_maps = '$mapsJson' WHERE name = '$heroNameEscaped'");
        $updated++;
    } else {
        $notFound[] = $heroName;
    }
}

$conn->close();

echo "<pre>";
echo "✅ Updated: $updated heroes\n\n";

if (!empty($notFound)) {
    echo "⚠️  The following names were NOT found in your heroes table:\n";
    foreach ($notFound as $name) {
        echo "  - $name\n";
    }
    echo "\nCheck that the name in \$heroBestMaps exactly matches the name column in your DB.\n";
} else {
    echo "✅ All heroes matched successfully.\n";
}

echo "</pre>";
?>
