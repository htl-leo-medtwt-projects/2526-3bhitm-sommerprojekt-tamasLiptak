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
    if (d.includes('master'))      return '#da70d6';
    if (d.includes('diamond'))     return '#00d2ff';
    if (d.includes('platinum'))    return '#4dd9ac';
    if (d.includes('gold'))        return '#ffd700';
    if (d.includes('silver'))      return '#c0c0c0';
    if (d.includes('bronze'))      return '#cd7f32';
    return '#aaa';
}

function buildRankBadge(rank) {
    if (!rank) return '<span style="color:#555;font-family:FuturaDemi,sans-serif;font-size:0.65rem;text-transform:uppercase;">Unranked</span>';
    const division = rank.division || '';
    const tier     = rank.tier !== undefined ? rank.tier : '';
    const color    = getRankColor(division);
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

    const stats     = player.allStats || {};
    const combat    = stats.combat || {};
    const game      = stats.game || {};
    const best      = stats.best || {};
    const average   = stats.average || {};

    const elims     = combat.eliminations ?? '—';
    const deaths    = combat.deaths       ?? game.deaths ?? '—';
    const finalBlows= combat.final_blows  ?? '—';
    const gamesWon  = game.games_won      ?? '—';
    const gamesPlayed = game.games_played ?? '—';
    const winRate   = (gamesWon !== '—' && gamesPlayed && gamesPlayed > 0)
        ? Math.round((gamesWon / gamesPlayed) * 100) + '%'
        : '—';
    const kd        = (elims !== '—' && deaths !== '—' && deaths > 0)
        ? (elims / deaths).toFixed(2)
        : '—';
    const bestKS    = best.kill_streak_best ?? '—';
    const dmg       = combat.hero_damage_done
        ? (combat.hero_damage_done / 1000).toFixed(1) + 'k'
        : '—';
    const healing   = combat.healing_done
        ? (combat.healing_done / 1000).toFixed(1) + 'k'
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
                <span class="tip-stat-label">Best Streak</span>
                <span class="tip-stat-value" style="color:#ffd700;">${bestKS}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Dmg Done</span>
                <span class="tip-stat-value">${dmg}</span>
            </div>
            <div class="tip-stat">
                <span class="tip-stat-label">Healing Done</span>
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
    const tipW  = tip.offsetWidth;
    const tipH  = tip.offsetHeight;
    const scrollY = window.scrollY;
    const viewW = window.innerWidth;

    let left = rect.right + 12;
    let top  = rect.top + scrollY + (rect.height / 2) - (tipH / 2);

    // Flip to left if overflows right
    if (left + tipW > viewW - 12) {
        left = rect.left - tipW - 12;
    }
    // Keep vertically in viewport
    if (top < scrollY + 8) top = scrollY + 8;

    tip.style.left = left + 'px';
    tip.style.top  = top  + 'px';
}

// ─── Tooltip Styles ─────────────────────────────────────────────────────────

