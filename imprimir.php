<?php
include "conexion.php";
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.imprimir');
include "menu.php";
?>

<table width="95%" align="center" cellpadding="6" border="0">

<!-- ================= CABECERA ================= -->
<tr>
    <th colspan="8" align="center">
        <h2>C:\ IMPRIMIR INFORMES ></h2>
    </th>
</tr>
<tr>
    <table width="95%" align="center" cellpadding="6" border="1">
        <th colspan="5" align="left">
            <b>Nombre o CI</b><br>
            <input type="text" id="txtBuscar" size="30">
        </th>

        <th>
            <b>Escuela</b><br>
            <select id="f_designacion"></select>
        </th>

        <th>
            <b>Fecha</b><br>
            <input type="date" id="f_fecha">
        </th>

        <th>
            <b>Acciones</b><br>
            <button onclick="buscar()">Buscar</button>
            <button onclick="imprimirTodo()">Imprimir</button>
            <button onclick="imprimirTarjeta()">Rh</button>
        </th>
    </table>
</tr>
<tr>
    <th>
        &nbsp;
    </th>
</tr>

<!-- ================= TABLA ================= -->
<tr>
<td colspan="9">
<table width="95%" align="center" cellpadding="6" border="1">
    <thead>
        <tr>
            <th style="width:20px">ID</th>
            <th style="width:20px">COD</th>
            <th>Nombre y Apellido</th>
            <th>CI</th>
            <th style="width:17px">Edad</th>
            <th>Sexo</th>
            <th style="width:150px">Escuela</th>
            <th>Rh</th>
            <th style="width:110px">Fecha Alta</th>
            <th style="width:238px">Acciones</th>
        </tr>
    </thead>
    <tbody id="tabla"></tbody>
</table>
</td>
</tr>

</table>

<script>
const url = "backend/postulantesDAO.php";
let idsActuales = [];

/* ================= ESCUELAS ================= */
function cargarEscuelas() {
    fetch("backend/designacionesDAO.php?q=listar")
    .then(r => r.json())
    .then(data => {
        let opt = "<option value=''>-- Seleccione --</option>";
        data.forEach(d => {
            opt += `<option value="${d.id_escuela}">${d.nombre} - ${d.periodo}</option>`;
        });
        f_designacion.innerHTML = opt;
    });
}

/* ================= BUSCAR ================= */
function buscar() {

    let params = new URLSearchParams({
        q: 'buscar',
        texto: txtBuscar.value,
        designacion: f_designacion.value,
        fecha: f_fecha.value
    });

    fetch(url + "?" + params.toString())
    .then(r => r.json())
    .then(data => {
        let html = "";
        idsActuales = [];

        data.forEach(p => {
            idsActuales.push(p.id_postulante);

            html += `
            <tr>
                <td>${p.id_postulante}</td>
                <td>${p.codigo}</td>
                <td>${p.nombre}</td>
                <td>${p.ci}</td>
                <td>${p.edad}</td>
                <td>${p.sexo}</td>
                <td>${p.designacion}</td>
                <td>${formatoGrupoSanguineo(p.grupo_sanguineo)}</td>
                <td>${p.fecha}</td>
                <td>
                    <a href="pacientes.php">Editar</a> |
                    <a href="examen.php?id=${p.id_postulante}">Resultado</a> |
                    <a href="#" onclick="imprimirUno(${p.id_postulante})">Imprimir</a>
                </td>
            </tr>`;
        });

        tabla.innerHTML = html;
    });
}

/* ================= IMPRIMIR TODO ================= */
function imprimirTodo() {
    const designacion = obtenerDesignacionTexto();

    if (idsActuales.length === 0) {
        alert("No hay datos para imprimir");
        return;
    }
    if (designacion == "") {
        alert("Seleccione una escuela");
        return;
    }

    let form = document.createElement("form");
    form.method = "POST";
    form.action = "reportes/index.php";
    form.target = "_blank";

    idsActuales.forEach(id => {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    // Enviar designación (solo texto)    
    let inputDesignacion = document.createElement("input");
    inputDesignacion.type = "hidden";
    inputDesignacion.name = "designacion";
    inputDesignacion.value = designacion;
    form.appendChild(inputDesignacion);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function imprimirTarjeta() {
    const designacion = obtenerDesignacionTexto();

    if (idsActuales.length === 0) {
        alert("No hay datos para imprimir");
        return;
    }
    if (designacion == "") {
        alert("Seleccione una escuela");
        return;
    }

    let form = document.createElement("form");
    form.method = "POST";
    form.action = "reportes/informe_tipi.php";
    form.target = "_blank";

    idsActuales.forEach(id => {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    // Enviar designación (solo texto)    
    let inputDesignacion = document.createElement("input");
    inputDesignacion.type = "hidden";
    inputDesignacion.name = "designacion";
    inputDesignacion.value = designacion;
    form.appendChild(inputDesignacion);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function imprimirUno(id) {
    const designacion = obtenerDesignacionTexto();

    let form = document.createElement("form");
    form.method = "POST";
    form.action = "reportes/index.php";
    form.target = "_blank";

    let inputId = document.createElement("input");
    inputId.type = "hidden";
    inputId.name = "id";
    inputId.value = id;
    form.appendChild(inputId);

    let inputDesignacion = document.createElement("input");
    inputDesignacion.type = "hidden";
    inputDesignacion.name = "designacion";
    inputDesignacion.value = designacion;
    form.appendChild(inputDesignacion);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}


function obtenerDesignacionTexto() {
    const sel = document.getElementById("f_designacion");

    if (!sel.value) return "";
    const texto = sel.options[sel.selectedIndex].text;
    return texto.split(" - ")[0].trim();
}

cargarEscuelas();
</script>

<?php include "footer.php"; ?>
