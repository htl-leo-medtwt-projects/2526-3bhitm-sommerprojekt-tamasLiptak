function loadLeaderboard() {
    const container = document.getElementById('leaderboard-container');
    if (!container) return;

    const mockPlayers = [
    { name: 'Viol2t',  mmr: 5000 },
    { name: 'Fleta',   mmr: 4978 },
    { name: 'Profit',  mmr: 4955 },
    { name: 'Striker', mmr: 4932 },
    { name: 'Proper',  mmr: 4910 },
];

container.innerHTML = '';
mockPlayers.forEach(player => {
    const item = document.createElement('div');
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

document.addEventListener('DOMContentLoaded', loadLeaderboard);