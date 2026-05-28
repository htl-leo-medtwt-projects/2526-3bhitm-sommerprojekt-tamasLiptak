let allHeroes = [];

async function fetchHeroes() {
    try {
        const response = await fetch('./get_heroes.php');
        allHeroes = await response.json();
        
        renderHeroList(allHeroes);
        
        if (allHeroes.length > 0) {
            loadHeroDetails(allHeroes[0].heroID);
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

fetchHeroes();

var currentPath = window.location.pathname;
var navLinks = document.querySelectorAll('.navItem');

navLinks.forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});