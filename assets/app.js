const root = document.querySelector('[data-page]');
const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
const $ = (selector, within=document) => within.querySelector(selector);
const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
const notice = (message, error=false) => { const el=$('#notice'); if (!el) return; el.textContent=message; el.className='notice'+(error?' error':''); el.hidden=false; window.scrollTo({top:0,behavior:'smooth'}); };

async function api(resource, action='list', body=null, params={}) {
    const query=new URLSearchParams({resource,action,...params});
    const response=await fetch(`backend/api.php?${query}`, {method:body?'POST':'GET',headers:body?{'X-CSRF-Token':token,'Accept':'application/json'}:{'Accept':'application/json'},body});
    const result=await response.json().catch(()=>({error:'Respuesta inesperada del servidor.'}));
    if (!response.ok || result.error) throw new Error(result.error || 'No se pudo completar la operación.');
    return result.data ?? result;
}
const post = (resource, action, form) => api(resource, action, form instanceof FormData ? form : new FormData(form));
const options = (items, value, label, blank='Seleccionar') => `<option value="">${esc(blank)}</option>`+items.map(x=>`<option value="${esc(x[value])}">${esc(label(x))}</option>`).join('');
const formSet = (form, row) => {for (const [key,value] of Object.entries(row)) {const field=form.elements.namedItem(key);if (!field) continue;if (field.type==='checkbox') field.checked=!!Number(value);else field.value=value ?? ''; }form.scrollIntoView({behavior:'smooth',block:'center'});};
const dateLabel = value => value ? new Date(value.replace(' ','T')).toLocaleString('es-PY') : '—';
const actionButtons = (id) => `<div class="actions"><button class="small" data-edit="${id}">Editar</button><button class="small danger" data-delete="${id}">Eliminar</button></div>`;
async function saveForm(form, resource, reload) {try {await post(resource,'save',form);form.reset();notice('Guardado correctamente.');await reload();}catch(e){notice(e.message,true);}}
async function remove(resource, idName, id, reload) {if (!confirm('¿Eliminar este registro? Podrás restaurarlo desde la base de datos.')) return;try{const fd=new FormData();fd.set(idName,id);await post(resource,'delete',fd);notice('Registro eliminado.');await reload();}catch(e){notice(e.message,true);}}

async function initSchools() {
    const form=$('#school-form'); let rows=[];
    const reload=async()=>{rows=await api('escuelas');$('#school-rows').innerHTML=rows.map(r=>`<tr><td><span class="badge">${esc(r.codigo)}</span></td><td>${esc(r.nombre)}</td><td>${actionButtons(r.id_escuela)}</td></tr>`).join('')||'<tr><td colspan="3">Todavía no hay escuelas.</td></tr>';};
    form.addEventListener('submit',e=>{e.preventDefault();saveForm(form,'escuelas',reload);});
    $('#school-rows').addEventListener('click',e=>{let id=e.target.dataset.edit||e.target.dataset.delete;if (!id) return;let row=rows.find(r=>r.id_escuela==id);if(e.target.dataset.edit)formSet(form,row);else remove('escuelas','id_escuela',id,reload);});
    await reload();
}

