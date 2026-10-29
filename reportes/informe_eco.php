<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/informes_eco_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$plantilla = plantillaInformeEco((string) ($_POST['estudio'] ?? ''));
if ($plantilla === null) exit('Estudio ECO no válido');

$permisoReporte = 'reportes.eco.imprimir';
$id = $_POST['id'] ?? null;
$ids = $_POST['ids'] ?? [];
include __DIR__ . '/../backend/header_pacientes.php';

$configuracion = [
    'mode' => 'utf-8',
    'format' => $plantilla['formato'],
    'margin_top' => 14,
    'margin_right' => 15,
    'margin_bottom' => 20,
    'margin_left' => 15,
];

$fuentesWindows = 'C:/Windows/Fonts';
if (is_file($fuentesWindows . '/times.ttf')) {
    $variables = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $variablesFuentes = (new \Mpdf\Config\FontVariables())->getDefaults();
    $configuracion['fontDir'] = array_merge($variables['fontDir'], [$fuentesWindows]);
    $configuracion['fontdata'] = $variablesFuentes['fontdata'] + [
        'timesnewroman' => [
            'R' => 'times.ttf', 'B' => 'timesbd.ttf',
            'I' => 'timesi.ttf', 'BI' => 'timesbi.ttf',
        ],
    ];
}

$guardados = [];
$listaIds = array_map(static fn(array $paciente): int => (int) $paciente['id_paciente'], $pacientes);
if ($listaIds !== []) {
    $marcas = implode(',', array_fill(0, count($listaIds), '?'));
    $stmt = $conexion->prepare(
        "SELECT id_postulante, resultados
         FROM postulantes_informes_eco
         WHERE codigo_estudio = ? AND id_postulante IN ({$marcas})"
    );
    $tipos = 's' . str_repeat('i', count($listaIds));
    $parametros = array_merge([$plantilla['codigo']], $listaIds);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $decodificado = json_decode($fila['resultados'], true);
        if (is_array($decodificado)) $guardados[(int) $fila['id_postulante']] = $decodificado;
    }
}

$mpdf = new \Mpdf\Mpdf($configuracion);
$mpdf->SetTitle($plantilla['nombre']);
$mpdf->SetAuthor('Clínica Médica Florentín');

