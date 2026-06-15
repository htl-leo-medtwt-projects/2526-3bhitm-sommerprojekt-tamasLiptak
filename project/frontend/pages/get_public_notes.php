<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "athena_db");
$heroID = isset($_GET['heroID']) ? (int)$_GET['heroID'] : 0;
if (!$heroID) {
    echo json_encode([]);
    exit;
}
$stmt = $conn->prepare("
    SELECT cn.*, u.username,
           h.name AS counteredByName, h.portrait AS counteredByPortrait, h.role AS counteredByRole
    FROM counter_notes cn
    JOIN users u ON u.userID = cn.userID
    LEFT JOIN heroes h ON h.heroID = cn.counteredByHeroID
    WHERE cn.heroID = ? AND cn.isPublic = 1
    ORDER BY cn.createdAt DESC
");
$stmt->bind_param("i", $heroID);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as &$row) {
    $row['goodComps']      = json_decode($row['goodComps']      ?? '[]', true);
    $row['dangerousComps'] = json_decode($row['dangerousComps'] ?? '[]', true);
}
echo json_encode($rows);
$conn->close();