async function initPatients() {
    const patientForm=$('#patient-form'),evalForm=$('#evaluation-form'); let patients=[],evaluations=[],schools=[];
    const reload=async()=>{[patients,evaluations,schools]=await Promise.all([api('pacientes'),api('evaluaciones'),api('escuelas')]);
        const patientOptions=options(patients,'id_paciente',p=>`${p.apellidos} ${p.nombres} · ${p.numero_documento}`);
        for(const id of ['father-select','mother-select','evaluation-patient']) {const element=$(`#${id}`),prior=element.value;element.innerHTML=patientOptions;element.value=prior;}
        const schoolEl=$('#evaluation-school'),priorSchool=schoolEl.value;schoolEl.innerHTML=options(schools,'id_escuela',x=>x.nombre);schoolEl.value=priorSchool;
        $('#patient-rows').innerHTML=patients.map(p=>`<tr><td>${esc(p.apellidos)} ${esc(p.nombres)}</td><td>${esc(p.numero_documento)}</td><td>${esc(p.edad)}</td><td>${actionButtons(p.id_paciente)}</td></tr>`).join('')||'<tr><td colspan="4">Sin pacientes.</td></tr>';
        $('#evaluation-rows').innerHTML=evaluations.map(v=>`<tr><td>${esc(v.apellidos)} ${esc(v.nombres)}</td><td>${esc(v.escuela)}</td><td>${esc(v.periodo_mes)}/${esc(v.periodo_anio)}</td><td><span class="badge">${esc(v.estado)}</span></td><td>${actionButtons(v.id_evaluacion)}</td></tr>`).join('')||'<tr><td colspan="5">Sin evaluaciones.</td></tr>';
    };
    patientForm.addEventListener('submit',e=>{e.preventDefault();saveForm(patientForm,'pacientes',reload);});
    evalForm.addEventListener('submit',e=>{e.preventDefault();saveForm(evalForm,'evaluaciones',reload);});
    evalForm.elements.namedItem('anio').value=new Date().getFullYear();evalForm.elements.namedItem('mes').value=new Date().getMonth()+1;
    $('#patient-rows').addEventListener('click',e=>{let id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;let row=patients.find(x=>x.id_paciente==id);if(e.target.dataset.edit)formSet(patientForm,row);else remove('pacientes','id_paciente',id,reload);});
    $('#evaluation-rows').addEventListener('click',e=>{let id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;let row=evaluations.find(x=>x.id_evaluacion==id);if(e.target.dataset.edit)formSet(evalForm,{...row,anio:row.periodo_anio,mes:row.periodo_mes});else remove('evaluaciones','id_evaluacion',id,reload);});
    await reload();
    const selected=new URLSearchParams(location.search).get('paciente');if(selected)$('#evaluation-patient').value=selected;
}

async function initReferrers() {
    const form=$('#referrer-form'),refForm=$('#referral-form');let referrers=[],patients=[],referrals=[];
    const reload=async()=>{[referrers,patients,referrals]=await Promise.all([api('derivadores'),api('pacientes'),api('derivaciones')]);
        $('#referrer-rows').innerHTML=referrers.map(r=>`<tr><td>${esc(r.nombre)}</td><td>${esc(r.tipo)}</td><td>${actionButtons(r.id_derivador)}</td></tr>`).join('')||'<tr><td colspan="3">Sin derivadores.</td></tr>';
        $('#referral-rows').innerHTML=referrals.map(r=>`<tr><td>${esc(r.paciente)}</td><td>${esc(r.derivador)}</td><td>${dateLabel(r.creado_en)}</td><td><button class="small danger" data-delete="${r.id_derivacion}">Eliminar</button></td></tr>`).join('')||'<tr><td colspan="4">Sin derivaciones.</td></tr>';
        $('#referral-referrer').innerHTML=options(referrers,'id_derivador',r=>r.nombre);$('#referral-patient').innerHTML=options(patients,'id_paciente',p=>`${p.apellidos} ${p.nombres}`);
    };
    form.addEventListener('submit',e=>{e.preventDefault();saveForm(form,'derivadores',reload);});
    refForm.addEventListener('submit',e=>{e.preventDefault();saveForm(refForm,'derivaciones',reload);});
    $('#referrer-rows').addEventListener('click',e=>{let id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;let row=referrers.find(r=>r.id_derivador==id);if(e.target.dataset.edit)formSet(form,row);else remove('derivadores','id_derivador',id,reload);});
    $('#referral-rows').addEventListener('click',e=>{let id=e.target.dataset.delete;if(id)remove('derivaciones','id_derivacion',id,reload);});
    await reload();
}

