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
.eco-image-editor { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.eco-image-slot { border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
.eco-image-slot > strong { display: block; margin-bottom: 8px; }
.eco-image-preview { background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; display: block; height: 180px; margin-bottom: 10px; object-fit: contain; width: 100%; }
.eco-image-remove { align-items: center; display: flex; font-size: .9rem; gap: 7px; margin-top: 9px; }
.eco-print-option { align-items: flex-start; border: 1px solid var(--border); border-radius: 12px; display: flex; gap: 10px; padding: 14px; }
.eco-print-option input { margin-top: 3px; }
@media (max-width: 680px) { .eco-image-editor { grid-template-columns: 1fr; } }
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

<dialog class="modal eco-selection-modal" id="modalImpresionEco">
    <div class="modal-header">
        <div><span class="eyebrow">Opciones de impresión</span><h2 id="impresionEstudioEco">Informe ECO</h2></div>
        <button class="icon-button" type="button" onclick="modalImpresionEco.close()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
        <label class="eco-print-option">
            <input type="checkbox" id="ecoIncluirImagenes" checked>
            <span><strong>Incluir imágenes</strong><br><small>Agrega al PDF las imágenes guardadas en cada informe. Puede desmarcarlo para imprimir solamente el texto.</small></span>
        </label>
    </div>
    <div class="modal-footer">
        <button class="btn-secondary" type="button" onclick="modalImpresionEco.close()">Cancelar</button>
        <button type="button" onclick="confirmarImpresionEco()">Imprimir</button>
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
let idsImpresionEco = [];
let codigoImpresionEco = '';
let urlsTemporalesEco = [];

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
    else prepararImpresionEco([id], codigo);
}

async function editarEco(id, codigo) {
    if (!puedeEditarEco) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const params = new URLSearchParams({q:'detalle', estudio:codigo, id_postulante:id});
    try {
        const detalle = await Salus.json('backend/informesEcoDAO.php?' + params);
        liberarVistasImagenEco();
        postulanteEcoActual = Number(id);
        estudioEcoActual = codigo;
        ecoEstudioTitulo.textContent = detalle.plantilla.nombre;
        ecoPostulanteTitulo.textContent = paciente.nombre;
        const resultado = detalle.resultado;
        const imagenes = Array.isArray(detalle.imagenes) ? detalle.imagenes : [null, null];
        const campos = detalle.plantilla.campos.map(campo => `<div class="campo">
            <label>${Salus.escape(campo.etiqueta)}</label>
            <textarea data-eco-campo="${Salus.escape(campo.clave)}" maxlength="8000">${Salus.escape(resultado.campos[campo.clave] || '')}</textarea>
        </div>`).join('');
        ecoCampos.innerHTML = `<div class="campo"><label>Motivo del estudio</label><input id="ecoMotivo" maxlength="500" value="${Salus.escape(resultado.motivo || '')}"></div>
            <div class="campo eco-introduccion"><label>Introducción</label><textarea id="ecoIntroduccion" maxlength="5000">${Salus.escape(resultado.introduccion || '')}</textarea></div>
            ${campos}
            <div class="campo eco-conclusion"><label>Conclusión</label><textarea id="ecoConclusion" maxlength="8000" placeholder="Este estudio puede no requerir conclusión.">${Salus.escape(resultado.conclusion || '')}</textarea></div>
            <div class="campo">
                <label>Imágenes del estudio</label>
                <small>Puede guardar hasta dos imágenes JPG, PNG o WEBP de 5 MB cada una. Seleccionar un archivo reemplaza la imagen de esa posición.</small>
                <div class="eco-image-editor">
                    ${[1, 2].map(posicion => bloqueImagenEco(posicion, imagenes[posicion - 1] || '')).join('')}
                </div>
            </div>`;
        configurarControlesImagenEco(imagenes);
        modalEco.showModal();
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

function bloqueImagenEco(posicion, url) {
    const existente = Boolean(url);
    return `<div class="eco-image-slot">
        <strong>Imagen ${posicion}</strong>
        <img class="eco-image-preview" id="ecoVistaImagen${posicion}" src="${Salus.escape(url)}" alt="Vista previa de imagen ${posicion}" ${existente ? '' : 'hidden'}>
        <input type="file" id="ecoImagen${posicion}" accept="image/jpeg,image/png,image/webp">
        <label class="eco-image-remove" id="ecoQuitarLabel${posicion}" ${existente ? '' : 'hidden'}>
            <input type="checkbox" id="ecoQuitarImagen${posicion}"> Quitar la imagen guardada
        </label>
    </div>`;
}

function configurarControlesImagenEco(imagenes) {
    [1, 2].forEach(posicion => {
        const archivo = document.getElementById(`ecoImagen${posicion}`);
        const quitar = document.getElementById(`ecoQuitarImagen${posicion}`);
        const vista = document.getElementById(`ecoVistaImagen${posicion}`);
        const urlGuardada = imagenes[posicion - 1] || '';
        archivo.addEventListener('change', () => {
            const seleccionado = archivo.files[0];
            if (!seleccionado) {
                vista.src = urlGuardada;
                vista.hidden = !urlGuardada || quitar.checked;
                return;
            }
            if (seleccionado.size > 5 * 1024 * 1024) {
                archivo.value = '';
                Salus.toast('Cada imagen puede pesar como máximo 5 MB.', 'error');
                return;
            }
            const temporal = URL.createObjectURL(seleccionado);
            urlsTemporalesEco.push(temporal);
            quitar.checked = false;
            vista.src = temporal;
            vista.hidden = false;
        });
        quitar.addEventListener('change', () => {
            if (quitar.checked) {
                archivo.value = '';
                vista.hidden = true;
            } else {
                vista.src = urlGuardada;
                vista.hidden = !urlGuardada;
            }
        });
    });
}

function liberarVistasImagenEco() {
    urlsTemporalesEco.forEach(url => URL.revokeObjectURL(url));
    urlsTemporalesEco = [];
}

frmEco.addEventListener('submit', async event => {
    event.preventDefault();
    if (!postulanteEcoActual || !estudioEcoActual) return;
    const campos = {};
    ecoCampos.querySelectorAll('[data-eco-campo]').forEach(control => { campos[control.dataset.ecoCampo] = control.value; });
    const resultado = {motivo:ecoMotivo.value, introduccion:ecoIntroduccion.value, campos, conclusion:ecoConclusion.value};
    try {
        const cuerpo = Salus.form({
            id_postulante:postulanteEcoActual,
            estudio:estudioEcoActual,
            resultado:JSON.stringify(resultado)
        });
        [1, 2].forEach(posicion => {
            const archivo = document.getElementById(`ecoImagen${posicion}`).files[0];
            const quitar = document.getElementById(`ecoQuitarImagen${posicion}`).checked;
            if (archivo) cuerpo.append(`imagen_${posicion}`, archivo);
            if (quitar) cuerpo.append(`eliminar_imagen_${posicion}`, '1');
        });
        await Salus.json('backend/informesEcoDAO.php?q=guardar', {
            method:'POST',
            body:cuerpo
        });
        modalEco.close();
        liberarVistasImagenEco();
        Salus.toast('Resultado ECO guardado.');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
});

function prepararImpresionEco(ids, codigo) {
    if (!ids.length) {
        Salus.toast('No hay postulantes compatibles para imprimir.', 'error');
        return;
    }
    const plantilla = plantillasEco.find(item => item.codigo === codigo);
    if (!plantilla) return;
    idsImpresionEco = ids;
    codigoImpresionEco = codigo;
    impresionEstudioEco.textContent = plantilla.nombre;
    ecoIncluirImagenes.checked = true;
    modalImpresionEco.showModal();
}

function confirmarImpresionEco() {
    const ids = [...idsImpresionEco];
    const codigo = codigoImpresionEco;
    const incluirImagenes = ecoIncluirImagenes.checked;
    modalImpresionEco.close();
    enviarInformeEco(ids, codigo, incluirImagenes);
}

function enviarInformeEco(ids, codigo, incluirImagenes) {
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
    const imagenes = document.createElement('input');
    imagenes.type = 'hidden';
    imagenes.name = 'incluir_imagenes';
    imagenes.value = incluirImagenes ? '1' : '0';
    form.appendChild(imagenes);
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
    prepararImpresionEco(compatibles.map(paciente => Number(paciente.id_postulante)), codigo);
}

modalEco.addEventListener('close', liberarVistasImagenEco);
txtBuscar.addEventListener('keydown', event => { if (event.key === 'Enter') buscar(); });
iniciarEco();
</script>
<?php include 'footer.php'; ?>
