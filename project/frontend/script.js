// ─── Helpers ────────────────────────────────────────────────────────────────

function secsToHours(secs) {
    if (!secs) return '0h';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function getRankColor(division) {
    if (!division) return '#aaa';
    const d = division.toLowerCase();
    if (d.includes('grandmaster')) return '#ff8c00';
    if (d.includes('master')) return '#da70d6';
    if (d.includes('diamond')) return '#00d2ff';
    if (d.includes('platinum')) return '#4dd9ac';
    if (d.includes('gold')) return '#ffd700';
    if (d.includes('silver')) return '#c0c0c0';
    if (d.includes('bronze')) return '#cd7f32';
    return '#aaa';
}

function buildRankBadge(rank) {
    if (!rank) return '<span style="color:#555;font-family:FuturaDemi,sans-serif;font-size:0.65rem;text-transform:uppercase;">Unranked</span>';
    const division = rank.division || '';
    const tier = rank.tier !== undefined ? rank.tier : '';
    const color = getRankColor(division);
    const tierIcon = rank.tier_icon || rank.tierIcon || '';

    let html = `<span style="color:${color};font-family:BigNoodleTooOblique,sans-serif;font-size:1rem;text-transform:uppercase;line-height:1;">`;
    if (tierIcon) {
        html += `<img src="${tierIcon}" style="width:18px;height:18px;vertical-align:middle;margin-right:3px;">`;
    }
    html += `${division}${tier ? ' ' + tier : ''}</span>`;
    return html;
}

function buildEndorsementBadge(endorsement) {
    if (!endorsement) return '';
    const level = endorsement.level || 0;
    const frame = endorsement.frame || '';
    if (frame) {
        return `<img src="${frame}" title="Endorsement ${level}" style="width:28px;height:28px;vertical-align:middle;" onerror="this.replaceWith(document.createTextNode('⭐'+${level}))">`;
    }
    return `<span style="color:#ffd700;font-size:13px;">⭐ ${level}</span>`;
}

// ─── Tooltip ────────────────────────────────────────────────────────────────

let activeTooltip = null;

function createTooltip(player) {
    const tip = document.createElement('div');
    tip.className = 'leaderboard-tooltip';

    const stats = player.allStats || {};
    const combat = stats.combat || {};
    const game = stats.game || {};
    const best = stats.best || {};
    const average = stats.average || {};

    const elims = combat.eliminations ?? '—';
    const deaths = combat.deaths ?? game.deaths ?? '—';
    const finalBlows = combat.final_blows ?? '—';
    const gamesWon = game.games_won ?? '—';
    const gamesPlayed = game.games_played ?? '—';
    const winRate = (gamesWon !== '—' && gamesPlayed && gamesPlayed > 0)
        ? Math.round((gamesWon / gamesPlayed) * 100) + '%'
        : '—';
    const kd = (elims !== '—' && deaths !== '—' && deaths > 0)
        ? (elims / deaths).toFixed(2)
        : '—';
    const bestKS = best.kill_streak_best ?? '—';
    const dmg = combat.hero_damage_done
        ? (combat.hero_damage_done / 1000).toFixed(1) + 'k'
        : '—';
    const assists = stats.assists || {};
    const healing = assists.healing_done
        ? (assists.healing_done / 1000).toFixed(1) + 'k'
        : '—';
    const timeHours = secsToHours(player.heroTimeSecs);

    const rankColor = getRankColor(player.rank?.division || '');
    const heroImg = player.heroPortrait
        ? `<img src="${player.heroPortrait}" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">`
        : `<span style="font-size:28px;">🎮</span>`;

    tip.innerHTML = `
        <div class="tip-header">
            <div class="tip-hero-thumb">${heroImg}</div>
            <div class="tip-header-text">
                <div class="tip-btag">${player.battletag}</div>
                <div class="tip-hero-name">${player.heroName ?? 'Unknown'} &middot; ${timeHours}</div>
                <div class="tip-rank-row">
                    ${buildRankBadge(player.rank)}
                    <span class="tip-endorsement">${buildEndorsementBadge(player.endorsement)}</span>
                </div>
            </div>
        </div>
        <div class="tip-stats-grid">
            <div class="tip-stat">
                <span class="tip-stat-label">K/D</span>
                <span class="tip-stat-value" style="color:#00d2ff;">${kd}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Eliminations</span>
                <span class="tip-stat-value">${typeof elims === 'number' ? elims.toLocaleString() : elims}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Deaths</span>
                <span class="tip-stat-value">${typeof deaths === 'number' ? deaths.toLocaleString() : deaths}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Final Blows</span>
                <span class="tip-stat-value">${typeof finalBlows === 'number' ? finalBlows.toLocaleString() : finalBlows}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Win Rate</span>
                <span class="tip-stat-value" style="color:#4dd9ac;">${winRate}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Best Kill Streak</span>
                <span class="tip-stat-value" style="color:#ffd700;">${bestKS}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Dmg Done All Time</span>
                <span class="tip-stat-value">${dmg}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Healing Done All Time</span>
                <span class="tip-stat-value" style="color:#ff6eb4;">${healing}</span>
            </div>
        </div>
        ${player.profilePrivate ? '<div class="tip-private">⚠ Profile is private — limited data</div>' : ''}
    `;
    return tip;
}

function positionTooltip(tip, anchor) {
    document.body.appendChild(tip);
    const rect = anchor.getBoundingClientRect();
    const tipW = tip.offsetWidth;
    const tipH = tip.offsetHeight;
    const scrollY = window.scrollY;
    const viewW = window.innerWidth;

    let left = rect.right + 12;
    let top = rect.top + scrollY + (rect.height / 2) - (tipH / 2);

    // Flip to left if overflows right
    if (left + tipW > viewW - 12) {
        left = rect.left - tipW - 12;
    }
    // Keep vertically in viewport
    if (top < scrollY + 8) top = scrollY + 8;

    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
}

// ─── Leaderboard ────────────────────────────────────────────────────────────

async function loadLeaderboard() {
    const container = document.getElementById('leaderboardContainer');
    if (!container) return;

    try {
        const res = await fetch('./leaderboard.php');
        const players = await res.json();

        container.innerHTML = '';

        if (!players.length) {
            container.innerHTML = '<p style="color:#aaa;font-family:FuturaDemi;padding:20px;">No players with a Battle.net tag yet.</p>';
            return;
        }

        players.forEach((player, index) => {
            const item = document.createElement('div');
            item.className = 'playerItem';

            // Hero portrait or fallback avatar
            const portraitSrc = player.heroPortrait
                ? player.heroPortrait
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(player.battletag.split('#')[0])}&background=0D1B2A&color=00d2ff&size=55&bold=true&format=png`;

            // Division label
            const division = player.rank?.division ?? null;
            const tier = player.rank?.tier !== undefined ? ` ${player.rank.tier}` : '';
            const rankLabel = division ? `${division}${tier}` : 'Unranked';
            const rankColor = getRankColor(division);

            // Endorsement
            const endorseLvl = player.endorsement?.level ?? null;

            const tierIcon = player.rank?.tier_icon || player.rank?.tierIcon || '';

            item.style.setProperty('--rank-color', rankColor);
            item.innerHTML = `
                <div class="pi-accent"></div>
                <div class="pi-portrait">
                    <img src="${portraitSrc}" alt="${player.heroName ?? player.battletag}">
                </div>
                <div class="pi-info">
                    <span class="pi-btag">${player.battletag}</span>
                    <span class="pi-hero">${player.heroName ?? 'Unknown hero'}</span>
                </div>
                <div class="pi-right">
                    <div class="pi-rank">
                        ${tierIcon ? `<img src="${tierIcon}" alt="${rankLabel}">` : ''}
                        <span class="pi-rank-label" style="color:${rankColor};">${rankLabel}</span>
                    </div>
                    <div class="pi-bottom">
                        ${endorseLvl !== null ? `<span class="pi-endorsement">⭐ ${endorseLvl}</span>` : ''}
                        <span class="pi-index">#${index + 1}</span>
                    </div>
                </div>
            `;

            // ── Tooltip events ──
            item.addEventListener('mouseenter', () => {
                if (activeTooltip) {
                    activeTooltip.remove();
                    activeTooltip = null;
                }
                const tip = createTooltip(player);
                positionTooltip(tip, item);
                requestAnimationFrame(() => tip.classList.add('tip-visible'));
                activeTooltip = tip;
            });

            item.addEventListener('mouseleave', () => {
                if (activeTooltip) {
                    activeTooltip.classList.remove('tip-visible');
                    const dying = activeTooltip;
                    activeTooltip = null;
                    setTimeout(() => dying.remove(), 200);
                }
            });

            container.appendChild(item);
        });

    } catch (error) {
        console.error('Error loading leaderboard:', error);
        container.innerHTML = '<p style="color:#ff4444;font-family:FuturaDemi;padding:20px;">Failed to load leaderboard.</p>';
    }
}

// ─── This Week's Favourites — Voting ────────────────────────────────────────

const VOTE_API = './pages/vote.php';
const HEROES_API = './pages/get_heroes.php';
const MAPS_API = './pages/get_maps.php';
const VOTES_API = './pages/get_votes.php';
const SKINS_API = './pages/get_skins.php';

let allHeroes = [];
let allMaps = [];
let skinStructure = {};
let userVotes = { hero: null, map: null, skin: null };
let isLoggedIn = false;
let overlayTimeout = null;

async function initFavourites() {
    const [heroesRes, mapsRes, votesRes, skinStructRes] = await Promise.all([
        fetch(HEROES_API),
        fetch(MAPS_API),
        fetch(VOTES_API),
        fetch(SKINS_API + '?mode=structure'),
    ]);

    allHeroes = await heroesRes.json();
    allMaps = await mapsRes.json();
    skinStructure = await skinStructRes.json();
    const votes = await votesRes.json();

    userVotes = votes.user_votes || { hero: null, map: null, skin: null };
    isLoggedIn = votes.logged_in || false;

    renderTopItem('map', votes.top_map);
    renderTopItem('hero', votes.top_hero);
    renderTopItem('skin', votes.top_skin);
    attachHoverListeners();
}

// ── Render the winning item into the grid box ────────────────────────────────
function renderTopItem(type, data) {
    const index = type === 'map' ? 0 : type === 'hero' ? 1 : 2;
    const box = document.querySelectorAll('.gridItem')[index];
    if (!box) return;

    if (data) {
        box.style.backgroundImage = `url(${data.image_url || data.screenshot})`;
        box.style.backgroundSize = 'cover';
        box.style.backgroundPosition = type === 'hero' ? '80% center' : type === 'skin' ? 'left 40%' : 'center'; const subLabel = type === 'skin' && data.hero_name
            ? `<span class="gridItem-sub">${data.hero_name}</span>` : '';
        box.innerHTML = `
            <div class="gridItem-label">
                <span class="gridItem-name">${data.name}</span>
                ${subLabel}
                <span class="gridItem-votes">${data.votes} vote${data.votes == 1 ? '' : 's'}</span>
            </div>`;
    } else {
        box.style.backgroundImage = '';
        box.innerHTML = `<span class="gridItem-name">${type.charAt(0).toUpperCase() + type.slice(1)}</span>`;
    }
    box.dataset.voteType = type;
}

// ── Hover listeners ──────────────────────────────────────────────────────────
function attachHoverListeners() {
    const gridItems = document.querySelectorAll('.gridItem');
    if (gridItems[0]) setupHover(gridItems[0], 'map');
    if (gridItems[1]) setupHover(gridItems[1], 'hero');
    if (gridItems[2]) setupHover(gridItems[2], 'skin');
}

function setupHover(box, type) {
    box.addEventListener('mouseenter', () => {
        clearTimeout(overlayTimeout);
        showOverlay(box, type);
    });
    box.addEventListener('mouseleave', (e) => {
        const overlay = document.getElementById('vote-overlay');
        if (overlay && overlay.contains(e.relatedTarget)) return;
        overlayTimeout = setTimeout(() => closeOverlay(), 150);
    });
}

// ── Build & show the overlay ─────────────────────────────────────────────────
function showOverlay(anchor, type) {
    closeOverlay(true);

    const overlay = document.createElement('div');
    overlay.id = 'vote-overlay';
    overlay.className = 'vote-overlay';

    overlay.addEventListener('mouseenter', () => clearTimeout(overlayTimeout));
    overlay.addEventListener('mouseleave', () => {
        overlayTimeout = setTimeout(() => closeOverlay(), 150);
    });

    if (!isLoggedIn) {
        overlay.innerHTML = `<p class="vote-login-notice">Log in to vote for this week's favourites.</p>`;
    } else if (type === 'hero') {
        overlay.appendChild(buildHeroGrid());
    } else if (type === 'map') {
        overlay.appendChild(buildMapGrid());
    } else {
        overlay.appendChild(buildSkinTree());
    }

    document.body.appendChild(overlay);

    const rect = anchor.getBoundingClientRect();
    overlay.style.position = 'fixed';
    overlay.style.left = `${Math.max(0, rect.right - 450)}px`;
    overlay.style.bottom = `${window.innerHeight - rect.top + 10}px`;

    requestAnimationFrame(() => overlay.classList.add('vote-overlay--visible'));
}

function closeOverlay(immediate = false) {
    const overlay = document.getElementById('vote-overlay');
    if (!overlay) return;
    if (immediate) { overlay.remove(); return; }
    overlay.classList.remove('vote-overlay--visible');
    overlay.addEventListener('transitionend', () => overlay.remove(), { once: true });
}

// ── Hero grid grouped by role ────────────────────────────────────────────────
function buildHeroGrid() {
    const roles = ['tank', 'damage', 'support'];
    const frag = document.createDocumentFragment();

    roles.forEach(role => {
        const group = allHeroes.filter(h => h.role === role);
        if (!group.length) return;

        const section = document.createElement('div');
        section.className = 'vote-role-section';

        const title = document.createElement('h4');
        title.className = 'vote-role-title vote-group-toggle';
        title.textContent = role.charAt(0).toUpperCase() + role.slice(1);
        title.dataset.open = 'true';

        const grid = document.createElement('div');
        grid.className = 'vote-grid';

        group.forEach(hero => {
            const card = document.createElement('div');
            card.className = 'vote-card' + (userVotes.hero === hero.name ? ' vote-card--active' : '');
            card.innerHTML = `
                <img src="${hero.portrait}" alt="${hero.name}" class="vote-card-img">
                <span class="vote-card-name">${hero.name}</span>`;
            card.addEventListener('click', () => castVote('hero', hero.name, card));
            grid.appendChild(card);
        });

        title.addEventListener('click', () => toggleGroup(title, grid));
        section.appendChild(title);
        section.appendChild(grid);
        frag.appendChild(section);
    });

    return frag;
}

// ── Map grid ─────────────────────────────────────────────────────────────────
function buildMapGrid() {
    const frag = document.createDocumentFragment();
    const grid = document.createElement('div');
    grid.className = 'vote-grid';

    allMaps.forEach(map => {
        const card = document.createElement('div');
        card.className = 'vote-card vote-card--map' + (userVotes.map === map.name ? ' vote-card--active' : '');
        card.innerHTML = `
            <img src="${map.screenshot}" alt="${map.name}" class="vote-card-img vote-card-img--map">
            <span class="vote-card-name">${map.name}</span>`;
        card.addEventListener('click', () => castVote('map', map.name, card));
        grid.appendChild(card);
    });

    frag.appendChild(grid);
    return frag;
}


// ── Skin tree: role → hero → source → rarity → lazy-loaded cards ─────────────
function buildSkinTree() {
    const roles = ['tank', 'damage', 'support'];
    const frag = document.createDocumentFragment();

    roles.forEach(role => {
        const heroes = allHeroes.filter(h => h.role === role);
        if (!heroes.length) return;

        const roleSection = document.createElement('div');
        roleSection.className = 'vote-role-section';

        const roleTitle = document.createElement('h4');
        roleTitle.className = 'vote-role-title vote-group-toggle';
        roleTitle.textContent = role.charAt(0).toUpperCase() + role.slice(1);
        roleTitle.dataset.open = 'false';

        const heroContainer = document.createElement('div');
        heroContainer.className = 'vote-rarity-container vote-group-hidden';

        heroes.forEach(hero => {
            const heroSection = document.createElement('div');
            heroSection.className = 'vote-rarity-section';

            const heroTitle = document.createElement('h5');
            heroTitle.className = 'vote-rarity-title vote-group-toggle';
            heroTitle.dataset.open = 'false';
            heroTitle.dataset.loaded = 'false';
            heroTitle.dataset.hero = hero.name;
            heroTitle.innerHTML = `
                <img src="${hero.portrait}" alt="${hero.name}" class="vote-hero-thumb">
                ${hero.name}`;

            const sourceContainer = document.createElement('div');
            sourceContainer.className = 'vote-rarity-container vote-group-hidden';

            heroTitle.addEventListener('click', () => {
                if (heroTitle.dataset.loaded === 'false') {
                    loadHeroSkinStructure(hero.name, sourceContainer, heroTitle);
                } else {
                    toggleGroup(heroTitle, sourceContainer);
                }
            });

            heroSection.appendChild(heroTitle);
            heroSection.appendChild(sourceContainer);
            heroContainer.appendChild(heroSection);
        });

        roleTitle.addEventListener('click', () => toggleGroup(roleTitle, heroContainer));
        roleSection.appendChild(roleTitle);
        roleSection.appendChild(heroContainer);
        frag.appendChild(roleSection);
    });

    return frag;
}

// ── Load sources for a hero ───────────────────────────────────────────────────
async function loadHeroSkinStructure(heroName, container, toggle) {
    container.innerHTML = `<p class="vote-loading">Loading...</p>`;
    toggleGroup(toggle, container);
    toggle.dataset.loaded = 'true';

    try {
        const params = new URLSearchParams({ mode: 'structure', hero: heroName });
        const res = await fetch(`${SKINS_API}?${params}`);
        const structure = await res.json();

        container.innerHTML = '';

        Object.entries(structure).forEach(([source, rarities]) => {
            const sourceSection = document.createElement('div');
            sourceSection.className = 'vote-rarity-section';

            const sourceTitle = document.createElement('h5');
            sourceTitle.className = 'vote-rarity-title vote-group-toggle';
            sourceTitle.textContent = source;
            sourceTitle.dataset.open = 'false';

            const rarityContainer = document.createElement('div');
            rarityContainer.className = 'vote-rarity-container vote-group-hidden';

            Object.entries(rarities).forEach(([rarity, count]) => {
                const raritySection = document.createElement('div');
                raritySection.className = 'vote-rarity-section';

                const rarityTitle = document.createElement('h5');
                rarityTitle.className = 'vote-rarity-title vote-group-toggle';
                rarityTitle.textContent = `${rarity} (${count})`;
                rarityTitle.dataset.open = 'false';
                rarityTitle.dataset.loaded = 'false';

                const skinGrid = document.createElement('div');
                skinGrid.className = 'vote-grid vote-group-hidden';

                rarityTitle.addEventListener('click', () => {
                    if (rarityTitle.dataset.loaded === 'false') {
                        loadSkins(heroName, source, rarity, skinGrid, rarityTitle);
                    } else {
                        toggleGroup(rarityTitle, skinGrid);
                    }
                });

                raritySection.appendChild(rarityTitle);
                raritySection.appendChild(skinGrid);
                rarityContainer.appendChild(raritySection);
            });

            sourceTitle.addEventListener('click', () => toggleGroup(sourceTitle, rarityContainer));
            sourceSection.appendChild(sourceTitle);
            sourceSection.appendChild(rarityContainer);
            container.appendChild(sourceSection);
        });

    } catch (err) {
        container.innerHTML = `<p class="vote-loading">Failed to load.</p>`;
    }
}

// ── Lazy load skins for a hero + source + rarity ─────────────────────────────
async function loadSkins(heroName, source, rarity, grid, toggle) {
    grid.innerHTML = `<p class="vote-loading">Loading...</p>`;
    toggleGroup(toggle, grid);
    toggle.dataset.loaded = 'true';

    try {
        const params = new URLSearchParams({ mode: 'skins', hero: heroName, source, rarity });
        const res = await fetch(`${SKINS_API}?${params}`);
        const skins = await res.json();

        grid.innerHTML = '';

        skins.forEach(skin => {
            const card = document.createElement('div');
            card.className = 'vote-card vote-card--map' + (userVotes.skin === skin.name ? ' vote-card--active' : '');
            card.innerHTML = `
                <img src="${skin.image_url}" alt="${skin.name}" class="vote-card-img vote-card-img--map">
                <span class="vote-card-name">${skin.name}</span>`;
            card.addEventListener('click', () => castVote('skin', `${heroName}|${skin.name}`, card));
            attachSkinPreview(card, skin, heroName);
            grid.appendChild(card);
        });
    } catch (err) {
        grid.innerHTML = `<p class="vote-loading">Failed to load.</p>`;
    }
}

// ── Skin preview tooltip ─────────────────────────────────────────────────────

const RARITY_COLORS = {
    Mythic: '#ff9c00',
    Legendary: '#ff6a00',
    Epic: '#9b4dca',
    Rare: '#0070dd',
    Common: '#9d9d9d',
    Unknown: '#555555',
};

function attachSkinPreview(card, skin, heroName) {
    card.addEventListener('mouseenter', (e) => showSkinPreview(e, skin, heroName));
    card.addEventListener('mouseleave', hideSkinPreview);
}

function showSkinPreview(e, skin, heroName) {
    hideSkinPreview();

    const preview = document.createElement('div');
    preview.id = 'skin-preview';
    preview.className = 'skin-preview';

    const rarityColor = RARITY_COLORS[skin.rarity] || RARITY_COLORS.Unknown;

    preview.innerHTML = `
        <img src="${skin.image_url}" alt="${skin.name}" class="skin-preview-img">
        <div class="skin-preview-info" style="border-top: 2px solid ${rarityColor}">
            <span class="skin-preview-name">${skin.name}</span>
            <span class="skin-preview-hero">${heroName}</span>
            <div class="skin-preview-meta">
                <span class="skin-preview-rarity" style="color: ${rarityColor}">${skin.rarity}</span>
                <span class="skin-preview-cost">${skin.cost}</span>
            </div>
        </div>`;

    document.body.appendChild(preview);

    // Position to the right of the card, or left if no space
    const rect = e.currentTarget.getBoundingClientRect();
    const pw = 220;
    const left = rect.right + 8 + pw > window.innerWidth
        ? rect.left - pw - 8
        : rect.right + 8;

    preview.style.position = 'fixed';
    preview.style.left = `${left}px`;
    preview.style.top = `${Math.min(rect.top, window.innerHeight - preview.offsetHeight - 10)}px`;

    requestAnimationFrame(() => preview.classList.add('skin-preview--visible'));
}

function hideSkinPreview() {
    const existing = document.getElementById('skin-preview');
    if (existing) existing.remove();
}

// ── Toggle a collapsible group ───────────────────────────────────────────────
function toggleGroup(toggle, content) {
    const isOpen = toggle.dataset.open === 'true';
    toggle.dataset.open = isOpen ? 'false' : 'true';
    content.classList.toggle('vote-group-hidden', isOpen);
}

// ── Cast a vote ──────────────────────────────────────────────────────────────
async function castVote(type, value, clickedCard) {
    const overlay = document.getElementById('vote-overlay');
    if (overlay) {
        overlay.querySelectorAll('.vote-card').forEach(c => c.classList.remove('vote-card--active'));
    }
    clickedCard.classList.add('vote-card--active');
    userVotes[type] = value;

    try {
        const body = new URLSearchParams({ type, value });
        const res = await fetch(VOTE_API, { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            const votesRes = await fetch(VOTES_API);
            const votesData = await votesRes.json();
            renderTopItem('map', votesData.top_map);
            renderTopItem('hero', votesData.top_hero);
            renderTopItem('skin', votesData.top_skin);
        }
    } catch (err) {
        console.error('Vote failed:', err);
    }
}


// ── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initFavourites);

// ─── Login handler ──────────────────────────────────────────────────────────

function handleLogin() {
    var loginForm = document.getElementById('loginForm');
    if (!loginForm) return;
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        var formData = new FormData(loginForm);
        try {
            var response = await fetch('login.php', { method: 'POST', body: formData });
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
            console.error('Database connection failed', err);
        }
    });
}

// ─── Init ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadLeaderboard();
    handleLogin();
});

var currentPath = window.location.pathname;
var navLinks = document.querySelectorAll('.navItem');
navLinks.forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});
