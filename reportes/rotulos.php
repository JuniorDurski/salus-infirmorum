<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/seguridad.php';
require_once __DIR__ . '/membrete_clinica.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$permisoReporte = 'reportes.rotulos.imprimir';
$id = null;
$ids = preg_split('/\s*,\s*/', trim((string) ($_POST['ids'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);

include __DIR__ . '/../backend/header_pacientes.php';
validarCsrf();

// header_pacientes consulta por IN; se restaura aquí el orden exacto recibido desde la pantalla.
$pacientesPorId = [];
foreach ($pacientes as $paciente) {
    $pacientesPorId[(int) $paciente['id_paciente']] = $paciente;
}
$pacientes = array_values(array_filter(array_map(
    static fn(int $idPaciente): ?array => $pacientesPorId[$idPaciente] ?? null,
    $listaIds
)));

$configuracion = configurarFuentesInforme([
    'mode' => 'utf-8',
    'format' => 'legal',
    'margin_top' => 8,
    'margin_right' => 8,
    'margin_bottom' => 8,
    'margin_left' => 8,
]);

$mpdf = new \Mpdf\Mpdf($configuracion);
$mpdf->SetTitle('Rótulos de postulantes');
$mpdf->SetAuthor('Clínica Médica Florentín');
$mpdf->SetDisplayMode('fullwidth');

$mpdf->WriteHTML('<style>
    body { color: #000; font-family: winarial, freesans, sans-serif; margin: 0; padding: 0; }
    .rotulo { border: 2mm solid #000; border-collapse: collapse; height: 84mm; margin: 0 0 4mm; page-break-inside: avoid; table-layout: fixed; width: 100%; }
    .rotulo td { font-family: winarial, freesans, sans-serif; font-size: 28pt; font-weight: normal; line-height: 1.04; padding: 5mm 7mm; text-align: center; vertical-align: middle; }
    .rotulo-nombre, .rotulo-cedula { display: block; }
    .rotulo-cedula { margin-top: 3mm; }
</style>');

foreach ($pacientes as $paciente) {
    $nombre = rotuloHtml($paciente['nombre']);
    $cedula = rotuloHtml(formatearCedulaRotulo((string) $paciente['ci']));
    $mpdf->WriteHTML(
        '<table class="rotulo"><tr><td>'
        . '<div class="rotulo-nombre">' . $nombre . '</div>'
        . '<div class="rotulo-cedula">C.I.: ' . $cedula . '</div>'
        . '</td></tr></table>'
    );
}

$mpdf->Output(count($pacientes) === 1 ? 'Rotulo_postulante.pdf' : 'Rotulos_postulantes.pdf', 'I');

function formatearCedulaRotulo(string $documento): string
{
    $original = trim($documento);
    if ($original === '' || preg_match('/^\d+$/', $original) !== 1) {
        return $original;
    }
    return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $original) ?? $original;
}

function rotuloHtml($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
