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
    <title>Athena-Log</title>
    <link rel="stylesheet" href="style.css">
    <script src="./script.js" defer></script>
</head>

<body>
    <div id="background">

    </div>

    <nav class="nav">
        <div class="navLinks">
            <a href="#" class="navItem">HEROES</a>
            <a href="#" class="navItem">MAPS</a>
            <a href="#" class="navItem">COUNTERS</a>
        </div>

        <div class="playButton">
            <div class="playContent">
                <a href="index.php">Athena Log</a>
            </div>
        </div>

        <div class="userNav">
            <a href="./pages/account.php" class="navItem">
                <?php echo htmlspecialchars($navUsername); ?>
            </a>
            <div class="userPfp" style="
            background-image: url('<?php echo substr($navPfp, 3) ?>');
            background-size: cover; 
            background-position: center;"></div>
        </div>
    </nav>

    <div class="intro">
        <h1>Welcome guest!</h1><br>
        <p>Athena-Log is your ultimate interactive companion for mastering
            Overwatch, designed to feel like a natural extension of the game
            itself. <br><br> It moves beyond static wikis by offering a
            personalized
            tactical dashboard where you can manage your mains, track your
            personal skin collection, and save custom strategy notes
            directly to your profile.
        </p>
        <p></p>
    </div>

    <div class="topList">
        <h2 class="listTitle">Top 5 Best Players</h2>
        <div class="playerRows" id="leaderboardContainer">
            <div class="playerItem">
            </div>
            <div class="playerItem">
            </div>
            <div class="playerItem">
            </div>
            <div class="playerItem">
            </div>
            <div class="playerItem">
            </div>
        </div>
    </div>

    <div class="thisX">
        <h1>This Week's Favourites</h1>
        <div class="thisXgrid">
            <div class="gridItem">Fav Map</div>
            <div class="gridItem">Fav Hero</div>
            <div class="gridItem">Fav Skin</div>
        </div>
    </div>
</body>

</html>