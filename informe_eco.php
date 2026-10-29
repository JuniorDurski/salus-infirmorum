<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.eco.imprimir');
$puedeEditarEco = tienePermiso($usuarioAutenticado, 'reportes.eco.editar');
$tituloPagina = 'Informe ECO';
include 'menu.php';
?>

<style>
.eco-editor { display: grid; gap: 15px; }
.eco-editor .campo { margin: 0; }
.eco-editor textarea { min-height: 105px; }
.eco-editor .eco-introduccion textarea, .eco-editor .eco-conclusion textarea { min-height: 125px; }
.eco-modal { width: min(880px, calc(100vw - 30px)); max-height: 92vh; }
</style>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Documentos</span>
        <h1>Informe ECO</h1>
        <p>Seleccione el estudio, edite sus resultados por postulante y genere los informes.</p>
    </div>
</div>

<section class="card">
    <div class="field-grid" style="grid-template-columns:1.5fr 1.2fr 1fr 1.5fr">
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
        <div class="campo">
            <label for="f_estudio">Estudio a imprimir</label>
            <select id="f_estudio" required><option value="">Cargando estudios…</option></select>
        </div>
    </div>
    <div class="form-actions">
        <button type="button" onclick="buscar()">Buscar postulantes</button>
        <button type="button" class="btn-secondary" onclick="imprimirTodos()">Imprimir todos</button>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header">
        <div><h2>Resultados</h2><p class="help-text" id="resumen">Seleccione un estudio y use los filtros.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Postulante</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Resultado ECO</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
    </table></div>
</section>

<dialog class="modal eco-modal" id="modalEco">
    <form id="frmEco" class="modal-form">
        <div class="modal-header">
            <div><span class="eyebrow" id="ecoEstudioTitulo">Informe ECO</span><h2 id="ecoPostulanteTitulo">Editar resultado</h2></div>
            <button class="icon-button" type="button" onclick="modalEco.close()" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body"><div class="eco-editor" id="ecoCampos"></div></div>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" onclick="modalEco.close()">Cancelar</button>
            <button type="submit">Guardar resultado</button>
        </div>
    </form>
</dialog>

<script>
const puedeEditarEco = <?= $puedeEditarEco ? 'true' : 'false' ?>;
let pacientesEco = [], estadosEco = {}, postulanteEcoActual = null;

