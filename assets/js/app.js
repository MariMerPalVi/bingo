(function () {
    const page = document.body.dataset.page;
    const csrf = document.body.dataset.csrf || '';
    const gameHost = document.querySelector('.operator-workspace') || document.body;
    let gameId = Number(gameHost.dataset.gameId || 0);
    let lastState = null;

    const columns = [
        ['B', 1, 15],
        ['I', 16, 30],
        ['N', 31, 45],
        ['G', 46, 60],
        ['O', 61, 75],
    ];

    document.addEventListener('DOMContentLoaded', function () {
        renderStaticControls();
        bindOperator();
        refreshState();
        setInterval(refreshState, 2000);
    });

    function renderStaticControls() {
        renderBoard([], null);

        const pad = document.getElementById('number-buttons');
        if (!pad) {
            return;
        }

        pad.innerHTML = '';
        for (let n = 1; n <= 75; n += 1) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'number-button';
            button.dataset.number = String(n);
            button.textContent = String(n);
            button.addEventListener('click', () => markNumber(n));
            pad.appendChild(button);
        }
    }

    function bindOperator() {
        if (page !== 'operator') {
            return;
        }

        const createForm = document.getElementById('create-game-form');
        createForm?.addEventListener('submit', async function (event) {
            event.preventDefault();
            const data = new FormData(createForm);
            const response = await post('create_game', data);
            showMessage(response);
            if (response.ok) {
                window.location.reload();
            }
        });

        document.getElementById('mark-form')?.addEventListener('submit', function (event) {
            event.preventDefault();
            const input = document.getElementById('manual-number');
            markNumber(Number(input.value));
            input.value = '';
            input.focus();
        });

        document.querySelectorAll('.game-option').forEach((button) => {
            button.addEventListener('click', () => {
                gameId = Number(button.dataset.gameId);
                document.querySelector('.operator-workspace').dataset.gameId = String(gameId);
                document.querySelectorAll('.game-option').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                refreshState();
            });
        });

        document.querySelectorAll('[data-action]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!gameId) {
                    showMessage({ ok: false, message: 'Primero cree o seleccione una partida.' });
                    return;
                }
                const confirmText = button.dataset.confirm;
                if (confirmText && !window.confirm(confirmText)) {
                    return;
                }
                const data = new FormData();
                data.set('partida_id', String(gameId));
                const response = await post(button.dataset.action, data);
                showMessage(response);
                refreshState();
            });
        });
    }

    async function markNumber(number) {
        if (page !== 'operator') {
            return;
        }
        if (!gameId) {
            showMessage({ ok: false, message: 'Primero cree o seleccione una partida.' });
            return;
        }
        if (!Number.isInteger(number) || number < 1 || number > 75) {
            showMessage({ ok: false, message: 'El numero debe estar entre 1 y 75.' });
            return;
        }
        if (lastState?.marked?.includes(number)) {
            showMessage({ ok: false, message: 'Ese numero ya fue marcado.' });
            return;
        }

        const data = new FormData();
        data.set('partida_id', String(gameId));
        data.set('numero', String(number));
        const response = await post('mark_number', data);
        showMessage(response);
        refreshState();
    }

    async function deleteNumber(number) {
        if (page !== 'operator' || !window.confirm('Eliminar este numero del historial?')) {
            return;
        }
        const data = new FormData();
        data.set('partida_id', String(gameId));
        data.set('numero', String(number));
        const response = await post('delete_number', data);
        showMessage(response);
        refreshState();
    }

    async function post(action, data) {
        data.set('action', action);
        data.set('csrf_token', csrf);

        const request = await fetch('api.php', {
            method: 'POST',
            body: data,
            headers: { 'X-CSRF-Token': csrf },
        });
        return request.json();
    }

    async function refreshState() {
        const url = gameId ? `api.php?action=state&partida_id=${gameId}` : 'api.php?action=state';
        const request = await fetch(url, { cache: 'no-store' });
        const state = await request.json();

        if (!state.ok) {
            showMessage(state);
            return;
        }

        if (!gameId && state.game) {
            gameId = state.game.id;
        }

        lastState = state;
        renderState(state);
    }

    function renderState(state) {
        const game = state.game;
        const last = state.last;
        const lastText = last ? last.codigo_bingo : '--';

        setText('last-number', lastText);
        setText('counter', `${state.count || 0} de 75 numeros`);

        if (page === 'operator') {
            setText('game-title', game ? game.nombre_partida : 'Sin partida');
            setText('game-status', game ? game.estado_label : '--');
        }

        if (page === 'viewer') {
            setText('viewer-game-title', game ? game.nombre_partida : 'Esperando partida');
            setText('viewer-status', game ? game.estado_label : '--');
        }

        renderBoard(state.marked || [], last?.numero || null);
        renderHistory(state.numbers || []);
        renderNumberButtons(state.marked || [], last?.numero || null);
    }

    function renderBoard(markedNumbers, latest) {
        const board = document.getElementById('bingo-board');
        if (!board) {
            return;
        }

        const marked = new Set(markedNumbers.map(Number));
        board.innerHTML = '';

        columns.forEach(([letter, start, end]) => {
            const column = document.createElement('div');
            column.className = 'board-column';

            const heading = document.createElement('div');
            heading.className = 'board-heading';
            heading.textContent = letter;
            column.appendChild(heading);

            for (let n = start; n <= end; n += 1) {
                const cell = document.createElement('div');
                cell.className = 'board-cell';
                if (marked.has(n)) {
                    cell.classList.add('marked');
                }
                if (latest === n) {
                    cell.classList.add('latest');
                }
                cell.textContent = String(n);
                column.appendChild(cell);
            }

            board.appendChild(column);
        });
    }

    function renderNumberButtons(markedNumbers, latest) {
        const marked = new Set(markedNumbers.map(Number));
        document.querySelectorAll('.number-button').forEach((button) => {
            const number = Number(button.dataset.number);
            button.classList.toggle('marked', marked.has(number));
            button.classList.toggle('latest', latest === number);
            button.disabled = marked.has(number);
            button.title = marked.has(number) ? 'Numero ya marcado' : `Marcar ${number}`;
        });
    }

    function renderHistory(numbers) {
        const list = document.getElementById('history-list');
        if (!list) {
            return;
        }

        if (!numbers.length) {
            list.innerHTML = '<div class="history-item empty"><strong>Sin numeros</strong><small>Esperando salida</small></div>';
            return;
        }

        list.innerHTML = '';
        numbers.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'history-item';
            row.innerHTML = `
                <div class="history-main">
                    <span class="history-order">${escapeHtml(item.orden_salida)}</span>
                    <div>
                        <strong>${escapeHtml(item.codigo_bingo)}</strong>
                        <small>${escapeHtml(item.fecha_hora)}</small>
                    </div>
                </div>
            `;

            if (page === 'operator') {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'danger-button history-correct';
                button.textContent = 'Corregir';
                button.addEventListener('click', () => deleteNumber(Number(item.numero)));
                row.appendChild(button);
            }

            list.appendChild(row);
        });
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    function showMessage(response) {
        const message = document.getElementById('message');
        if (!message) {
            return;
        }

        message.hidden = false;
        message.className = `message ${response.ok ? 'success' : 'error'}`;
        message.textContent = response.message || '';
        window.setTimeout(() => {
            message.hidden = true;
        }, 3500);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
