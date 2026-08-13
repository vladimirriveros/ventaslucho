(function(){
    const d=document, body=d.body, root=d.documentElement;
    const q=(s)=>d.querySelector(s);
    const storageKey='conserdei-theme';
    function setTheme(theme){root.dataset.theme=theme;localStorage.setItem(storageKey,theme);const icon=q('#theme-toggle i');if(icon){icon.className=theme==='dark'?'fas fa-sun':'far fa-moon';}}
    setTheme(localStorage.getItem(storageKey)||'light');
    d.addEventListener('click',(e)=>{
        if(e.target.closest('#theme-toggle')) setTheme(root.dataset.theme==='dark'?'light':'dark');
        if(e.target.closest('#sidebar-toggle')) body.classList.toggle('sidebar-open');
        if(e.target.closest('#sidebar-collapse')){body.classList.toggle('sidebar-collapsed');localStorage.setItem('conserdei-sidebar',body.classList.contains('sidebar-collapsed')?'1':'0');}
        if(e.target.id==='app-backdrop'||e.target.closest('.app-nav-link')) body.classList.remove('sidebar-open');
    });
    if(localStorage.getItem('conserdei-sidebar')==='1'&&innerWidth>=992) body.classList.add('sidebar-collapsed');
    addEventListener('resize',()=>{if(innerWidth>=992) body.classList.remove('sidebar-open');});

    window.appToast=function(message,type='info',duration=4800){
        const old=q('.app-toast');if(old)old.remove();
        const toast=d.createElement('div');toast.className=`app-toast ${type}`;
        const icons={success:'check-circle',error:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
        const icon=d.createElement('i');icon.className=`fas fa-${icons[type]||icons.info} mt-1`;
        const content=d.createElement('div');content.textContent=String(message ?? '');
        toast.append(icon,content);body.appendChild(toast);setTimeout(()=>toast.remove(),duration);
    };

    async function loadAlerts(){
        if(!window.Conserdei?.alertasUrl)return;
        const box=q('#notifications-content'), count=q('#notification-count'), scope=q('#notifications-scope');
        try{
            const response=await fetch(window.Conserdei.alertasUrl,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            if(!response.ok)throw new Error('No se pudieron consultar las alertas');
            const data=await response.json();
            const stock=Number(data.stock_bajo||0), exp=Number(data.lotes_por_vencer||0), expired=Number(data.lotes_vencidos||0), total=Number(data.total ?? (stock+exp+expired));
            const center=window.Conserdei.alertCenterUrl||'#';
            if(scope)scope.textContent=data.alcance||'Sucursal asignada';
            if(count){count.textContent=String(total);count.classList.toggle('d-none',total===0);}
            if(box){
                if(total===0){box.innerHTML='<div class="notification-empty"><i class="fas fa-check-circle text-success mr-1"></i> No hay alertas pendientes.</div>';}
                else{
                    box.innerHTML=(stock?`<a class="notification-item danger" href="${center}?seccion=stock#stock-bajo"><i class="fas fa-box-open"></i><span><strong>${stock} producto${stock===1?'':'s'} con stock bajo</strong><small>Incluye productos sin existencias.</small></span></a>`:'')+
                    (exp?`<a class="notification-item" href="${center}?seccion=lotes#por-vencer"><i class="fas fa-hourglass-half"></i><span><strong>${exp} lote${exp===1?'':'s'} próximo${exp===1?'':'s'} a vencer</strong><small>Vencimiento dentro de ${data.dias_vencimiento||7} días.</small></span></a>`:'')+
                    (expired?`<a class="notification-item danger" href="${center}?seccion=vencidos#vencidos"><i class="fas fa-calendar-times"></i><span><strong>${expired} lote${expired===1?'':'s'} vencido${expired===1?'':'s'}</strong><small>Tienen existencias y requieren revisión.</small></span></a>`:'');
                }
            }
            const today=new Date().toISOString().slice(0,10), key=`alerts-seen-${window.Conserdei.userId}-${today}`;
            if(total>0&&!sessionStorage.getItem(key)){window.appToast(`Atención: ${stock} productos con stock bajo, ${exp} lotes por vencer y ${expired} lotes vencidos.`,'warning',8000);sessionStorage.setItem(key,'1');}
        }catch(error){
            if(box)box.innerHTML='<div class="notification-empty">No se pudieron actualizar las alertas. Use el Centro de alertas.</div>';
        }
    }
    d.addEventListener('DOMContentLoaded',()=>{
        loadAlerts();
        window.setInterval(loadAlerts,60000);
    });
    window.addEventListener('focus',loadAlerts);
    d.addEventListener('visibilitychange',()=>{if(!d.hidden)loadAlerts();});

    d.addEventListener('livewire:init',()=>{
        Livewire.on('mostrar-alerta',(payload)=>{const data=Array.isArray(payload)?payload[0]:payload;window.appToast(data?.mensaje||data?.text||'Operación completada',data?.icono||'info');});
    });
})();

/* V11 · Adaptador universal de tablas para teléfonos.
   Conserva la tabla en escritorio y, mediante data-label, permite que CSS
   presente cada fila como tarjeta en pantallas pequeñas. */
(function () {
    const TABLE_SELECTOR = 'table.table:not([data-mobile-table="scroll"]):not(.no-mobile-cards)';

    function normalizeLabel(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .replace(/[↑↓↕]+/g, '')
            .trim();
    }

    function headerLabels(table) {
        const rows = Array.from(table.querySelectorAll(':scope > thead > tr'));
        if (!rows.length) return [];

        /* Escoger la fila de encabezado con más columnas reduce errores en
           cabeceras decorativas que usan colspan. */
        let headerRow = rows[0];
        rows.forEach((row) => {
            if (row.querySelectorAll('th').length > headerRow.querySelectorAll('th').length) {
                headerRow = row;
            }
        });

        return Array.from(headerRow.querySelectorAll(':scope > th')).map((th) =>
            normalizeLabel(th.innerText || th.textContent)
        );
    }

    function enhanceTable(table) {
        if (!(table instanceof HTMLTableElement)) return;
        const labels = headerLabels(table);
        if (labels.length < 2) return;

        table.classList.add('app-responsive-table');
        table.setAttribute('data-responsive-ready', '1');

        table.querySelectorAll(':scope > tbody > tr').forEach((row) => {
            const cells = Array.from(row.children).filter((cell) => cell.tagName === 'TD');
            if (!cells.length) return;

            cells.forEach((cell, index) => {
                if (cell.hasAttribute('colspan')) {
                    cell.classList.add('mobile-table-empty');
                    cell.setAttribute('data-label', '');
                    return;
                }

                let label = labels[index] || '';
                if (!label && index === cells.length - 1 && cell.querySelector('button, a.btn, .btn-group')) {
                    label = 'Acciones';
                }
                cell.setAttribute('data-label', label);
            });
        });
    }

    function enhanceWithin(root) {
        if (!root) return;
        if (root.matches && root.matches(TABLE_SELECTOR)) enhanceTable(root);
        if (root.querySelectorAll) root.querySelectorAll(TABLE_SELECTOR).forEach(enhanceTable);
    }

    function bootResponsiveTables() {
        enhanceWithin(document);

        /* DataTables y Livewire reemplazan filas dinámicamente. El observer
           prepara únicamente los nodos nuevos sin interferir con su estado. */
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;
                    const element = /** @type {Element} */ (node);
                    const table = element.closest && element.closest(TABLE_SELECTOR);
                    if (table) enhanceTable(table);
                    enhanceWithin(element);
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        if (window.jQuery) {
            window.jQuery(document).on('draw.dt', function (_event, settings) {
                if (settings && settings.nTable) enhanceTable(settings.nTable);
            });
        }

        document.addEventListener('livewire:navigated', () => enhanceWithin(document));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootResponsiveTables, { once: true });
    } else {
        bootResponsiveTables();
    }
})();
