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

$heroID = isset($_GET['heroID']) ? (int)$_GET['heroID'] : 0;

if ($heroID > 0) {
    $query = "
        SELECT
            c.counterID,
            c.heroID,
            c.counteredByHeroID,
            h_counter.name        AS counteredByName,
            h_counter.portrait    AS counteredByPortrait,
            h_counter.role        AS counteredByRole,
            c.counterTips,
            c.teammateHelp,
            c.goodComps,
            c.dangerousComps,
            c.severity
        FROM counters c
        JOIN heroes h_counter ON h_counter.heroID = c.counteredByHeroID
        WHERE c.heroID = ?
        ORDER BY c.severity DESC, h_counter.name ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $heroID);
    $stmt->execute();
    $result = $stmt->get_result();

    $counters = [];
    while ($row = $result->fetch_assoc()) {
        $row['goodComps']      = !empty($row['goodComps'])      ? json_decode($row['goodComps'],      true) : [];
        $row['dangerousComps'] = !empty($row['dangerousComps']) ? json_decode($row['dangerousComps'], true) : [];
        $counters[] = $row;
    }

    echo json_encode($counters);
    $stmt->close();

} else {
    $result = $conn->query("SELECT * FROM counters ORDER BY heroID ASC, severity DESC");
    $all = [];
    while ($row = $result->fetch_assoc()) {
        $row['goodComps']      = !empty($row['goodComps'])      ? json_decode($row['goodComps'],      true) : [];
        $row['dangerousComps'] = !empty($row['dangerousComps']) ? json_decode($row['dangerousComps'], true) : [];
        $all[] = $row;
    }
    echo json_encode($all);
}

$conn->close();
?>
