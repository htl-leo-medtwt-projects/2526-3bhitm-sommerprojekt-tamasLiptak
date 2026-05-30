<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "athena_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ── 1. Load all heroes from DB into a name→ID map ─────────────
$result = $conn->query("SELECT heroID, name FROM heroes");
$heroMap = [];
while ($row = $result->fetch_assoc()) {
    $heroMap[strtolower($row['name'])] = (int)$row['heroID'];
}

// Helper: get ID by name (case-insensitive, partial friendly)
function hid($name, $map) {
    $key = strtolower(trim($name));
    if (isset($map[$key])) return $map[$key];
    // Fuzzy fallback for names like "Lúcio" stored as "Lúcio" or "Lucio"
    foreach ($map as $k => $id) {
        if (strpos($k, $key) !== false || strpos($key, $k) !== false) return $id;
    }
    return null;
}

// ── 2. Counter data ────────────────────────────────────────────
// Format per entry:
// [ 'hero' => 'Hero Being Looked Up',
//   'counteredBy' => 'Hero That Counters Them',
//   'severity' => 1|2|3,
//   'counterTips' => 'string',
//   'teammateHelp' => 'string',
//   'goodComps' => ['comp1', 'comp2'],
//   'dangerousComps' => ['comp1', 'comp2'] ]

