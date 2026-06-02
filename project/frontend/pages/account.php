<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";

$conn = new mysqli($host, $user, $pass, $db);

$authMessage    = "";
$btagErrorMessage = "";
$loggedIn       = isset($_SESSION['loggedIn']) ? $_SESSION['loggedIn'] : false;
$userData       = isset($_SESSION['userData']) ? $_SESSION['userData'] : null;
$authMode       = isset($_POST['authMode']) ? $_POST['authMode'] : 'login';
$defaultPfp     = './../media/profilepictures/default/default.png';
$navPfp         = ($loggedIn && !empty($userData['profilepicture'])) ? $userData['profilepicture'] : $defaultPfp;

// Authentication
if (isset($_POST['authSubmit'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    if ($_POST['authMode'] === 'register') {
        $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $check->bind_param("s", $u);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $authMessage = '<p class="authMsg authMsg--warn">Username already exists.</p>';
        } else {
            $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert->bind_param("ss", $u, $p);
            $insert->execute();
            $authMessage = '<p class="authMsg authMsg--success">Account created! Please login.</p>';
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
            $navPfp = !empty($userData['profilepicture']) ? $userData['profilepicture'] : $defaultPfp;
        } else {
            $authMessage = '<p class="authMsg authMsg--error">Access Denied: Invalid Credentials</p>';
        }
    }
}

// File upload
if (isset($_POST['uploadSubmit']) && $loggedIn) {
    $targetDir = "./../media/profilepictures/uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName  = basename($_FILES["fileToUpload"]["name"]);
    $targetFile = $targetDir . $fileName;
    $uploadOk  = true;

    if (getimagesize($_FILES["fileToUpload"]["tmp_name"]) === false) {
        $authMessage = '<p class="authMsg authMsg--error">File is not an image.</p>';
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
                    $navPfp = $targetFile;
                    $authMessage = '<p class="authMsg authMsg--success">Avatar updated!</p>';
                }
            } catch (mysqli_sql_exception $e) {
                $authMessage = '<p class="authMsg authMsg--error">DB Error: ' . $e->getMessage() . '</p>';
            }
        } else {
            $authMessage = '<p class="authMsg authMsg--error">Permission Error: Could not move file.</p>';
        }
    }
}

