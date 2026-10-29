<?php
$identidadMenu = $usuarioAutenticado ?? requerirAutenticacion($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> | Postulantes |</title>
</head>

<script>
    const urlMenu = "backend/usuariosDAO.php";
    const csrfMenu = <?= json_encode(tokenCsrf()) ?>;
    let oscuro = false;

    window.onload = function () {
        cargarMenu();
        fetch(urlMenu + "?q=fuente_color")
            .then(r => r.json())
            .then(d => {
                oscuro = d.fuente_claro;
                aplicarModo();
            });
    };

    function aplicarModo() {
        const cuerpo = document.getElementById("cuerpo");
        const logo = document.getElementById("logo");
        const menu = document.getElementById("menu-colores");

        if (oscuro != 1) {
                // MODO OSCURO - VERDE (MATRIX)
            cuerpo.bgColor = "black";
            cuerpo.text = "lime";
            cuerpo.link = "lime";
            cuerpo.vLink = "lime";
            cuerpo.aLink = "lime";
            menu.color = "lime"; // cambiar menú a verde

            logo.src = "images/icono_menu_oscuro.png";

            aplicarModoCampos(true);
        } else {
            // MODO CLARO - AZUL
            cuerpo.bgColor = "white";
            cuerpo.text = "black";
            cuerpo.link = "003399";
            cuerpo.vLink = "003399";
            cuerpo.aLink = "003399";

            menu.color = "003399"; // menú azul

            logo.src = "images/icono_menu.png";
            aplicarModoCampos(false);
        }
    }

    function cambiarModo() {
        const datos = new FormData();
        datos.append('csrf_token', csrfMenu);
        fetch(urlMenu + "?q=cambiar_fuente", { method: 'POST', body: datos })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'error') {
                    alert(res.mensaje);
                    return;
                }
                aplicarModo();
                location.reload();
            });
    }

    function cargarMenu() {
        fetch('backend/menusDAO.php')
            .then(r => {
                if (r.status === 401) location.href = 'login.php';
                return r.json();
            })
            .then(menus => {
                const contenedor = document.getElementById('opciones-menu');
                contenedor.innerHTML = menus.map(renderMenu).join('');
            });
    }

    function renderMenu(menu) {
        const etiqueta = escaparHtml(menu.etiqueta);
        const principal = menu.url
            ? `<a href="${escaparHtml(menu.url)}">${etiqueta}</a>`
            : `<span>${etiqueta}</span>`;
        const hijos = (menu.hijos || []).map(hijo => {
            const texto = escaparHtml(hijo.etiqueta);
            return hijo.url ? `<a href="${escaparHtml(hijo.url)}">${texto}</a>` : texto;
        }).join(' &gt; ');

        return `<font size="4">| ${principal}${hijos ? ` [ ${hijos} ]` : ''} | </font>`;
    }

    function escaparHtml(valor) {
        const nodo = document.createElement('div');
        nodo.textContent = valor ?? '';
        return nodo.innerHTML;
    }

    function cerrarSesion() {
        const datos = new FormData();
        datos.append('csrf_token', csrfMenu);
        fetch(urlMenu + '?q=logout', { method: 'POST', body: datos })
            .then(() => location.href = 'login.php');
    }

    function aplicarModoCampos(esOscuro) {
        const inputs = document.getElementsByTagName("input");
        const selects = document.getElementsByTagName("select");
        const textareas = document.getElementsByTagName("textarea");
        const buttons = document.getElementsByTagName("button");

        const bg = esOscuro ? "black" : "white";
        const color = esOscuro ? "lime" : "black";
        const borde = esOscuro ? "1px solid lime" : "1px solid #003399";

        for (let i = 0; i < inputs.length; i++) {
            inputs[i].style.backgroundColor = bg;
            inputs[i].style.color = color;
            inputs[i].style.border = borde;
        }

        for (let i = 0; i < selects.length; i++) {
            selects[i].style.backgroundColor = bg;
            selects[i].style.color = color;
            selects[i].style.border = borde;
        }

        for (let i = 0; i < textareas.length; i++) {
            textareas[i].style.backgroundColor = bg;
            textareas[i].style.color = color;
            textareas[i].style.border = borde;
        }

        for (let i = 0; i < buttons.length; i++) {
            buttons[i].style.backgroundColor = bg;
            buttons[i].style.color = color;
            buttons[i].style.border = borde;
        }
    }

    function formatoGrupoSanguineo(texto) {
        if (!texto) return '';

        texto = texto.toUpperCase().trim();

        let grupo = '';

        if (texto.startsWith('AB')) grupo = 'AB';
        else if (texto.startsWith('A')) grupo = 'A';
        else if (texto.startsWith('B')) grupo = 'B';
        else if (texto.startsWith('O')) grupo = 'O';

        let signo = '';
        if (texto.includes('POSITIVO')) signo = '+';
        else if (texto.includes('NEGATIVO')) signo = '-';

        return grupo + signo;
    }
</script>

<body id="cuerpo" bgcolor="white" text="black" link="blue" vlink="blue" alink="blue">

<font face="monospace">

<!-- MENÚ CONSOLA -->
<table width="100%" border="0" cellspacing="4" cellpadding="6">
    <tr>

        <!-- LOGO -->
        <td width="118" align="left">
            <a href="index.php">
                <img id="logo" src="images/icono_menu.png" alt="Logo" width="200">
            </a>
        </td>

        <!-- OPCIONES DEL MENÚ -->
        <td align="left">

            <table id="menu-colores" border="0" cellspacing="5" cellpadding="2">
                <tr>
                    <td id="opciones-menu"></td>
                    <td>
                        <font size="4">|
                            <a href="#" onclick="cambiarModo()">ASPECTO</a> <-> |
                        </font>
                    </td>

                    <td>
                        <font size="4">|
                            <a href="#" onclick="cerrarSesion()">SALIR</a> -> |
                        </font>
                    </td>
                </tr>
            </table>

        </td>
        <td align="right" nowrap>
            <strong><?= htmlspecialchars($identidadMenu['nombre']) ?></strong><br>
            <small>@<?= htmlspecialchars($identidadMenu['usuario']) ?> |
                <?= htmlspecialchars(implode(', ', $identidadMenu['roles'])) ?></small>
        </td>
    </tr>
</table>

