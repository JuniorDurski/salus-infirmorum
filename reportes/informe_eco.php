<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/informes_eco_service.php';
require_once __DIR__ . '/membrete_clinica.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido');
}

$plantilla = plantillaInformeEco((string) ($_POST['estudio'] ?? ''));
if ($plantilla === null) exit('Estudio ECO no válido');

$incluirImagenes = (string) ($_POST['incluir_imagenes'] ?? '1') === '1';

$permisoReporte = 'reportes.eco.imprimir';
$id = $_POST['id'] ?? null;
$ids = $_POST['ids'] ?? [];
include __DIR__ . '/../backend/header_pacientes.php';

if ($plantilla['sexo'] !== null) {
    $pacientes = array_values(array_filter(
        $pacientes,
        static fn(array $paciente): bool => $paciente['sexo'] === $plantilla['sexo']
    ));
}
if ($pacientes === []) exit('No hay postulantes compatibles con el estudio seleccionado');

$configuracion = [
    'mode' => 'utf-8',
    'format' => $plantilla['formato'],
    'margin_top' => 4,
    'margin_right' => 15,
    'margin_bottom' => 20,
    'margin_left' => 15,
];

$configuracion = configurarFuentesInforme($configuracion);

$guardados = [];
$imagenesGuardadas = [];
$listaIds = array_map(static fn(array $paciente): int => (int) $paciente['id_paciente'], $pacientes);
if ($listaIds !== []) {
    $marcas = implode(',', array_fill(0, count($listaIds), '?'));
    $stmt = $conexion->prepare(
        "SELECT id_postulante, resultados, imagen_1, imagen_2
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
        $imagenesGuardadas[(int) $fila['id_postulante']] = [
            $fila['imagen_1'] ?: null,
            $fila['imagen_2'] ?: null,
        ];
    }
}

$mpdf = new \Mpdf\Mpdf($configuracion);
$mpdf->SetTitle($plantilla['nombre']);
$mpdf->SetAuthor('Clínica Médica Florentín');

