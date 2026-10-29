<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$permisoReporte = 'reportes.rx.imprimir';
$id = $_POST['id'] ?? null;
$ids = $_POST['ids'] ?? [];

include __DIR__ . '/../backend/header_pacientes.php';

$configuracion = [
    'mode' => 'utf-8',
    'format' => 'legal',
    'margin_top' => 7.5,
    'margin_right' => 23.5,
    'margin_bottom' => 5,
    'margin_left' => 23.5,
];

$fuenteFirma = 'freeserif';
$rutaFuenteFirma = 'C:/Windows/Fonts/FRSCRIPT.TTF';
if (is_file($rutaFuenteFirma)) {
    $variables = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $variablesFuentes = (new \Mpdf\Config\FontVariables())->getDefaults();
    $configuracion['fontDir'] = array_merge($variables['fontDir'], [dirname($rutaFuenteFirma)]);
    $configuracion['fontdata'] = $variablesFuentes['fontdata'] + [
        'frenchscriptmt' => ['R' => basename($rutaFuenteFirma)],
    ];
    $fuenteFirma = 'frenchscriptmt';
}

$mpdf = new \Mpdf\Mpdf($configuracion);
$mpdf->SetTitle('Informes RX');
$mpdf->SetAuthor('Clínica Médica Florentín');