async function initReports() {
    const filters=$('#report-filters'),school=$('#report-school');let rows=[];
    school.innerHTML=options(await api('escuelas'),'id_escuela',s=>`${s.nombre} (${s.codigo})`,'Seleccioná una escuela');
    function query(){return Object.fromEntries(new FormData(filters));}
    const reload=async()=>{const q=query();if(!q.escuela){rows=[];$('#report-rows').innerHTML='';$('#report-count').textContent='Seleccioná una escuela para empezar.';return;}
        rows=await api('evaluaciones','list',null,q);$('#report-rows').innerHTML=rows.map(r=>`<tr><td><input class="report-check" type="checkbox" value="${r.id_evaluacion}" aria-label="Seleccionar ${esc(r.apellidos)}"></td><td>${esc(r.apellidos)} ${esc(r.nombres)}</td><td>${esc(r.numero_documento)}</td><td>${esc(r.escuela)}</td><td>${esc(r.periodo_mes)}/${esc(r.periodo_anio)}</td><td>${dateLabel(r.creado_en)}</td><td>${r.tiene_resultados?'Guardado':'Pendiente'}</td><td><button type="button" class="small" data-single="${r.id_evaluacion}">PDF</button></td></tr>`).join('')||'<tr><td colspan="8">No hay evaluaciones para esos filtros.</td></tr>';
        $('#report-count').textContent=`${rows.length} evaluaciones encontradas`;$('#select-all').checked=false;
    };
    function print(scope='selected',type='normal',one=null){const q=query();if(!q.escuela){notice('Seleccioná una escuela.',true);return;}const selected=one?[one]:[...document.querySelectorAll('.report-check:checked')].map(x=>x.value);if(scope==='selected'&&!selected.length){notice('Seleccioná al menos una evaluación.',true);return;}if(scope==='filtered'&&!rows.length){notice('No hay evaluaciones para imprimir.',true);return;}
        const form=document.createElement('form');form.method='POST';form.action='reportes/index.php';form.target='_blank';form.style.display='none';const add=(key,val)=>{const input=document.createElement('input');input.type='hidden';input.name=key;input.value=val;form.append(input);};add('csrf',token);add('id_escuela',q.escuela);add('alcance',scope);if(type==='tipi')add('tipo','tipi');for(const [key,val] of Object.entries(q))if(key!=='escuela')add(key,val);if(scope==='selected')selected.forEach(id=>add('ids[]',id));document.body.append(form);form.submit();form.remove();
    }
    filters.addEventListener('submit',e=>{e.preventDefault();reload().catch(e=>notice(e.message,true));});school.addEventListener('change',()=>reload().catch(e=>notice(e.message,true)));
    $('#select-all').addEventListener('change',e=>document.querySelectorAll('.report-check').forEach(box=>box.checked=e.target.checked));
    $('#report-rows').addEventListener('click',e=>{const id=e.target.dataset.single;if(id)print('selected','normal',id);});
    $('#print-selected').addEventListener('click',()=>print());$('#print-all').addEventListener('click',()=>print('filtered'));$('#print-rh').addEventListener('click',()=>print('selected','tipi'));
}

async function initProfile(){const form=$('#profile-form');const [p,titles,universities]=await Promise.all([api('perfil'),api('titulos'),api('universidades')]);$('#profile-title').innerHTML=options(titles,'id_titulo',t=>`${t.nombre} (${t.abreviatura})`,'Sin título');$('#profile-university').innerHTML=options(universities,'id_universidad',u=>u.nombre,'Sin universidad');formSet(form,p);$('#profile-identity').textContent=`${p.nombre} · Documento ${p.ci}`;form.addEventListener('submit',async e=>{e.preventDefault();try{await post('perfil','save',form);notice('Perfil actualizado.');document.documentElement.dataset.theme=form.elements.namedItem('modo_oscuro').checked?'dark':'light';}catch(err){notice(err.message,true);}});}

