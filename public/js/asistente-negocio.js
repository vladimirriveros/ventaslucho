(function () {
    const config = window.Conserdei || {};
    if (!config.assistantUrl) return;

    const root = document.getElementById('business-assistant');
    if (!root) return;

    const launcher = document.getElementById('business-assistant-launcher');
    const panel = document.getElementById('business-assistant-panel');
    const close = document.getElementById('business-assistant-close');
    const form = document.getElementById('business-assistant-form');
    const input = document.getElementById('business-assistant-input');
    const send = document.getElementById('business-assistant-send');
    const mic = document.getElementById('business-assistant-mic');
    const messages = document.getElementById('business-assistant-messages');
    const suggestions = document.getElementById('business-assistant-suggestions');
    const branch = document.getElementById('business-assistant-branch');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const historyKey = `conserdei-assistant-${config.userId || 'user'}`;
    let busy = false;

    const defaults = [
        '¿Cuánto vendimos hoy?',
        '¿Cuál fue el producto más vendido este mes?',
        '¿Cuántos productos tenemos?',
        '¿Qué productos tienen stock bajo?'
    ];

    function setOpen(value) {
        root.dataset.open = value ? 'true' : 'false';
        panel.setAttribute('aria-hidden', value ? 'false' : 'true');
        if (value) setTimeout(() => input.focus(), 120);
    }

    launcher.addEventListener('click', () => setOpen(true));
    close.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.dataset.open === 'true') setOpen(false);
    });

    function scrollBottom() {
        requestAnimationFrame(() => { messages.scrollTop = messages.scrollHeight; });
    }

    function appendMessage(role, text, data) {
        const wrap = document.createElement('div');
        wrap.className = `business-assistant-message ${role}`;
        const bubble = document.createElement('div');
        bubble.className = 'business-assistant-bubble';
        const textNode = document.createElement('div');
        textNode.textContent = String(text || '');
        bubble.appendChild(textNode);

        if (data && role === 'bot') {
            const result = document.createElement('div');
            result.className = 'business-assistant-result';

            if (Array.isArray(data.cards) && data.cards.length) {
                const cards = document.createElement('div');
                cards.className = 'business-assistant-cards';
                data.cards.forEach((item) => {
                    const card = document.createElement('div');
                    card.className = 'business-assistant-card';
                    const label = document.createElement('small');
                    const value = document.createElement('strong');
                    label.textContent = String(item.label || '');
                    value.textContent = String(item.value ?? '');
                    card.append(label, value);
                    cards.appendChild(card);
                });
                result.appendChild(cards);
            }

            if (data.table && Array.isArray(data.table.headers) && Array.isArray(data.table.rows)) {
                const tableWrap = document.createElement('div');
                tableWrap.className = 'business-assistant-table-wrap';
                const table = document.createElement('table');
                table.className = 'business-assistant-table';
                const thead = document.createElement('thead');
                const trh = document.createElement('tr');
                data.table.headers.forEach((header) => {
                    const th = document.createElement('th');
                    th.textContent = String(header ?? '');
                    trh.appendChild(th);
                });
                thead.appendChild(trh);
                const tbody = document.createElement('tbody');
                data.table.rows.forEach((row) => {
                    const tr = document.createElement('tr');
                    (Array.isArray(row) ? row : []).forEach((cell) => {
                        const td = document.createElement('td');
                        td.textContent = String(cell ?? '');
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });
                table.append(thead, tbody);
                tableWrap.appendChild(table);
                result.appendChild(tableWrap);
            }

            if (data.note) {
                const note = document.createElement('div');
                note.className = 'business-assistant-note';
                note.textContent = String(data.note);
                result.appendChild(note);
            }

            bubble.appendChild(result);
        }

        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function loadingMessage() {
        const wrap = document.createElement('div');
        wrap.className = 'business-assistant-message bot loading';
        const bubble = document.createElement('div');
        bubble.className = 'business-assistant-bubble';
        bubble.setAttribute('aria-label', 'Consultando datos');
        for (let i = 0; i < 3; i += 1) {
            const dot = document.createElement('span');
            dot.className = 'business-assistant-dot';
            bubble.appendChild(dot);
        }
        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function renderSuggestions(items) {
        suggestions.innerHTML = '';
        (Array.isArray(items) && items.length ? items : defaults).slice(0, 5).forEach((text) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'business-assistant-chip';
            button.textContent = String(text);
            button.title = String(text);
            button.addEventListener('click', () => ask(String(text)));
            suggestions.appendChild(button);
        });
    }

    function saveHistory() {
        try {
            const rows = Array.from(messages.querySelectorAll('.business-assistant-message')).slice(-14).map((el) => ({
                role: el.classList.contains('user') ? 'user' : 'bot',
                text: el.querySelector('.business-assistant-bubble > div')?.textContent || ''
            })).filter((row) => row.text);
            sessionStorage.setItem(historyKey, JSON.stringify(rows));
        } catch (_) {}
    }

    function restoreHistory() {
        try {
            const rows = JSON.parse(sessionStorage.getItem(historyKey) || '[]');
            if (Array.isArray(rows) && rows.length) {
                rows.forEach((row) => appendMessage(row.role === 'user' ? 'user' : 'bot', row.text));
                return true;
            }
        } catch (_) {}
        return false;
    }

    async function ask(rawText) {
        const text = String(rawText || '').trim();
        if (!text || busy) return;
        busy = true;
        send.disabled = true;
        input.disabled = true;
        appendMessage('user', text);
        input.value = '';
        resizeInput();
        const loading = loadingMessage();

        try {
            const payload = { mensaje: text };
            if (branch && branch.value) payload.sucursal_id = Number(branch.value);

            // Usar siempre una URL del mismo origen. En producción (Render/HTTPS), una
            // URL absoluta generada como http://... puede ser bloqueada por el navegador
            // como contenido mixto y produce únicamente "Failed to fetch".
            let assistantEndpoint = String(config.assistantUrl || '/admin/asistente/consultar');
            try {
                const parsed = new URL(assistantEndpoint, window.location.origin);
                assistantEndpoint = `${parsed.pathname}${parsed.search}`;
            } catch (_) {
                assistantEndpoint = '/admin/asistente/consultar';
            }

            const response = await fetch(assistantEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });

            const raw = await response.text();
            let data = {};
            try { data = raw ? JSON.parse(raw) : {}; } catch (_) {}

            loading.remove();
            if (!response.ok) {
                const validation = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                const statusMessages = {
                    401: 'La sesión terminó. Inicie sesión nuevamente.',
                    403: 'Su usuario no tiene permiso para realizar esta consulta.',
                    419: 'La sesión de seguridad venció. Actualice la página e intente nuevamente.',
                    422: 'Revise la consulta enviada.',
                    429: 'Se enviaron demasiadas consultas. Espere un momento e intente nuevamente.',
                    500: 'Laravel encontró un error al procesar la consulta. Revise storage/logs/laravel.log.'
                };
                throw new Error(validation || data.message || data.reply || statusMessages[response.status] || `Error HTTP ${response.status}.`);
            }

            appendMessage('bot', data.reply || 'Consulta completada.', data);
            renderSuggestions(data.suggestions);
            saveHistory();
        } catch (error) {
            loading.remove();
            const message = error instanceof TypeError && /fetch|network/i.test(String(error.message || ''))
                ? 'No pude comunicarme con Laravel. Actualice la página e intente nuevamente. Si está en Render, verifique que el despliegue haya terminado correctamente.'
                : (error.message || 'No pude completar la consulta. Intente nuevamente.');
            appendMessage('bot', message);
            renderSuggestions(defaults);
            saveHistory();
        } finally {
            busy = false;
            send.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        ask(input.value);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 100)}px`;
    }
    input.addEventListener('input', resizeInput);

    if (branch) {
        branch.addEventListener('change', () => {
            const label = branch.options[branch.selectedIndex]?.text || 'Todas las sucursales';
            appendMessage('bot', `Alcance cambiado a: ${label}. Las próximas consultas usarán este filtro.`);
            saveHistory();
        });
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        mic.style.display = 'none';
        form.style.gridTemplateColumns = '1fr 40px';
    } else {
        const recognition = new SpeechRecognition();
        recognition.lang = 'es-BO';
        recognition.interimResults = false;
        recognition.continuous = false;

        mic.addEventListener('click', () => {
            if (mic.classList.contains('listening')) {
                recognition.stop();
                return;
            }
            try { recognition.start(); } catch (_) {}
        });
        recognition.addEventListener('start', () => {
            mic.classList.add('listening');
            mic.title = 'Escuchando…';
        });
        recognition.addEventListener('end', () => {
            mic.classList.remove('listening');
            mic.title = 'Dictar consulta';
        });
        recognition.addEventListener('result', (event) => {
            const transcript = event.results?.[0]?.[0]?.transcript || '';
            input.value = transcript;
            resizeInput();
            input.focus();
        });
        recognition.addEventListener('error', () => {
            mic.classList.remove('listening');
            if (window.appToast) window.appToast('No se pudo usar el micrófono. Puede escribir la consulta manualmente.', 'warning');
        });
    }

    if (!restoreHistory()) {
        appendMessage('bot', 'Hola. Puedo consultar ventas, inventario, precios, stock y calcular pedidos. No modificaré datos del sistema.');
    }
    renderSuggestions(defaults);
})();