$counters = [

    // ══════════════════════════════════════════════════════════
    //  ANA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Ana','counteredBy'=>'Genji','severity'=>3,
     'counterTips'=>'Genji can deflect Ana\'s sleep dart and biotic grenade back at her. Keep distance and avoid shooting him head-on when Deflect is active. Pre-aim high ground corners and use sleep dart pre-emptively when you hear him dashing.',
     'teammateHelp'=>'A Kiriko suzu can cleanse sleep darts from allies. Winston or D.Va can dive Genji off of Ana to peel for her. Brigitte\'s armor and stun shut Genji down completely.',
     'goodComps'=>['Winston + Kiriko dive','D.Va + Lucio speed','Reinhardt + Brigitte brawl'],
     'dangerousComps'=>['Genji + Nano blade Genji','Double flanker with Tracer']],

    ['hero'=>'Ana','counteredBy'=>'Tracer','severity'=>2,
     'counterTips'=>'Tracer\'s blinks make landing sleep dart very difficult. Use your sidearm at close range and try to land a sleep dart when she stops to reload or recall. Stay near teammates.',
     'teammateHelp'=>'Brigitte hard-counters Tracer — her inspire aura and stun force Tracer away. Cassidy\'s magnetic grenade is a reliable Tracer delete.',
     'goodComps'=>['Brigitte + Ana poke','Bastion + Orisa hold'],
     'dangerousComps'=>['Tracer + Sombra double dive','Tracer + Genji dive']],

    ['hero'=>'Ana','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra\'s hack disables Ana\'s entire kit — no sleep dart, no grenade, no ult charge. Stay grouped so teammates can kill a hacked Sombra before she reaches you. Hold sleep dart for when she decloaks.',
     'teammateHelp'=>'Kiriko teleports to Ana and suzu-cleanses hack instantly. Winston can chase Sombra off high ground. Wrecking Ball can detect Sombra with sensors.',
     'goodComps'=>['Kiriko + Ana','Winston dive to punish Sombra'],
     'dangerousComps'=>['Sombra + Reaper deathmatch flanks','Sombra + Zarya']],

    ['hero'=>'Ana','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Widowmaker out-ranges Ana and one-shots her easily. Use cover aggressively and never hold the same angle twice. Your sleep dart can punish a scoped widow at medium range.',
     'teammateHelp'=>'Winston is the classic Widow dive. Echo or Pharah force Widow off her perch. A Hanzo can duel her on her own angles.',
     'goodComps'=>['Winston + Lucio dive','Pharah + Mercy poke'],
     'dangerousComps'=>['Widowmaker + Ashe poke','Double sniper']],

    // ══════════════════════════════════════════════════════════
    //  ASHE
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Ashe','counteredBy'=>'Winston','severity'=>3,
     'counterTips'=>'Winston leaps directly onto Ashe and his bubble blocks her shots. Move away from high ground when you see a Winston nearby. Use B.O.B. to stall him and try to escape with your shotgun knockback.',
     'teammateHelp'=>'Roadhog can hook Winston mid-leap. Brigitte\'s stun peels Winston off Ashe. Zenyatta discord orb on Winston makes the dive punishable.',
     'goodComps'=>['Orisa + Ashe poke anchor','Roadhog + Ashe'],
     'dangerousComps'=>['Winston + Tracer dive','Winston + D.Va double dive']],

    ['hero'=>'Ashe','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah flies out of Ashe\'s effective hitscan range and punishes stationary positioning. Track her arc and use Dynamite to zone her landing spots. B.O.B. can tag airborne Pharah.',
     'teammateHelp'=>'Cassidy or Soldier: 76 can out-duel Pharah in the air. Ana\'s sleep dart destroys Pharah. Roadhog hook kills Pharah instantly if she flies low.',
     'goodComps'=>['Cassidy + Zenyatta poke','Soldier: 76 + Mercy'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno air combo']],

    ['hero'=>'Ashe','counteredBy'=>'Reaper','severity'=>2,
     'counterTips'=>'Reaper teleports or wraiths onto Ashe and deletes her in close quarters. Maintain distance and use Dynamite to zone doorways. Never let Reaper walk up freely.',
     'teammateHelp'=>'Roadhog can hook Reaper before he reaches Ashe. Brigitte stun stops his engage. High-ground tanks like Orisa or Sigma shield Ashe from him.',
     'goodComps'=>['Orisa + Ashe hold','Sigma + Ashe long-range'],
     'dangerousComps'=>['Reaper + Roadhog brawl','Reaper + Junker Queen brawl']],

    // ══════════════════════════════════════════════════════════
    //  BAPTISTE
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Baptiste','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Immortality Field, Regen Burst, and Amplification Matrix. Prioritize killing the drone immediately when hacked. Pre-place immortality field before entering a Sombra-known zone.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Baptiste instantly. Winston or D.Va can chase Sombra off Baptiste. Wrecking Ball sensor detects invisible Sombra.',
     'goodComps'=>['Kiriko + Baptiste','Winston dive to kill Sombra'],
     'dangerousComps'=>['Sombra + Reaper brawl dives','Sombra + Zarya counter-dive']],

    ['hero'=>'Baptiste','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Baptiste has large hitbox and is often static while healing — easy Widow target. Use Exo Boots to jump behind cover and reposition. Don\'t hold long sightlines.',
     'teammateHelp'=>'Winston dive on Widow protects the backline. Echo or Pharah force Widow off angles. D.Va fly-matrix can block sniper shots.',
     'goodComps'=>['Orisa + Baptiste poke','D.Va + Baptiste'],
     'dangerousComps'=>['Widowmaker + Ashe poke','Double sniper spam']],

    ['hero'=>'Baptiste','counteredBy'=>'Genji','severity'=>2,
     'counterTips'=>'Genji can deflect Baptiste\'s biotic launcher burst back at him. Don\'t spam bullets into Deflect. Immortality Field saves teammates he dives but not Baptiste himself.',
     'teammateHelp'=>'Brigitte is the best Genji answer — stun and inspire. Kiriko teleport-suzu combo saves Baptiste from burst combos.',
     'goodComps'=>['Brigitte + Baptiste','Orisa + Baptiste hold'],
     'dangerousComps'=>['Genji + Tracer double flanker','Dive with Sombra']],

    // ══════════════════════════════════════════════════════════
    //  BASTION
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Bastion','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Bastion can\'t angle up to hit Pharah effectively in Sentry mode. Switch to Recon mode to track her or use Artillery ult. Ask a hitscan teammate to handle her.',
     'teammateHelp'=>'Cassidy, Soldier: 76, or Widowmaker easily punish Pharah. Ana sleep dart drops Pharah out of the sky.',
     'goodComps'=>['Orisa shield + Bastion','Reinhardt shield + Bastion'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno aerial']],

    ['hero'=>'Bastion','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Bastion completely — no abilities, no Sentry mode, no ult. Place yourself near teammates so they can kill the Sombra quickly. Her EMP destroys whole team setups around Bastion.',
     'teammateHelp'=>'Kiriko suzu cleanses hack instantly. Wrecking Ball sensor detects Sombra. Any tank peeling for Bastion forces Sombra off.',
     'goodComps'=>['Orisa + Bastion + Kiriko hold','Reinhardt shield anchor'],
     'dangerousComps'=>['Sombra + EMP into Bastion setup','Genji deflect spam into Bastion']],

    ['hero'=>'Bastion','counteredBy'=>'Genji','severity'=>3,
     'counterTips'=>'Genji can reflect Bastion\'s Sentry mode bullets back at him for an instant kill. Never fire into Deflect. Switch to Recon mode and dodge when Deflect is up.',
     'teammateHelp'=>'Brigitte hard counters Genji. Widowmaker or Hanzo can duel Genji on angles before he reaches Bastion.',
     'goodComps'=>['Brigitte + Bastion brawl','Reinhardt + Bastion + Brigitte'],
     'dangerousComps'=>['Genji + Tracer dive','Genji + Ana nano dive']],

    // ══════════════════════════════════════════════════════════
    //  BRIGITTE
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Brigitte','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Brigitte has no ranged ability to answer Pharah\'s aerial poke. Shield bash and rocket flail are melee-range only. Stay under cover and ask teammates to handle Pharah while you keep supports alive.',
     'teammateHelp'=>'Cassidy or Soldier: 76 hard counter Pharah. Ana sleep dart brings Pharah down. Widowmaker one-shots Pharah.',
     'goodComps'=>['Orisa + Brigitte hold','Sigma + Brigitte poke'],
     'dangerousComps'=>['Pharah + Mercy','Pharah + Juno air dominance']],

    ['hero'=>'Brigitte','counteredBy'=>'Reaper','severity'=>2,
     'counterTips'=>'Reaper\'s lifesteal neutralizes Brigitte\'s inspire healing. He can walk through her melee range and outsustain her. Use Shield Bash + Whipshot to interrupt his engage, then disengage.',
     'teammateHelp'=>'Roadhog hook removes Reaper from the fight. Junkrat mines zone doorways Reaper uses. High-ground holds prevent Reaper flanks.',
     'goodComps'=>['Orisa + Brigitte + Ana poke','Bastion + Brigitte hold'],
     'dangerousComps'=>['Reaper + Junker Queen brawl','Reaper + Roadhog dive-brawl']],

    ['hero'=>'Brigitte','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack disables Shield Bash, Rally, and Repair Pack. A hacked Brigitte is just a slow melee hero. Use whipshot to interrupt Sombra\'s hack cast before it completes.',
     'teammateHelp'=>'Kiriko suzu cleanses hack. Winston dive deletes Sombra off the support line. D.Va fly matrix can intercept Sombra\'s engage path.',
     'goodComps'=>['Kiriko + Brigitte','Reinhardt + Brigitte + Ana'],
     'dangerousComps'=>['Sombra + Tracer double flank','Sombra EMP into full dive']],

    // ══════════════════════════════════════════════════════════
    //  CASSIDY
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Cassidy','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah flies out of Cassidy\'s reliable hitscan range. Fan the Hammer does not reach her effectively. Use Magnetic Grenade on Pharah when she flies close to the ground and ask a Soldier or Widow to handle air.',
     'teammateHelp'=>'Soldier: 76 or Widowmaker take over air duty. Ana sleep dart drops Pharah. D.Va fly matrix blocks Pharah rockets.',
     'goodComps'=>['Soldier: 76 + Cassidy hitscan pair','Orisa + Cassidy poke'],
     'dangerousComps'=>['Pharah + Mercy domination','Pharah + Juno aerial']],

    ['hero'=>'Cassidy','counteredBy'=>'Genji','severity'=>2,
     'counterTips'=>'Genji deflects Cassidy\'s fan the hammer — avoid shooting during Deflect. Use Magnetic Grenade as a burst tool that ignores deflect. At close range, Cassidy wins the duel if grenade hits.',
     'teammateHelp'=>'Brigitte hard counters Genji. Kiriko teleport blocks Genji bursts on supports. Any area-denial hero zones out Genji\'s flanks.',
     'goodComps'=>['Brigitte + Cassidy','Reinhardt + Cassidy brawl'],
     'dangerousComps'=>['Genji + Ana nano blade','Genji + Tracer dive']],

    ['hero'=>'Cassidy','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook-shotgun one-shots Cassidy at close range. Maintain distance, use cover, and pre-aim hook paths. Magnetic Grenade attached to Roadhog right before hook wins the duel.',
     'teammateHelp'=>'Orisa spear disrupts Roadhog hook. Zarya bubble on Cassidy prevents one-shots. Ana anti-heal grenade cripples Roadhog sustain.',
     'goodComps'=>['Orisa + Cassidy','Zarya + Cassidy'],
     'dangerousComps'=>['Roadhog + Reaper brawl dive','Roadhog + Junkrat spam']],

    // ══════════════════════════════════════════════════════════
    //  D.VA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'D.Va','counteredBy'=>'Zarya','severity'=>3,
     'counterTips'=>'Zarya\'s bubble charges off every matrix-blocked shot and her beam melts D.Va\'s mech. Never use Defense Matrix into a bubbled Zarya. Save matrix for her Graviton Surge. Peel away when her energy is high.',
     'teammateHelp'=>'Ana anti-grenade counters Zarya sustain. Sombra hacks Zarya\'s bubble away. Ramattra blocks Zarya beam with Nemesis form annihilation.',
     'goodComps'=>['Ana + D.Va poke dive','Sombra + D.Va dive'],
     'dangerousComps'=>['Zarya + Reaper brawl','Zarya + Graviton + Hanzo combo']],

    ['hero'=>'D.Va','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper\'s shotguns shred D.Va\'s mech from inside her blind spots. His Wraith lets him escape Defense Matrix. Avoid getting flanked — use Boosters to keep distance when Reaper is close.',
     'teammateHelp'=>'Roadhog hook catches Reaper. Brigitte stun stops Reaper engage. Ana anti-heal grenade prevents Reaper lifesteal.',
     'goodComps'=>['Orisa + D.Va anchor hold','Sigma + D.Va'],
     'dangerousComps'=>['Reaper + Roadhog brawl','Reaper + Zarya brawl combo']],

    ['hero'=>'D.Va','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook drags D.Va out of position and his shotgun burst destroys the mech quickly. Never drift into hook range alone. Boosters can interrupt hook cast if timed perfectly.',
     'teammateHelp'=>'Orisa spear disrupts Roadhog hook aim. Sombra hacks Roadhog, removing hook. Ana anti-heal grenade stops his tank healing.',
     'goodComps'=>['Orisa + D.Va','Sombra + D.Va dive'],
     'dangerousComps'=>['Roadhog + Reaper','Roadhog + Junkrat brawl']],

    // ══════════════════════════════════════════════════════════
    //  DOOMFIST
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Doomfist','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack removes all of Doomfist\'s mobility and mitigation, turning him into a stationary target. Play very cautiously on cooldowns — if hacked mid-combo, you die. Try to punch Sombra before she can cast hack.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Doomfist mid-fight. Wrecking Ball sensor spots Sombra. Moira fade repositions Doomfist away from Sombra.',
     'goodComps'=>['Kiriko + Doomfist dive','Lucio + Doomfist dive speed'],
     'dangerousComps'=>['Sombra + Tracer double hack dive','Sombra + Ana counter-dive']],

    ['hero'=>'Doomfist','counteredBy'=>'Brigitte','severity'=>3,
     'counterTips'=>'Brigitte\'s Shield Bash interrupts Doomfist combos and her inspire healing counters his mitigation loss. Avoid one-shotting targets near a Brigitte. Her armor pack reduces burst damage.',
     'teammateHelp'=>'Lucio speedboost lets Doomfist disengage if Brigitte appears. Ana nano boost buffs Doomfist through brawl if needed.',
     'goodComps'=>['Lucio + Doomfist speed','Ana + Doomfist nano dive'],
     'dangerousComps'=>['Brigitte + Orisa hold','Brigitte + Reinhardt brawl']],

    ['hero'=>'Doomfist','counteredBy'=>'Ana','severity'=>2,
     'counterTips'=>'Ana sleep dart can catch Doomfist mid-air between dashes. Anti-heal grenade cancels his passive HP regeneration from blocking. Her high single-target damage also punishes him in extended fights.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-grenade when Doomfist has it. Moira orb healing offsets anti-heal. Lucio speedboost helps Doomfist flee Ana sightlines.',
     'goodComps'=>['Kiriko + Doomfist','Lucio speed + Doomfist'],
     'dangerousComps'=>['Ana + Kiriko anti-dive','Ana + Orisa poke hold']],

    // ══════════════════════════════════════════════════════════
    //  ECHO
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Echo','counteredBy'=>'Pharah','severity'=>2,
     'counterTips'=>'Pharah and Echo compete in air space — Pharah\'s concussive blast and rockets punish Echo when hovering. Avoid hovering near Pharah. Use Focusing Beam on low-HP targets on the ground instead.',
     'teammateHelp'=>'Ana sleep dart or Cassidy magnetic grenade handles Pharah. Sigma anti-gravity orb disrupts Pharah\'s flight path.',
     'goodComps'=>['Echo + Cassidy hitscan pair','Ana + Echo'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno air duo']],

    ['hero'=>'Echo','counteredBy'=>'Widowmaker','severity'=>3,
     'counterTips'=>'Echo has a very large hitbox while flying, making her a prime Widowmaker target. Stay behind cover and use Glide sparingly. Duplicate a Widowmaker to mirror her on the same angle.',
     'teammateHelp'=>'Winston dive on Widow is ideal. D.Va fly-matrix blocks sniper bullets. Another Widowmaker or Hanzo can duel her.',
     'goodComps'=>['Winston + Echo dive','D.Va + Echo'],
     'dangerousComps'=>['Widowmaker + Ashe double sniper','Widowmaker + Cassidy hitscan lock']],

    ['hero'=>'Echo','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook pulls Echo out of the air and his shotgun burst one-shots her. Don\'t fly low near a Roadhog. Duplicate a Roadhog with Duplicate to win the hook duel.',
     'teammateHelp'=>'Orisa spear interrupts Roadhog hook. Ana anti-grenade cripples Roadhog self-heal. Zarya bubble saves Echo from a hook kill.',
     'goodComps'=>['Orisa + Echo poke','Ana + Echo'],
     'dangerousComps'=>['Roadhog + Reaper brawl','Roadhog + Junkrat trap zone']],

    // ══════════════════════════════════════════════════════════
    //  GENJI
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Genji','counteredBy'=>'Brigitte','severity'=>3,
     'counterTips'=>'Brigitte\'s Shield Bash stuns Genji mid-dash and her inspire healing counters his burst damage. Play aggressively — she is your hardest counter. Avoid fighting her alone; wait for nano blade and use teammates as distraction.',
     'teammateHelp'=>'Ana nano boost powers through Brigitte if the fight is unavoidable. Lucio boop can separate Brigitte from the group so Genji can fight others.',
     'goodComps'=>['Ana + Genji nano blade','Lucio + Genji dive speed'],
     'dangerousComps'=>['Brigitte + Orisa brawl','Brigitte + Reinhardt brawl hold']],

    ['hero'=>'Genji','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy\'s Magnetic Grenade attaches on dash and cannot be deflected reliably. At close range, Fan the Hammer also punishes Genji. Deflect still works against his main shots — use it on fan the hammer.',
     'teammateHelp'=>'Lucio speed helps Genji disengage from Cassidy. Kiriko suzu cleanses magnetic grenade effect.',
     'goodComps'=>['Ana + Genji nano blade dive','Lucio + Genji + Tracer'],
     'dangerousComps'=>['Cassidy + Brigitte lockdown','Cassidy + Orisa hold']],

    ['hero'=>'Genji','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook can grab Genji mid-dash if aimed predictively. His huge HP and shotgun burst kill Genji before he can react. Deflect the hook if you see it coming — otherwise stay at range.',
     'teammateHelp'=>'Orisa spear disrupts Roadhog. Lucio speed lets Genji dodge hooks. D.Va matrix can block the hook chain.',
     'goodComps'=>['Lucio + Genji speed dive','Kiriko + Genji'],
     'dangerousComps'=>['Roadhog + Moira sustain brawl','Roadhog + Reaper brawl']],

    // ══════════════════════════════════════════════════════════
    //  HANZO
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Hanzo','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Widowmaker wins the sniper duel — she charges shots faster and has mobility tools Hanzo lacks. Pre-fire angles with Storm Arrow. Use Lunge to dodge her scoped shot and reposition immediately.',
     'teammateHelp'=>'Winston dives Widow off angles. D.Va fly matrix blocks her shots. Echo duplicate can mirror Widow.',
     'goodComps'=>['Hanzo + Orisa poke hold','Hanzo + Sigma long-range'],
     'dangerousComps'=>['Widowmaker + Ashe double sniper','Widowmaker + Cassidy poke']],

    ['hero'=>'Hanzo','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper closes distance fast and Hanzo has very low close-range damage before Storm Arrow is loaded. Use Lunge to escape Reaper\'s teleport flank and try to pre-aim teleport landing spots.',
     'teammateHelp'=>'Roadhog hook intercepts Reaper before he reaches backline. Brigitte stun stops Reaper engage. Orisa fortify+spear combo punishes Reaper approach.',
     'goodComps'=>['Orisa + Hanzo poke','Roadhog + Hanzo'],
     'dangerousComps'=>['Reaper + Sombra backline dive','Reaper + Zarya brawl']],

    ['hero'=>'Hanzo','counteredBy'=>'D.Va','severity'=>2,
     'counterTips'=>'D.Va\'s Defense Matrix eats Hanzo\'s arrows including Storm Arrows and Dragonstrike. Fire at non-matrix angles or use Sonic Arrow to track her position. Avoid ult when matrix is available.',
     'teammateHelp'=>'Sombra hacks D.Va, removing matrix. Zarya bubble protects Hanzo from D.Va dive. Ana sleep dart prevents D.Va bomb.',
     'goodComps'=>['Sigma + Hanzo poke','Orisa + Hanzo hold'],
     'dangerousComps'=>['D.Va + Winston double dive','D.Va + Genji dive']],

    // ══════════════════════════════════════════════════════════
    //  JUNKRAT
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Junkrat','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah flies above Junkrat\'s grenade arc range completely. You cannot effectively hit her with grenades at high altitude. Use RIP-Tire to track Pharah if she is low, otherwise request a teammate swap.',
     'teammateHelp'=>'Cassidy, Soldier: 76, or Widowmaker eliminate Pharah. Ana sleep dart grounds her. Hanzo\'s Storm Arrow punishes aerial targets.',
     'goodComps'=>['Junkrat + Orisa choke hold','Junkrat + Reinhardt gate'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno air combo']],

    ['hero'=>'Junkrat','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Widowmaker out-ranges Junkrat completely and can one-shot him easily. Stay behind walls and use indirect angles. Your grenades deny ground approaches but can\'t answer sniper perches.',
     'teammateHelp'=>'Winston or Wrecking Ball dive Widow off position. D.Va fly-matrix covers Junkrat from sniper fire. Pharah can contest Widow\'s high ground.',
     'goodComps'=>['Junkrat + Reinhardt choke hold','Junkrat + Orisa'],
     'dangerousComps'=>['Widowmaker + Ashe long range','Double sniper poke']],

    ['hero'=>'Junkrat','counteredBy'=>'D.Va','severity'=>3,
     'counterTips'=>'D.Va Defense Matrix erases Junkrat\'s grenades and mines entirely. Focus fire elsewhere while matrix is active. RIP-Tire can be eaten by matrix — time it for when matrix is on cooldown.',
     'teammateHelp'=>'Sombra hacks D.Va, disabling matrix. Zarya\'s Graviton pulls D.Va into Junkrat mine clusters. Ana sleep dart prevents Self-Destruct.',
     'goodComps'=>['Junkrat + Sombra','Junkrat + Zarya Graviton combo'],
     'dangerousComps'=>['D.Va + Winston dive eating mines','D.Va + Tracer dive']],

    // ══════════════════════════════════════════════════════════
    //  JUNKER QUEEN
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Junker Queen','counteredBy'=>'Ana','severity'=>3,
     'counterTips'=>'Ana\'s anti-heal grenade completely shuts down Junker Queen\'s passive wound healing and Commanding Shout sustain. Never fight Ana in the open — close the gap through terrain and use Carnage before she grenade you.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-heal from Junker Queen instantly. Moira orb healing can partially offset anti-heal. Lucio speed helps JQ engage before grenade lands.',
     'goodComps'=>['Kiriko + Junker Queen','Lucio + Junker Queen brawl'],
     'dangerousComps'=>['Ana + Brigitte anti-brawl','Ana + Kiriko sustain lock']],

    ['hero'=>'Junker Queen','counteredBy'=>'Orisa','severity'=>2,
     'counterTips'=>'Orisa\'s Fortify makes her immune to JQ\'s knockback and slow. Her high poke damage and Spear punish JQ\'s aggressive dashes. Engage through flanks and avoid head-on fights with a fortified Orisa.',
     'teammateHelp'=>'Sombra hacks Orisa, disabling Fortify. Kiriko suzu on JQ removes spear slow. Ana nano boost helps JQ brawl through Orisa.',
     'goodComps'=>['Lucio + JQ speed','Kiriko + JQ brawl'],
     'dangerousComps'=>['Orisa + Bastion anchor hold','Orisa + Sigma poke']],

    ['hero'=>'Junker Queen','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog out-sustains Junker Queen in a direct brawl due to his self-heal. Hook punishes her engage cooldowns. Use Shout before engaging to maximize HP and try to outflank him.',
     'teammateHelp'=>'Ana anti-heal grenade on Roadhog negates his Take a Breather. Sombra hack removes hook. Lucio speed lets JQ disengage from a bad brawl.',
     'goodComps'=>['Lucio + JQ speed engage','Ana + JQ brawl burst'],
     'dangerousComps'=>['Roadhog + Reaper sustain brawl','Roadhog + Moira healing brawl']],

    // ══════════════════════════════════════════════════════════
    //  JUNO
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Juno','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Juno is a mobile support but Widowmaker can track her through the air since she is predictable while boosting. Stay low and use terrain cover. Ring of Salvation can be cast from behind cover safely.',
     'teammateHelp'=>'Winston or D.Va dive Widow off angle. Pharah contests Widow\'s high-ground position. Cassidy magnetic grenade on airborne Widow punishes.',
     'goodComps'=>['Juno + Pharah aerial poke','Juno + D.Va speed dive'],
     'dangerousComps'=>['Widowmaker + Ashe long range poke','Widowmaker + Hanzo double sniper']],

    ['hero'=>'Juno','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack disables Juno\'s Hyper Ring speed boost and Orbital Ray ult. A hacked Juno loses her mobility and healing output. Pre-place Orbital Ray before entering a Sombra-heavy zone.',
     'teammateHelp'=>'Kiriko suzu cleanses hack from Juno. Winston dive keeps Sombra off the support line. Wrecking Ball sensor detects Sombra position.',
     'goodComps'=>['Kiriko + Juno double support','Winston + Juno dive'],
     'dangerousComps'=>['Sombra + Reaper flanks','Sombra + Tracer double dive']],

    // ══════════════════════════════════════════════════════════
    //  KIRIKO
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Kiriko','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Kiriko\'s teleport and Suzu — her two core tools. This makes her a stationary healer that is easy to eliminate. Save Suzu for the brief window before getting hacked by throwing it pre-emptively.',
     'teammateHelp'=>'Wrecking Ball sensor detects Sombra. Winston dive keeps Sombra busy. Brigitte stun stops Sombra before she can hack.',
     'goodComps'=>['Wrecking Ball + Kiriko','Winston + Kiriko dive'],
     'dangerousComps'=>['Sombra + Tracer double flank','Sombra EMP into full dive']],

    ['hero'=>'Kiriko','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy\'s Magnetic Grenade bypasses Kiriko\'s Suzu invincibility window and deals good burst to her fragile frame. Avoid clustering with teammates near a Cassidy angle. Teleport away from grenade range.',
     'teammateHelp'=>'Reinhardt shield covers Kiriko from Cassidy poke. Orisa fortify and spear push Cassidy off angles. Genji can duel Cassidy to relieve pressure.',
     'goodComps'=>['Kiriko + Reinhardt brawl','Kiriko + Ana poke'],
     'dangerousComps'=>['Cassidy + Brigitte anti-flanker','Cassidy + Orisa hold']],

    // ══════════════════════════════════════════════════════════
    //  LIFEWEAVER
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Lifeweaver','counteredBy'=>'Tracer','severity'=>3,
     'counterTips'=>'Lifeweaver has very slow mobility tools and Tracer easily out-maneuvers him. His Thorn Volley is slow and inaccurate against a blinking Tracer. Petal Platform can be used to deny Tracer ground approach.',
     'teammateHelp'=>'Brigitte hard counters Tracer. Cassidy magnetic grenade deletes Tracer. Kiriko kunai can pressure Tracer off the support.',
     'goodComps'=>['Brigitte + Lifeweaver','Orisa + Lifeweaver anchor'],
     'dangerousComps'=>['Tracer + Sombra double flank','Tracer + Genji dive']],

    ['hero'=>'Lifeweaver','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables all of Lifeweaver\'s abilities — Pull, Platform, and Rejuvenating Dash. Hacked Lifeweaver is defenseless. Group with teammates so they can kill Sombra off you.',
     'teammateHelp'=>'Kiriko suzu cleanses hack. Winston dive forces Sombra to disengage. Wrecking Ball sensor reveals Sombra position.',
     'goodComps'=>['Kiriko + Lifeweaver double support','Winston + Lifeweaver dive'],
     'dangerousComps'=>['Sombra + Reaper backline','Sombra EMP into dive']],

    // ══════════════════════════════════════════════════════════
    //  LUCIO
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Lúcio','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack stops Lucio\'s wallride, boop, and Sound Barrier ult — all critical for his mobility and peel. Try to boop Sombra before hack completes and stay near high walls to maintain some movement options.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Lucio. Wrecking Ball sensor detects Sombra. Brigitte can peel Sombra off Lucio with stun.',
     'goodComps'=>['Kiriko + Lucio','Brigitte + Lucio brawl speed'],
     'dangerousComps'=>['Sombra + Tracer double flank','Sombra EMP + Lucio ult cancel']],

    ['hero'=>'Lúcio','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy\'s magnetic grenade attaches to Lucio even while wallriding and deals heavy burst. His fan the hammer punishes Lucio at close range. Stay on high walls out of grenade range.',
     'teammateHelp'=>'Winston dive on Cassidy protects Lucio. Reinhardt shield forces Cassidy to focus elsewhere. Genji duel against Cassidy relieves backline pressure.',
     'goodComps'=>['Winston + Lucio dive','Reinhardt + Lucio brawl'],
     'dangerousComps'=>['Cassidy + Brigitte lockdown','Cassidy + Orisa poke hold']],

    // ══════════════════════════════════════════════════════════
    //  MAUGA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Mauga','counteredBy'=>'Ana','severity'=>3,
     'counterTips'=>'Ana anti-heal grenade completely shuts down Mauga\'s cardiac overdrive healing passive. His overhealth generation stops under anti-heal. Stay close to teammates, rush Ana before she can grenade, and use Cage Fight ult when you have healing.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-grenade on Mauga. Moira orb healing partially offsets anti-heal. Lucio speed helps Mauga close gap on Ana.',
     'goodComps'=>['Kiriko + Mauga brawl','Lucio + Mauga engage speed'],
     'dangerousComps'=>['Ana + Brigitte anti-brawl','Ana + Kiriko sustain']],

    ['hero'=>'Mauga','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook pulls Mauga out of position or hooks him mid-Overrun charge. His shotgun burst is effective against Mauga\'s large hitbox. Use Cage Fight when Roadhog hooks in to trap him.',
     'teammateHelp'=>'Sombra hack removes Roadhog hook. Ana anti-heal on Roadhog negates his self-heal. Orisa spear disrupts Roadhog positioning.',
     'goodComps'=>['Lucio + Mauga','Kiriko + Mauga brawl'],
     'dangerousComps'=>['Roadhog + Reaper brawl','Roadhog + Moira sustain']],

    // ══════════════════════════════════════════════════════════
    //  MEI
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Mei','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah completely avoids Mei\'s ice wall and cryo-freeze by flying overhead. Primary fire can\'t reach effective Pharah altitude. Use Blizzard to zone landing spots and request hitscan support.',
     'teammateHelp'=>'Cassidy, Soldier: 76, or Widowmaker handle Pharah in the air. Hanzo Storm Arrow punishes aerial targets. Ana sleep dart grounds Pharah.',
     'goodComps'=>['Mei + Orisa choke hold','Mei + Reinhardt brawl gate'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno air dominance']],

    ['hero'=>'Mei','counteredBy'=>'Reaper','severity'=>2,
     'counterTips'=>'Reaper closes on Mei with Wraith Form which ignores slow, and his shotguns delete her fast. Use Cryo-Freeze to survive his initial burst and ice wall to separate him from teammates.',
     'teammateHelp'=>'Roadhog hook catches Reaper. Brigitte stun stops his engage. High-ground hold denies Reaper ground flanks entirely.',
     'goodComps'=>['Mei + Orisa','Mei + Roadhog brawl'],
     'dangerousComps'=>['Reaper + Zarya bubble push','Reaper + Junker Queen brawl']],

    // ══════════════════════════════════════════════════════════
    //  MERCY
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Mercy','counteredBy'=>'Widowmaker','severity'=>3,
     'counterTips'=>'Widowmaker one-shots Mercy instantly — she has lowest HP in the game. Constantly guardian angel to a moving target and avoid stationary healing. Pre-emptively switch beam targets to confuse Widow\'s aim.',
     'teammateHelp'=>'Winston dive on Widow is the standard answer. D.Va fly matrix blocks sniper shots covering Mercy. Another Widowmaker or Hanzo can counter-snipe.',
     'goodComps'=>['Pharah + Mercy (force Widow off angle)','D.Va + Mercy'],
     'dangerousComps'=>['Widowmaker + Ashe double poke','Widowmaker + Hanzo sniper pair']],

    ['hero'=>'Mercy','counteredBy'=>'Tracer','severity'=>3,
     'counterTips'=>'Tracer blips onto Mercy and bursts her in one clip easily. Mercy can\'t fight back at that range. Guardian Angel to a tank or off-angle teammate to break Tracer\'s follow-up. Call for Brigitte to peel.',
     'teammateHelp'=>'Brigitte is the premier Tracer answer. Cassidy magnetic grenade deletes Tracer. Kiriko kunai burst punishes Tracer when she targets Mercy.',
     'goodComps'=>['Brigitte + Mercy brawl','Orisa + Mercy poke hold'],
     'dangerousComps'=>['Tracer + Genji flanker combo','Tracer + Sombra double dive']],

    ['hero'=>'Mercy','counteredBy'=>'Genji','severity'=>2,
     'counterTips'=>'Genji dash jumps to Mercy\'s elevated position and bursts her quickly. Constantly guardian angel to moving targets and avoid staying in the air too long. Pistol can push him back if desperate.',
     'teammateHelp'=>'Brigitte stun stops Genji on Mercy. Kiriko teleport saves Mercy. Cassidy grenade tracks Genji to Mercy\'s position.',
     'goodComps'=>['Brigitte + Mercy brawl','Orisa + Mercy anchor hold'],
     'dangerousComps'=>['Genji + Ana nano blade dive','Genji + Tracer dive combo']],

    // ══════════════════════════════════════════════════════════
    //  MOIRA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Moira','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Moira\'s Fade escape — her only protection tool. A hacked Moira is an easy kill. Group with teammates so they can cover you after hack. Try to throw orbs before getting in hack range.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Moira. Wrecking Ball sensor detects Sombra. Winston dive keeps Sombra off the support line.',
     'goodComps'=>['Kiriko + Moira double support','Reinhardt + Moira brawl'],
     'dangerousComps'=>['Sombra + Tracer backline dive','Sombra EMP + full dive combo']],

    ['hero'=>'Moira','counteredBy'=>'Pharah','severity'=>2,
     'counterTips'=>'Moira has no reliable long-range ability to contest Pharah. Her orbs travel slowly and can\'t track Pharah. Use Fade to escape Pharah rockets and wait for Pharah to come to ground level.',
     'teammateHelp'=>'Cassidy or Soldier: 76 handle Pharah. Ana sleep dart grounds Pharah. Roadhog hook punishes low-flying Pharah.',
     'goodComps'=>['Moira + Orisa hold','Moira + Reinhardt brawl'],
     'dangerousComps'=>['Pharah + Mercy poke','Pharah + Juno air combo']],

    // ══════════════════════════════════════════════════════════
    //  ORISA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Orisa','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper shreds Orisa up close — his shotguns deal massive damage against her large hitbox and lifesteal sustains him through her poke. Fortify when he engages and use Spear to interrupt his burst window. Keep distance with terrain.',
     'teammateHelp'=>'Ana anti-heal grenade nullifies Reaper lifesteal. Roadhog hook removes Reaper from the engagement. Brigitte stun stops Reaper\'s short-range burst.',
     'goodComps'=>['Orisa + Ana poke','Orisa + Kiriko'],
     'dangerousComps'=>['Reaper + Roadhog brawl push','Reaper + Zarya shield brawl']],

    ['hero'=>'Orisa','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack disables Fortify and Javelin Spin — Orisa\'s key defensive tools. A hacked Orisa can be hooked or burst down. Play near teammates to get hack removed quickly.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Orisa. Winston dive forces Sombra off. Wrecking Ball sensor detects Sombra.',
     'goodComps'=>['Kiriko + Orisa anchor','Ana + Orisa poke'],
     'dangerousComps'=>['Sombra + Reaper counter-dive','Sombra EMP + Reaper combo']],

    ['hero'=>'Orisa','counteredBy'=>'Ramattra','severity'=>2,
     'counterTips'=>'Ramattra Nemesis form\'s punch blocks bypass Orisa\'s poke and his long-range range in Omnic form threatens her. Fortify his punches and use Spear to slow his Nemesis advance. Don\'t fight him in close quarters.',
     'teammateHelp'=>'Ana anti-heal grenade limits Ramattra sustain in Nemesis form. Sombra hacks Ramattra to prevent Nemesis switch. Kiriko suzu saves team from Annihilation ult.',
     'goodComps'=>['Orisa + Ana poke','Orisa + Sigma long range hold'],
     'dangerousComps'=>['Ramattra + Reaper + Ana brawl push','Ramattra + Zarya Annihilation combo']],

    // ══════════════════════════════════════════════════════════
    //  PHARAH
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Pharah','counteredBy'=>'Soldier: 76','severity'=>3,
     'counterTips'=>'Soldier: 76 is the most reliable Pharah counter — his hitscan with high accuracy destroys Pharah in the air. His sprint lets him reposition under Pharah rockets. Concussive Blast can dodge his Helix Rockets but avoid direct fire.',
     'teammateHelp'=>'Mercy amplifies Pharah\'s rockets when paired (Pharmercy). Ana sleep dart removes Pharah while you reposition. Lucio speedboost helps Pharah dodge hitscan.',
     'goodComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno air dominance'],
     'dangerousComps'=>['Soldier: 76 + Widowmaker double hitscan','Cassidy + Widowmaker poke']],

    ['hero'=>'Pharah','counteredBy'=>'Widowmaker','severity'=>3,
     'counterTips'=>'Widowmaker one-shots Pharah with a headshot and can track her in the air easily. Fly unpredictably — zig-zag and use Hover Jets to vary altitude. Concussive Blast to change direction rapidly.',
     'teammateHelp'=>'Mercy damage boost forces Widowmaker off angle. Echo can fly to contest Widow\'s high ground. Winston dive disrupts Widow.',
     'goodComps'=>['Pharah + Mercy poke','Pharah + Juno'],
     'dangerousComps'=>['Widowmaker + Cassidy double poke','Widowmaker + Soldier: 76']],

    ['hero'=>'Pharah','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy Magnetic Grenade can catch Pharah when she dips close to the ground and his hitscan pierces through her rocket barrage. Fly at max altitude to stay above grenade range.',
     'teammateHelp'=>'Mercy gives Pharah damage boost and damage reduce. Ana sleep dart punishes Cassidy. Juno ring boosts Pharah to escape grenade range.',
     'goodComps'=>['Pharah + Mercy','Pharah + Juno + Mercy triple air'],
     'dangerousComps'=>['Cassidy + Soldier: 76 dual hitscan','Cassidy + Widowmaker']],

    // ══════════════════════════════════════════════════════════
    //  RAMATTRA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Ramattra','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Ramattra\'s Nemesis Form switch and all abilities, removing his primary tank threat. Stay in Omnic form for range if Sombra is nearby and try to Void Barrier to protect yourself until hack expires.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Ramattra. Wrecking Ball sensor detects Sombra. Ana nano boost allows Ramattra to brawl through even after hack expires.',
     'goodComps'=>['Kiriko + Ramattra brawl','Ana + Ramattra nano','Lucio + Ramattra speed'],
     'dangerousComps'=>['Sombra + Tracer double dive','Sombra EMP + full dive combo']],

    ['hero'=>'Ramattra','counteredBy'=>'Pharah','severity'=>2,
     'counterTips'=>'Pharah punishes Ramattra in both forms — his Void Barrier doesn\'t protect from above and he has no anti-air tool. Nemesis form is useless against a flying Pharah. Use Annihilation ult to zone the ground fight and request air support.',
     'teammateHelp'=>'Cassidy or Soldier: 76 handle Pharah. Ana sleep dart drops Pharah. Sigma Kinetic Grasp absorbs Pharah rockets from above.',
     'goodComps'=>['Ramattra + Ana brawl','Ramattra + Kiriko sustain'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno air dominance']],

    // ══════════════════════════════════════════════════════════
    //  REAPER
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Reaper','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah is completely out of Reaper\'s range. His shotguns can\'t reach her altitude and Wraith Form doesn\'t help against air attacks. You can only wait out Pharah and use Death Blossom ult to clear the ground fight.',
     'teammateHelp'=>'Cassidy or Soldier: 76 can counter Pharah for the team. Ana sleep dart grounds her. Roadhog hook punishes Pharah flying low.',
     'goodComps'=>['Reaper + Roadhog brawl','Reaper + Zarya brawl'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno aerial combo']],

    ['hero'=>'Reaper','counteredBy'=>'Ana','severity'=>2,
     'counterTips'=>'Ana anti-heal grenade completely removes Reaper\'s lifesteal passive — his core sustain. A grenaded Reaper loses all his tankiness in a brawl. Fight Ana only through Wraith Form to dodge the grenade.',
     'teammateHelp'=>'Lucio speed helps Reaper dodge Ana grenades. Kiriko suzu cleanses anti-heal on Reaper. Sombra hacks Ana, disabling sleep dart and grenade.',
     'goodComps'=>['Reaper + Lucio speed','Reaper + Kiriko suzu save'],
     'dangerousComps'=>['Ana + Brigitte anti-brawl combo','Ana + Kiriko sustain duo']],

    ['hero'=>'Reaper','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hooks Reaper out of Wraith Form entry and his shotgun burst at that range deals comparable damage. Both heroes are brawlers — Roadhog wins because he has more HP and self-heal. Try to flank Roadhog from behind.',
     'teammateHelp'=>'Ana anti-heal stops Roadhog self-heal. Sombra hacks Roadhog, removing hook. Zarya bubble on Reaper saves him from the hook kill combo.',
     'goodComps'=>['Reaper + Ana + Zarya brawl','Reaper + Zarya bubble protect'],
     'dangerousComps'=>['Roadhog + Moira healing brawl','Roadhog + Junker Queen brawl']],

    // ══════════════════════════════════════════════════════════
    //  REINHARDT
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Reinhardt','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah bombs down on Reinhardt and his shield is angled forward — it doesn\'t cover aerial attacks effectively. Tilt your camera up and angle shield overhead. Fire Strike can reach Pharah if she is low. Request hitscan immediately.',
     'teammateHelp'=>'Cassidy, Soldier: 76, or Widowmaker cover air threats. Ana sleep dart drops Pharah. Junkrat can zone Pharah with RIP-Tire.',
     'goodComps'=>['Reinhardt + Ana brawl','Reinhardt + Kiriko'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno aerial']],

    ['hero'=>'Reinhardt','counteredBy'=>'Bastion','severity'=>3,
     'counterTips'=>'Bastion Sentry mode destroys Reinhardt\'s shield in seconds. Never stand behind shield against Bastion — retreat and flank. Use Charge to surprise Bastion from off-angle and pin him. Fire Strike through walls to poke.',
     'teammateHelp'=>'Genji deflect redirects Bastion fire back at him. Sombra hacks Bastion, disabling Sentry. Pharah bombs Bastion position from above.',
     'goodComps'=>['Reinhardt + Pharah combo','Reinhardt + Sombra'],
     'dangerousComps'=>['Bastion + Orisa + Brigitte anchor hold','Bastion + Reinhardt shield + Mercy']],

    ['hero'=>'Reinhardt','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack removes Reinhardt\'s shield, charge, and fire strike — all his core tools. A hacked Reinhardt is stuck as a melee hero only. Charge through Sombra to punish her if she appears in front.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Reinhardt instantly. Ana sleep dart punishes Sombra before she hacks. Wrecking Ball sensor detects Sombra before engage.',
     'goodComps'=>['Reinhardt + Kiriko brawl','Reinhardt + Ana brawl'],
     'dangerousComps'=>['Sombra + Reaper backline dive','Sombra EMP + full dive']],

    // ══════════════════════════════════════════════════════════
    //  ROADHOG
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Roadhog','counteredBy'=>'Ana','severity'=>3,
     'counterTips'=>'Ana anti-heal grenade completely stops Roadhog\'s Take a Breather self-heal — his entire survivability kit. Never heal in the open against an Ana. Use Whole Hog or hook Ana directly to eliminate her.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-heal on Roadhog. Lucio speed helps Roadhog gap-close on Ana. Sombra hacks Ana, removing sleep dart and grenade.',
     'goodComps'=>['Roadhog + Kiriko','Roadhog + Lucio speed brawl'],
     'dangerousComps'=>['Ana + Brigitte anti-brawl','Ana + Kiriko sustain duo']],

    ['hero'=>'Roadhog','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack disables Roadhog\'s hook, breather, and ult — his entire toolkit. A hacked Roadhog is a slow, large target. Hook Sombra before she can get into hack range or position behind cover when she is spotted.',
     'teammateHelp'=>'Kiriko suzu cleanses hack. Wrecking Ball sensor spots Sombra. Moira fade-orb helps sustain Roadhog after hack expires.',
     'goodComps'=>['Roadhog + Kiriko','Roadhog + Moira brawl sustain'],
     'dangerousComps'=>['Sombra + Reaper combo dive','Sombra EMP + Tracer double dive']],

    ['hero'=>'Roadhog','counteredBy'=>'Orisa','severity'=>2,
     'counterTips'=>'Orisa Javelin Spin prevents Roadhog from hooking her and she can spear him during the hook animation. Her fortify makes her immune to hook displacement. Fight Orisa only when Spear and Fortify are on cooldown.',
     'teammateHelp'=>'Sombra hacks Orisa, removing Fortify. Zarya bubble on Roadhog during spear saves him. Ana anti-heal on Orisa limits her poke damage sustain.',
     'goodComps'=>['Roadhog + Ana','Roadhog + Kiriko brawl'],
     'dangerousComps'=>['Orisa + Bastion anchor hold','Orisa + Sigma poke']],

    // ══════════════════════════════════════════════════════════
    //  SIGMA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Sigma','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper\'s close range shotguns bypass Sigma\'s Experimental Barrier and shred him quickly. His Wraith lets him close distance through Kinetic Grasp. Keep Reaper at range using Hyperspheres and avoid close-quarters fights.',
     'teammateHelp'=>'Roadhog hook removes Reaper from the engagement. Ana anti-heal grenade stops Reaper lifesteal. Brigitte stun peels Reaper off Sigma.',
     'goodComps'=>['Sigma + Ana poke','Sigma + Kiriko'],
     'dangerousComps'=>['Reaper + Zarya brawl push','Reaper + Roadhog brawl dive']],

    ['hero'=>'Sigma','counteredBy'=>'Pharah','severity'=>2,
     'counterTips'=>'Pharah\'s rockets bypass Sigma\'s barrier from above. Kinetic Grasp can absorb rockets when facing up but the timing is difficult. Use Gravitic Flux to punish Pharah when she is low altitude.',
     'teammateHelp'=>'Cassidy or Soldier: 76 handle Pharah. Ana sleep dart drops Pharah. Hanzo Storm Arrow punishes aerial movement.',
     'goodComps'=>['Sigma + Ana long-range','Sigma + Kiriko poke'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno aerial']],

    // ══════════════════════════════════════════════════════════
    //  SOJOURN
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Sojourn','counteredBy'=>'Winston','severity'=>3,
     'counterTips'=>'Winston leaps onto Sojourn and his bubble blocks her railgun charges. Her slides don\'t escape Winston\'s large tesla cannon arc. Use Disruptor Shot to zone Winston and try to railgun him at close range during his bubble downtime.',
     'teammateHelp'=>'Roadhog hook interrupts Winston leap. Brigitte stun peels Winston off Sojourn. Ana sleep dart catches Winston mid-dive.',
     'goodComps'=>['Sojourn + Orisa anchor','Sojourn + Roadhog brawl'],
     'dangerousComps'=>['Winston + Tracer dive','Winston + D.Va double dive']],

    ['hero'=>'Sojourn','counteredBy'=>'D.Va','severity'=>2,
     'counterTips'=>'D.Va Defense Matrix eats Sojourn\'s railgun shots — including charged ones. Her matrix windows negate Sojourn\'s burst potential. Slide away when matrix is up and re-engage when matrix is on cooldown.',
     'teammateHelp'=>'Sombra hacks D.Va, disabling matrix. Zarya bubble on Sojourn protects from D.Va dive. Ana sleep dart prevents Self-Destruct.',
     'goodComps'=>['Sojourn + Ana poke','Sojourn + Orisa hold'],
     'dangerousComps'=>['D.Va + Winston double dive','D.Va + Genji dive']],

    // ══════════════════════════════════════════════════════════
    //  SOLDIER: 76
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Soldier: 76','counteredBy'=>'Winston','severity'=>3,
     'counterTips'=>'Winston leaps directly onto Soldier and his bubble blocks hitscan shots completely. Sprint can create distance but Winston closes it fast. Save Helix Rockets for when bubble expires — they deal massive burst damage at point blank.',
     'teammateHelp'=>'Roadhog hook catches Winston mid-jump. Brigitte stun peels Winston off Soldier. Ana sleep dart stops Winston mid-dive.',
     'goodComps'=>['Soldier: 76 + Orisa anchor','Soldier: 76 + Mercy damage boost'],
     'dangerousComps'=>['Winston + Tracer dive','Winston + D.Va double dive']],

    ['hero'=>'Soldier: 76','counteredBy'=>'Genji','severity'=>2,
     'counterTips'=>'Genji deflects Soldier\'s primary fire and Helix Rockets back at him. Don\'t shoot during Deflect — use Sprint to reposition instead. At medium range, Soldier wins if Deflect is on cooldown.',
     'teammateHelp'=>'Brigitte stun shuts Genji down. Kiriko teleport saves Soldier from Genji burst. Cassidy grenade punishes Genji flanks.',
     'goodComps'=>['Soldier: 76 + Brigitte','Soldier: 76 + Orisa hold'],
     'dangerousComps'=>['Genji + Ana nano blade','Genji + Tracer double flanker']],

    // ══════════════════════════════════════════════════════════
    //  SOMBRA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Sombra','counteredBy'=>'Kiriko','severity'=>3,
     'counterTips'=>'Kiriko suzu cleanses hacks from the entire team, negating Sombra\'s only offensive tool. Without hack, Sombra\'s pistol DPS is low. Hack Kiriko first before engaging a team fight — she is your top priority target.',
     'teammateHelp'=>'Any flanker can chase down Sombra once detected. Wrecking Ball sensor detects invis Sombra. Winston can pressure Sombra away from Kiriko.',
     'goodComps'=>['Sombra + Tracer double hack dive','Sombra + Winston dive'],
     'dangerousComps'=>['Kiriko + Brigitte lockdown','Kiriko + Ana sustain duo']],

    ['hero'=>'Sombra','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy\'s Magnetic Grenade can be thrown on Sombra\'s predicted decloak position and his fan the hammer bursts her at close range. She has very low HP. Stay mid-range and bait out her decloak.',
     'teammateHelp'=>'Wrecking Ball sensor detects Sombra position. Brigitte stun peels Sombra off supports. Kiriko suzu protects targeted teammates from hack burst.',
     'goodComps'=>['Sombra + Reaper backline','Sombra EMP + dive'],
     'dangerousComps'=>['Cassidy + Brigitte lockdown','Cassidy + Orisa hitscan hold']],

    // ══════════════════════════════════════════════════════════
    //  SYMMETRA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Symmetra','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Symmetra\'s beam and turrets cannot reach Pharah in the air. She has zero anti-air capability. Teleporter can reposition team to escape Pharah pressure. Request a hitscan hero or use Photon Barrier ult to deny ground.',
     'teammateHelp'=>'Cassidy, Soldier: 76, or Widowmaker handle Pharah. Ana sleep dart grounds Pharah. Roadhog hook punishes low-flying Pharah.',
     'goodComps'=>['Symmetra + Reinhardt choke','Symmetra + Orisa hold point'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno air dominance']],

    ['hero'=>'Symmetra','counteredBy'=>'Widowmaker','severity'=>3,
     'counterTips'=>'Widowmaker out-ranges Symmetra completely and one-shots her. Teleporter can move team around sniper sightlines. Place turrets on high-ground routes Widow uses to approach.',
     'teammateHelp'=>'Winston or Wrecking Ball dive Widow off angle. D.Va fly matrix covers Symmetra from sniper shots. Echo can fly to contest Widow\'s position.',
     'goodComps'=>['Symmetra + Reinhardt gate hold','Symmetra + Sigma poke'],
     'dangerousComps'=>['Widowmaker + Ashe double poke','Widowmaker + Hanzo sniper']],

    ['hero'=>'Symmetra','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper teleports past Symmetra\'s turrets and deletes her in close range. Her ramp-up beam needs time to charge which Reaper won\'t give her. Place turrets at varying heights to slow his approach.',
     'teammateHelp'=>'Roadhog hook catches Reaper approach. Brigitte stun stops Reaper at close range. Orisa Fortify + Spear punishes Reaper in the open.',
     'goodComps'=>['Symmetra + Orisa choke hold','Symmetra + Roadhog brawl'],
     'dangerousComps'=>['Reaper + Zarya brawl push','Reaper + Roadhog dive combo']],

    // ══════════════════════════════════════════════════════════
    //  TORBJORN
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Torbjörn','counteredBy'=>'Pharah','severity'=>3,
     'counterTips'=>'Pharah bombs Torbjörn\'s turret and him from above angles he cannot effectively target. Turret deals good damage if Pharah dips low. Use Overload ult to give the turret extra HP and request hitscan.',
     'teammateHelp'=>'Cassidy or Soldier: 76 eliminate Pharah. Ana sleep dart drops Pharah. Hanzo Storm Arrow punishes her aerial movement.',
     'goodComps'=>['Torbjörn + Orisa hold','Torbjörn + Bastion anchor brawl'],
     'dangerousComps'=>['Pharah + Mercy Pharmercy','Pharah + Juno air dominance']],

    ['hero'=>'Torbjörn','counteredBy'=>'Widowmaker','severity'=>2,
     'counterTips'=>'Widowmaker one-shots Torbjörn easily and can destroy his turret from max range. Place turret in a position that covers sniper angles. Rivet Gun at medium range can duel Widow if she isn\'t scoped.',
     'teammateHelp'=>'Winston dive on Widow. D.Va fly matrix blocks sniper shots to turret. Pharah contests Widow\'s high ground position.',
     'goodComps'=>['Torbjörn + Orisa poke hold','Torbjörn + Sigma long-range'],
     'dangerousComps'=>['Widowmaker + Ashe double sniper','Double sniper poke']],

    ['hero'=>'Torbjörn','counteredBy'=>'Wrecking Ball','severity'=>3,
     'counterTips'=>'Wrecking Ball destroys Torbjörn\'s turret instantly with Grappling Claw slam and his high mobility prevents Torbjörn from setting up again. Throw turret far from the objective to buy time and use Overload to survive.',
     'teammateHelp'=>'Ana sleep dart catches Wrecking Ball after a slam. Roadhog hook grabs Wrecking Ball. Junkrat mines zone Ball\'s slam entry paths.',
     'goodComps'=>['Torbjörn + Orisa hold','Torbjörn + Junkrat zone trap'],
     'dangerousComps'=>['Wrecking Ball + Winston double dive','Wrecking Ball + Sombra EMP']],

    // ══════════════════════════════════════════════════════════
    //  TRACER
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Tracer','counteredBy'=>'Brigitte','severity'=>3,
     'counterTips'=>'Brigitte Shield Bash stuns Tracer mid-blink and her inspire healing counters Tracer burst. Never attack a Brigitte alone. Use Pulse Bomb on Brigitte when she overextends but avoid her stun range entirely.',
     'teammateHelp'=>'Lucio speedboost extends Tracer\'s reach past Brigitte. Ana sleep dart helps if Tracer is hounded. Sombra hack on Brigitte removes her shield bash briefly.',
     'goodComps'=>['Tracer + Genji double dive','Tracer + Sombra hack dive'],
     'dangerousComps'=>['Brigitte + Orisa brawl hold','Brigitte + Cassidy lockdown']],

    ['hero'=>'Tracer','counteredBy'=>'Cassidy','severity'=>2,
     'counterTips'=>'Cassidy\'s Magnetic Grenade attaches to Tracer and deals burst damage through blinks. His fan the hammer also punishes blink entry. Use Recall to rewind grenade damage and bait out grenade before engaging.',
     'teammateHelp'=>'Lucio speedboost helps Tracer escape grenade range. Kiriko suzu cleanses grenade damage effect. Winston dive on Cassidy removes him before he can grenade.',
     'goodComps'=>['Tracer + Winston dive','Tracer + Lucio speed'],
     'dangerousComps'=>['Cassidy + Brigitte double lockdown','Cassidy + Orisa poke']],

    ['hero'=>'Tracer','counteredBy'=>'Moira','severity'=>2,
     'counterTips'=>'Moira\'s biotic orb latches onto Tracer through blinks and her Fade can disengage when Tracer commits. Her sustained healing and grasp drain match Tracer\'s burst. Use Recall to refresh HP and burst Moira at sub-5m range.',
     'teammateHelp'=>'Kiriko teleport assists Tracer in escaping Moira drain. Sombra hack removes Moira\'s Fade. Any high-burst hero deletes Moira before she can sustain through Tracer.',
     'goodComps'=>['Tracer + Genji dive','Tracer + Sombra double dive'],
     'dangerousComps'=>['Moira + Brigitte brawl sustain','Moira + Roadhog sustain']],

    // ══════════════════════════════════════════════════════════
    //  WIDOWMAKER
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Widowmaker','counteredBy'=>'Winston','severity'=>3,
     'counterTips'=>'Winston\'s leap travels the entire map vertically and his bubble prevents Widowmaker from shooting back. Grapple to high ground as soon as you hear his leap. Widow cannot fight Winston at close range — escape immediately.',
     'teammateHelp'=>'D.Va Defense Matrix can block Winston leap landing. Roadhog hook catches Winston when he dives Widow. Ana sleep dart stops Winston mid-dive.',
     'goodComps'=>['Widowmaker + D.Va','Widowmaker + Roadhog peel'],
     'dangerousComps'=>['Winston + D.Va double dive','Winston + Tracer dive']],

    ['hero'=>'Widowmaker','counteredBy'=>'D.Va','severity'=>2,
     'counterTips'=>'D.Va fly matrix eats Widowmaker\'s fully-charged shots — the exact shots that make her dangerous. Time your shots for matrix downtime. Grapple to high angles where D.Va\'s matrix arc can\'t reach.',
     'teammateHelp'=>'Roadhog hooks D.Va when she flies close. Ana sleep dart stops D.Va bomb. Pharah can contest D.Va\'s air control.',
     'goodComps'=>['Widowmaker + Roadhog peel','Widowmaker + Ana poke'],
     'dangerousComps'=>['D.Va + Winston double dive','D.Va + Tracer dive cover']],

    ['hero'=>'Widowmaker','counteredBy'=>'Sombra','severity'=>2,
     'counterTips'=>'Sombra hack disables Widowmaker\'s Grappling Hook — her only escape. A hacked Widow is a sitting target with a slow bolt rifle. Hack cooldown window is the most dangerous moment. Stay near ledge drops to escape without grapple.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Widowmaker. Wrecking Ball sensor detects Sombra approach. Roadhog can peel Sombra off Widow.',
     'goodComps'=>['Widowmaker + Kiriko','Widowmaker + Roadhog + Ana poke'],
     'dangerousComps'=>['Sombra + Tracer double backline dive','Sombra EMP + full dive']],

    // ══════════════════════════════════════════════════════════
    //  WINSTON
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Winston','counteredBy'=>'Zarya','severity'=>3,
     'counterTips'=>'Zarya\'s bubble charges on every Winston tesla cannon shot, making him power up his hardest counter. Her beam melts Winston through his bubble. Never shoot into a Zarya bubble. Save bubble for defensive use and look for other targets.',
     'teammateHelp'=>'Ana anti-grenade limits Zarya sustain. Sombra hacks Zarya\'s bubble off. Roadhog hook removes Zarya from position before she reaches high energy.',
     'goodComps'=>['Winston + Ana dive','Winston + Sombra hack dive'],
     'dangerousComps'=>['Zarya + Reaper brawl','Zarya + Graviton + Hanzo combo']],

    ['hero'=>'Winston','counteredBy'=>'Reaper','severity'=>3,
     'counterTips'=>'Reaper\'s shotguns destroy Winston\'s relatively low HP pool fast and his lifesteal outheals Winston\'s tesla damage. Avoid diving into a Reaper without bubble. Use Primal Rage to escape if Reaper corners you.',
     'teammateHelp'=>'Ana anti-heal grenade stops Reaper lifesteal. Kiriko suzu saves Winston from burst. Brigitte stun peels Reaper off Winston after a dive.',
     'goodComps'=>['Winston + Ana dive','Winston + Kiriko dive'],
     'dangerousComps'=>['Reaper + Zarya brawl','Reaper + Roadhog sustain brawl']],

    // ══════════════════════════════════════════════════════════
    //  WRECKING BALL
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Wrecking Ball','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables all of Wrecking Ball\'s mobility — Grappling Claw, Minefield, and Roll mode. A hacked Ball is stuck in Walker form with no escape. Drop mines before entering a zone where Sombra is suspected.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Wrecking Ball instantly. Ana sleep dart pins Sombra before hack. Wrecking Ball\'s own sensors ironically help detect Sombra approach.',
     'goodComps'=>['Wrecking Ball + Kiriko dive','Wrecking Ball + Ana dive'],
     'dangerousComps'=>['Sombra + Reaper counter-dive','Sombra EMP + full dive combo']],

    ['hero'=>'Wrecking Ball','counteredBy'=>'Ana','severity'=>2,
     'counterTips'=>'Ana sleep dart catches Wrecking Ball mid-swing and her anti-heal grenade prevents his passive HP regeneration. Stay unpredictable with swing paths to dodge sleep dart. Use Adaptive Shield frequently to build HP before she grenades.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-heal on Ball. Lucio speedboost covers Ball\'s disengages. Moira orb healing offsets anti-heal somewhat.',
     'goodComps'=>['Wrecking Ball + Kiriko','Wrecking Ball + Lucio speed'],
     'dangerousComps'=>['Ana + Kiriko sustain anti-dive','Ana + Brigitte anti-dive hold']],

    // ══════════════════════════════════════════════════════════
    //  ZARYA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Zarya','counteredBy'=>'Sombra','severity'=>3,
     'counterTips'=>'Sombra hack disables Zarya\'s bubbles entirely — her only source of energy and protection. A de-bubbled low-energy Zarya is much easier to kill. Hack Zarya right before a fight starts to remove her peak damage potential.',
     'teammateHelp'=>'Kiriko suzu cleanses hack on Zarya. Wrecking Ball sensor detects Sombra. Winston dive keeps Sombra from reaching Zarya\'s backline.',
     'goodComps'=>['Zarya + Ana Graviton combo','Zarya + Reaper brawl'],
     'dangerousComps'=>['Sombra + Tracer double dive hack','Sombra EMP + full dive combo']],

    ['hero'=>'Zarya','counteredBy'=>'Ana','severity'=>2,
     'counterTips'=>'Ana anti-heal grenade prevents Zarya from regenerating HP between bubbles. Her high damage also brings Zarya\'s HP down during the non-bubble window. Sleep dart can interrupt Graviton Surge if timed perfectly.',
     'teammateHelp'=>'Kiriko suzu cleanses anti-grenade on Zarya. Moira orb partially offsets anti-heal. Lucio speedboost helps Zarya disengage from Ana sightlines.',
     'goodComps'=>['Zarya + Reaper brawl','Zarya + Graviton + Kiriko'],
     'dangerousComps'=>['Ana + Kiriko sustain anti-dive','Ana + Brigitte anti-brawl combo']],

    ['hero'=>'Zarya','counteredBy'=>'Roadhog','severity'=>2,
     'counterTips'=>'Roadhog hook grabs Zarya when she has no bubbles and his shotgun burst deals massive damage. Time the hook between bubble cooldowns. Graviton Surge traps both Roadhog and teammates — save it for grouped enemies.',
     'teammateHelp'=>'Sombra hacks Roadhog, removing his hook. Ana anti-heal negates Roadhog self-heal. Kiriko suzu saves Zarya from hook kill combo.',
     'goodComps'=>['Zarya + Ana Graviton','Zarya + Reaper + Ana brawl'],
     'dangerousComps'=>['Roadhog + Reaper brawl','Roadhog + Moira sustain brawl']],

    // ══════════════════════════════════════════════════════════
    //  ZENYATTA
    // ══════════════════════════════════════════════════════════
    ['hero'=>'Zenyatta','counteredBy'=>'Tracer','severity'=>3,
     'counterTips'=>'Tracer deletes Zenyatta\'s 225 HP in one clip easily. He has no mobility tools. Use Transcendence ult to survive a Pulse Bomb if timed right. Position far behind tanks and rely on teammates for peel.',
     'teammateHelp'=>'Brigitte hard counters Tracer. Cassidy magnetic grenade kills Tracer. Kiriko teleport saves Zenyatta from flanks.',
     'goodComps'=>['Zenyatta + Brigitte brawl','Zenyatta + Orisa anchor hold'],
     'dangerousComps'=>['Tracer + Genji double dive','Tracer + Sombra backline']],

    ['hero'=>'Zenyatta','counteredBy'=>'Widowmaker','severity'=>3,
     'counterTips'=>'Widowmaker one-shots Zenyatta easily — he has the lowest effective HP among supports. Never hold a sightline near a Widowmaker. Keep Discord orb on Widow to help teammates kill her.',
     'teammateHelp'=>'Winston dive on Widow is essential. D.Va fly matrix covers Zenyatta from sniper fire. Echo fly-duel against Widow helps.',
     'goodComps'=>['Zenyatta + Winston + D.Va dive','Zenyatta + Orisa hold'],
     'dangerousComps'=>['Widowmaker + Ashe double sniper','Widowmaker + Hanzo sniper pair']],

    ['hero'=>'Zenyatta','counteredBy'=>'Genji','severity'=>2,
     'counterTips'=>'Genji dashes onto Zenyatta and burst-combos him before he can orb-react. Use Orb of Discord on Genji and throw primary fire as he approaches to deal chip damage. Transcendence can save you from nano blade but not regular Genji burst.',
     'teammateHelp'=>'Brigitte stun stops Genji on Zenyatta. Kiriko teleport saves Zenyatta from Genji. Roadhog hook catches Genji mid-dash.',
     'goodComps'=>['Zenyatta + Brigitte brawl','Zenyatta + Reinhardt hold'],
     'dangerousComps'=>['Genji + Ana nano blade','Genji + Tracer dive combo']],

];

