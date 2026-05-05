async function loadFavorites() {
    try {
        var [mapsRes, heroesRes] = await Promise.all([
            fetch('https://overfast-api.tekrop.fr/maps'),
            fetch('https://overfast-api.tekrop.fr/heroes')
        ]);
        // Maps
        var maps = await mapsRes.json();
        var heroesList = await heroesRes.json();

        var randomMap = maps[Math.floor(Math.random() * maps.length)];
        var gridItems = document.querySelectorAll('.gridItem');
        gridItems[0].innerHTML = `<span>${randomMap.name}</span>`;
        gridItems[0].style.backgroundImage = `url(${randomMap.screenshot})`;
        gridItems[0].style.backgroundSize = 'cover';
        gridItems[0].style.backgroundPosition = 'center';

        // Heroes
        var randomHeroSummary = heroesList[Math.floor(Math.random() * heroesList.length)];

        var heroDetailRes = await fetch(`https://overfast-api.tekrop.fr/heroes/${randomHeroSummary.key}`);
        var heroDetail = await heroDetailRes.json();

        if (heroDetail.backgrounds && heroDetail.backgrounds.length > 0) {
            gridItems[1].innerHTML = `<span>${heroDetail.name}</span>`;
            gridItems[1].style.backgroundImage = `url(${heroDetail.backgrounds[2].url})`;
            gridItems[1].style.backgroundSize = 'cover';
            gridItems[1].style.backgroundPosition = '90%';
        }

        // Skin
        gridItems[2].innerHTML = `<span>Fav Skin</span>`;

    } catch (error) {
        console.error("Error fetching OverFast data:", error);
    }
}

function loadLeaderboard() {
    var container = document.getElementById('leaderboardContainer');
    if (!container) return;

    var mockPlayers = [
        { name: 'Viol2t', mmr: 5000 },
        { name: 'Fleta', mmr: 4978 },
        { name: 'Profit', mmr: 4955 },
        { name: 'Striker', mmr: 4932 },
        { name: 'Proper', mmr: 4910 },
    ];

    container.innerHTML = '';
    mockPlayers.forEach(player => {
        var item = document.createElement('div');
        item.className = 'playerItem';
        item.innerHTML = `
        <div class="pfpSquare">
            <img src="https://ui-avatars.com/api/?name=${player.name}&background=0D1B2A&color=00d2ff&size=55&bold=true&format=png"
                 alt="${player.name}"
                 style="width:100%;height:100%;object-fit:cover;">
        </div>
        <span class="playerName">${player.name}</span>
        <span class="playerMmr">${player.mmr}</span>
    `;
        container.appendChild(item);
    });
}

function handleLogin() {
    var loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        var formData = new FormData(loginForm);

        try {
            var response = await fetch('login.php', {
                method: 'POST',
                body: formData
            });

            var data = await response.json();

            if (data.status === 'success') {
                document.getElementById('loginModal').style.display = 'none';
                if (document.getElementById('accountContent')) {
                    document.getElementById('accountContent').style.display = 'block';
                }
            } else {
                document.getElementById('loginError').style.display = 'block';
            }
        } catch (err) {
            console.error("Database connection failed", err);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadLeaderboard();
    loadFavorites();
    handleLogin();
});