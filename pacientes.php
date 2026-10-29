<?php
include "conexion.php";
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
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

            <input type="hidden" name="id_paciente" id="id_paciente">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">

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
                    <b>Fecha de nacimiento</b><br>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>
                </th>
            
                <th align="left">
                    <b>Sexo</b><br>
                    <select name="es_femenino" id="es_femenino" required>
                        <option value="">-- Seleccione --</option>
                        <option value="0">MASCULINO</option>
                        <option value="1">FEMENINO</option>
                    </select>
                </th>
            </tr>

            <tr>
                <th align="left" colspan="2">
                    <b>Escuela</b><br>
                    <select name="id_escuela" id="id_escuela" style="width: 100%;" required></select>
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
                    <input type="text" name="codigo" id="codigo" size="20" maxlength="20">
                </th>
            </tr>
            <tr>
                <th align="left">
                    <b>País</b><br>
                    <select name="id_pais" id="id_pais" style="width: 100%;"></select>
                </th>
                <th align="left">
                    <b>Teléfono internacional</b><br>
                    <input type="tel" name="telefono" id="telefono" placeholder="+595981123456">
                </th>
                <th align="left">
                    <b>Foto del paciente</b><br>
                    <input type="hidden" name="url_foto" id="url_foto">
                    <input type="file" id="foto_paciente" accept="image/jpeg,image/png,image/webp">
                    <img id="vista_foto" src="" alt="Foto" width="80" style="display:none; margin-top:5px;">
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
                <th style="width:150px">Escuela</th>
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

/* ================= CARGAR ESCUELAS ================= */
function cargarEscuelas() {
    fetch("backend/designacionesDAO.php?q=listar")
        .then(r => r.json())
        .then(data => {
            let opt = "<option value=''>-- Seleccione --</option>";
            data.forEach(d => {
                opt += `<option value="${d.id_escuela}">${d.nombre} - ${d.periodo} </option>`;
            });
            id_escuela.innerHTML = opt;
        });
}

function cargarPaises() {
    fetch("backend/referencialesDAO.php?q=paises")
        .then(r => r.json())
        .then(data => {
            id_pais.innerHTML = '<option value="">-- Sin especificar --</option>' +
                data.map(p => `<option value="${p.id_pais}">${p.nombre} (${p.prefijo_telefonico})</option>`).join('');
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
frmPostulante.onsubmit = async function(e){
    e.preventDefault();
    let fd = new FormData(this);

    const respuesta = await fetch(url + "?q=guardar", {
        method: "POST",
        body: fd
    });
    const res = await respuesta.json();
    if (res.status == "ok") {
        if (foto_paciente.files[0]) {
            const foto = new FormData();
            foto.append('tipo', 'paciente_foto');
            foto.append('id_paciente', res.id_paciente);
            foto.append('archivo', foto_paciente.files[0]);
            foto.append('csrf_token', csrfMenu);
            const respuestaFoto = await fetch('backend/uploadDAO.php', { method: 'POST', body: foto });
            const resultadoFoto = await respuestaFoto.json();
            if (resultadoFoto.status !== 'ok') {
                alert(resultadoFoto.mensaje);
                return;
            }
        }
        limpiar();
        cargar();
    } else {
        alert(res.mensaje);
    }
};

/* ================= EDITAR ================= */
function editar(p) {
    id_paciente.value = p.id_paciente;
    nombre.value = p.nombre;
    codigo.value = p.codigo ?? '';
    ci.value = p.ci;
    fecha_nacimiento.value = p.fecha_nacimiento;
    es_femenino.value = p.es_femenino;
    grupo_sanguineo.value = p.grupo_sanguineo;
    id_escuela.value = p.id_escuela;
    id_pais.value = p.id_pais ?? '';
    telefono.value = p.telefono ?? '';
    url_foto.value = p.url_foto ?? '';
    vista_foto.src = p.url_foto || '';
    vista_foto.style.display = p.url_foto ? 'block' : 'none';
}


/* ================= ELIMINAR ================= */
function eliminar(id) {
    if (!confirm("¿Desactivar paciente?")) return;

    let fd = new FormData();
    fd.append("id", id);
    fd.append("csrf_token", csrfMenu);

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
    id_paciente.value = "";
    codigo.value = "";
    url_foto.value = "";
    vista_foto.style.display = 'none';
}


cargarEscuelas();
cargarPaises();
cargar();
</script>

<?php include "footer.php"; ?>