$logo = str_replace('\\', '/', __DIR__ . '/../images/img_logo.png');
$css = '
<style>
    body { color: #000; font-family: "Times New Roman", freeserif, serif; font-size: 10pt; }
    .rx-header { height: 45mm; }
    .rx-header-layout { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .rx-header-copy { color: #777; font-family: Arial, sans-serif; font-weight: bold; text-align: center; vertical-align: top; }
    .rx-header-text { border-collapse: collapse; margin: 0 auto; width: 80mm; }
    .rx-header-text td { color: #777; font-family: Arial, sans-serif; font-weight: bold; text-align: center; }
    .rx-clinic { border-bottom: 0.35mm solid #777; font-size: 14pt; line-height: 1.2; padding: 1mm 0 4mm; }
    .rx-address { font-size: 9pt; line-height: 1.1; padding-top: 3mm; }
    .rx-phone { border-bottom: 0.35mm solid #777; font-size: 8.5pt; line-height: 1.15; padding: 1mm 0 5mm; }
    .rx-logo { height: 24mm; margin-right: -12mm; margin-top: 2mm; opacity: 0.55; width: 38mm; }
    .rx-content { margin-left: auto; margin-right: auto; width: 92%; }
    .rx-patient { border-collapse: collapse; font-family: "Times New Roman", freeserif, serif; font-size: 12pt; line-height: 1.1; margin: 0; table-layout: fixed; width: 100%; }
    .rx-patient td { padding: 0.5mm 0; vertical-align: top; }
    .rx-patient-label { width: 35%; }
    .rx-patient-separator { width: 3%; }
    .rx-patient-value { width: 17%; }
    .rx-patient-secondary-label { width: 9%; }
    .rx-patient-secondary-separator { width: 3%; }
    .rx-patient-secondary-value { width: 33%; }
    .rx-patient-rule { border-top: 0.25mm solid #000; margin: 0; width: 100%; }
    .rx-study-title { font-size: 14pt; font-weight: normal; margin: 19mm 0 11mm; text-align: center; }
    .rx-report-title { font-size: 11pt; margin: 0 0 5mm; text-decoration: underline; }
    .rx-findings { font-size: 11.5pt; font-style: italic; line-height: 1.25; margin: 0 0 0 9mm; padding-left: 4mm; }
    .rx-findings li { padding: 0 0 1mm 1.5mm; }
    .rx-lumbar { font-size: 9pt; line-height: 1.2; margin-top: 13mm; }
    .rx-lumbar-title { margin: 0 0 7mm 0; }
    .rx-lumbar-row { border-collapse: collapse; margin-left: 10mm; width: 85%; }
    .rx-lumbar-row td { padding: 0 0 6mm 0; vertical-align: top; }
    .rx-lumbar-dash { width: 7mm; }
    .rx-signature { font-size: 10pt; line-height: 1.15; margin: 2mm 0 0; }
    .rx-signature-name { font-family: ' . $fuenteFirma . ', freeserif, serif; font-size: 26pt; line-height: 1; margin: 1mm 0 0; }
    .rx-registration { font-family: "Times New Roman", freeserif, serif; font-size: 12pt; margin: 1.5mm 0 0 9.5mm; }
    .rx-bottom-rule { border-top: 0.25mm solid #000; margin: 5mm 0 0; width: 100%; }
    .rx-disc-space { height: 55mm; }
</style>';

$mpdf->WriteHTML($css);

foreach ($pacientes as $indice => $paciente) {
    if ($indice > 0) {
        $mpdf->AddPage();
    }

    $nombre = htmlspecialchars((string) $paciente['nombre'], ENT_QUOTES, 'UTF-8');
    $fecha = htmlspecialchars((string) $paciente['fecha'], ENT_QUOTES, 'UTF-8');
    $edad = htmlspecialchars((string) $paciente['edad'], ENT_QUOTES, 'UTF-8');
    $documento = htmlspecialchars((string) $paciente['ci'], ENT_QUOTES, 'UTF-8');
    $codigo = htmlspecialchars(trim((string) ($paciente['codigo_paciente'] ?? '')), ENT_QUOTES, 'UTF-8');
    $celdaCodigo = $codigo !== ''
        ? '<td class="rx-patient-secondary-label" width="9%">COD</td><td class="rx-patient-secondary-separator" width="3%">:</td><td class="rx-patient-secondary-value" width="33%">' . $codigo . '</td>'
        : '<td width="9%"></td><td width="3%"></td><td width="33%"></td>';

    $html = '
        <div class="rx-header">
            <table class="rx-header-layout">
                <tr>
                    <td width="14%"></td>
                    <td width="57%" class="rx-header-copy">
                        <table class="rx-header-text">
                            <tr><td class="rx-clinic">CLINICA MEDICA FLORENTIN</td></tr>
                            <tr><td class="rx-address">Villa Elisa, Calle 7 y Von Polesky</td></tr>
                            <tr><td class="rx-phone">+595987 67 88 88</td></tr>
                        </table>
                    </td>
                    <td width="29%" style="text-align:right; vertical-align:top;">
                        <img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" class="rx-logo">
                    </td>
                </tr>
            </table>
        </div>

        <div class="rx-content">
        <table class="rx-patient">
            <tr>
                <td class="rx-patient-label" width="35%">NOMBRE&nbsp;Y&nbsp;APELLIDO</td>
                <td class="rx-patient-separator" width="3%">:</td>
                <td colspan="4" width="62%">' . $nombre . '</td>
            </tr>
            <tr>
                <td class="rx-patient-label" width="35%">FECHA</td>
                <td class="rx-patient-separator" width="3%">:</td>
                <td class="rx-patient-value" width="17%">' . $fecha . '</td>
                ' . $celdaCodigo . '
            </tr>
            <tr>
                <td class="rx-patient-label" width="35%">EDAD</td>
                <td class="rx-patient-separator" width="3%">:</td>
                <td class="rx-patient-value" width="17%">' . $edad . '&nbsp; AÑOS</td>
                <td class="rx-patient-secondary-label" width="9%">CI</td>
                <td class="rx-patient-secondary-separator" width="3%">:</td>
                <td class="rx-patient-secondary-value" width="33%">' . $documento . '</td>
            </tr>
        </table>
        <div class="rx-patient-rule"></div>

        <div class="rx-study-title">RADIOGRAFÍA DE TÓRAX PA Y LATERAL</div>
        <div class="rx-report-title">INFORME RADIOLÓGICO:</div>

        <ul class="rx-findings">
            <li>Pulmones aireados, hilos simétricos, trama hiliar e hilo basal conservada.</li>
            <li>Índice cardiopulmonar conservado.</li>
            <li>Mediastino no ensanchado.</li>
            <li>Senos cardio y costofrénicos libres.</li>
            <li>No se identifican signos de consolidación.</li>
        </ul>

        <div class="rx-lumbar">
            <div class="rx-lumbar-title">RADIOGRAFIA DE COLUMNA LUMBAR AP Y LATERAL</div>
            <table class="rx-lumbar-row">
                <tr><td class="rx-lumbar-dash">-</td><td>Alineacion sagital y altura de los cuerpos vertebrales conservados</td></tr>
                <tr><td class="rx-lumbar-dash">-</td><td>Mineralización osea conservada</td></tr>
                <tr><td class="rx-lumbar-dash">-</td><td>No se aprecian trazos de fracturas</td></tr>
            </table>
        </div>

        <div class="rx-signature">
            <div>Atte.</div>
            <div class="rx-signature-name">Dr. Andrés Marecos</div>
            <div class="rx-registration">R.P: 8136</div>
        </div>
        <div class="rx-bottom-rule"></div>
        <div class="rx-disc-space"></div>
        </div>';

    $mpdf->WriteHTML($html);
}

$mpdf->Output('Informes_RX.pdf', 'I');
