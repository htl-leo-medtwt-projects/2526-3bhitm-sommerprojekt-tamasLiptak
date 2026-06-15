let allHeroes = [];
let allMaps = [];

async function fetchMapsForHeroes() {
    try {
        const response = await fetch('./get_maps.php');
        allMaps = await response.json();
    } catch (error) {
        console.error("Failed to load maps for hero page:", error);
    }
}

async function fetchHeroes() {
    try {
        const response = await fetch('./get_heroes.php');
        allHeroes = await response.json();

        renderHeroList(allHeroes);

        if (allHeroes.length > 0) {
            setTimeout(() => loadHeroDetails(allHeroes[0].heroID), 0);
        }
    } catch (error) {
        console.error("Failed to load heroes from database:", error);
    }
}

function renderHeroList(heroes) {
    console.log('Heroes received from DB:', heroes);
    const container = document.getElementById('heroListContainer');
    if (!container) return;

    const roles = ['tank', 'damage', 'support'];
    container.innerHTML = '';

    roles.forEach(role => {
        const roleHeroes = heroes.filter(h => h.role.toLowerCase() === role);

        const roleWrapper = document.createElement('div');
        roleWrapper.className = 'roleSection';
        roleWrapper.innerHTML = `<h3>${role.toUpperCase()}</h3>`;

        const grid = document.createElement('div');
        grid.className = 'portraitGrid';

        roleHeroes.forEach(hero => {
            const div = document.createElement('div');
            div.className = 'heroPortrait';
            div.style.backgroundImage = `url(${hero.portrait})`;
            div.title = hero.name;

            div.onclick = () => loadHeroDetails(hero.heroID);
            grid.appendChild(div);
        });

        roleWrapper.appendChild(grid);
        container.appendChild(roleWrapper);
    });
}

function loadHeroDetails(heroID) {
    const hero = allHeroes.find(h => h.heroID === heroID);
    if (!hero) return;

    document.getElementById('selectedHeroName').innerText = hero.name;
    document.getElementById('selectedHeroDesc').innerText = hero.description || '';

    const bigImg = document.getElementById('heroBigImage');
    if (bigImg) {
        bigImg.src = hero.screenshot ? hero.screenshot : hero.portrait;
    }

    const mapSuggestions = document.querySelector('.mapSuggestions');
    mapSuggestions.innerHTML = '';

    const mapsToShow = (hero.best_maps && hero.best_maps.length > 0)
        ? hero.best_maps.slice(0, 3)
        : [];

    if (mapsToShow.length > 0) {
        const matchedMaps = mapsToShow.map(mapName =>
            allMaps.find(m => m.name.toLowerCase() === mapName.toLowerCase())
        ).filter(Boolean);

        matchedMaps.forEach(map => {
            const box = document.createElement('div');
            box.className = 'mapBox';
            box.style.backgroundImage = `url(${map.screenshot})`;
            box.title = map.name;
            box.onclick = () => {
                window.location.href = `./maps.php?map=${encodeURIComponent(map.name)}`;
            };
            mapSuggestions.appendChild(box);
        });

        const remaining = 3 - matchedMaps.length;
        for (let i = 0; i < remaining; i++) {
            const empty = document.createElement('div');
            empty.className = 'emptyBox';
            mapSuggestions.appendChild(empty);
        }
    } else {
        for (let i = 0; i < 3; i++) {
            const empty = document.createElement('div');
            empty.className = 'emptyBox';
            mapSuggestions.appendChild(empty);
        }
    }

    const abilityContainer = document.getElementById('abilityContainer');
    abilityContainer.innerHTML = '';

    if (hero.abilities && hero.abilities.length > 0) {
        hero.abilities.forEach(ability => {
            const abBox = document.createElement('div');
            abBox.className = 'abilityBox';
            abBox.style.backgroundImage = `url(${ability.icon})`;
            abBox.title = ability.name;
            abilityContainer.appendChild(abBox);
        });
    }
}

fetchMapsForHeroes().then(() => fetchHeroes());

var currentPath = window.location.pathname;
var navLinks = document.querySelectorAll('.navItem');

navLinks.forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});