$color = htmlspecialchars($plantilla['color'], ENT_QUOTES, 'UTF-8');
$css = '<style>
    body { color: #000; font-family: timesnewroman, freeserif, serif; }
    .eco-page { color: ' . $color . '; font-family: timesnewroman, freeserif, serif; font-size: 11pt; line-height: 1.1; }
    .eco-patient { border-collapse: collapse; font-size: 11pt; font-weight: bold; line-height: 1.08; width: 100%; }
    .eco-patient td { padding: 0 0 1.2mm; vertical-align: top; }
    .eco-patient .right { text-align: left; width: 31%; }
    .eco-ci-age { border-collapse: collapse; table-layout: fixed; width: 66%; }
    .eco-ci-age td { padding: 0; width: 50%; }
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
    .eco-signature { margin: 4mm 0 0 auto; page-break-inside: avoid; width: 38%; }
    .eco-photo-row { border-collapse: collapse; margin-top: 3mm; page-break-inside: avoid; table-layout: fixed; width: 100%; }
    .eco-photo-row td { padding: 0; }
    .eco-photo-space { height: 38mm; width: 62%; }
    .eco-photo-signature { text-align: center; vertical-align: bottom; width: 34%; }
    .eco-images { border-collapse: collapse; margin-top: 4mm; page-break-inside: avoid; table-layout: fixed; width: 100%; }
    .eco-images td { padding: 0 2mm; text-align: center; vertical-align: top; width: 50%; }
    .eco-images img { border: 0.2mm solid #555; }
</style>';
$mpdf->WriteHTML($css);

$membrete = membreteClinicaHtml();

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
    $imagenesPaciente = [];
    if ($incluirImagenes) {
        foreach ($imagenesGuardadas[(int) $paciente['id_paciente']] ?? [] as $rutaImagen) {
            $rutaLocal = rutaLocalImagenEco($rutaImagen);
            if ($rutaLocal !== null) $imagenesPaciente[] = $rutaLocal;
        }
    }

    $html = '<div class="eco-page">' . $membrete . '
        <table class="eco-patient">
            <tr><td><span class="eco-label">PACIENTE:</span> ' . $nombre . '</td><td class="right"><span class="eco-label">FECHA:</span> ' . $fecha . '</td></tr>
            <tr><td colspan="2"><table class="eco-ci-age"><tr>
                <td><span class="eco-label">CI:</span> ' . $ci . '</td>
                <td><span class="eco-label">EDAD:</span> ' . $edad . ' AÑOS</td>
            </tr></table></td></tr>
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

    $html .= '</div>';
    $mpdf->WriteHTML($html);

    $mostrarFirma = !empty($plantilla['mostrar_firma']);
    // Estilos en línea: mPDF no aplica las clases anidadas del sello dentro de celdas.
    $sello = '<div style="color:#000; font-family:calibri, freesans, sans-serif; font-size:10pt; font-weight:normal; line-height:1.05; text-align:center;">'
        . '<span style="font-family:timesnewroman, freeserif, serif; font-size:13pt; font-weight:bold;">Dr. Carlos Florentín Báez</span><br>'
        . 'Medicina Familiar<br>Esp. Ecografía - Imagen<br>Reg. Prof. Nº 12.713</div>';
    $cierre = '<div class="eco-page"><div class="eco-signoff">Atentamente.</div>';
    if ($mostrarFirma && $imagenesPaciente === [] && !empty($plantilla['espacio_foto'])) {
        $cierre .= '<table class="eco-photo-row"><tr><td class="eco-photo-space">&nbsp;</td><td class="eco-photo-signature">' . $sello . '</td></tr></table>';
    } elseif ($mostrarFirma) {
        $cierre .= '<div class="eco-signature">' . $sello . '</div>';
    }
    $mpdf->WriteHTML($cierre . '</div>');

    if ($imagenesPaciente !== []) {
        // Las imágenes van debajo del sello, ajustadas al espacio que quedó libre en la hoja.
        $margenSuperior = 5;
        $disponible = $mpdf->h - $mpdf->bMargin - $mpdf->y - $margenSuperior - 2;
        if ($disponible < 45) {
            // No entran con un tamaño legible: pasan a la hoja siguiente.
            $mpdf->AddPage();
            $disponible = $mpdf->h - $mpdf->bMargin - $mpdf->y - $margenSuperior - 2;
        }
        $mpdf->WriteHTML(htmlImagenesEco($imagenesPaciente, $mpdf->pgwidth, min($disponible, 100)));
    }
}

$mpdf->Output('Informes_ECO.pdf', 'I');

/**
 * Arma una fila de dos celdas con 1 o 2 imágenes, cada una ajustada a media hoja
 * ($anchoTotal / 2) y a $altoMax mm de alto, respetando su proporción original.
 */
function htmlImagenesEco(array $rutas, float $anchoTotal, float $altoMax): string
{
    // Siempre dos celdas de igual ancho: cada imagen en la suya (con una sola, la derecha queda vacía).
    $rutas = array_slice($rutas, 0, 2);
    $anchoCaja = ($anchoTotal / 2) - 5;
    $celdas = '';
    foreach ($rutas as $ruta) {
        $info = @getimagesize($ruta);
        $proporcion = ($info && $info[0] > 0 && $info[1] > 0) ? $info[0] / $info[1] : 4 / 3;
        $ancho = $anchoCaja;
        $alto = $ancho / $proporcion;
        if ($alto > $altoMax) {
            $alto = $altoMax;
            $ancho = $alto * $proporcion;
        }
        $celdas .= sprintf(
            '<td><img src="%s" style="width:%.1fmm;height:%.1fmm"></td>',
            ecoHtml($ruta),
            $ancho,
            $alto
        );
    }
    if (count($rutas) === 1) $celdas .= '<td>&nbsp;</td>';
    return '<table class="eco-images"><tr>' . $celdas . '</tr></table>';
}

function rutaLocalImagenEco($rutaRelativa): ?string
{
    $rutaRelativa = (string) $rutaRelativa;
    if (!preg_match('#^uploads/informes_eco/[0-9]+/[A-Z0-9_]+/[a-f0-9]{32}\.(jpg|png|webp)$#', $rutaRelativa)) {
        return null;
    }

    $raizImagenes = realpath(dirname(__DIR__) . '/uploads/informes_eco');
    $rutaAbsoluta = realpath(dirname(__DIR__) . '/' . $rutaRelativa);
    if ($raizImagenes === false || $rutaAbsoluta === false || !is_file($rutaAbsoluta)) return null;

    $prefijo = rtrim($raizImagenes, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncasecmp($rutaAbsoluta, $prefijo, strlen($prefijo)) !== 0) return null;
    return str_replace('\\', '/', $rutaAbsoluta);
}

function ecoHtml($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

