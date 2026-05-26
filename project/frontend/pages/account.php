<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";

$conn = new mysqli($host, $user, $pass, $db);

$authMessage = "";
$loggedIn = isset($_SESSION['loggedIn']) ? $_SESSION['loggedIn'] : false;
$userData = isset($_SESSION['userData']) ? $_SESSION['userData'] : null;
$authMode = isset($_POST['authMode']) ? $_POST['authMode'] : 'login';
$defaultPfp = './../media/profilepictures/default/default.png';
$navPfp = ($loggedIn && !empty($userData['profilepicture'])) ? $userData['profilepicture'] : $defaultPfp;

// Authentication
if (isset($_POST['authSubmit'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    if ($_POST['authMode'] === 'register') {
        $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $check->bind_param("s", $u);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $authMessage = '<p style="color: #ffcc00; margin-top: 10px; font-family: FuturaDemi;">Username already exists.</p>';
        } else {
            $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert->bind_param("ss", $u, $p);
            $insert->execute();
            $authMessage = '<p style="color: #00ff00; margin-top: 10px; font-family: FuturaDemi;">Account created! Please login.</p>';
            $authMode = 'login';
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $u, $p);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $loggedIn = true;
            $userData = $result->fetch_assoc();
            $_SESSION['loggedIn'] = true;
            $_SESSION['userData'] = $userData;
        } else {
            $authMessage = '<p style="color: #ff4444; margin-top: 10px; font-family: FuturaDemi;">Access Denied: Invalid Credentials</p>';
        }
    }
}

// File upload
if (isset($_POST['uploadSubmit']) && $loggedIn) {
    $targetDir = "./../media/profilepictures/uploads/";

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $targetFile = $targetDir . $fileName;
    $uploadOk = true;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        $authMessage = "File is not an image.";
        $uploadOk = false;
    }

    if ($uploadOk) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            try {
                $stmt = $conn->prepare("UPDATE users SET profilepicture = ? WHERE userID = ?");
                $stmt->bind_param("si", $targetFile, $_SESSION['userData']['userID']);

                if ($stmt->execute()) {
                    $_SESSION['userData']['profilepicture'] = $targetFile;
                    $userData['profilepicture'] = $targetFile;
                    $authMessage = '<p style="color: #00ff00;">Avatar saved to database!</p>';
                }
            } catch (mysqli_sql_exception $e) {
                $authMessage = '<p style="color: #ff4444;">DB Error: ' . $e->getMessage() . '</p>';
            }
        } else {
            $authMessage = '<p style="color: #ff4444;">Permission Error: Could not move file to uploads folder.</p>';
        }
    }
}

// Update Preferences
if (isset($_POST['savePreferencesSubmit']) && $loggedIn) {
    $btag = trim($_POST['battletagInput']);
    $userId = $_SESSION['userData']['userID'];

    // Validation
    if (!empty($btag) && (!preg_match('/^[^#]{3,12}#[0-9]{1,5}$/', $btag) || strlen($btag) > 18)) {
        $btagErrorMessage = '<p style="color: #ff4444; font-size: 14px; margin-top: 5px; font-family: FuturaDemi;">Invalid Tag. Name must be 3-12 chars, followed by # and up to 5 digits (Max 18 total).</p>';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET battletag = ? WHERE userID = ?");
            $stmt->bind_param("si", $btag, $userId);

            if ($stmt->execute()) {
                $_SESSION['userData']['battletag'] = $btag;
                $userData['battletag'] = $btag;
                $authMessage = '<p style="color: #00ff00;">Preferences updated successfully!</p>';
            }
        } catch (mysqli_sql_exception $e) {
            $authMessage = '<p style="color: #ff4444;">DB Error: ' . $e->getMessage() . '</p>';
        }
    }
}

