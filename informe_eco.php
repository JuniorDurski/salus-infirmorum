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
.eco-selection-modal { width: min(540px, calc(100vw - 30px)); }
.eco-study-options { display: grid; gap: 10px; }
.eco-study-options button { text-align: left; width: 100%; }
.eco-print-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
.eco-print-label { color: var(--text-muted, #64748b); font-size: .9rem; font-weight: 600; }
</style>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Documentos</span>
        <h1>Informe ECO</h1>
        <p>Busque los postulantes, elija el estudio al imprimir o editar y genere sus informes.</p>
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
    <div class="form-actions eco-print-actions">
        <button type="button" onclick="buscar()">Buscar postulantes</button>
        <span class="eco-print-label">Imprimir todos:</span>
        <span id="botonesImpresionEco"></span>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header">
        <div><h2>Resultados</h2><p class="help-text" id="resumen">Use los filtros para buscar postulantes.</p></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Postulante</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="5" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
    </table></div>
</section>

<dialog class="modal eco-selection-modal" id="modalSeleccionEco">
    <div class="modal-header">
        <div><span class="eyebrow" id="seleccionAccionEco">Informe ECO</span><h2 id="seleccionPostulanteEco">Seleccionar estudio</h2></div>
        <button class="icon-button" type="button" onclick="modalSeleccionEco.close()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
        <p class="help-text" style="margin-top:0">Seleccione el estudio que desea utilizar.</p>
        <div class="eco-study-options" id="seleccionEstudiosEco"></div>
    </div>
    <div class="modal-footer">
        <button class="btn-secondary" type="button" onclick="modalSeleccionEco.close()">Cancelar</button>
    </div>
</dialog>

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
let pacientesEco = [];
let plantillasEco = [];
let postulanteEcoActual = null;
let estudioEcoActual = '';
let accionEcoActual = '';

async function iniciarEco() {
    try {
        const [escuelas, respuestaPlantillas] = await Promise.all([
            Salus.json('backend/designacionesDAO.php?q=listar'),
            Salus.json('backend/informesEcoDAO.php?q=plantillas')
        ]);
        f_designacion.innerHTML = '<option value="">Todas las escuelas</option>' + escuelas.map(escuela =>
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)} · ${Salus.escape(escuela.periodo)}</option>`
        ).join('');
        plantillasEco = respuestaPlantillas.plantillas || [];
        botonesImpresionEco.innerHTML = plantillasEco.map(plantilla =>
            `<button type="button" class="btn-secondary btn-small" data-eco-masivo="${Salus.escape(plantilla.codigo)}">${Salus.escape(plantilla.nombre)}</button>`
        ).join(' ');
        botonesImpresionEco.querySelectorAll('[data-eco-masivo]').forEach(boton => {
            boton.addEventListener('click', () => imprimirTodos(boton.dataset.ecoMasivo));
        });
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

async function buscar() {
    const params = new URLSearchParams({q:'buscar', texto:txtBuscar.value, designacion:f_designacion.value, fecha:f_fecha.value});
    try {
        pacientesEco = await Salus.json('backend/postulantesDAO.php?' + params);
        renderEco();
    } catch (error) {
        pacientesEco = [];
        tabla.innerHTML = '<tr><td colspan="5" class="empty-state">No fue posible cargar los resultados.</td></tr>';
        Salus.toast(error.message, 'error');
    }
}

function renderEco() {
    resumen.textContent = `${pacientesEco.length} postulante(s) encontrado(s)`;
    if (!pacientesEco.length) {
        tabla.innerHTML = '<tr><td colspan="5" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
        return;
    }
    tabla.innerHTML = pacientesEco.map(paciente => {
        const id = Number(paciente.id_postulante);
        const editar = puedeEditarEco
            ? `<button class="btn-small btn-secondary" type="button" onclick="abrirSeleccionEco(${id}, 'editar')">Editar resultado</button>`
            : '';
        return `<tr>
            <td><strong>${Salus.escape(paciente.nombre)}</strong><br><small>${Salus.escape(paciente.codigo || 'Sin código')}</small></td>
            <td>${Salus.escape(paciente.ci)}</td>
            <td>${Salus.escape(paciente.edad)} años<br><small>${Salus.escape(paciente.sexo)}</small></td>
            <td>${Salus.escape(paciente.escuela || '—')}</td>
            <td><div class="table-actions">${editar}<button class="btn-small btn-secondary" type="button" onclick="abrirSeleccionEco(${id}, 'imprimir')">Imprimir</button></div></td>
        </tr>`;
    }).join('');
}

function estudiosCompatiblesEco(paciente) {
    const sexo = String(paciente.sexo || '').toUpperCase();
    return plantillasEco.filter(plantilla => !plantilla.sexo || plantilla.sexo === sexo);
}

function abrirSeleccionEco(id, accion) {
    if (accion === 'editar' && !puedeEditarEco) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const estudios = estudiosCompatiblesEco(paciente);
    postulanteEcoActual = Number(id);
    accionEcoActual = accion;
    seleccionAccionEco.textContent = accion === 'editar' ? 'Editar resultado ECO' : 'Imprimir informe ECO';
    seleccionPostulanteEco.textContent = paciente.nombre;
    seleccionEstudiosEco.innerHTML = estudios.map(plantilla =>
        `<button type="button" class="btn-secondary" data-eco-estudio="${Salus.escape(plantilla.codigo)}">${Salus.escape(plantilla.nombre)}</button>`
    ).join('');
    seleccionEstudiosEco.querySelectorAll('[data-eco-estudio]').forEach(boton => {
        boton.addEventListener('click', () => seleccionarEstudioEco(boton.dataset.ecoEstudio));
    });
    modalSeleccionEco.showModal();
}

function seleccionarEstudioEco(codigo) {
    const id = postulanteEcoActual;
    const accion = accionEcoActual;
    modalSeleccionEco.close();
    if (accion === 'editar') editarEco(id, codigo);
    else enviarInformeEco([id], codigo);
}

async function editarEco(id, codigo) {
    if (!puedeEditarEco) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const params = new URLSearchParams({q:'detalle', estudio:codigo, id_postulante:id});
    try {
        const detalle = await Salus.json('backend/informesEcoDAO.php?' + params);
        postulanteEcoActual = Number(id);
        estudioEcoActual = codigo;
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
    if (!postulanteEcoActual || !estudioEcoActual) return;
    const campos = {};
    ecoCampos.querySelectorAll('[data-eco-campo]').forEach(control => { campos[control.dataset.ecoCampo] = control.value; });
    const resultado = {motivo:ecoMotivo.value, introduccion:ecoIntroduccion.value, campos, conclusion:ecoConclusion.value};
    try {
        await Salus.json('backend/informesEcoDAO.php?q=guardar', {
            method:'POST',
            body:Salus.form({id_postulante:postulanteEcoActual, estudio:estudioEcoActual, resultado:JSON.stringify(resultado)})
        });
        modalEco.close();
        Salus.toast('Resultado ECO guardado.');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
});

function enviarInformeEco(ids, codigo) {
    if (!plantillasEco.some(plantilla => plantilla.codigo === codigo)) {
        Salus.toast('Seleccione un estudio válido.', 'error');
        return;
    }
    if (!ids.length) {
        Salus.toast('No hay postulantes compatibles para imprimir.', 'error');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reportes/informe_eco.php';
    form.target = '_blank';
    const estudio = document.createElement('input');
    estudio.type = 'hidden';
    estudio.name = 'estudio';
    estudio.value = codigo;
    form.appendChild(estudio);
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

function imprimirTodos(codigo) {
    const plantilla = plantillasEco.find(item => item.codigo === codigo);
    if (!plantilla) return;
    const compatibles = pacientesEco.filter(paciente => estudiosCompatiblesEco(paciente).some(item => item.codigo === codigo));
    enviarInformeEco(compatibles.map(paciente => Number(paciente.id_postulante)), codigo);
}

txtBuscar.addEventListener('keydown', event => { if (event.key === 'Enter') buscar(); });
iniciarEco();
</script>
<?php include 'footer.php'; ?>
