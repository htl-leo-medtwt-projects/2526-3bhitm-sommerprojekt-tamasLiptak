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
    <script src="./counters_script.js" defer></script>
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
            <a href="./account.php" class="navItem">
                <?php echo htmlspecialchars($navUsername); ?>
            </a>
            <div class="userPfp" style="
                background-image: url('<?php echo htmlspecialchars($navPfp, 3); ?>');
                background-size: cover;
                background-position: center;">
            </div>
        </div>
    </nav>

    <div class="countersWrapper">

        <!-- LEFT: hero roster picker -->
        <div class="heroRosterPanel">
            <div class="rosterHeader">Select Hero</div>
            <div class="rosterSearchWrap">
                <input
                    type="text"
                    id="heroSearch"
                    class="rosterSearch"
                    placeholder="Search hero…"
                    autocomplete="off">
            </div>
            <div id="heroRoster"></div>
        </div>
        <!-- MIDDLE: counter detail panel -->
        <div class="counterDetailPanel">

            <!-- Hero banner -->
            <div class="counterHeroBanner">
                <div class="counterPanelBg" id="counterPanelBg"></div>
                <div class="counterPanelBgOverlay"></div>
                <div class="counterHeroInfo">
                    <div class="selectedHeroAvatar" id="selectedHeroAvatar"></div>
                    <div class="selectedHeroMeta">
                        <div class="selectedHeroLabel">Counters for</div>
                        <div id="selectedHeroName">—</div>
                        <div id="selectedHeroRole"></div>
                    </div>
                </div>
            </div>

            <!-- TOP HALF: Counter list -->
            <div class="detailHalf detailHalfTop">
                <div class="detailHalfHeader">
                    <span class="countersPanelTitle">Who counters this hero</span>
                    <?php if ($loggedIn): ?>
                        <button class="noteAddBtn" id="addPublicNoteBtn" title="Submit a public tip">+</button>
                    <?php endif; ?>
                </div>
                <div id="counterList" class="detailHalfScroll">
                    <div class="counterLoading">Select a hero to view their counters</div>
                </div>
                <div id="emptyState">
                    <div class="emptyText">No counter data for this hero yet.</div>
                </div>
            </div>

            <!-- BOTTOM HALF: My Notes -->
            <div class="detailHalf detailHalfBottom">
                <div class="detailHalfHeader">
                    <span class="countersPanelTitle">My Notes</span>
                    <?php if ($loggedIn): ?>
                        <button class="noteAddBtn" id="addPrivateNoteBtn" title="Add a private note">+</button>
                    <?php endif; ?>
                </div>
                <?php if ($loggedIn): ?>
                    <div id="myNotesList" class="detailHalfScroll">
                        <div class="counterLoading">Select a hero to see your notes</div>
                    </div>
                <?php else: ?>
                    <div class="notesLoginPrompt">
                        <span>Log in to save personal notes for each hero.</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Add Note -->
        <div id="noteModal" class="noteModalOverlay" style="display:none;">
            <div class="noteModalBox">
                <div class="noteModalTitle" id="noteModalTitle">Add Note</div>

                <div class="noteModalField">
                    <label class="noteModalLabel">Hero being countered</label>
                    <div class="noteModalHeroGrid" id="noteHeroPicker"></div>
                </div>

                <div class="noteModalField">
                    <label class="noteModalLabel">Severity</label>
                    <div class="noteModalSeverityRow">
                        <button class="noteSevBtn sevSoft active" data-sev="1">Soft Counter</button>
                        <button class="noteSevBtn sevHard" data-sev="2">Hard Counter</button>
                        <button class="noteSevBtn sevExtreme" data-sev="3">Extreme Threat</button>
                    </div>
                </div>

                <div class="noteModalField">
                    <label class="noteModalLabel">How to counter them</label>
                    <textarea id="noteCounterTips" class="noteModalTextarea" placeholder="Describe how to counter this hero…" maxlength="500"></textarea>
                </div>

                <div class="noteModalField">
                    <label class="noteModalLabel">How teammates can help</label>
                    <textarea id="noteTeammateHelp" class="noteModalTextarea" placeholder="What teammates should do…" maxlength="500"></textarea>
                </div>

                <div class="noteModalComps">
                    <div class="noteModalField">
                        <label class="noteModalLabel" style="color:#5cb87a;">✦ Good comps with this hero</label>
                        <input id="noteGoodComp1" class="noteModalInput" placeholder="e.g. Hero + Hero strategy" maxlength="80">
                        <input id="noteGoodComp2" class="noteModalInput" placeholder="e.g. Hero + Hero strategy" maxlength="80">
                    </div>
                    <div class="noteModalField">
                        <label class="noteModalLabel" style="color:#dc3a3a;">✦ Dangerous enemy comps</label>
                        <input id="noteDangerComp1" class="noteModalInput" placeholder="e.g. Hero + Hero combo" maxlength="80">
                        <input id="noteDangerComp2" class="noteModalInput" placeholder="e.g. Hero + Hero combo" maxlength="80">
                    </div>
                </div>

                <div class="noteModalActions">
                    <button class="noteModalCancel" id="noteModalCancel">Cancel</button>
                    <button class="noteModalSubmit" id="noteModalSubmit">Save</button>
                </div>

            </div>

        </div>
        <div id="deleteConfirmModal" class="noteModalOverlay" style="display:none;">
        <div class="noteModalBox" style="width:320px; gap:18px;">
            <div class="noteModalTitle">Delete Note</div>
            <div style="font-size:13px; color:rgba(255,255,255,0.6); line-height:1.6;">
                Are you sure you want to delete this note? This cannot be undone.
            </div>
            <div class="noteModalActions">
                <button class="noteModalCancel" id="deleteConfirmCancel">Cancel</button>
                <button class="noteModalSubmit" id="deleteConfirmOk" style="border-color:rgba(220,50,50,0.5); color:#dc3a3a; background:rgba(220,50,50,0.1);">Delete</button>
            </div>
        </div>
    </div>
    

    <!-- RIGHT: general tips sidebar -->
    <div class="counterSidebar">
        <div class="sidebarHeader">General Tips</div>
        <div class="sidebarBody">

            <div class="sidebarTip">
                <div class="sidebarTipTitle">Portrait Shortcut</div>
                <div class="sidebarTipText">
                    Click any counter's portrait to instantly jump to <em>their</em> counter matchups — chain through counters to find the perfect pick.
                </div>
            </div>

            <div class="sidebarTip">
                <div class="sidebarTipTitle">Severity Guide</div>
                <div class="sidebarTipText">
                    <strong style="color:#5cb87a;">Soft Counter</strong> — slight disadvantage, skill can overcome it.<br><br>
                    <strong style="color:#e69620;">Hard Counter</strong> — serious threat, swap if possible.<br><br>
                    <strong style="color:#dc3a3a;">Extreme Threat</strong> — switch immediately or ask a teammate to swap.
                </div>
            </div>

            <div class="sidebarTip">
                <div class="sidebarTipTitle">Team Comps</div>
                <div class="sidebarTipText">
                    Each card shows friendly comps that synergise with your hero and dangerous enemy compositions to watch for.
                </div>
            </div>

            <div class="sidebarTip">
                <div class="sidebarTipTitle">Tip</div>
                <div class="sidebarTipText">
                    Counter data is community-curated. If you spot an error or want to add a matchup, ask an admin to update it via the database.
                </div>
            </div>

        </div>
    </div>

    </div>


</body>

</html>