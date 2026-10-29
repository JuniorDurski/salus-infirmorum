<?php
include "conexion.php";
include "menu.php";
?>

<table width="95%" align="center" cellpadding="6" border="0">
<tr>
    <th colspan="2"><h2>C:\ PACIENTES ></h2></th>
</tr>

<tr>
<!-- ================= FORMULARIO ================= -->
<td width="30%" valign="top">
    <table border="1" width="100%" cellpadding="5">
        <form id="frmPostulante">
            <thead>
                <tr>
                    <th align="left" colspan="3"><h3>CARGA DE DATOS</h3></th>
                </tr>
            </thead>

            <input type="hidden" name="id_postulante" id="id_postulante">

            <tr>
                <th align="left" colspan="3">
                    <b>Nombre y Apellido</b><br>
                    <input type="text" name="nombre" id="nombre" size="48" required>
                </th>
            </tr>

            <tr>
                <th align="left">
                    <b>CI</b><br>
                    <input type="text" name="ci" id="ci" style="width: 95%;" required>
                </th>

                <th align="left">
                    <b>Edad</b><br>
                    <input type="text" name="edad" id="edad" size="2" style="width: 54px;" required>
                </th>
            
                <th align="left">
                    <b>Sexo</b><br>
                    <select name="sexo" id="sexo" required>
                        <option value="">-- Seleccione --</option>
                        <option value="MASCULINO">MASCULINO</option>
                        <option value="FEMENINO">FEMENINO</option>
                    </select>
                </th>
            
            <tr>
                <th align="left" colspan="2">
                    <b>Designación</b><br>
                    <select name="id_designacion" id="id_designacion" style="width: 100%;" required></select>
                </th>               
           
                <th align="left" >
                    <b>Grupo Sanguíneo</b><br>
                    <select name="grupo_sanguineo" id="grupo_sanguineo">
                        <option value="">-- Seleccione --</option>
                        <option value="A Rh Positivo">A Rh Positivo</option>
                        <option value="A Rh Negativo">A Rh Negativo</option>
                        <option value="B Rh Positivo">B Rh Positivo</option>
                        <option value="B Rh Negativo">B Rh Negativo</option>
                        <option value="AB Rh Positivo">AB Rh Positivo</option>
                        <option value="AB Rh Negativo">AB Rh Negativo</option>
                        <option value="O Rh Positivo">O Rh Positivo</option>
                        <option value="AO Rh Negativo">O Rh Negativo</option>
                    </select>
                </th>
            </tr>
            <tr>
                <th align="left" colspan="3">
                    <b>COD</b><br>
                    <input type="text" name="codigo" id="codigo" size="3" maxlength="3">
                </th>
            </tr>

            <tr>
                <th align="left" colspan="3">
                    <button type="submit">Guardar</button>
                    <button type="button" onclick="limpiar()">Cancelar</button>
                </th>
            </tr>
        </form>
    </table>
</td>

<!-- ================= TABLA ================= -->
<td width="70%" valign="top">
    <table border="1" width="100%" cellpadding="5">
        <thead>
            <tr>
                <th colspan="10"><h3>LOS DATOS CARGADOS</h3></th>
            </tr>
            <tr>
                <th style="width:20px">ID</th>
                <th style="width:20px">COD</th>
                <th>Nombre y Apellido</th>
                <th style="width:50px">CI</th>
                <th style="width:30px">Edad</th>
                <th style="width:50px">Sexo</th>
                <th style="width:150px">Designación</th>
                <th style="width:10px ">Rh</th>
                <th style="width:110px">Fecha de Alta</th>
                <th style="width:150px">Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla"></tbody>
    </table>
</td>
</tr>
</table>

<script>
const url = "backend/postulantesDAO.php";

/* ================= CARGAR DESIGNACIONES ================= */
function cargarDesignaciones() {
    fetch("backend/designacionesDAO.php?q=listar")
        .then(r => r.json())
        .then(data => {
            let opt = "<option value=''>-- Seleccione --</option>";
            data.forEach(d => {
                opt += `<option value="${d.id_designacion}">${d.designacion} - ${d.periodo} </option>`;
            });
            id_designacion.innerHTML = opt;
        });
}

/* ================= LISTAR ================= */
function cargar() {
    fetch(url + "?q=listar")
        .then(r => r.json())
        .then(data => {
            let html = "";
            data.forEach(p => {
                html += `
                <tr>
                    <td>${p.id_postulante}</td>
                    <td>${p.codigo ?? ''}</td>
                    <td>${p.nombre}</td>
                    <td>${p.ci}</td>
                    <td>${p.edad}</td>
                    <td>${p.sexo}</td>
                    <td>${p.designacion}</td>
                    <td>${formatoGrupoSanguineo(p.grupo_sanguineo)}</td>
                    <td>${p.fecha}</td>                    
                    <td>
                        <button onclick='editar(${JSON.stringify(p)})'>Editar</button>
                        <button onclick='eliminar(${p.id_postulante})'>Eliminar</button>
                    </td>
                </tr>`;
            });
            tabla.innerHTML = html;
        });
}

/* ================= GUARDAR ================= */
frmPostulante.onsubmit = function(e){
    e.preventDefault();
    let fd = new FormData(this);

    fetch(url + "?q=guardar", {
        method: "POST",
        body: fd
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

/* ================= EDITAR ================= */
function editar(p) {
    id_postulante.value = p.id_postulante;
    nombre.value = p.nombre;
    codigo.value = p.codigo ?? '';
    ci.value = p.ci;
    edad.value = p.edad;
    sexo.value = p.sexo;
    grupo_sanguineo.value = p.grupo_sanguineo;
    id_designacion.value = p.id_designacion;
}


/* ================= ELIMINAR ================= */
function eliminar(id) {
    if (!confirm("¿Eliminar postulante?")) return;

    let fd = new FormData();
    fd.append("id", id);

    fetch(url + "?q=eliminar", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status == "ok") cargar();
        else alert(res.mensaje);
    });
}

function limpiar() {
    frmPostulante.reset();
    id_postulante.value = "";
    codigo.value = "";
}


cargarDesignaciones();
cargar();
</script>

<?php include "footer.php"; ?>
