
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
    fetchMyNotes(heroID);
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

        const header = card.querySelector('.counterCardHeader');
        const body = card.querySelector('.counterCardBody');
        const chevron = card.querySelector('.counterChevron');
        header.addEventListener('click', () => {
            const isOpen = card.classList.toggle('counterCardOpen');
            chevron.textContent = isOpen ? '▾' : '▸';
        });

        // Clicking the portrait jumps to that counter heros counters
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

// Notes 

let noteModalMode = null;

document.getElementById('addPublicNoteBtn')?.addEventListener('click', () => openNoteModal('public'));
document.getElementById('addPrivateNoteBtn')?.addEventListener('click', () => openNoteModal('private'));
document.getElementById('noteModalCancel')?.addEventListener('click', closeNoteModal);
document.getElementById('noteModalSubmit')?.addEventListener('click', submitNote);

document.getElementById('noteModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'noteModal') closeNoteModal();
});


function openNoteModal(mode) {
    if (!currentHeroID) return;
    noteModalMode = mode;
    const title = document.getElementById('noteModalTitle');
    if (title) title.textContent = mode === 'public' ? 'Submit Public Tip' : 'Add Private Note';

    ['noteCounterTips', 'noteTeammateHelp', 'noteGoodComp1', 'noteGoodComp2', 'noteDangerComp1', 'noteDangerComp2']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

    populateNoteHeroPicker(currentHeroID);

    selectedSeverity = 1;
    document.querySelectorAll('.noteSevBtn').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.sev) === 1);
    });

    document.getElementById('noteModal').style.display = 'flex';
}

let noteSelectedHeroID = null;

function populateNoteHeroPicker(defaultHeroID) {
    const grid = document.getElementById('noteHeroPicker');
    if (!grid) return;
    grid.innerHTML = '';
    noteSelectedHeroID = defaultHeroID;

    allHeroes.forEach(hero => {
        const icon = document.createElement('div');
        icon.className = 'noteHeroPickerIcon';
        icon.style.backgroundImage = `url(${hero.portrait})`;
        icon.title = hero.name;
        if (parseInt(hero.heroID) === parseInt(defaultHeroID)) icon.classList.add('noteHeroActive');

        icon.addEventListener('click', () => {
            noteSelectedHeroID = parseInt(hero.heroID);
            grid.querySelectorAll('.noteHeroPickerIcon').forEach(el => el.classList.remove('noteHeroActive'));
            icon.classList.add('noteHeroActive');
        });

        grid.appendChild(icon);
    });
}

document.querySelectorAll('.noteSevBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        selectedSeverity = parseInt(btn.dataset.sev);
        document.querySelectorAll('.noteSevBtn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    });
});

function closeNoteModal() {
    const modal = document.getElementById('noteModal');
    if (modal) modal.style.display = 'none';
    noteModalMode = null;
}

async function submitNote() {
    const counterTips = document.getElementById('noteCounterTips')?.value.trim();
    const teammateHelp = document.getElementById('noteTeammateHelp')?.value.trim();
    const goodComps = [document.getElementById('noteGoodComp1')?.value.trim(), document.getElementById('noteGoodComp2')?.value.trim()].filter(Boolean);
    const dangerousComps = [document.getElementById('noteDangerComp1')?.value.trim(), document.getElementById('noteDangerComp2')?.value.trim()].filter(Boolean);

    if (!counterTips || !currentHeroID) return;

    const btn = document.getElementById('noteModalSubmit');
    if (btn) btn.textContent = 'Saving…';

    try {
        const res = await fetch('./save_note.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                heroID: currentHeroID,
                counteredByHeroID: noteSelectedHeroID || currentHeroID,
                severity: selectedSeverity,
                counterTips,
                teammateHelp,
                goodComps,
                dangerousComps,
                isPublic: noteModalMode === 'public' ? 1 : 0
            })
        });
        const data = await res.json();
        if (data.success) {
            closeNoteModal();
            fetchMyNotes(currentHeroID);
        } else {
            alert(data.error || 'Could not save note.');
        }
    } catch (err) {
        console.error('Note save failed:', err);
    } finally {
        if (btn) btn.textContent = 'Save';
    }
}

