<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/seguridad.php';
require_once __DIR__ . '/../backend/rotulos_service.php';
require_once __DIR__ . '/membrete_clinica.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$formato = obtenerFormatoRotulo((string) ($_POST['formato'] ?? ''));
if ($formato === null) {
    http_response_code(400);
    exit('Formato de rótulo no válido');
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
$mpdf->SetTitle($formato['nombre']);
$mpdf->SetAuthor('Clínica Médica Florentín');
$mpdf->SetDisplayMode('fullwidth');

$mpdf->WriteHTML('<style>
    body { color: #000; font-family: winarial, freesans, sans-serif; margin: 0; padding: 0; }
    .rotulo { border: 2mm solid #000; border-collapse: collapse; height: 84mm; margin: 0 0 4mm; page-break-inside: avoid; table-layout: fixed; width: 100%; }
    .rotulo td { font-family: winarial, freesans, sans-serif; font-size: ' . (int) $formato['tamano_letra'] . 'pt; font-weight: normal; line-height: 1.04; padding: 5mm 7mm; text-align: center; vertical-align: middle; }
    .rotulo-linea { display: block; }
    .rotulo-linea + .rotulo-linea { margin-top: 3mm; }
</style>');

foreach ($pacientes as $paciente) {
    $valores = [
        'codigo' => rotuloHtml(formatearCodigoRotulo((string) ($paciente['codigo_paciente'] ?? ''))),
        'nombre' => rotuloHtml($paciente['nombre']),
        'cedula' => 'C.I.: ' . rotuloHtml(formatearCedulaRotulo((string) $paciente['ci'])),
    ];
    $lineas = '';
    foreach ($formato['campos'] as $campo) {
        $lineas .= '<div class="rotulo-linea rotulo-' . $campo . '">' . ($valores[$campo] ?? '') . '</div>';
    }
    $mpdf->WriteHTML(
        '<table class="rotulo"><tr><td>'
        . $lineas
        . '</td></tr></table>'
    );
}

$sufijoFormato = strtoupper($formato['codigo']);
$mpdf->Output(
    count($pacientes) === 1 ? "Rotulo_{$sufijoFormato}.pdf" : "Rotulos_{$sufijoFormato}.pdf",
    'I'
);

function formatearCedulaRotulo(string $documento): string
{
    $original = trim($documento);
    if ($original === '' || preg_match('/^\d+$/', $original) !== 1) {
        return $original;
    }
    return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $original) ?? $original;
}

function formatearCodigoRotulo(string $codigo): string
{
    $codigo = trim($codigo);
    $posicionGuion = strpos($codigo, '-');
    return $posicionGuion === false ? $codigo : trim(substr($codigo, 0, $posicionGuion));
}

function rotuloHtml($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
