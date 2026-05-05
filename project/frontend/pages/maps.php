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
    <title>Athena-Log | Maps</title>
    <link rel="stylesheet" href="./../style.css">
    <script src="./maps_script.js" defer></script>
</head>

<body>
    <div id="background"></div>
    <nav class="nav">
        <div class="navLinks">
            <a href="./heroes.php" class="navItem">HEROES</a>
            <a href="./maps.php" class="navItem" style="color: #00d2ff;">MAPS</a>
            <a href="./counters.php" class="navItem">COUNTERS</a>
        </div>
        <div class="playButton">
            <div class="playContent">
                <a href="./../index.php">Athena Log</a>
            </div>
        </div>
        <div class="userNav">
            <a href="./account.php" class="navItem"><?php echo htmlspecialchars($navUsername); ?></a>
            <div class="userPfp" style="background-image: url('<?php echo $navPfp ?>'); background-size: cover;"></div>
        </div>
    </nav>

    <div class="mapsContainer">
        <div class="mapSidebar">
            <div class="gamemodeList" id="gamemodeList"></div>
            <div class="mapGridScroll" id="mapGrid"></div>
        </div>
        <div class="mapDisplay" id="mapBigImage">
            <div class="mapInfoCard">
                <h1 id="mapName" class="mapNameLarge">Select Map</h1>
                <p id="mapLocation" class="mapLocation"></p>
                <p id="mapModes" class="mapModes"></p>
            </div>
        </div>
    </div>

</body>

</html>