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
            <a href="#" class="navItem">HEROES</a>
            <a href="#" class="navItem">MAPS</a>
            <a href="#" class="navItem">COUNTERS</a>
        </div>
        <div class="playButton">
            <div class="playContent">
                <a href="./../index.html">Athena Log</a>
            </div>
        </div>
        <div class="userNav">
            <a href="#" class="navItem"><?php echo $loggedIn ? $userData['username'] : 'Guest'; ?></a>
            <div class="userPfp"></div>
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

        echo
        '<div class="accountContainer" id="accountContainer">' .
            '<header><h1 class="pageTitle">Account Settings</h1></header>' .
            '<div class="settingsGrid">' .
            '<div class="settingsCard">' .
            '<h2 class="cardTitle">Profile Information</h2>' .
            '<div class="userHeader">' .
            '<div class="accountPfp" style="background-image: url(\'' . $currentPfp . '\'); background-size: cover; background-position: center;"></div>' .
            '<div>' .
            '<h2 class="heroUsername">' . strtoupper($userData['username']) . '</h2>' .
            
            '<form action="account.php" method="POST" enctype="multipart/form-data" style="margin-top: 10px;">' .
            '<label class="editPfpButton" style="display: inline-block; cursor: pointer;">' .
            'Change Avatar' .
            '<input type="file" name="fileToUpload" id="fileToUpload" accept="image/*" required style="display: none;" onchange="document.getElementById(\'uploadConfirmBtn\').style.display=\'inline-block\'">' .
            '</label>' .
            '<button type="submit" name="uploadSubmit" id="uploadConfirmBtn" class="editPfpButton" style="display:none; border-color: #00d2ff; color: #00d2ff; margin-left: 10px;">' .
            'Confirm Upload' .
            '</button>' .
            '</form>' .
            
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
            '</div>' .
            '</div>' .
            '<footer><button class="saveButton">Save Changes</button></footer>' .
            '</div>';
    }
    ?>
</body>

</html>