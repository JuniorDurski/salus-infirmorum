<?php
include "conexion.php";
$usuarioAutenticado = requerirAutenticacion($conexion, 'escuelas.ver');
include "menu.php";
?>



<!-- CONTENEDOR GENERAL -->
<table width="95%" align="center" cellpadding="6" border="0">
    <tr >
        <th colspan="2"> <h2>C:\ ESCUELAS ></h2></th>
    </tr>
    <tr>
        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <td width="30%" valign="top">
            <table border="1" width="100%" cellpadding="5" align="left">
                <form id="frmDesignacion">
                    <thead>
                        <tr>
                            <th align="left"> <h3>CARGA DE DATOS</h3></th>
                        </tr>
                    </thead>
                    
                    <input type="hidden" name="id_escuela" id="id_escuela">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
                    <tr>
                        <th align="left">
                            <b>Escuela</b><br>
                            <input type="text" name="nombre" id="nombre" size="30" required>
                        </th>
                    </tr>
                    <tr>
                        <th align="left">
                            <b>Periodo (año y mes)</b><br>
                            <input type="number" name="periodo_anio" id="periodo_anio" min="1900" max="2200" size="6" required>
                            <select name="periodo_mes" id="periodo_mes" required>
                                <option value="">Mes</option>
                                <option value="1">Enero</option><option value="2">Febrero</option>
                                <option value="3">Marzo</option><option value="4">Abril</option>
                                <option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option>
                                <option value="9">Septiembre</option><option value="10">Octubre</option>
                                <option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                        </th>
                    </tr>
                    <tr>
                        <th align="left">
                            <b>País (opcional)</b><br>
                            <select name="id_pais" id="id_pais" style="width: 100%;"></select>
                        </th>
                    </tr>
                    <tr>
                        <th align="left">
                            <button type="submit">Guardar</button>
                            <button type="button" onclick="limpiar()">Cancelar</button>
                        </th>
                    </tr>
                </form>
            </table>

        </td>

        <!-- COLUMNA DERECHA: TABLA -->
        <td width="70%" valign="top">
            <table border="1" width="100%" cellpadding="5">
                <thead>
                    <tr>
                        <th colspan="4"> <h3>LOS DATOS CARGADOS</h3></th>
                    </tr>
                    <tr>
                        <th align="left">ID</th>
                        <th align="left">Escuela</th>
                        <th align="left">Periodo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla"></tbody>
            </table>

        </td>
    </tr>
</table>

<script>
const url = "backend/designacionesDAO.php";

function cargar() {
    fetch(url + "?q=listar")
        .then(r => r.json())
        .then(data => {
            let html = "";
            data.forEach(d => {
                html += `
                <tr>
                    <td>${d.id_escuela}</td>
                    <td>${d.nombre}</td>
                    <td>${d.periodo}</td>
                    <td style="width:150px">
                        <button onclick='editar(${JSON.stringify(d)})'>Editar</button>
                        <button onclick='eliminar(${d.id_escuela})'>Eliminar</button>
                    </td>
                </tr>`;
            });
            document.getElementById("tabla").innerHTML = html;
        });
}

document.getElementById("frmDesignacion").onsubmit = function(e){
    e.preventDefault();

    let form = new FormData(this);

    fetch(url + "?q=guardar", {
        method: "POST",
        body: form
    })
    .then(r => r.json())
    .then(res => {
        if (res.status == "ok") {
            limpiar();
            cargar();
        } else {
            alert(res.mensaje);
        }
    });
};

function editar(d) {
    id_escuela.value = d.id_escuela;
    nombre.value = d.nombre;
    periodo_anio.value = d.periodo_anio;
    periodo_mes.value = d.periodo_mes;
    id_pais.value = d.id_pais ?? '';
}

function eliminar(id) {
    if (!confirm("¿Eliminar registro?")) return;

    let fd = new FormData();
    fd.append("id", id);
    fd.append("csrf_token", csrfMenu);

    fetch(url + "?q=eliminar", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status == "ok") {
            cargar();
        } else {
            alert(res.mensaje);
        }
    });
}

function limpiar() {
    frmDesignacion.reset();
    id_escuela.value = "";
}

fetch('backend/referencialesDAO.php?q=paises')
    .then(r => r.json())
    .then(data => {
        id_pais.innerHTML = '<option value="">-- Sin especificar --</option>' +
            data.map(p => `<option value="${p.id_pais}">${p.nombre}</option>`).join('');
    });

cargar();
</script>

<?php include "footer.php"; ?>
