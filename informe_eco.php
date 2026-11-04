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
.eco-study-options button { text-align: left; width: 100%; justify-content: space-between; }
.eco-estado-imagen { font-size: .72rem; }
.eco-print-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
.eco-print-label { color: var(--text-muted, #64748b); font-size: .9rem; font-weight: 600; }
.eco-image-editor { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.eco-image-slot { border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
.eco-image-slot > strong { display: block; margin-bottom: 8px; }
.eco-image-preview { background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; cursor: zoom-in; display: block; height: 180px; margin-bottom: 10px; object-fit: contain; width: 100%; }
.eco-image-zoom { margin-bottom: 10px; }
.eco-viewer { background: #0b0f14; border: 0; max-height: 96vh; max-width: 96vw; padding: 0; width: auto; }
.eco-viewer::backdrop { background: rgba(0, 0, 0, .8); }
.eco-viewer img { display: block; max-height: 90vh; max-width: 94vw; object-fit: contain; }
.eco-viewer-close { position: absolute; right: 10px; top: 10px; }
.eco-image-remove { align-items: center; display: flex; font-size: .9rem; gap: 7px; margin-top: 9px; }
.eco-print-option { align-items: flex-start; border: 1px solid var(--border); border-radius: 12px; display: flex; gap: 10px; padding: 14px; }
.eco-print-option input { margin-top: 3px; }
.eco-lote-modal { width: min(1100px, calc(100vw - 30px)); max-height: 94vh; }
.eco-lote-tabla select { min-width: 260px; width: 100%; }
.eco-lote-tabla td { vertical-align: middle; }
.eco-lote-estudios { display: flex; flex-wrap: wrap; gap: 4px; }
.eco-lote-resumen { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 12px; }
.eco-lote-fila-error { background: var(--danger-soft); }
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
    <div class="field-grid filtros-informe">
        <div class="campo">
            <label for="txtBuscar">Nombre o documento</label>
            <input type="search" id="txtBuscar" placeholder="Escriba para buscar…">
        </div>
        <div class="campo">
            <label for="f_designacion">Escuela</label>
            <select id="f_designacion"><option value="">Cargando…</option></select>
        </div>
        <div class="campo">
            <label for="f_fecha">Fecha de registro</label>
            <input type="date" id="f_fecha" value="<?= htmlspecialchars((string) $conexion->query('SELECT CURDATE() AS hoy')->fetch_assoc()['hoy'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="campo campo-boton"><button type="button" class="btn-secondary" data-ver-todos>Ver todos</button></div>
    </div>
</section>

<section class="card" style="margin-top:12px">
    <div class="card-header">
        <div><h2>Resultados</h2><p class="help-text" id="resumen">Use los filtros para buscar postulantes.</p></div>
        <div class="eco-print-actions acciones-impresion">
            <?php if ($puedeEditarEco): ?>
            <button type="button" class="btn-small" onclick="abrirLoteEco()" title="Subir el .zip del ecógrafo con una carpeta por postulante">Cargar comprimido</button>
            <?php endif; ?>
            <span class="eco-print-label">Imprimir todos:</span>
            <span id="botonesImpresionEco"></span>
        </div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Postulante</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th title="Estudios con al menos una imagen cargada">Cant.</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
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
        <button type="button" class="btn-print" onclick="confirmarImpresionEco()">Imprimir</button>
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

<dialog class="eco-viewer" id="visorImagenEco" onclick="if (event.target === this) this.close()">
    <button class="icon-button eco-viewer-close" type="button" onclick="visorImagenEco.close()" aria-label="Cerrar">×</button>
    <img id="visorImagenEcoImg" alt="Imagen ampliada">
</dialog>

<dialog class="modal eco-modal" id="modalImagenesEco">
    <form id="frmImagenesEco" class="modal-form">
        <div class="modal-header">
            <div><span class="eyebrow" id="imagenesEstudioTitulo">Imágenes ECO</span><h2 id="imagenesPostulanteTitulo">Imágenes</h2></div>
            <button class="icon-button" type="button" onclick="modalImagenesEco.close()" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body">
            <p class="help-text" style="margin-top:0">Puede cargar hasta dos imágenes (JPG, PNG o WEBP, máximo 15 MB cada una). Elegir un archivo reemplaza la imagen de esa posición. Al imprimir se ajustan solas al espacio libre de la hoja.</p>
            <div class="eco-image-editor" id="ecoImagenesSlots"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" onclick="modalImagenesEco.close()">Cancelar</button>
            <button type="submit">Guardar imágenes</button>
        </div>
    </form>
</dialog>

<?php if ($puedeEditarEco): ?>
<dialog class="modal eco-lote-modal" id="modalLoteEco">
    <div class="modal-header">
        <div><span class="eyebrow">Imágenes ECO</span><h2>Cargar comprimido</h2></div>
        <button class="icon-button" type="button" onclick="modalLoteEco.close()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
        <div id="loteEcoPaso1">
            <p class="help-text" style="margin-top:0">
                Suba el <strong>.zip</strong> del ecógrafo (una carpeta por postulante, nombrada con sus apellidos).
                Las carpetas se comparan con los <strong id="loteEcoCantidad">0</strong> postulantes de la lista actual,
                así que primero filtre por fecha o escuela. Las imágenes se asignan en orden:
                hombres <em>Abdominal → Testicular</em>; mujeres <em>Abdominal → Ginecológica → Mamaria</em>.
            </p>
            <div class="campo"><input type="file" id="loteEcoArchivo" accept=".zip,application/zip,application/x-zip-compressed"></div>
        </div>
        <div id="loteEcoPaso2" hidden>
            <div class="eco-lote-resumen" id="loteEcoResumen"></div>
            <p class="help-text" style="margin-top:0">Revise a quién corresponde cada carpeta. Puede corregir el postulante o elegir «No cargar».</p>
            <div class="table-wrap"><table class="data-table eco-lote-tabla">
                <thead><tr><th>Carpeta</th><th>Postulante</th><th>Se cargará como</th></tr></thead>
                <tbody id="loteEcoFilas"></tbody>
            </table></div>
            <label class="eco-print-option" style="margin-top:12px">
                <input type="checkbox" id="loteEcoReemplazar" checked>
                <span><strong>Reemplazar imágenes ya cargadas</strong><br><small>Si lo desmarca, los estudios que ya tienen imagen se dejan como están.</small></span>
            </label>
            <p class="help-text" id="loteEcoSinCarpeta"></p>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn-secondary" type="button" onclick="modalLoteEco.close()">Cancelar</button>
        <button type="button" id="loteEcoAnalizar" onclick="analizarLoteEco()">Analizar comprimido</button>
        <button type="button" id="loteEcoAplicar" onclick="aplicarLoteEco()" hidden>Cargar imágenes</button>
    </div>
</dialog>
<?php endif; ?>

<script>
const puedeEditarEco = <?= $puedeEditarEco ? 'true' : 'false' ?>;
let pacientesEco = [];
let imagenesEco = {}; // id_postulante -> Set de estudios con al menos una imagen
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
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)}</option>`
        ).join('');
        plantillasEco = respuestaPlantillas.plantillas || [];
        botonesImpresionEco.innerHTML = plantillasEco.map(plantilla =>
            `<button type="button" class="btn-secondary btn-small btn-print" data-eco-masivo="${Salus.escape(plantilla.codigo)}">${Salus.escape(plantilla.nombre)}</button>`
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
        await cargarImagenesEco();
        renderEco();
    } catch (error) {
        pacientesEco = [];
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No fue posible cargar los resultados.</td></tr>';
        Salus.toast(error.message, 'error');
    }
}

// Qué estudios de cada postulante ya tienen imagen (con una sola alcanza).
async function cargarImagenesEco() {
    imagenesEco = {};
    if (!pacientesEco.length) return;
    const params = new URLSearchParams({q:'estado_imagenes'});
    pacientesEco.forEach(paciente => params.append('ids[]', paciente.id_postulante));
    try {
        const respuesta = await Salus.json('backend/informesEcoDAO.php?' + params);
        Object.entries(respuesta.imagenes || {}).forEach(([id, codigos]) => { imagenesEco[id] = new Set(codigos); });
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

function tieneImagenEco(id, codigo) {
    return Boolean(imagenesEco[String(Number(id))]?.has(codigo));
}

// "Cant.": estudios con imagen sobre estudios que le corresponden (mujeres 3, hombres 2).
function cantidadImagenesEco(paciente) {
    const estudios = estudiosCompatiblesEco(paciente);
    const cargados = estudios.filter(plantilla => tieneImagenEco(paciente.id_postulante, plantilla.codigo)).length;
    const clase = cargados === estudios.length ? 'badge-success' : cargados > 0 ? 'badge-warn' : 'badge-muted';
    return `<span class="badge ${clase}">${cargados}/${estudios.length}</span>`;
}

function renderEco() {
    resumen.textContent = `${pacientesEco.length} postulante(s) encontrado(s)`;
    if (!pacientesEco.length) {
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
        return;
    }
    tabla.innerHTML = pacientesEco.map(paciente => {
        const id = Number(paciente.id_postulante);
        const editar = puedeEditarEco
            ? `<button class="btn-small btn-secondary" type="button" onclick="abrirSeleccionEco(${id}, 'editar')">Editar resultado</button>
               <button class="btn-small btn-secondary" type="button" onclick="abrirSeleccionEco(${id}, 'imagenes')">Imágenes</button>`
            : '';
        return `<tr>
            <td><strong>${Salus.escape(Salus.ordenNombre(paciente))}</strong><br><small>${Salus.escape(paciente.codigo_informe || '')}</small></td>
            <td>${Salus.escape(Salus.cedula(paciente.ci))}</td>
            <td>${Salus.escape(paciente.edad)} años<br><small>${Salus.escape(paciente.sexo)}</small></td>
            <td>${Salus.escape(paciente.escuela || '—')}</td>
            <td>${cantidadImagenesEco(paciente)}</td>
            <td><div class="table-actions">${editar}<button class="btn-small btn-secondary btn-print" type="button" onclick="abrirSeleccionEco(${id}, 'imprimir')">Imprimir individualmente</button></div></td>
        </tr>`;
    }).join('');
}

function estudiosCompatiblesEco(paciente) {
    const sexo = String(paciente.sexo || '').toUpperCase();
    return plantillasEco.filter(plantilla => !plantilla.sexo || plantilla.sexo === sexo);
}

function abrirSeleccionEco(id, accion) {
    if ((accion === 'editar' || accion === 'imagenes') && !puedeEditarEco) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const estudios = estudiosCompatiblesEco(paciente);
    postulanteEcoActual = Number(id);
    accionEcoActual = accion;
    seleccionAccionEco.textContent = {
        editar: 'Editar resultado ECO',
        imagenes: 'Imágenes del informe ECO',
    }[accion] || 'Imprimir informe ECO';
    seleccionPostulanteEco.textContent = Salus.ordenNombre(paciente);
    seleccionEstudiosEco.innerHTML = estudios.map(plantilla => {
        const estado = accion !== 'imagenes' ? ''
            : tieneImagenEco(id, plantilla.codigo)
                ? '<span class="badge badge-success eco-estado-imagen">Imagen cargada</span>'
                : '<span class="badge badge-muted eco-estado-imagen">Sin imagen</span>';
        return `<button type="button" class="btn-secondary" data-eco-estudio="${Salus.escape(plantilla.codigo)}"><span>${Salus.escape(plantilla.nombre)}</span>${estado}</button>`;
    }).join('');
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
    else if (accion === 'imagenes') abrirImagenesEco(id, codigo);
    else prepararImpresionEco([id], codigo);
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
        ecoPostulanteTitulo.textContent = Salus.ordenNombre(paciente);
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

async function abrirImagenesEco(id, codigo) {
    if (!puedeEditarEco) return;
    const paciente = pacientesEco.find(item => Number(item.id_postulante) === Number(id));
    if (!paciente) return;
    const params = new URLSearchParams({q:'detalle', estudio:codigo, id_postulante:id});
    try {
        const detalle = await Salus.json('backend/informesEcoDAO.php?' + params);
        liberarVistasImagenEco();
        postulanteEcoActual = Number(id);
        estudioEcoActual = codigo;
        imagenesEstudioTitulo.textContent = 'Imágenes · ' + detalle.plantilla.nombre;
        imagenesPostulanteTitulo.textContent = Salus.ordenNombre(paciente);
        const imagenes = Array.isArray(detalle.imagenes) ? detalle.imagenes : [null, null];
        ecoImagenesSlots.innerHTML = [1, 2].map(posicion => bloqueImagenEco(posicion, imagenes[posicion - 1] || '')).join('');
        configurarControlesImagenEco(imagenes);
        modalImagenesEco.showModal();
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

function bloqueImagenEco(posicion, url) {
    const existente = Boolean(url);
    return `<div class="eco-image-slot">
        <strong>Imagen ${posicion}</strong>
        <img class="eco-image-preview" id="ecoVistaImagen${posicion}" src="${Salus.escape(url)}" alt="Vista previa de imagen ${posicion}" title="Clic para ver en grande" ${existente ? '' : 'hidden'}>
        <button class="btn-small btn-secondary eco-image-zoom" type="button" id="ecoVerImagen${posicion}" ${existente ? '' : 'hidden'}>🔍 Ver en grande</button>
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
        const ver = document.getElementById(`ecoVerImagen${posicion}`);
        const urlGuardada = imagenes[posicion - 1] || '';
        const mostrar = (src, visible) => {
            vista.src = src;
            vista.hidden = !visible;
            ver.hidden = !visible;
        };
        const ampliar = () => { if (!vista.hidden) verImagenEco(vista.src); };
        vista.addEventListener('click', ampliar);
        ver.addEventListener('click', ampliar);
        archivo.addEventListener('change', () => {
            const seleccionado = archivo.files[0];
            if (!seleccionado) {
                mostrar(urlGuardada, Boolean(urlGuardada) && !quitar.checked);
                return;
            }
            if (seleccionado.size > 15 * 1024 * 1024) {
                archivo.value = '';
                Salus.toast('Cada imagen puede pesar como máximo 15 MB.', 'error');
                return;
            }
            const temporal = URL.createObjectURL(seleccionado);
            urlsTemporalesEco.push(temporal);
            quitar.checked = false;
            mostrar(temporal, true);
        });
        quitar.addEventListener('change', () => {
            if (quitar.checked) {
                archivo.value = '';
                mostrar(urlGuardada, false);
            } else {
                mostrar(urlGuardada, Boolean(urlGuardada));
            }
        });
    });
}