async function fetchMyNotes(heroID) {
    const list = document.getElementById('myNotesList');
    if (!list) return;
    list.innerHTML = '<div class="counterLoading">Loading…</div>';

    try {
        const res = await fetch(`./get_notes.php?heroID=${heroID}`);
        const notes = await res.json();
        renderMyNotes(notes);
    } catch (err) {
        console.error('Failed to load notes:', err);
        list.innerHTML = '<div class="counterLoading">Error loading notes.</div>';
    }
}

function renderMyNotes(notes) {
    const list = document.getElementById('myNotesList');
    if (!list) return;

    if (!notes || notes.length === 0) {
        list.innerHTML = '<div class="counterLoading">No notes yet for this hero.</div>';
        return;
    }

    list.innerHTML = '';
    notes.forEach(note => {
        const severityLabel = ['', 'Soft Counter', 'Hard Counter', 'Extreme Threat'][note.severity] || 'Counter';
        const severityClass = ['', 'sevSoft', 'sevHard', 'sevExtreme'][note.severity] || 'sevHard';
        const goodComps = Array.isArray(note.goodComps) ? note.goodComps : JSON.parse(note.goodComps || '[]');
        const dangerComps = Array.isArray(note.dangerousComps) ? note.dangerousComps : JSON.parse(note.dangerousComps || '[]');

        const card = document.createElement('div');
        const hero = allHeroes.find(h => parseInt(h.heroID) === parseInt(note.counteredByHeroID)); const portraitUrl = hero ? hero.portrait : '';
        const heroName = hero ? hero.name : 'Unknown Hero';
        const heroRole = hero ? hero.role.toUpperCase() : '';

        card.innerHTML = `
    <div class="counterCardHeader">
        <div class="counterPortrait" style="background-image: url(${portraitUrl})"></div>
        <div class="counterMeta">
            <div class="counterHeroName">${heroName}</div>
            <div class="counterRole">${heroRole}</div>
        </div>
        <div class="severityBadge ${severityClass}">${severityLabel}</div>
        <div class="counterChevron">▸</div>
    </div>
    <div class="counterCardBody">
        ${note.counterTips ? `
        <div class="counterSection">
            <div class="counterSectionTitle">How to counter them</div>
            <div class="counterSectionText">${note.counterTips}</div>
        </div>` : ''}
        ${note.teammateHelp ? `
        <div class="counterSection">
            <div class="counterSectionTitle">How teammates can help</div>
            <div class="counterSectionText">${note.teammateHelp}</div>
        </div>` : ''}
        <div class="counterComps">
            <div class="compBlock compGood">
                <div class="compTitle">✦ Good Comps With This Hero</div>
                <div class="compList">${renderComps(goodComps)}</div>
            </div>
            <div class="compBlock compDanger">
                <div class="compTitle">✦ Dangerous Enemy Comps</div>
                <div class="compList">${renderComps(dangerComps)}</div>
            </div>
        </div>
        <div class="noteCardFooter">
            <span class="noteCardMeta">${note.isPublic == 1 ? 'Public tip' : 'Private note'} · ${note.createdAt}</span>
            <button class="noteDeleteBtn" data-noteid="${note.noteID}">✕ Delete</button>
        </div>
    </div>
`;

        const header = card.querySelector('.counterCardHeader');
        const chevron = card.querySelector('.counterChevron');
        header.addEventListener('click', () => {
            const isOpen = card.classList.toggle('counterCardOpen');
            chevron.textContent = isOpen ? '▾' : '▸';
        });
        card.querySelector('.noteDeleteBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            deleteNote(note.noteID);
        });

        list.appendChild(card);
    });
}

let pendingDeleteID = null;

document.getElementById('deleteConfirmCancel')?.addEventListener('click', () => {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    pendingDeleteID = null;
});

document.getElementById('deleteConfirmOk')?.addEventListener('click', async () => {
    if (!pendingDeleteID) return;
    document.getElementById('deleteConfirmModal').style.display = 'none';
    try {
        const res = await fetch('./delete_note.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ noteID: pendingDeleteID })
        });
        const data = await res.json();
        if (data.success) {
            closeNoteModal();
            fetchMyNotes(noteSelectedHeroID || currentHeroID);
        }
    } catch (err) {
        console.error('Delete failed:', err);
    }
    pendingDeleteID = null;
});

function deleteNote(noteID) {
    pendingDeleteID = noteID;
    document.getElementById('deleteConfirmModal').style.display = 'flex';
}