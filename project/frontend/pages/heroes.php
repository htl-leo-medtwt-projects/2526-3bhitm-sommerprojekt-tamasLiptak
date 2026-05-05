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
    <title>Athena-Log | Heroes</title>
    <link rel="stylesheet" href="./../style.css">
    <script src="./heroes_script.js" defer></script>
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
            <a href="./account.php" class="navItem"><?php echo htmlspecialchars($navUsername); ?></a>
            <div class="userPfp" style="background-image: url('<?php echo $navPfp ?>'); background-size: cover;"></div>
        </div>
    </nav>

    <div class="heroesContainer">
        <div class="heroSidebar">
            <div class="heroGridScroll" id="heroListContainer">
            </div>

            <div class="abilitySection">
                <h3 style="font-size: 1rem;">Abilities</h3>
                <div class="abilityGrid" id="abilityContainer">
                </div>
            </div>
        </div>

        <div class="heroDisplay">
            <img src="" id="heroBigImage" alt="">

            <div class="heroInfoCard">
                <h1 id="selectedHeroName" class="heroNameLarge">Select Hero</h1>
                <p id="selectedHeroDesc" class="heroDesc">Click a hero portrait to view details.</p>

                <div class="mapSuggestions">
                    <div class="mapBox"></div>
                    <div class="mapBox"></div>
                    <div class="mapBox"></div>
                </div><br>
                <span style="font-family: Pulse; font-size: 0.8rem; color: #00d2ff;">← Top 3 Maps</span>
            </div>
        </div>
    </div>
</body>

</html>