function verImagenEco(src) {
    visorImagenEcoImg.src = src;
    visorImagenEco.showModal();
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

frmImagenesEco.addEventListener('submit', async event => {
    event.preventDefault();
    if (!postulanteEcoActual || !estudioEcoActual) return;
    const boton = frmImagenesEco.querySelector('button[type="submit"]');
    const cuerpo = Salus.form({id_postulante:postulanteEcoActual, estudio:estudioEcoActual});
    [1, 2].forEach(posicion => {
        const archivo = document.getElementById(`ecoImagen${posicion}`).files[0];
        const quitar = document.getElementById(`ecoQuitarImagen${posicion}`).checked;
        if (archivo) cuerpo.append(`imagen_${posicion}`, archivo);
        if (quitar) cuerpo.append(`eliminar_imagen_${posicion}`, '1');
    });
    boton.disabled = true;
    try {
        await Salus.json('backend/informesEcoDAO.php?q=imagenes', {method:'POST', body:cuerpo});
        modalImagenesEco.close();
        Salus.toast('Imágenes guardadas.');
        await cargarImagenesEco();
        renderEco();
    } catch (error) {
        Salus.toast(error.message, 'error');
    } finally {
        boton.disabled = false;
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

// ---- Carga masiva desde un .zip ----
const ESTUDIOS_LOTE_ECO = {
    MASCULINO: ['ABDOMINAL_URGENCIA', 'TESTICULAR'],
    FEMENINO: ['ABDOMINAL_URGENCIA', 'GINECOLOGICA', 'MAMARIA'],
};
const ESTADOS_LOTE_ECO = {
    ok: ['badge-success', 'Coincide'],
    ambiguo: ['badge-warn', 'Varios posibles'],
    conflicto: ['badge-warn', 'Repetido'],
    sin_coincidencia: ['badge-danger', 'Sin coincidencia'],
};
let loteEco = null; // {lote, carpetas:[...]}

function abrirLoteEco() {
    if (!puedeEditarEco) return;
    loteEco = null;
    loteEcoArchivo.value = '';
    loteEcoCantidad.textContent = pacientesEco.length;
    loteEcoPaso1.hidden = false;
    loteEcoPaso2.hidden = true;
    loteEcoAnalizar.hidden = false;
    loteEcoAplicar.hidden = true;
    modalLoteEco.showModal();
}

async function analizarLoteEco() {
    const archivo = loteEcoArchivo.files[0];
    if (!archivo) return Salus.toast('Seleccione el archivo .zip.', 'error');
    if (!/\.zip$/i.test(archivo.name)) return Salus.toast('El archivo debe ser .zip.', 'error');
    if (archivo.size > 40 * 1024 * 1024) return Salus.toast('El comprimido puede pesar como máximo 40 MB.', 'error');
    if (!pacientesEco.length) return Salus.toast('No hay postulantes en la lista. Ajuste los filtros.', 'error');
    const cuerpo = Salus.form();
    cuerpo.append('comprimido', archivo);
    pacientesEco.forEach(paciente => cuerpo.append('ids[]', paciente.id_postulante));
    loteEcoAnalizar.disabled = true;
    loteEcoAnalizar.textContent = 'Analizando…';
    try {
        loteEco = await Salus.json('backend/informesEcoDAO.php?q=lote_analizar', {method:'POST', body:cuerpo});
        renderLoteEco();
        loteEcoPaso1.hidden = true;
        loteEcoPaso2.hidden = false;
        loteEcoAnalizar.hidden = true;
        loteEcoAplicar.hidden = false;
    } catch (error) {
        Salus.toast(error.message, 'error');
    } finally {
        loteEcoAnalizar.disabled = false;
        loteEcoAnalizar.textContent = 'Analizar comprimido';
    }
}

function pacienteEcoPorId(id) {
    return pacientesEco.find(item => Number(item.id_postulante) === Number(id)) || null;
}

function renderLoteEco() {
    const ordenados = [...pacientesEco].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre)));
    loteEcoFilas.innerHTML = loteEco.carpetas.map((fila, indice) => {
        const sugeridos = (fila.candidatos || []).map(Number);
        const opcion = paciente => {
            const id = Number(paciente.id_postulante);
            return `<option value="${id}" ${id === Number(fila.id_postulante) ? 'selected' : ''}>${Salus.escape(Salus.ordenNombre(paciente))} · ${Salus.escape(paciente.sexo)}</option>`;
        };
        const grupoSugeridos = sugeridos.map(pacienteEcoPorId).filter(Boolean);
        const resto = ordenados.filter(paciente => !sugeridos.includes(Number(paciente.id_postulante)));
        const [clase, texto] = ESTADOS_LOTE_ECO[fila.estado] || ESTADOS_LOTE_ECO.sin_coincidencia;
        return `<tr data-lote-fila="${indice}">
            <td><strong>${Salus.escape(fila.apellidos || fila.carpeta)}</strong><br>
                <small>${Number(fila.imagenes)} imagen(es)</small> <span class="badge ${clase}">${texto}</span></td>
            <td><select data-lote-select="${indice}">
                <option value="">— No cargar —</option>
                ${grupoSugeridos.length ? `<optgroup label="Sugeridos">${grupoSugeridos.map(opcion).join('')}</optgroup>` : ''}
                <optgroup label="Todos los de la lista">${resto.map(opcion).join('')}</optgroup>
            </select></td>
            <td class="eco-lote-estudios" data-lote-estudios="${indice}"></td>
        </tr>`;
    }).join('');
    loteEcoFilas.querySelectorAll('[data-lote-select]').forEach(select => select.addEventListener('change', actualizarLoteEco));
    actualizarLoteEco();
}

// Recalcula los estudios de cada fila, los repetidos y el resumen al cambiar una asignación.
function actualizarLoteEco() {
    const elegidos = [...loteEcoFilas.querySelectorAll('[data-lote-select]')].map(select => select.value);
    const usos = elegidos.reduce((mapa, id) => (id && (mapa[id] = (mapa[id] || 0) + 1), mapa), {});
    let listas = 0, imagenes = 0, problemas = 0;
    loteEco.carpetas.forEach((fila, indice) => {
        const celda = loteEcoFilas.querySelector(`[data-lote-estudios="${indice}"]`);
        const tr = celda.closest('tr');
        const id = elegidos[indice];
        const paciente = id ? pacienteEcoPorId(id) : null;
        tr.classList.remove('eco-lote-fila-error');
        if (!paciente) { celda.innerHTML = '<small class="help-text">No se cargará</small>'; return; }
        if (usos[id] > 1) {
            tr.classList.add('eco-lote-fila-error');
            celda.innerHTML = '<span class="badge badge-danger">Postulante asignado a otra carpeta</span>';
            problemas++;
            return;
        }
        const codigos = ESTUDIOS_LOTE_ECO[String(paciente.sexo).toUpperCase()] || ESTUDIOS_LOTE_ECO.MASCULINO;
        const cantidad = Number(fila.imagenes);
        if (cantidad > codigos.length) {
            tr.classList.add('eco-lote-fila-error');
            celda.innerHTML = `<span class="badge badge-danger">${cantidad} imágenes; se esperan ${codigos.length}. No se cargará</span>`;
            problemas++;
            return;
        }
        celda.innerHTML = codigos.slice(0, cantidad).map(codigo => {
            const nombre = plantillasEco.find(item => item.codigo === codigo)?.nombre || codigo;
            const reemplaza = tieneImagenEco(paciente.id_postulante, codigo);
            return `<span class="badge ${reemplaza ? 'badge-warn' : 'badge-success'}" title="${reemplaza ? 'Ya tiene imagen' : 'Sin imagen todavía'}">${Salus.escape(nombre)}${reemplaza ? ' ↻' : ''}</span>`;
        }).join('') + (cantidad < codigos.length ? ` <small class="help-text">(faltan ${codigos.length - cantidad})</small>` : '');
        listas++;
        imagenes += cantidad;
    });
    const sinCarpeta = pacientesEco.filter(paciente => !usos[String(Number(paciente.id_postulante))]);
    loteEcoResumen.innerHTML = `<span class="badge badge-success">${listas} postulante(s) · ${imagenes} imagen(es)</span>`
        + (problemas ? `<span class="badge badge-danger">${problemas} con problemas</span>` : '')
        + `<span class="badge badge-muted">${loteEco.carpetas.length} carpeta(s) en el zip</span>`;
    loteEcoSinCarpeta.textContent = sinCarpeta.length
        ? `Postulantes de la lista sin carpeta (${sinCarpeta.length}): ` + sinCarpeta.map(paciente => paciente.nombre).join(', ')
        : 'Todos los postulantes de la lista tienen carpeta.';
    loteEcoAplicar.disabled = problemas > 0 || listas === 0;
    loteEcoAplicar.textContent = `Cargar ${imagenes} imagen(es)`;
}

async function aplicarLoteEco() {
    if (!loteEco) return;
    const asignaciones = loteEco.carpetas.map((fila, indice) => ({
        carpeta: fila.carpeta,
        id_postulante: Number(loteEcoFilas.querySelector(`[data-lote-select="${indice}"]`).value) || 0,
    })).filter(item => item.id_postulante > 0);
    loteEcoAplicar.disabled = true;
    loteEcoAplicar.textContent = 'Cargando…';
    try {
        const respuesta = await Salus.json('backend/informesEcoDAO.php?q=lote_aplicar', {
            method:'POST',
            body:Salus.form({lote:loteEco.lote, asignaciones:JSON.stringify(asignaciones), reemplazar:loteEcoReemplazar.checked ? '1' : '0'}),
        });
        modalLoteEco.close();
        let mensaje = `${respuesta.cargadas} imagen(es) cargadas para ${respuesta.postulantes} postulante(s).`;
        if (respuesta.omitidas) mensaje += ` ${respuesta.omitidas} omitida(s) porque ya tenían imagen.`;
        Salus.toast(mensaje, respuesta.errores?.length ? 'error' : undefined);
        if (respuesta.errores?.length) alert('Algunas imágenes no se cargaron:\n\n' + respuesta.errores.join('\n'));
        await cargarImagenesEco();
        renderEco();
    } catch (error) {
        Salus.toast(error.message, 'error');
        actualizarLoteEco();
    }
}

modalImagenesEco.addEventListener('close', liberarVistasImagenEco);
Salus.filtrosAutomaticos([txtBuscar, f_designacion, f_fecha], buscar);
iniciarEco().then(buscar);
</script>
<?php include 'footer.php'; ?>
