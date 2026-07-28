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
        const box=q('#notifications-content'), dot=q('#notification-dot'), scope=q('#notifications-scope');
        try{
            const response=await fetch(window.Conserdei.alertasUrl,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            if(!response.ok)throw new Error('No se pudieron consultar las alertas');
            const data=await response.json();
            const stock=Number(data.stock_bajo||0), exp=Number(data.lotes_por_vencer||0), total=stock+exp;
            if(scope)scope.textContent=data.alcance||'Sucursal asignada';
            if(dot)dot.classList.toggle('d-none',total===0);
            if(box){
                if(total===0){box.innerHTML='<div class="notification-empty"><i class="fas fa-check-circle text-success mr-1"></i> No hay alertas pendientes.</div>';}
                else{
                    box.innerHTML=(stock?`<a class="notification-item danger" href="${window.Conserdei.stockUrl}"><i class="fas fa-box-open"></i><span><strong>${stock} producto${stock===1?'':'s'} con stock bajo</strong><small>Incluye productos sin existencias en la sucursal.</small></span></a>`:'')+
                    (exp?`<a class="notification-item" href="${window.Conserdei.lotesUrl}"><i class="fas fa-calendar-alt"></i><span><strong>${exp} lote${exp===1?'':'s'} próximo${exp===1?'':'s'} a vencer</strong><small>Vencimiento dentro de ${data.dias_vencimiento||7} días.</small></span></a>`:'');
                }
            }
            const today=new Date().toISOString().slice(0,10), key=`alerts-seen-${window.Conserdei.userId}-${today}`;
            if(total>0&&!sessionStorage.getItem(key)){window.appToast(`Atención: ${stock} productos con stock bajo y ${exp} lotes próximos a vencer.`,'warning',7000);sessionStorage.setItem(key,'1');}
        }catch(error){
            if(box)box.innerHTML='<div class="notification-empty">No se pudieron cargar las alertas.</div>';
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
