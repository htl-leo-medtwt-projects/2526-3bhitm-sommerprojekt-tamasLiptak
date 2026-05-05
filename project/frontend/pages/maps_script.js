let allMaps = [];
let selectedGamemode = null;

async function fetchMaps() {
    try {
        const response = await fetch('https://overfast-api.tekrop.fr/maps');
        allMaps = await response.json();
        const gamemodes = [...new Set(allMaps.flatMap(m => m.gamemodes))].sort();
        renderGamemodeList(gamemodes);
        selectGamemode(gamemodes[0]);
    } catch (error) {
        console.error('Failed to load maps:', error);
    }
}

function renderGamemodeList(gamemodes) {
    const container = document.getElementById('gamemodeList');
    container.innerHTML = '';
    gamemodes.forEach(gm => {
        const btn = document.createElement('button');
        btn.className = 'gamemodeBtn';
        btn.textContent = gm.charAt(0).toUpperCase() + gm.slice(1);
        btn.dataset.gm = gm;
        btn.onclick = () => selectGamemode(gm);
        container.appendChild(btn);
    });
}

function selectGamemode(gm) {
    selectedGamemode = gm;
    document.querySelectorAll('.gamemodeBtn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.gm === gm);
    });
    const filtered = allMaps.filter(m => m.gamemodes.includes(gm));
    renderMapGrid(filtered);
    if (filtered.length > 0) loadMapDetails(filtered[0]);
}

function renderMapGrid(maps) {
    const grid = document.getElementById('mapGrid');
    grid.innerHTML = '';
    maps.forEach(map => {
        const div = document.createElement('div');
        div.className = 'mapThumb';
        div.style.backgroundImage = `url(${map.screenshot})`;
        div.title = map.name;
        div.onmouseenter = () => loadMapDetails(map);
        div.onclick = () => loadMapDetails(map);
        const label = document.createElement('span');
        label.className = 'mapThumbLabel';
        label.textContent = map.name;
        div.appendChild(label);
        grid.appendChild(div);
    });
}

function loadMapDetails(map) {
    document.getElementById('mapBigImage').style.backgroundImage = `url(${map.screenshot})`;
    document.getElementById('mapName').textContent = map.name;
    document.getElementById('mapLocation').textContent = map.location || '';
    document.getElementById('mapModes').textContent = map.gamemodes.map(g => g.charAt(0).toUpperCase() + g.slice(1)).join(', ');
}

fetchMaps();