async function iniciarEco() {
    try {
        const [escuelas, respuestaPlantillas] = await Promise.all([
            Salus.json('backend/designacionesDAO.php?q=listar'),
            Salus.json('backend/informesEcoDAO.php?q=plantillas')
        ]);
        f_designacion.innerHTML = '<option value="">Todas las escuelas</option>' + escuelas.map(escuela =>
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)} · ${Salus.escape(escuela.periodo)}</option>`
        ).join('');
        f_estudio.innerHTML = '<option value="">Seleccione un estudio</option>' + respuestaPlantillas.plantillas.map(plantilla =>
            `<option value="${Salus.escape(plantilla.codigo)}">${Salus.escape(plantilla.nombre)}</option>`
        ).join('');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

async function buscar() {
    if (!f_estudio.value) {
        Salus.toast('Seleccione el estudio que desea preparar.', 'error');
        f_estudio.focus();
        return;
    }
    const params = new URLSearchParams({q:'buscar', texto:txtBuscar.value, designacion:f_designacion.value, fecha:f_fecha.value});
    try {
        pacientesEco = await Salus.json('backend/postulantesDAO.php?' + params);
        await cargarEstadosEco();
        renderEco();
    } catch (error) {
        pacientesEco = [];
        estadosEco = {};
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No fue posible cargar los resultados.</td></tr>';
        Salus.toast(error.message, 'error');
    }
}

async function cargarEstadosEco() {
    estadosEco = {};
    if (!pacientesEco.length || !f_estudio.value) return;
    const params = new URLSearchParams({q:'resultados', estudio:f_estudio.value});
    pacientesEco.forEach(paciente => params.append('ids[]', paciente.id_postulante));
    const respuesta = await Salus.json('backend/informesEcoDAO.php?' + params);
    estadosEco = respuesta.resultados || {};
}

function renderEco() {
    resumen.textContent = `${pacientesEco.length} postulante(s) encontrado(s)`;
    if (!pacientesEco.length) {
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
        return;
    }
    tabla.innerHTML = pacientesEco.map(paciente => {
        const id = Number(paciente.id_postulante);
        const editado = Boolean(estadosEco[String(id)]);
        const estado = editado
            ? '<span class="badge badge-success">Editado</span>'
            : '<span class="badge badge-muted">Plantilla predeterminada</span>';
        const editar = puedeEditarEco
            ? `<button class="btn-small btn-secondary" type="button" onclick="editarEco(${id})">Editar resultado</button>`
            : '';
        return `<tr>
            <td><strong>${Salus.escape(paciente.nombre)}</strong><br><small>${Salus.escape(paciente.codigo || 'Sin código')}</small></td>
            <td>${Salus.escape(paciente.ci)}</td>
            <td>${Salus.escape(paciente.edad)} años<br><small>${Salus.escape(paciente.sexo)}</small></td>
            <td>${Salus.escape(paciente.escuela || '—')}</td>
            <td>${estado}</td>
            <td><div class="table-actions">${editar}<button class="btn-small btn-secondary" type="button" onclick="imprimirUno(${id})">Imprimir</button></div></td>
        </tr>`;
    }).join('');
}

async function editarEco(id) {
    if (!puedeEditarEco || !f_estudio.value) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const params = new URLSearchParams({q:'detalle', estudio:f_estudio.value, id_postulante:id});
    try {
        const detalle = await Salus.json('backend/informesEcoDAO.php?' + params);
        postulanteEcoActual = id;
        ecoEstudioTitulo.textContent = detalle.plantilla.nombre;
        ecoPostulanteTitulo.textContent = paciente.nombre;
        const resultado = detalle.resultado;
        const campos = detalle.plantilla.campos.map(campo => `<div class="campo">
            <label>${Salus.escape(campo.etiqueta)}</label>
            <textarea data-eco-campo="${Salus.escape(campo.clave)}" maxlength="8000">${Salus.escape(resultado.campos[campo.clave] || '')}</textarea>
        </div>`).join('');
        ecoCampos.innerHTML = `<div class="campo"><label>Motivo del estudio</label><input id="ecoMotivo" maxlength="500" value="${Salus.escape(resultado.motivo || '')}"></div>
            <div class="campo eco-introduccion"><label>Introducción</label><textarea id="ecoIntroduccion" maxlength="5000">${Salus.escape(resultado.introduccion || '')}</textarea></div>
            ${campos}
            <div class="campo eco-conclusion"><label>Conclusión</label><textarea id="ecoConclusion" maxlength="8000" placeholder="Este estudio puede no requerir conclusión.">${Salus.escape(resultado.conclusion || '')}</textarea></div>`;
        modalEco.showModal();
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

frmEco.addEventListener('submit', async event => {
    event.preventDefault();
    if (!postulanteEcoActual) return;
    const campos = {};
    ecoCampos.querySelectorAll('[data-eco-campo]').forEach(control => { campos[control.dataset.ecoCampo] = control.value; });
    const resultado = {motivo:ecoMotivo.value, introduccion:ecoIntroduccion.value, campos, conclusion:ecoConclusion.value};
    try {
        await Salus.json('backend/informesEcoDAO.php?q=guardar', {
            method:'POST',
            body:Salus.form({id_postulante:postulanteEcoActual, estudio:f_estudio.value, resultado:JSON.stringify(resultado)})
        });
        modalEco.close();
        await cargarEstadosEco();
        renderEco();
        Salus.toast('Resultado ECO guardado.');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
});

function enviarInformeEco(ids) {
    if (!f_estudio.value) { Salus.toast('Seleccione un estudio.', 'error'); return; }
    if (!ids.length) { Salus.toast('No hay postulantes para imprimir.', 'error'); return; }
    const form = document.createElement('form');
    form.method = 'POST'; form.action = 'reportes/informe_eco.php'; form.target = '_blank';
    const estudio = document.createElement('input'); estudio.type = 'hidden'; estudio.name = 'estudio'; estudio.value = f_estudio.value; form.appendChild(estudio);
    ids.forEach(id => { const input=document.createElement('input'); input.type='hidden'; input.name=ids.length===1?'id':'ids[]'; input.value=id; form.appendChild(input); });
    document.body.appendChild(form); form.submit(); form.remove();
}

function imprimirTodos() { enviarInformeEco(pacientesEco.map(paciente => Number(paciente.id_postulante))); }
function imprimirUno(id) { enviarInformeEco([id]); }

f_estudio.addEventListener('change', async () => {
    if (!pacientesEco.length) return;
    try { await cargarEstadosEco(); renderEco(); } catch (error) { Salus.toast(error.message, 'error'); }
});
txtBuscar.addEventListener('keydown', event => { if (event.key === 'Enter') buscar(); });
iniciarEco();
</script>
<?php include 'footer.php'; ?>
