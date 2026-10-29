<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/informes_audiometria_service.php';
require_once __DIR__ . '/membrete_clinica.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$permisoReporte = 'reportes.audiometria.imprimir';
$id = $_POST['id'] ?? null;
$ids = $_POST['ids'] ?? [];
include __DIR__ . '/../backend/header_pacientes.php';

// DX guardados y fechas de nacimiento (header_pacientes no trae la fecha de nacimiento).
$dxGuardados = [];
$nacimientos = [];
$listaIds = array_map(static fn(array $paciente): int => (int) $paciente['id_paciente'], $pacientes);
$marcas = implode(',', array_fill(0, count($listaIds), '?'));
$tipos = str_repeat('i', count($listaIds));

$stmt = $conexion->prepare("SELECT id_postulante, dx FROM postulantes_informes_audiometria WHERE id_postulante IN ({$marcas})");
$stmt->bind_param($tipos, ...$listaIds);
$stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
    $dxGuardados[(int) $fila['id_postulante']] = $fila['dx'];
}

$stmt = $conexion->prepare(
    "SELECT id_paciente, DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') AS nacimiento
     FROM pacientes WHERE id_paciente IN ({$marcas})"
);
$stmt->bind_param($tipos, ...$listaIds);
$stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
    $nacimientos[(int) $fila['id_paciente']] = (string) ($fila['nacimiento'] ?? '');
}

$configuracion = configurarFuentesInforme([
    'mode' => 'utf-8',
    'format' => 'legal',
    'margin_top' => 4,
    // Márgenes laterales del modelo de Word (doc/MODELO AUDIOMETRIA.docx).
    'margin_right' => 23,
    'margin_bottom' => 15,
    'margin_left' => 23,
]);

$mpdf = new \Mpdf\Mpdf($configuracion);
$mpdf->SetTitle('Informes de audiometría');
$mpdf->SetAuthor('Clínica Médica Florentín');

// Tipografía del modelo: título Cambria 16; etiquetas Arial Black subrayadas (en Word al 85 % de ancho,
// aquí aproximado con un tamaño menor y letras más juntas); datos Arial Black 9;
// "AUDIOMETRIA TONAL" en Arial; línea del DX en Arial Narrow.
$css = '<style>
    body { color: #000; font-family: timesnewroman, freeserif, serif; }
    .au-title { font-family: wincambria, timesnewroman, serif; font-size: 16pt; margin: 6mm 0 11mm 76mm; }
    .au-data { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .au-data td { font-family: arialblack, freesans, sans-serif; font-size: 9pt; height: 6.3mm; padding: 0; vertical-align: middle; }
    .au-subtitle { font-family: winarial, freesans, sans-serif; font-size: 11.5pt; margin: 8mm 0 4mm; text-align: center; }
    .au-chart { margin: 0 0 18mm; text-align: center; }
    .au-dx { font-family: winarialnarrow, freesans, sans-serif; font-size: 11pt; line-height: 1.45; }
</style>';
$mpdf->WriteHTML($css);

$membrete = membreteClinicaHtml();
$audiograma = htmlspecialchars(str_replace('\\', '/', dirname(__DIR__) . '/images/audiograma.png'), ENT_QUOTES, 'UTF-8');

// Puntos suspensivos para la línea del DX y los renglones vacíos, como en el modelo.
$mpdf->SetFont('winarialnarrow', '', 11);
$anchoPunto = max(0.5, $mpdf->GetStringWidth('…'));
$renglonPuntos = str_repeat('…', (int) floor(($mpdf->pgwidth - 2) / $anchoPunto));
$guiaDx = str_repeat('…', (int) floor(26 / $anchoPunto));

foreach ($pacientes as $indice => $paciente) {
    if ($indice > 0) $mpdf->AddPage();
    $idPaciente = (int) $paciente['id_paciente'];
    $lineasDx = explode("\n", $dxGuardados[$idPaciente] ?? DX_AUDIOMETRIA_PREDETERMINADO);
    $fecha = str_replace('/', '-', (string) $paciente['fecha']);

    $html = $membrete
        . '<div class="au-title">AUDIOMETRÍA</div>'
        . '<table class="au-data">'
        . filaAudiometria('FECHA:', $fecha)
        . filaAudiometria('NOMBRE Y APELLIDO', $paciente['nombre'])
        . filaAudiometria('NÚMERO DE CÉDULA', $paciente['ci'])
        . filaAudiometria('FECHA DE NACIMIENTO', $nacimientos[$idPaciente] ?? '')
        . filaAudiometria('EDAD', $paciente['edad'], 8.5)
        . '</table>'
        . '<div class="au-subtitle">AUDIOMETRIA TONAL</div>'
        // El audiograma va en blanco: se completa a mano en la hoja impresa.
        . '<div class="au-chart"><img src="' . $audiograma . '" style="width:159.5mm;"></div>'
        . '<div class="au-dx">DX' . $guiaDx . audioHtml(array_shift($lineasDx));
    foreach ($lineasDx as $linea) $html .= '<br>' . audioHtml($linea);
    $html .= '<br>' . $renglonPuntos . '<br>' . $renglonPuntos . '</div>';
    $mpdf->WriteHTML($html);
}

$mpdf->Output('Informes_Audiometria.pdf', 'I');

/** Fila de datos: etiqueta subrayada a la izquierda y valor alineado en la columna de la derecha. */
function filaAudiometria(string $etiqueta, $valor, float $tamanoEtiqueta = 10): string
{
    return '<tr><td width="45%"><span style="font-size:' . $tamanoEtiqueta . 'pt; letter-spacing:-0.25pt; text-decoration:underline;">'
        . audioHtml($etiqueta) . '</span></td><td width="55%">' . audioHtml($valor) . '</td></tr>';
}

function audioHtml($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