function injectTooltipStyles() {
    if (document.getElementById('leaderboard-tip-styles')) return;
    const style = document.createElement('style');
    style.id = 'leaderboard-tip-styles';
    style.textContent = `
        /* ── Tooltip container: matches .topList glassmorphism + skewed shape ── */
        .leaderboard-tooltip {
            position: absolute;
            z-index: 9999;
            width: 300px;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(4px);
            border-left: 3px solid #00d2ff;
            box-shadow: 0 0 20px rgba(0, 210, 255, 0.12), 0 10px 40px rgba(0,0,0,0.85);
            pointer-events: none;
            opacity: 0;
            transform: translateX(-10px);
            transition: opacity 0.15s ease, transform 0.15s ease;
            clip-path: polygon(0% 0%, 100% 0%, 97% 100%, 0% 100%);
            color: white;
        }
        .leaderboard-tooltip.tip-visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* ── Header: hero portrait + name/rank block ── */
        .tip-header {
            display: flex;
            align-items: stretch;
            background: rgba(0, 210, 255, 0.06);
            border-bottom: 1px solid rgba(0, 210, 255, 0.12);
        }
        .tip-hero-thumb {
            flex-shrink: 0;
            width: 68px;
            height: 68px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 210, 255, 0.05);
        }
        .tip-hero-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tip-header-text {
            flex: 1;
            padding: 9px 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 3px;
            min-width: 0;
        }
        .tip-btag {
            font-family: BigNoodleTooOblique, sans-serif;
            font-size: 1.35rem;
            color: #ffffff;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1;
        }
        .tip-hero-name {
            font-family: FuturaDemi, sans-serif;
            font-size: 0.65rem;
            color: #00d2ff;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }
        .tip-rank-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 3px;
        }
        .tip-rank-row span,
        .tip-endorsement span {
            font-family: FuturaDemi, sans-serif;
            font-size: 0.65rem;
        }
        .tip-endorsement {
            display: flex;
            align-items: center;
        }

        /* ── Stats grid: tight 2-col layout ── */
        .tip-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: rgba(0, 210, 255, 0.05);
        }
        .tip-stat {
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.75);
            padding: 8px 14px;
        }
        .tip-stat-label {
            font-family: FuturaDemi, sans-serif;
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.3);
            margin-bottom: 1px;
        }
        .tip-stat-value {
            font-family: BigNoodleTooOblique, sans-serif;
            font-size: 1.45rem;
            color: #ffffff;
            line-height: 1;
        }

        /* ── Private profile warning ── */
        .tip-private {
            font-family: Pulse, sans-serif;
            font-size: 0.65rem;
            color: #ff8c00;
            text-align: center;
            padding: 6px 14px;
            background: rgba(255, 140, 0, 0.07);
            border-top: 1px solid rgba(255, 140, 0, 0.18);
        }

        /* ── topList panel tightening ── */
        #leaderboardContainer {
            margin: 0 -1.5%;
        }
        .listTitle {
            margin-bottom: 12px !important;
        }

        /* ── Player row ── */
        .playerItem {
            position: relative;
            display: flex !important;
            align-items: center;
            gap: 0 !important;
            margin-bottom: 0 !important;
            padding: 0 !important;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.2s ease;
            cursor: default;
            overflow: hidden;
        }
        .playerItem:last-child { border-bottom: none; }
        .playerItem:hover { background: rgba(0,210,255,0.05) !important; }
        .playerItem:hover .pi-btag { color: #00d2ff; }

        .pi-accent {
            width: 3px;
            align-self: stretch;
            flex-shrink: 0;
            background: var(--rank-color, rgba(255,255,255,0.12));
            box-shadow: 0 0 8px var(--rank-color, transparent);
        }
        .pi-portrait {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            overflow: hidden;
            background: rgba(0,210,255,0.04);
        }
        .pi-portrait img { width:100%; height:100%; object-fit:cover; }
        .pi-info {
            flex: 1;
            min-width: 0;
            padding: 7px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .pi-btag {
            font-family: BigNoodleTooOblique, sans-serif;
            font-size: 1.15rem;
            color: #fff;
            text-transform: uppercase;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.2s ease;
        }
        .pi-hero {
            font-family: FuturaDemi, sans-serif;
            font-size: 0.58rem;
            color: rgba(255,255,255,0.32);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pi-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 3px;
            padding: 7px 10px 7px 6px;
            flex-shrink: 0;
        }
        .pi-rank {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .pi-rank img { width:16px; height:16px; }
        .pi-rank-label {
            font-family: BigNoodleTooOblique, sans-serif;
            font-size: 1rem;
            line-height: 1;
            text-transform: uppercase;
        }
        .pi-bottom {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pi-endorsement {
            font-family: FuturaDemi, sans-serif;
            font-size: 0.58rem;
            color: #ffd700;
            letter-spacing: 0.05em;
        }
        .pi-index {
            font-family: BigNoodleTooOblique, sans-serif;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.18);
        }
    `;
    document.head.appendChild(style);
}

// ─── Leaderboard ────────────────────────────────────────────────────────────

async function loadLeaderboard() {
    const container = document.getElementById('leaderboardContainer');
    if (!container) return;

    injectTooltipStyles();

    try {
        const res     = await fetch('./leaderboard.php');
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
            const tier     = player.rank?.tier !== undefined ? ` ${player.rank.tier}` : '';
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

// ─── Favourites ─────────────────────────────────────────────────────────────

async function loadFavorites() {
    try {
        var [mapsRes, heroesRes] = await Promise.all([
            fetch('https://overfast-api.tekrop.fr/maps'),
            fetch('https://overfast-api.tekrop.fr/heroes')
        ]);
        var maps = await mapsRes.json();
        var heroesList = await heroesRes.json();
        var randomMap = maps[Math.floor(Math.random() * maps.length)];
        var gridItems = document.querySelectorAll('.gridItem');
        gridItems[0].innerHTML = `<span>${randomMap.name}</span>`;
        gridItems[0].style.backgroundImage = `url(${randomMap.screenshot})`;
        gridItems[0].style.backgroundSize = 'cover';
        gridItems[0].style.backgroundPosition = 'center';

        var randomHeroSummary = heroesList[Math.floor(Math.random() * heroesList.length)];
        var heroDetailRes = await fetch(`https://overfast-api.tekrop.fr/heroes/${randomHeroSummary.key}`);
        var heroDetail = await heroDetailRes.json();
        if (heroDetail.backgrounds && heroDetail.backgrounds.length > 0) {
            gridItems[1].innerHTML = `<span>${heroDetail.name}</span>`;
            gridItems[1].style.backgroundImage = `url(${heroDetail.backgrounds[2].url})`;
            gridItems[1].style.backgroundSize = 'cover';
            gridItems[1].style.backgroundPosition = '90%';
        }
        gridItems[2].innerHTML = `<span>Fav Skin</span>`;
    } catch (error) {
        console.error('Error fetching OverFast data:', error);
    }
}

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
    loadFavorites();
    handleLogin();
});

var currentPath = window.location.pathname;
var navLinks = document.querySelectorAll('.navItem');
navLinks.forEach(link => {
    if (currentPath.includes(link.getAttribute('href').replace('./', ''))) {
        link.classList.add('active');
    }
});