// Update Preferences
if (isset($_POST['savePreferencesSubmit']) && $loggedIn) {
    $btag   = trim($_POST['battletagInput']);
    $userId = $_SESSION['userData']['userID'];

    if (!empty($btag) && (!preg_match('/^[^#]{3,12}#[0-9]{1,5}$/', $btag) || strlen($btag) > 18)) {
        $btagErrorMessage = '<p class="authMsg authMsg--error">Invalid Tag. Name must be 3-12 chars, followed by # and up to 5 digits (Max 18 total).</p>';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET battletag = ? WHERE userID = ?");
            $stmt->bind_param("si", $btag, $userId);
            if ($stmt->execute()) {
                $_SESSION['userData']['battletag'] = $btag;
                $userData['battletag'] = $btag;
                $authMessage = '<p class="authMsg authMsg--success">Preferences updated successfully!</p>';
            }
        } catch (mysqli_sql_exception $e) {
            $authMessage = '<p class="authMsg authMsg--error">DB Error: ' . $e->getMessage() . '</p>';
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
            <a href="#" class="navItem"><?php echo $loggedIn ? htmlspecialchars($userData['username']) : 'Guest'; ?></a>
            <div class="userPfp" style="background-image:url('<?php echo $navPfp; ?>');background-size:cover;background-position:center;"></div>
        </div>
    </nav>

    <?php if (!$loggedIn): ?>

        <div class="modalOverlay">
            <div class="loginCard">
                <h2 class="cardTitle"><?php echo ($authMode === 'login') ? 'Athena Login' : 'Create Account'; ?></h2>
                <form action="account.php" method="POST">
                    <input type="hidden" name="authMode" value="<?php echo $authMode; ?>">
                    <div class="inputGroup">
                        <h3>Username</h3>
                        <input type="text" name="username" required>
                    </div>
                    <div class="inputGroup">
                        <h3>Password</h3>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="authSubmit" class="saveButton saveButton--full">
                        <?php echo ($authMode === 'login') ? 'Initialize Log' : 'Register Account'; ?>
                    </button>
                    <?php echo $authMessage; ?>
                </form>
                <form action="account.php" method="POST" class="toggleForm">
                    <input type="hidden" name="authMode" value="<?php echo ($authMode === 'login') ? 'register' : 'login'; ?>">
                    <button type="submit" class="toggleBtn">
                        <?php echo ($authMode === 'login') ? 'Need an account? Register' : 'Already have an account? Login'; ?>
                    </button>
                </form>
            </div>
        </div>

    <?php else:
        $currentPfp  = !empty($userData['profilepicture']) ? $userData['profilepicture'] : $defaultPfp;
        $currentBtag = $userData['battletag'] ?? '';
    ?>

        <div class="accountContainer">
            <header>
                <h1 class="pageTitle">Account Settings</h1>
            </header>

            <div class="settingsGrid">

                <div class="settingsCard">
                    <h2 class="cardTitle">Profile Information</h2>

                    <div class="userHeader">
                        <div class="accountPfp" style="background-image:url('<?php echo $currentPfp; ?>');"></div>
                        <div class="userHeaderInfo">
                            <h2 class="heroUsername"><?php echo strtoupper(htmlspecialchars($userData['username'])); ?></h2>
                            <form action="account.php" method="POST" enctype="multipart/form-data" class="avatarForm">
                                <input type="hidden" name="uploadSubmit" value="1">
                                <input type="file" name="fileToUpload" accept="image/*" id="avatarInput" class="avatarInput">
                                <label for="avatarInput" class="avatarLabel">Change Avatar</label>
                            </form>
                            <?php if (isset($_POST['uploadSubmit'])) echo $authMessage; ?>
                        </div>
                    </div>

                    <div class="inputGroup">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($userData['username']); ?>" readonly class="readonlyInput">
                    </div>
                    <div class="inputGroup">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($userData['email'] ?? 'Not set'); ?>" readonly class="readonlyInput">
                    </div>
                    <div class="inputGroup">
                        <label for="profileBio">Notes</label>
                        <textarea id="profileBio" rows="3" placeholder="Put your notes here..."></textarea>
                    </div>
                </div>

                <div class="settingsCard">
                    <h2 class="cardTitle">Preferences</h2>
                    <form action="account.php" method="POST">
                        <div class="inputGroup">
                            <label for="favoriteRole">Favorite Role</label>
                            <select id="favoriteRole">
                                <option value="tank">Tank</option>
                                <option value="damage">Damage</option>
                                <option value="support">Support</option>
                            </select>
                        </div>
                        <div class="inputGroup">
                            <label for="regionSelect">Region</label>
                            <select id="regionSelect">
                                <option value="eu">Europe</option>
                                <option value="us">Americas</option>
                                <option value="as">Asia</option>
                            </select>
                        </div>
                        <div class="inputGroup">
                            <label>Battle.net Tag</label>
                            <input type="text" placeholder="Battletag#1234" id="battletagInput"
                                name="battletagInput" maxlength="18"
                                value="<?php echo htmlspecialchars($currentBtag); ?>">
                            <?php echo $btagErrorMessage; ?>
                        </div>
                        <?php if (!isset($_POST['uploadSubmit'])) echo $authMessage; ?>

                    </form>
                </div>
                <footer class="settingsFooter">
                    <button type="submit" name="savePreferencesSubmit" class="saveButton">Save Changes</button>
                    <button type="submit" name="logoutSubmit" class="logoutButton">Log Out</button>
                </footer>
            </div>
        </div>

    <?php endif; ?>

    <script>
        document.getElementById('avatarInput')?.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                this.form.submit();
            }
        });
    </script>

</body>

</html>