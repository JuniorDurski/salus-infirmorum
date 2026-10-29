<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';

include     __DIR__ . '/../backend/calculo_laboratorio.php';

$id  = $_POST['id']  ?? null;
$ids = $_POST['ids'] ?? [];

if ($id === null && empty($ids)) {
    die('No se recibieron pacientes');
}
/* ===========================
   LISTA DE PACIENTES
=========================== */
    include __DIR__ . '/../backend/header_pacientes.php';

/* ===========================
   CONFIGURAR MPDF
=========================== */
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'legal',
    'margin_top'    => 20,
    'margin_bottom' => 68,
    'margin_left'   => 20,
    'margin_right'  => 5
]);

$mpdf->SetHTMLFooter($footer);


/* ===========================
   CSS — MÁS CHICO
=========================== */
$css = '
<style>

    body { font-family: Arial; }

    .card {
        width: 45%;
        margin-left: 0;
        padding: 10px;
        border: 2px solid #000;
    }

    .header-table {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }

    .header-logo {
        width: 55px;
    }

    .header-title {
        font-size: 12px;
        font-weight: bold;
        text-align: left;
        padding-top: 5px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    td.label {
        width: 28%;
        font-weight: bold;
        padding: 3px 0;
        white-space: nowrap;
    }

    td.valor {
        border-bottom: 1px solid #000;
        padding: 3px 0;
        font-size: 11px;
    }

</style>
';


$mpdf->WriteHTML($css);

/* ===========================
   GENERAR TARJETA
=========================== */

foreach ($pacientes as $p) {

    $html = '
<div class="card">

    <table class="header-table">
        <tr>
            <td class="label">
                <img src="'.__DIR__.'/../images/img_logo.png" class="header-logo">
            </td>
            <td class="header-title">
                GRUPO SANGUÍNEO Y FACTOR Rh
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="label">Nombre:</td>
            <td class="valor">'.htmlspecialchars($p["nombre"]).'</td>
        </tr>

        <tr>
            <td class="label">Cédula:</td>
            <td class="valor">'.htmlspecialchars($p["ci"]).'</td>
        </tr>

        <tr>
            <td class="label">Pertenece a:</td>
            <td class="valor">'.htmlspecialchars($p["tipi"]).'</td>
        </tr>

        <tr>
            <td class="label">Fecha:</td>
            <td class="valor">'.$p["fecha"].'</td>
        </tr>
    </table>

</div>
';


    $mpdf->WriteHTML($html);
    $mpdf->AddPage();
}

/* ===========================
   SALIDA
=========================== */
$mpdf->Output("tarjetas_postulantes.pdf", "I");
