<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

$loggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;
$userData = $loggedIn ? $_SESSION['userData'] : null;

$navUsername = $loggedIn ? $userData['username'] : 'Guest';
$navPfp = ($loggedIn && !empty($userData['profilepicture']))
    ? $userData['profilepicture']
    : './media/profilepictures/default/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athena-Log | Counters</title>
    <link rel="stylesheet" href="./../style.css">
</head>
<body>

        <nav class="nav">
        <div class="navLinks">
            <a href="./heroes.php" class="navItem">HEROES</a>
            <a href="./maps.php" class="navItem">MAPS</a>
            <a href="./counters.php" class="navItem">COUNTERS</a>
        </div>
        <div class="playButton">
            <div class="playContent">
                <a href="./../index.php">Athena Log</a>
            </div>
        </div>
        <div class="userNav">
            <a href="#" class="navItem"><?php echo $loggedIn ? $userData['username'] : 'Guest'; ?></a>
            <div class="userPfp" style="
            background-image: url('<?php echo $navPfp ?>');
            background-size: cover; 
            background-position: center;"></div>
        </div>
    </nav>
    
</body>
</html>