async function initUsers(){const form=$('#user-form'),roleForm=$('#role-form');let users=[],roles=[],audit=[];
    const reload=async()=>{[users,roles,audit]=await Promise.all([api('usuarios'),api('roles'),api('auditoria')]);$('#user-rows').innerHTML=users.map(u=>`<tr><td>${esc(u.nombre)}</td><td>${esc(u.ci)}</td><td>${esc(u.roles||'—')}</td><td>${actionButtons(u.id_usuario)}</td></tr>`).join('')||'<tr><td colspan="4">Sin usuarios.</td></tr>';
        $('#role-user').innerHTML=options(users,'id_usuario',u=>u.nombre);$('#role-role').innerHTML=options(roles,'id_rol',r=>r.nombre);
        $('#audit-rows').innerHTML=audit.map(a=>`<tr><td>${dateLabel(a.creado_en)}</td><td>${esc(a.actor_identificador)}</td><td>${esc(a.accion)}</td><td>${esc(a.entidad)}</td><td><details><summary>Ver</summary><pre>${esc(a.datos_anteriores)}\n→\n${esc(a.datos_nuevos)}</pre></details></td></tr>`).join('')||'<tr><td colspan="5">Sin movimientos.</td></tr>';
    };
    form.addEventListener('submit',e=>{e.preventDefault();saveForm(form,'usuarios',reload);});$('#user-rows').addEventListener('click',e=>{let id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;let row=users.find(u=>u.id_usuario==id);if(e.target.dataset.edit)formSet(form,row);else remove('usuarios','id_usuario',id,reload);});
    roleForm.addEventListener('submit',async e=>{e.preventDefault();try{await post('usuario_roles','assign',roleForm);notice('Rol asignado.');await reload();}catch(err){notice(err.message,true);}});
    $('#revoke-role').addEventListener('click',async()=>{try{await post('usuario_roles','delete',roleForm);notice('Rol revocado.');await reload();}catch(err){notice(err.message,true);}});
    await reload();
    await initSecurityCatalogs();
    await initReferenceCatalogs();
}

async function initReferenceCatalogs(){
    let titles=[],universities=[];
    const titleForm=$('#title-form'),universityForm=$('#university-form');
    const reload=async()=>{
        [titles,universities]=await Promise.all([api('titulos'),api('universidades')]);
        $('#title-rows').innerHTML=titles.map(t=>`<tr><td>${esc(t.nombre)}</td><td>${esc(t.abreviatura)}</td><td>${actionButtons(t.id_titulo)}</td></tr>`).join('');
        $('#university-rows').innerHTML=universities.map(u=>`<tr><td>${esc(u.nombre)}</td><td>${esc(u.pais||'—')}</td><td>${actionButtons(u.id_universidad)}</td></tr>`).join('');
    };
    for(const [form,resource] of [[titleForm,'titulos'],[universityForm,'universidades']])form.addEventListener('submit',e=>{e.preventDefault();saveForm(form,resource,reload);});
    for(const [rowId,form,resource,key,getRows] of [['title-rows',titleForm,'titulos','id_titulo',()=>titles],['university-rows',universityForm,'universidades','id_universidad',()=>universities]]){
        $(`#${rowId}`).addEventListener('click',e=>{const id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;const row=getRows().find(x=>x[key]==id);if(e.target.dataset.edit)formSet(form,row);else remove(resource,key,id,reload);});
    }
    await reload();
}

