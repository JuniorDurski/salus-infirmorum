<?php

/* ===========================
   PIE DE PÁGINA DEL PDF
=========================== */
$footer = '

    <table width="100%" style="border-top:2px solid #000; padding-top:8px; font-size:12px; line-height:1.3;">
        <tr>
            <td>
                &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
            </td>
            <td width="70%" style="text-align:left; font-size:15px; vertical-align:top;">
                <div>
                    <br>
                    Villa Elisa - Centro Von Poleski, calle 7, calle Toledo.<br>
                    Guayayvi - Centro Barrio Santa Rosa.<br>
                    Celular: +595 985 29 60 89 &nbsp; | &nbsp; Correo: laboratorioflorentin@gmail.com<br>
                    <span style="font-weight:bold;">HABILITADO POR MSPYBS y LCSP, DRHC 77/2022</span><br>
                </div>
            </td>

            <td width="30%" style="text-align:right; vertical-align:top;">
                <img src="'.__DIR__.'/../images/img_firma.jpg" style="width:300px;">
            </td>
        </tr>
    </table>
    <br><br><br>';

/* ===========================
   CABECERA DEL PDF
=========================== */
function headerPaciente($p) {
    return '
        <table width="100%" style="border-bottom:2px solid #000; padding:0px 0px;">
            <tr>
                <td width="20%" style="text-align:left;">
                    <img src="'.__DIR__.'/../images/img_logo.png" width="130">
                </td>

                <td width="60%" style="text-align:center; font-weight:bold; font-size:16px;">
                    <br>
                    CENTRO DE ANÁLISIS CLÍNICOS FLORENTIN<br>
                    <span style="font-size:12px;">Dra. Ninfa Raquel Florentin Baez</span><br>
                    <span style="font-size:11px;">Bioquímica R. P. 2.315</span>
                </td>

                <td width="20%" style="text-align:right;">
                    <img src="'.__DIR__.'/../images/img_qr.png" width="120">
                </td>
            </tr>
        </table>
        <table class="datos" width="100%" style="border-bottom:2px solid #000;">
            <tr>
                <td colspan="3"><strong>Apellidos y Nombres:</strong> '.$p["nombre"].'</td>
                <td style="width:240px" ><strong>Codigo:</strong> '.$p["id"].'</td>
            </tr>
            <tr>
                <td><strong>C.I.:</strong> '.$p["ci"].'</td>
                <td><strong>Edad:</strong> '.$p["edad"].'</td>
                <td><strong>Sexo:</strong> '.$p["sexo"].'</td>
                <td><strong>Fecha:</strong> '.$p["fecha"].'</td>
            </tr>
        </table>

        <table class="tabla" >
            <tr style="border-bottom: 2px solid #000;">
                <th class="section-title col-examen">
                    EXAMENES
                </th>
                <th class="section-title col-resultado">
                    RESULTADOS
                </th>
                <th class="section-title col-unidad">
                    UNIDAD
                </th>
                <th class="section-title col-referencia">
                    VALORES REFERENCIALES
                </th>
            </tr>
        </table>';
}
?>
