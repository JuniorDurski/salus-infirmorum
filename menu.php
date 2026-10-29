<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> | Postulantes |</title>
</head>

<script>
    const urlMenu = "backend/usuariosDAO.php";
    let oscuro = false;

    window.onload = function () {
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
            cuerpo.inp

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
        fetch(urlMenu + "?q=cambiar_fuente")
            .then(() => {
                aplicarModo();
                location.reload();
            });
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
                    <td>
                        <font size="4">|
                            <a href="designaciones.php">DESIGNACIONES </a>|
                        </font>
                    </td>
                    <td>
                        <font size="4">|
                            <a href="pacientes.php">PACIENTES </a>|
                        </font>
                    </td>
                    <td>
                        <font size="4">|
                            <a href="imprimir.php">IMPRIMIR INFORMES </a>|
                        </font>
                    </td>
                    

                    <td>
                        <font size="4">|
                            <a href="#" onclick="cambiarModo()">ASPECTO</a> <-> |
                        </font>
                    </td>

                    <td>
                        <font size="4">|
                            <a href="imprimir.php">SALIR</a> -> |
                        </font>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

