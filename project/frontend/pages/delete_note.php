<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['loggedIn']) || !$_SESSION['loggedIn']) { echo json_encode(["error"=>"Not logged in"]); exit; }
$data   = json_decode(file_get_contents('php://input'), true);
$noteID = isset($data['noteID']) ? (int)$data['noteID'] : 0;
$userID = (int)$_SESSION['userData']['userID'];
$conn   = new mysqli("localhost","root","","athena_db");
$stmt   = $conn->prepare("DELETE FROM counter_notes WHERE noteID=? AND userID=?");
$stmt->bind_param("ii", $noteID, $userID);
$stmt->execute();
echo json_encode(["success" => $stmt->affected_rows > 0]);
$conn->close();
?>