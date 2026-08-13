<div class="business-assistant" id="business-assistant" data-open="false">
    <button type="button" class="business-assistant-launcher" id="business-assistant-launcher" aria-label="Abrir asistente" title="Asistente de ventas e inventario">
        <i class="fas fa-comment-dots"></i>
        <span>Asistente</span>
    </button>

    <section class="business-assistant-panel" id="business-assistant-panel" role="dialog" aria-label="Asistente de ventas e inventario" aria-hidden="true">
        <header class="business-assistant-header">
            <div class="business-assistant-brand">
                <span class="business-assistant-avatar"><i class="fas fa-chart-line"></i></span>
                <div class="min-w-0">
                    <strong>Asistente CONSERDEI</strong>
                    <small>Ventas · Inventario · Precios</small>
                </div>
            </div>
            <button type="button" class="business-assistant-close" id="business-assistant-close" aria-label="Cerrar asistente"><i class="fas fa-times"></i></button>
        </header>

        <div class="business-assistant-scope">
            @if($asistenteAccesoGlobal ?? false)
                <label for="business-assistant-branch"><i class="fas fa-building mr-1"></i>Consultar</label>
                <select id="business-assistant-branch" class="custom-select custom-select-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach(($asistenteSucursales ?? collect()) as $sucursal)
                        <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}{{ $sucursal->activa ? '' : ' · Inactiva' }}</option>
                    @endforeach
                </select>
            @else
                <span><i class="fas fa-map-marker-alt"></i>{{ auth()->user()->sucursal?->nombre ?? 'Sucursal asignada' }}</span>
            @endif
            <span class="business-assistant-safe"><i class="fas fa-shield-alt"></i> Solo consulta</span>
        </div>

        <div class="business-assistant-messages" id="business-assistant-messages" aria-live="polite"></div>

        <div class="business-assistant-suggestions" id="business-assistant-suggestions"></div>

        <form class="business-assistant-form" id="business-assistant-form" autocomplete="off">
            <button type="button" class="business-assistant-mic" id="business-assistant-mic" aria-label="Dictar consulta" title="Dictar consulta">
                <i class="fas fa-microphone"></i>
            </button>
            <textarea id="business-assistant-input" rows="1" maxlength="800" placeholder="Ej.: ¿Cuánto vendimos hoy?"></textarea>
            <button type="submit" class="business-assistant-send" id="business-assistant-send" aria-label="Enviar consulta"><i class="fas fa-paper-plane"></i></button>
        </form>
        <div class="business-assistant-footnote">Las respuestas se calculan con los datos actuales del sistema. El asistente no registra operaciones.</div>
    </section>
</div>