// Logout
if (isset($_POST['logoutSubmit'])) {
    $_SESSION = array();
    if (ini_get("session_use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header("Location: account.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athena-Log | Account</title>
    <link rel="stylesheet" href="./../style.css">
</head>

<body>
    <div id="background" style="background-image: url(./../media/backgrounds/account/ow1.jpg);"></div>

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

    <?php
    if (!$loggedIn) {
        $title = ($authMode === 'login') ? 'Athena Login' : 'Create Account';
        $buttonText = ($authMode === 'login') ? 'Initialize Log' : 'Register Account';
        $toggleLink = ($authMode === 'login') ? 'Need an account? Register' : 'Already have an account? Login';
        $nextMode = ($authMode === 'login') ? 'register' : 'login';

        echo
        '<div id="loginModal" class="modalOverlay">' .
            '<div class="loginCard">' .
            '<h2 class="cardTitle">' . $title . '</h2>' .
            '<form action="account.php" method="POST">' .
            '<input type="hidden" name="authMode" value="' . $authMode . '">' .
            '<div class="inputGroup">' .
            '<h3>Username</h3>' .
            '<input type="text" name="username" required>' .
            '</div>' .
            '<div class="inputGroup">' .
            '<h3>Password</h3>' .
            '<input type="password" name="password" required>' .
            '</div>' .
            '<button type="submit" name="authSubmit" class="saveButton" style="width: 100%;">' . $buttonText . '</button>' .
            $authMessage .
            '</form>' .
            '<form action="account.php" method="POST" style="margin-top: 15px; text-align: center;">' .
            '<input type="hidden" name="authMode" value="' . $nextMode . '">' .
            '<button type="submit" style="background: none; border: none; color: #00d2ff; cursor: pointer; font-family: FuturaDemi; text-decoration: underline;">' .
            $toggleLink .
            '</button>' .
            '</form>' .
            '</div>' .
            '</div>';
    }

    if ($loggedIn) {
        $currentPfp = !empty($userData['profilepicture']) ? $userData['profilepicture'] : './../media/profilepictures/default/default.png';
        $currentBtag = $userData['battletag'] ?? '';

        $btagErrorHTML = $btagErrorMessage ?? '';

        echo
        '<div class="accountContainer" id="accountContainer">' .
            '<header><h1 class="pageTitle">Account Settings</h1></header>' .

            '<form action="account.php" method="POST">' .

            '<div class="settingsGrid">' .
            '<div class="settingsCard">' .
            '<h2 class="cardTitle">Profile Information</h2>' .
            '<div class="userHeader">' .
            '<div class="accountPfp" style="background-image: url(\'' . $currentPfp . '\'); background-size: cover; background-position: center;"></div>' .
            '<div>' .
            '<h2 class="heroUsername">' . strtoupper($userData['username']) . '</h2>' .
            '</div>' .
            '</div>' .
            '<div class="inputGroup">' .
            '<label>Username</label>' .
            '<input type="text" value="' . $userData['username'] . '" readonly class="readonlyInput">' .
            '</div>' .
            '<div class="inputGroup">' .
            '<label>Email Address</label>' .
            '<input type="email" value="' . ($userData['email'] ?? 'Not set') . '" readonly class="readonlyInput">' .
            '</div>' .
            '<div class="inputGroup">' .
            '<label for="profileBio">Notes</label>' .
            '<textarea id="profileBio" rows="3" placeholder="Put your notes here..."></textarea>' .
            '</div>' .
            '</div>' .

            '<div class="settingsCard">' .
            '<h2 class="cardTitle">Preferences</h2>' .
            '<div class="inputGroup">' .
            '<label for="favoriteRole">Favorite Role</label>' .
            '<select id="favoriteRole"><option value="tank">Tank</option><option value="damage">Damage</option><option value="support">Support</option></select>' .
            '</div>' .
            '<div class="inputGroup">' .
            '<label for="regionSelect">Region</label>' .
            '<select id="regionSelect"><option value="eu">Europe</option><option value="us">Americas</option><option value="as">Asia</option></select>' .
            '</div>' .
            '<div class="inputGroup">' .
            '<label>Battle.net Tag</label>' .
            '<input type="text" placeholder="Battletag#1234" id="battletagInput" name="battletagInput" maxlength="18" value="' . htmlspecialchars($currentBtag) . '">' .
            $btagErrorHTML .
            '</div>' .
            $authMessage .
            '</div>' .
            '</div>' .

            '<footer style="display: flex; gap: 15px; align-items: center;">' .
            '<button type="submit" name="savePreferencesSubmit" class="saveButton">Save Changes</button>' .
            '<button type="submit" name="logoutSubmit" class="logoutButton">LOG OUT</button>' .
            '</footer>' .

            '</form>' .
            '</div>';
    }
    ?>
</body>

</html>