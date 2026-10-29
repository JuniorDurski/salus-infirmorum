<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.audiometria.imprimir');
$puedeEditarAudiometria = tienePermiso($usuarioAutenticado, 'reportes.audiometria.editar');
$tituloPagina = 'Informe audiometría';
include 'menu.php';
?>

<style>
.au-modal { width: min(640px, calc(100vw - 30px)); }
.au-modal textarea { min-height: 120px; text-transform: uppercase; }
.au-sugerencias { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.au-estado { font-size: .78rem; }
</style>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Documentos</span>
        <h1>Informe audiometría</h1>
        <p>Busque los postulantes, cargue o edite el DX y genere sus informes de audiometría.</p>
    </div>
</div>

<section class="card">
    <div class="field-grid" style="grid-template-columns:1.5fr 1.2fr 1fr">
        <div class="campo">
            <label for="txtBuscar">Nombre o documento</label>
            <input type="search" id="txtBuscar" placeholder="Escriba para buscar…">
        </div>
        <div class="campo">
            <label for="f_designacion">Escuela</label>
            <select id="f_designacion"><option value="">Cargando…</option></select>
        </div>
        <div class="campo">
            <label for="f_fecha">Fecha de alta</label>
            <input type="date" id="f_fecha">
        </div>
    </div>
    <div class="form-actions">
        <button type="button" onclick="buscar()">Buscar postulantes</button>
        <button type="button" class="btn-secondary" onclick="imprimirTodosAudiometria()">Imprimir todos</button>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header">
        <div><h2>Resultados</h2><p class="help-text" id="resumen">Use los filtros para buscar postulantes.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Postulante</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>DX</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
    </table></div>
</section>

<dialog class="modal au-modal" id="modalAudiometria">
    <form id="frmAudiometria" class="modal-form">
        <div class="modal-header">
            <div><span class="eyebrow">DX de audiometría</span><h2 id="auPostulanteTitulo">Editar DX</h2></div>
            <button class="icon-button" type="button" onclick="modalAudiometria.close()" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body">
            <div class="campo">
                <label for="auDx">DX</label>
                <textarea id="auDx" maxlength="2000" required></textarea>
                <div class="au-sugerencias">
                    <button type="button" class="btn-small btn-secondary" data-au-sugerencia="AUDICIÓN NORMAL PARA AMBOS OÍDOS">Audición normal ambos oídos</button>
                    <button type="button" class="btn-small btn-secondary" data-au-sugerencia="HIPOACUSIA LEVE EN OÍDO DERECHO">Hipoacusia leve OD</button>
                    <button type="button" class="btn-small btn-secondary" data-au-sugerencia="HIPOACUSIA LEVE EN OÍDO IZQUIERDO">Hipoacusia leve OI</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" onclick="modalAudiometria.close()">Cancelar</button>
            <button type="submit">Guardar DX</button>
        </div>
    </form>
</dialog>

<script>
const puedeEditarAudiometria = <?= $puedeEditarAudiometria ? 'true' : 'false' ?>;
let pacientesAudiometria = [];
let dxGuardadosAudiometria = new Set();
let postulanteAudiometriaActual = null;

async function iniciarAudiometria() {
    try {
        const escuelas = await Salus.json('backend/designacionesDAO.php?q=listar');
        f_designacion.innerHTML = '<option value="">Todas las escuelas</option>' + escuelas.map(escuela =>
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)} · ${Salus.escape(escuela.periodo)}</option>`
        ).join('');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

async function buscar() {
    const params = new URLSearchParams({q:'buscar', texto:txtBuscar.value, designacion:f_designacion.value, fecha:f_fecha.value});
    try {
        pacientesAudiometria = await Salus.json('backend/postulantesDAO.php?' + params);
        await cargarEstadosAudiometria();
        renderAudiometria();
    } catch (error) {
        pacientesAudiometria = [];
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No fue posible cargar los resultados.</td></tr>';
        Salus.toast(error.message, 'error');
    }
}

async function cargarEstadosAudiometria() {
    dxGuardadosAudiometria = new Set();
    if (!pacientesAudiometria.length) return;
    const params = new URLSearchParams({q:'estados'});
    pacientesAudiometria.forEach(paciente => params.append('ids[]', paciente.id_postulante));
    const respuesta = await Salus.json('backend/informesAudiometriaDAO.php?' + params);
    dxGuardadosAudiometria = new Set((respuesta.guardados || []).map(Number));
}

function renderAudiometria() {
    resumen.textContent = `${pacientesAudiometria.length} postulante(s) encontrado(s)`;
    if (!pacientesAudiometria.length) {
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
        return;
    }
    tabla.innerHTML = pacientesAudiometria.map(paciente => {
        const id = Number(paciente.id_postulante);
        const estado = dxGuardadosAudiometria.has(id)
            ? '<span class="badge badge-success au-estado">Cargado</span>'
            : '<span class="help-text au-estado">Predeterminado</span>';
        const editar = puedeEditarAudiometria
            ? `<button class="btn-small btn-secondary" type="button" onclick="editarAudiometria(${id})">Editar DX</button>`
            : '';
        return `<tr>
            <td><strong>${Salus.escape(paciente.nombre)}</strong><br><small>${Salus.escape(paciente.codigo_generado || paciente.codigo || '')}</small></td>
            <td>${Salus.escape(paciente.ci)}</td>
            <td>${Salus.escape(paciente.edad)} años<br><small>${Salus.escape(paciente.sexo)}</small></td>
            <td>${Salus.escape(paciente.escuela || '—')}</td>
            <td>${estado}</td>
            <td><div class="table-actions">${editar}<button class="btn-small btn-secondary" type="button" onclick="enviarInformeAudiometria([${id}])">Imprimir</button></div></td>
        </tr>`;
    }).join('');
}

async function editarAudiometria(id) {
    if (!puedeEditarAudiometria) return;
    const paciente = pacientesAudiometria.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    try {
        const detalle = await Salus.json('backend/informesAudiometriaDAO.php?' + new URLSearchParams({q:'detalle', id_postulante:id}));
        postulanteAudiometriaActual = Number(id);
        auPostulanteTitulo.textContent = paciente.nombre;
        auDx.value = detalle.dx || '';
        modalAudiometria.showModal();
        auDx.focus();
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

document.querySelectorAll('[data-au-sugerencia]').forEach(boton => {
    boton.addEventListener('click', () => { auDx.value = boton.dataset.auSugerencia; auDx.focus(); });
});

frmAudiometria.addEventListener('submit', async event => {
    event.preventDefault();
    if (!postulanteAudiometriaActual) return;
    try {
        await Salus.json('backend/informesAudiometriaDAO.php?q=guardar', {
            method:'POST',
            body:Salus.form({id_postulante:postulanteAudiometriaActual, dx:auDx.value.toUpperCase()})
        });
        dxGuardadosAudiometria.add(postulanteAudiometriaActual);
        modalAudiometria.close();
        renderAudiometria();
        Salus.toast('DX guardado.');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
});

function enviarInformeAudiometria(ids) {
    if (!ids.length) {
        Salus.toast('No hay postulantes para imprimir.', 'error');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reportes/informe_audiometria.php';
    form.target = '_blank';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = ids.length === 1 ? 'id' : 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function imprimirTodosAudiometria() {
    enviarInformeAudiometria(pacientesAudiometria.map(paciente => Number(paciente.id_postulante)));
}

txtBuscar.addEventListener('keydown', event => { if (event.key === 'Enter') buscar(); });
iniciarAudiometria();
</script>
<?php include 'footer.php'; ?>
