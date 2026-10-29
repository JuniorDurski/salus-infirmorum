<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.rx.imprimir');
$tituloPagina = 'Informe RX';
include 'menu.php';
?>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Documentos</span>
        <h1>Informe RX</h1>
        <p>Busque postulantes y genere el informe radiológico en forma individual o masiva.</p>
    </div>
</div>

<section class="card">
    <div class="field-grid" style="grid-template-columns:2fr 1.4fr 1fr">
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
        <button type="button" class="btn-secondary" onclick="imprimirTodos()">Imprimir todos</button>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header">
        <div>
            <h2>Resultados</h2>
            <p class="help-text" id="resumen">Use los filtros para comenzar.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Postulante</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Alta</th><th></th></tr>
            </thead>
            <tbody id="tabla">
                <tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
let idsActuales = [];

async function cargarEscuelas() {
    try {
        const data = await Salus.json('backend/designacionesDAO.php?q=listar');
        f_designacion.innerHTML = '<option value="">Todas las escuelas</option>' + data.map(escuela =>
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)} · ${Salus.escape(escuela.periodo)}</option>`
        ).join('');
    } catch (error) {
        Salus.toast(error.message, 'error');
    }
}

async function buscar() {
    const params = new URLSearchParams({
        q: 'buscar',
        texto: txtBuscar.value,
        designacion: f_designacion.value,
        fecha: f_fecha.value
    });

    try {
        const data = await Salus.json('backend/postulantesDAO.php?' + params);
        idsActuales = data.map(postulante => Number(postulante.id_postulante));
        resumen.textContent = `${data.length} postulante(s) encontrado(s)`;
        tabla.innerHTML = data.length ? data.map(postulante => `<tr>
            <td><strong>${Salus.escape(postulante.nombre)}</strong><br><small>${Salus.escape(postulante.codigo_generado || postulante.codigo || '')}</small></td>
            <td>${Salus.escape(postulante.ci)}</td>
            <td>${Salus.escape(postulante.edad)} años<br><small>${Salus.escape(postulante.sexo)}</small></td>
            <td>${Salus.escape(postulante.escuela || '—')}</td>
            <td>${Salus.escape(postulante.fecha)}</td>
            <td><button class="btn-small btn-secondary" type="button" onclick="imprimirUno(${Number(postulante.id_postulante)})">Imprimir</button></td>
        </tr>`).join('') : '<tr><td colspan="6" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
    } catch (error) {
        idsActuales = [];
        resumen.textContent = 'No se pudo completar la búsqueda.';
        tabla.innerHTML = '<tr><td colspan="6" class="empty-state">No fue posible cargar los resultados.</td></tr>';
        Salus.toast(error.message, 'error');
    }
}

function enviarInforme(ids) {
    if (!ids.length) {
        Salus.toast('No hay postulantes para imprimir.', 'error');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reportes/informe_rx.php';
    form.target = '_blank';

    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = ids.length === 1 ? 'id' : 'ids[]';
        input.value = String(id);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function imprimirTodos() {
    enviarInforme(idsActuales);
}

function imprimirUno(id) {
    enviarInforme([id]);
}

txtBuscar.addEventListener('keydown', event => {
    if (event.key === 'Enter') buscar();
});

cargarEscuelas();
</script>
<?php include 'footer.php'; ?>
