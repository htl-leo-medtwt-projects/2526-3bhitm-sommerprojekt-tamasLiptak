async function fetchHeroes() {
    try {
        const response = await fetch('https://overfast-api.tekrop.fr/heroes');
        const heroes = await response.json();
        renderHeroList(heroes);
    } catch (error) {
        console.error("Failed to load heroes:", error);
    }
}

function renderHeroList(heroes) {
    console.log('Heroes received:', heroes);
    console.log('Is array:', Array.isArray(heroes));
    const container = document.getElementById('heroListContainer');
    if (!container) return;

    const roles = ['tank', 'damage', 'support'];
    container.innerHTML = '';

    roles.forEach(role => {
        const roleHeroes = heroes.filter(h => h.role === role);
        console.log(role, roleHeroes.length);
        const roleWrapper = document.createElement('div');
        roleWrapper.className = 'roleSection';
        roleWrapper.innerHTML = `<h3>${role}</h3>`;



        const grid = document.createElement('div');
        grid.className = 'portraitGrid';

        roleHeroes.forEach(hero => {
            const div = document.createElement('div');
            div.className = 'heroPortrait';
            div.style.backgroundImage = `url(${hero.portrait})`;
            div.onclick = () => loadHeroDetails(hero.key);
            grid.appendChild(div);
        });



        roleWrapper.appendChild(grid);
        container.appendChild(roleWrapper);
    });
}

async function loadHeroDetails(heroKey) {
    try {
        const response = await fetch(`https://overfast-api.tekrop.fr/heroes/${heroKey}`);
        const hero = await response.json();

        document.getElementById('selectedHeroName').innerText = hero.name;
        document.getElementById('selectedHeroDesc').innerText = hero.description;

        const bigImg = document.getElementById('heroBigImage');

        if (hero.backgrounds && hero.backgrounds.length > 0) {
            const preferred = hero.backgrounds.find(b => b.sizes.includes('xl+'))
                || hero.backgrounds.find(b => b.sizes.includes('lg'))
                || hero.backgrounds[0];
            bigImg.src = preferred.url;
        } else {
            bigImg.src = hero.portrait;
        }
        const abilityContainer = document.getElementById('abilityContainer');
        abilityContainer.innerHTML = '';

        hero.abilities.forEach(ability => {
            const abBox = document.createElement('div');
            abBox.className = 'abilityBox';
            abBox.style.backgroundImage = `url(${ability.icon})`;
            abBox.title = ability.name;
            abilityContainer.appendChild(abBox);
        });

    } catch (error) {
        console.error("Error fetching hero details:", error);
    }
}

fetchHeroes();

var currentPath = window.location.pathname;

var navLinks = document.querySelectorAll('.navItem');

navLinks.forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});