// ── 3. Insert all counters into DB ─────────────────────────────
$insertStmt = $conn->prepare("
    INSERT INTO counters (heroID, counteredByHeroID, counterTips, teammateHelp, goodComps, dangerousComps, severity)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        counterTips = VALUES(counterTips),
        teammateHelp = VALUES(teammateHelp),
        goodComps = VALUES(goodComps),
        dangerousComps = VALUES(dangerousComps),
        severity = VALUES(severity)
");

// We need a unique key for upsert — add this to the table if not exists:
// ALTER TABLE counters ADD UNIQUE KEY uq_matchup (heroID, counteredByHeroID);

$inserted = 0;
$skipped  = 0;
$errors   = [];

foreach ($counters as $c) {
    $heroID            = hid($c['hero'], $heroMap);
    $counteredByHeroID = hid($c['counteredBy'], $heroMap);

    if (!$heroID || !$counteredByHeroID) {
        $errors[] = "Hero not found in DB: '{$c['hero']}' or '{$c['counteredBy']}' — skipped.";
        $skipped++;
        continue;
    }

    $goodComps      = json_encode($c['goodComps']);
    $dangerousComps = json_encode($c['dangerousComps']);

    $insertStmt->bind_param(
        "iissssi",
        $heroID,
        $counteredByHeroID,
        $c['counterTips'],
        $c['teammateHelp'],
        $goodComps,
        $dangerousComps,
        $c['severity']
    );

    if ($insertStmt->execute()) {
        $inserted++;
    } else {
        $errors[] = "DB error for {$c['hero']} vs {$c['counteredBy']}: " . $insertStmt->error;
    }
}

$insertStmt->close();
$conn->close();

// ── 4. Summary output ─────────────────────────────────────────
echo "<!DOCTYPE html><html><head><title>Counter Import</title>
<style>
  body { font-family: monospace; background: #0a0c12; color: #ccc; padding: 32px; }
  h2   { color: #ff821e; }
  .ok  { color: #5cb87a; }
  .err { color: #dc3a3a; }
  .skip{ color: #e69620; }
</style></head><body>";

echo "<h2>⚔ Athena-Log Counter Import</h2>";
echo "<p class='ok'>✔ Inserted / updated: <strong>{$inserted}</strong> counter relationships</p>";

if ($skipped > 0) {
    echo "<p class='skip'>⚠ Skipped (hero not found in DB): <strong>{$skipped}</strong></p>";
}

if (!empty($errors)) {
    echo "<hr><p class='err'>Errors:</p><ul>";
    foreach ($errors as $e) {
        echo "<li class='err'>" . htmlspecialchars($e) . "</li>";
    }
    echo "</ul>";
}

echo "<br><p style='color:rgba(255,255,255,0.4);font-size:11px;'>
  Run <code>import_counters.php</code> again at any time — it upserts, so it won't duplicate.<br>
  Remember to add the unique key if you haven't:<br>
  <code>ALTER TABLE counters ADD UNIQUE KEY uq_matchup (heroID, counteredByHeroID);</code>
</p>";

echo "</body></html>";
?>
