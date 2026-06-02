
let allHeroes = [];
let currentHeroID = null;

// Nav active state
var currentPath = window.location.pathname;
document.querySelectorAll('.navItem').forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});

async function init() {
    await fetchHeroes();
}

async function fetchHeroes() {
    try {
        const res = await fetch('./get_heroes.php');
        allHeroes = await res.json();
        renderHeroRoster(allHeroes);

        if (allHeroes.length > 0) {
            selectHero(allHeroes[0].heroID);
        }
    } catch (err) {
        console.error('Failed to load heroes:', err);
    }
}

// LEFT: Heroes
function renderHeroRoster(heroes) {
    const container = document.getElementById('heroRoster');
    if (!container) return;

    const roles = ['tank', 'damage', 'support'];
    container.innerHTML = '';

    const searchInput = document.getElementById('heroSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase();
            filterRoster(q);
        });
    }

    roles.forEach(role => {
        const roleHeroes = heroes.filter(h => h.role.toLowerCase() === role);
        if (!roleHeroes.length) return;

        const section = document.createElement('div');
        section.className = 'rosterSection';
        section.dataset.role = role;

        const label = document.createElement('div');
        label.className = 'rosterRoleLabel';
        label.textContent = role.toUpperCase();
        section.appendChild(label);

        const grid = document.createElement('div');
        grid.className = 'rosterGrid';

        roleHeroes.forEach(hero => {
            const item = document.createElement('div');
            item.className = 'rosterHero';
            item.dataset.heroId = hero.heroID;
            item.dataset.heroName = hero.name.toLowerCase();
            item.title = hero.name;
            item.style.backgroundImage = `url(${hero.portrait})`;

            item.addEventListener('click', () => selectHero(hero.heroID));
            grid.appendChild(item);
        });

        section.appendChild(grid);
        container.appendChild(section);
    });
}

function filterRoster(query) {
    document.querySelectorAll('.rosterHero').forEach(el => {
        const match = el.dataset.heroName.includes(query);
        el.style.display = match ? '' : 'none';
    });
    document.querySelectorAll('.rosterSection').forEach(section => {
        const anyVisible = [...section.querySelectorAll('.rosterHero')]
            .some(el => el.style.display !== 'none');
        section.style.display = anyVisible ? '' : 'none';
    });
}

// Hero select
function selectHero(heroID) {
    // Force heroID to be an integer to eliminate string vs. number type mismatches
    heroID = parseInt(heroID);

    if (currentHeroID === heroID) return;
    currentHeroID = heroID;

    // Highlight active hero in roster
    document.querySelectorAll('.rosterHero').forEach(el => {
        el.classList.toggle('rosterHeroActive', parseInt(el.dataset.heroId) === heroID);
    });

    // Parse the array item ID as an integer to guarantee a clean type match
    const hero = allHeroes.find(h => parseInt(h.heroID) === heroID);
    if (!hero) return;

    // Update the selected hero header
    const nameEl = document.getElementById('selectedHeroName');
    const roleEl = document.getElementById('selectedHeroRole');
    const avatarEl = document.getElementById('selectedHeroAvatar');
    const bgEl = document.getElementById('counterPanelBg');

    if (nameEl) nameEl.textContent = hero.name;
    if (roleEl) roleEl.textContent = hero.role.toUpperCase();
    if (avatarEl) avatarEl.style.backgroundImage = `url(${hero.portrait})`;
    if (bgEl) bgEl.style.backgroundImage = `url(${hero.screenshot || hero.portrait})`;

    fetchCounters(heroID);
}

// Counter data
async function fetchCounters(heroID) {
    const panel = document.getElementById('counterList');
    const empty = document.getElementById('emptyState');
    if (panel) panel.innerHTML = '<div class="counterLoading">Loading counters…</div>';
    if (empty) empty.style.display = 'none';

    try {
        const res = await fetch(`./get_counters.php?heroID=${heroID}`);
        const counters = await res.json();

        if (!counters || counters.length === 0) {
            if (panel) panel.innerHTML = '';
            if (empty) empty.style.display = 'flex';
            return;
        }

        renderCounters(counters);
    } catch (err) {
        console.error('Failed to load counters:', err);
        if (panel) panel.innerHTML = '<div class="counterLoading">Error loading counters.</div>';
    }
}

function renderCounters(counters) {
    const panel = document.getElementById('counterList');
    if (!panel) return;
    panel.innerHTML = '';

    counters.forEach((c, i) => {
        const severityLabel = ['', 'Soft Counter', 'Hard Counter', 'Extreme Threat'][c.severity] || 'Counter';
        const severityClass = ['', 'sevSoft', 'sevHard', 'sevExtreme'][c.severity] || 'sevHard';

        const card = document.createElement('div');
        card.className = 'counterCard';
        card.style.animationDelay = `${i * 60}ms`;

        card.innerHTML = `
            <div class="counterCardHeader">
                <div class="counterPortrait" style="background-image: url(${c.counteredByPortrait})"></div>
                <div class="counterMeta">
                    <div class="counterHeroName">${c.counteredByName}</div>
                    <div class="counterRole">${(c.counteredByRole || '').toUpperCase()}</div>
                </div>
                <div class="severityBadge ${severityClass}">${severityLabel}</div>
                <div class="counterChevron">▸</div>
            </div>
            <div class="counterCardBody">
                <div class="counterSection">
                    <div class="counterSectionTitle">How to counter them</div>
                    <div class="counterSectionText">${c.counterTips}</div>
                </div>
                <div class="counterSection">
                    <div class="counterSectionTitle">How teammates can help</div>
                    <div class="counterSectionText">${c.teammateHelp}</div>
                </div>
                <div class="counterComps">
                    <div class="compBlock compGood">
                        <div class="compTitle">✦ Good Comps With This Hero</div>
                        <div class="compList">${renderComps(c.goodComps)}</div>
                    </div>
                    <div class="compBlock compDanger">
                        <div class="compTitle">✦ Dangerous Enemy Comps</div>
                        <div class="compList">${renderComps(c.dangerousComps)}</div>
                    </div>
                </div>
            </div>
        `;

        // Toggle on header click
        const header = card.querySelector('.counterCardHeader');
        const body = card.querySelector('.counterCardBody');
        const chevron = card.querySelector('.counterChevron');
        header.addEventListener('click', () => {
            const isOpen = card.classList.toggle('counterCardOpen');
            chevron.textContent = isOpen ? '▾' : '▸';
        });

        // Clicking the portrait jumps to that counter hero's counters
        const portrait = card.querySelector('.counterPortrait');
        portrait.addEventListener('click', (e) => {
            e.stopPropagation();
            selectHero(c.counteredByHeroID);
        });
        portrait.title = `View ${c.counteredByName}'s counters`;

        panel.appendChild(card);
    });
}

function renderComps(comps) {
    if (!comps || !comps.length) return '<span class="noData">None listed</span>';
    return comps.map(comp => `<div class="compTag">${comp}</div>`).join('');
}

init();
