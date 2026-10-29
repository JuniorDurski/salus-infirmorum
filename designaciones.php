<?php
include "conexion.php";
include "menu.php";
?>



<!-- CONTENEDOR GENERAL -->
<table width="95%" align="center" cellpadding="6" border="0">
    <tr >
        <th colspan="2"> <h2>C:\ DESIGNACIONES ><h2></th>
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
                    
                    <input type="hidden" name="id_designacion" id="id_designacion">
                    <tr>
                        <th align="left">
                            <b>Designación</b><br>
                            <input type="text" name="designacion" id="designacion" size="30" required>
                        </th>
                    </tr>
                    <tr>
                        <th align="left">
                            <b>Periodo</b><br>
                            <input type="text" name="periodo" id="periodo" size="30" required>
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
                        <th align="left">Designación</th>
                        <th align="left">Periodo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla"></tbody>
            </table>

        </td>
    </tz>
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
                    <td>${d.id_designacion}</td>
                    <td>${d.designacion}</td>
                    <td>${d.periodo}</td>
                    <td style="width:150px">
                        <button onclick='editar(${JSON.stringify(d)})'>Editar</button>
                        <button onclick='eliminar(${d.id_designacion})'>Eliminar</button>
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
    id_designacion.value = d.id_designacion;
    designacion.value    = d.designacion;
    periodo.value        = d.periodo;
}

function eliminar(id) {
    if (!confirm("¿Eliminar registro?")) return;

    let fd = new FormData();
    fd.append("id", id);

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
    id_designacion.value = "";
}

cargar();
</script>

<?php include "footer.php"; ?>