$color = htmlspecialchars($plantilla['color'], ENT_QUOTES, 'UTF-8');
$css = '<style>
    body { font-family: timesnewroman, freeserif, serif; }
    .eco-page { color: ' . $color . '; font-family: timesnewroman, freeserif, serif; font-size: 11pt; line-height: 1.1; }
    .eco-letterhead-space { height: 18mm; }
    .eco-patient { border-collapse: collapse; font-size: 11pt; font-weight: bold; line-height: 1.08; width: 100%; }
    .eco-patient td { padding: 0 0 1.2mm; vertical-align: top; }
    .eco-patient .right { text-align: left; width: 31%; }
    .eco-label { text-decoration: underline; }
    .eco-reason { font-weight: bold; margin-top: 1mm; }
    .eco-title { border: 1mm solid #000; border-radius: 4mm; color: ' . $color . '; font-size: 14pt; font-weight: bold; margin: 10mm auto 8mm; padding: 4mm 5mm; text-align: center; width: 72%; }
    .eco-introduction { font-weight: bold; margin-bottom: 5mm; white-space: pre-line; }
    .eco-field { margin: 0 0 2mm; text-align: justify; }
    .eco-field-label { font-weight: bold; }
    .eco-field-label.underlined { text-decoration: underline; }
    .eco-section-heading { font-size: 14pt; font-weight: bold; margin: 5mm 0 3mm; text-decoration: underline; }
    .eco-conclusion-title { font-size: 13pt; font-weight: bold; margin: 7mm 0 5mm; text-decoration: underline; }
    .eco-conclusion-title.boxed { border: 1mm solid #000; border-radius: 4mm; margin-left: auto; margin-right: auto; padding: 3mm 8mm; text-align: center; text-decoration: none; width: 34%; }
    .eco-conclusion-list { font-weight: bold; margin: 0 0 7mm 9mm; padding-left: 6mm; }
    .eco-conclusion-list li { margin-bottom: 1.5mm; }
    .eco-conclusion-text { font-weight: bold; margin: 0 0 8mm; text-align: justify; white-space: pre-line; }
    .eco-signoff { margin-top: 6mm; text-align: center; }
    .eco-signature { font-size: 11pt; font-style: italic; font-weight: bold; margin: 6mm 4mm 0 auto; page-break-inside: avoid; text-align: center; width: 48%; }
    .eco-signature .registration { font-size: 10pt; font-style: normal; font-weight: normal; }
</style>';
$mpdf->WriteHTML($css);

foreach ($pacientes as $indice => $paciente) {
    if ($indice > 0) $mpdf->AddPage();
    $resultado = normalizarResultadoInformeEco(
        $plantilla,
        $guardados[(int) $paciente['id_paciente']] ?? []
    );
    $nombre = ecoHtml($paciente['nombre']);
    $fecha = ecoHtml($paciente['fecha']);
    $edad = ecoHtml($paciente['edad']);
    $ci = ecoHtml($paciente['ci']);
    $motivo = ecoHtml($resultado['motivo']);

    $html = '<div class="eco-page"><div class="eco-letterhead-space"></div>
        <table class="eco-patient">
            <tr><td><span class="eco-label">PACIENTE:</span> ' . $nombre . '</td><td class="right"><span class="eco-label">FECHA:</span> ' . $fecha . '</td></tr>
            <tr><td><span class="eco-label">CI:</span> ' . $ci . '</td><td class="right"><span class="eco-label">EDAD:</span> ' . $edad . ' AÑOS</td></tr>
        </table>
        <div class="eco-reason"><span class="eco-label">MOTIVO DEL PRESENTE ESTUDIO:</span>&nbsp;&nbsp;&nbsp; ' . $motivo . '</div>
        <div class="eco-title">' . ecoHtml($plantilla['titulo']) . '</div>
        <div class="eco-introduction">' . nl2br(ecoHtml($resultado['introduccion'])) . '</div>';

    foreach ($plantilla['campos'] as $campo) {
        $valor = nl2br(ecoHtml($resultado['campos'][$campo['clave']] ?? ''));
        $etiqueta = ecoHtml($campo['etiqueta']);
        if ($campo['tipo'] === 'seccion') {
            $html .= '<div class="eco-section-heading">' . $etiqueta . ':</div><div class="eco-field">' . $valor . '</div>';
        } elseif ($campo['tipo'] === 'parrafo') {
            $html .= '<div class="eco-field">' . $valor . '</div>';
        } else {
            $clase = !empty($campo['subrayado']) ? ' underlined' : '';
            $html .= '<div class="eco-field"><span class="eco-field-label' . $clase . '">' . $etiqueta . ':</span> ' . $valor . '</div>';
        }
    }

    if ($resultado['conclusion'] !== '') {
        $claseTitulo = !empty($plantilla['conclusion_recuadro']) ? ' boxed' : '';
        $html .= '<div class="eco-conclusion-title' . $claseTitulo . '">CONCLUSION</div>';
        $lineas = array_values(array_filter(array_map('trim', explode("\n", $resultado['conclusion'])), static fn(string $linea): bool => $linea !== ''));
        if (!empty($plantilla['conclusion_lista'])) {
            $html .= '<ul class="eco-conclusion-list">';
            foreach ($lineas as $linea) $html .= '<li>' . ecoHtml($linea) . '</li>';
            $html .= '</ul>';
        } else {
            $html .= '<div class="eco-conclusion-text">' . nl2br(ecoHtml(implode("\n", $lineas))) . '</div>';
        }
    }

    $html .= '<div class="eco-signoff">Atentamente.</div>';
    if (!empty($plantilla['mostrar_firma'])) {
        $html .= '<div class="eco-signature">Dr. CARLOS FLORENTIN<br>ECOGRAFIA FATESA S/P<br><span class="registration">Reg. Prof. N° 12713</span></div>';
    }
    $html .= '</div>';
    $mpdf->WriteHTML($html);
}

$mpdf->Output('Informes_ECO.pdf', 'I');

function ecoHtml($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