async function initSecurityCatalogs(){
    const roleForm=$('#catalog-role-form'),permissionForm=$('#catalog-permission-form'),menuForm=$('#catalog-menu-form');
    let roles=[],permissions=[],menus=[],rolePermissions=[],menuPermissions=[];
    const showRelations=()=>{
        const roleId=$('#rp-role').value,menuId=$('#mp-menu').value;
        $('#role-permission-list').textContent='Concedidos: '+(rolePermissions.filter(x=>x.id_rol==roleId).map(x=>permissions.find(p=>p.id_permiso==x.id_permiso)?.codigo).filter(Boolean).join(', ')||'ninguno');
        $('#menu-permission-list').textContent='Requeridos: '+(menuPermissions.filter(x=>x.id_menu==menuId).map(x=>permissions.find(p=>p.id_permiso==x.id_permiso)?.codigo).filter(Boolean).join(', ')||'ninguno');
    };
    const reload=async()=>{
        [roles,permissions,menus,rolePermissions,menuPermissions]=await Promise.all([api('roles'),api('permisos'),api('menus_admin'),api('rol_permisos'),api('menu_permisos')]);
        $('#role-rows').innerHTML=roles.map(r=>`<tr><td>${esc(r.codigo)}</td><td>${esc(r.nombre)}</td><td>${actionButtons(r.id_rol)}</td></tr>`).join('');
        $('#permission-rows').innerHTML=permissions.map(p=>`<tr><td>${esc(p.codigo)}</td><td>${esc(p.modulo)}</td><td>${actionButtons(p.id_permiso)}</td></tr>`).join('');
        $('#menu-rows').innerHTML=menus.map(m=>`<tr><td>${esc(m.nombre)}</td><td>${esc(m.ruta||'Grupo')}</td><td>${actionButtons(m.id_menu)}</td></tr>`).join('');
        const values=[['rp-role',roles,'id_rol',x=>x.nombre],['role-role',roles,'id_rol',x=>x.nombre],['rp-permission',permissions,'id_permiso',x=>x.codigo],['mp-permission',permissions,'id_permiso',x=>x.codigo],['mp-menu',menus,'id_menu',x=>x.nombre],['menu-parent',menus,'id_menu',x=>x.nombre]];
        for(const [id,data,key,label] of values){const el=$(`#${id}`),prior=el.value;el.innerHTML=options(data,key,label,id==='menu-parent'?'Sin padre':'Seleccionar');el.value=prior;}
        showRelations();
    };
    for(const [form,resource] of [[roleForm,'roles'],[permissionForm,'permisos'],[menuForm,'menus_admin']])form.addEventListener('submit',e=>{e.preventDefault();saveForm(form,resource,reload);});
    for(const [rowId,form,resource,idKey,getRows] of [['role-rows',roleForm,'roles','id_rol',()=>roles],['permission-rows',permissionForm,'permisos','id_permiso',()=>permissions],['menu-rows',menuForm,'menus_admin','id_menu',()=>menus]]){
        $(`#${rowId}`).addEventListener('click',e=>{const id=e.target.dataset.edit||e.target.dataset.delete;if(!id)return;const row=getRows().find(x=>x[idKey]==id);if(e.target.dataset.edit)formSet(form,row);else remove(resource,idKey,id,reload);});
    }
    const relate=async(resource,form,action)=>{try{await post(resource,action,form);notice(action==='delete'?'Relación revocada.':'Relación asignada.');await reload();}catch(e){notice(e.message,true);}};
    const rolePermissionForm=$('#role-permission-form'),menuPermissionForm=$('#menu-permission-form');
    rolePermissionForm.addEventListener('submit',e=>{e.preventDefault();relate('rol_permisos',rolePermissionForm,'assign');});
    menuPermissionForm.addEventListener('submit',e=>{e.preventDefault();relate('menu_permisos',menuPermissionForm,'assign');});
    $('#rp-revoke').addEventListener('click',()=>relate('rol_permisos',rolePermissionForm,'delete'));
    $('#mp-revoke').addEventListener('click',()=>relate('menu_permisos',menuPermissionForm,'delete'));
    $('#rp-role').addEventListener('change',showRelations);$('#mp-menu').addEventListener('change',showRelations);
    await reload();
}

document.querySelector('#theme-toggle')?.addEventListener('click',async()=>{const dark=document.documentElement.dataset.theme!=='dark';document.documentElement.dataset.theme=dark?'dark':'light';try{const profile=await api('perfil');const fd=new FormData();for(const key of ['id_titulo','id_universidad','numero_registro','firma_url','sello_url','firma_sello_url'])fd.set(key,profile[key]??'');if(dark)fd.set('modo_oscuro','1');await post('perfil','save',fd);}catch(e){notice(e.message,true);}});

if(root){({escuelas:initSchools,pacientes:initPatients,derivadores:initReferrers,informes:initReports,perfil:initProfile,usuarios:initUsers}[root.dataset.page]?.()||Promise.resolve()).catch(e=>notice(e.message,true));}
