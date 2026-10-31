<?php

/**
 * Exporta a Excel o PDF los registros visibles en pacientes.php o postulantes.php.
 *
 * POST backend/pacientesExportar.php
 *   formato = xls | pdf
 *   entidad = pacientes | postulantes
 *   ids     = "12,11,10"  (ids separados por coma, en el orden de la lista)
 *   filtro  = texto que describe los filtros aplicados (se imprime en el PDF)
 *   csrf_token
 *
 * Los ids van en un solo campo de texto para no chocar con el límite
 * max_input_vars de PHP cuando la lista es larga.
 */

declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/codigos_pacientes.php';

requerirAutenticacion($conexion, 'pacientes.ver');
validarCsrf();

$formato = strtolower((string) ($_POST['formato'] ?? ''));
if (!in_array($formato, ['xls', 'pdf'], true)) {
    http_response_code(400);
    exit('Formato no válido.');
}

$entidad = strtolower((string) ($_POST['entidad'] ?? 'pacientes'));
$esPostulantes = $entidad === 'postulantes';
$entidad = $esPostulantes ? 'postulantes' : 'pacientes';
$reporte = strtolower((string) ($_POST['reporte'] ?? 'lista'));
if (!$esPostulantes || !in_array($reporte, ['lista', 'comision', 'clinica'], true)) {
    $reporte = 'lista';
}

$ids = array_values(array_unique(array_filter(
    array_map('intval', explode(',', (string) ($_POST['ids'] ?? ''))),
    static fn(int $id): bool => $id > 0
)));

if ($ids === []) {
    http_response_code(400);
    exit("No hay {$entidad} en la lista para exportar.");
}

$filtro = trim((string) ($_POST['filtro'] ?? ''));

/* ===========================
   DATOS
=========================== */
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = 'SELECT p.id_paciente,
               ' . sqlCodigoPaciente('p') . ' AS codigo,
               p.nombre,
               p.documento,
               DATE_FORMAT(p.fecha_nacimiento, \'%d/%m/%Y\') AS fecha_nacimiento,
               TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
               IF(p.es_femenino = 1, \'FEMENINO\', \'MASCULINO\') AS sexo,
               e.nombre AS escuela,
               p.grupo_sanguineo,
               p.telefono,
               DATE_FORMAT(p.creado_en, \'%d/%m/%Y\') AS fecha_alta,
               finanzas.comision_arreador,
               finanzas.monto_clinica,
               finanzas.arreador
          FROM pacientes p
          LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
          LEFT JOIN (
              SELECT ap.id_paciente, ae.comision_arreador, ae.monto_clinica, a.nombre AS arreador
              FROM postulantes_arreador_postulante ap
              INNER JOIN postulantes_arreadores a ON a.id_arreador = ap.id_arreador
              INNER JOIN postulantes_arreador_escuela ae
                      ON ae.id_arreador_escuela = ap.id_arreador_escuela
                     AND ae.anio = YEAR(CURDATE())
          ) finanzas ON finanzas.id_paciente = p.id_paciente
         WHERE p.id_paciente IN (' . $placeholders . ')';
$stmt = $conexion->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();

$porId = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
    $porId[(int) $fila['id_paciente']] = $fila;
}

// Mismo orden que la lista en pantalla
$filas = [];
$arreadoresReporte = [];
$escuelasReporte = [];
foreach ($ids as $id) {
    if (!isset($porId[$id])) {
        continue;
    }
    $p = $porId[$id];
    if (!empty($p['arreador'])) $arreadoresReporte[$p['arreador']] = true;
    if (!empty($p['escuela'])) $escuelasReporte[$p['escuela']] = true;
    $filas[] = [
        'N°' => count($filas) + 1,
        'ID' => $p['id_paciente'],
        'Código' => $p['codigo'],
        'Nombre' => $p['nombre'],
        'Documento' => $p['documento'],
        'Fecha nac.' => $p['fecha_nacimiento'],
        'Edad' => $p['edad'],
        'Sexo' => $p['sexo'],
        'Escuela' => $p['escuela'] ?? '',
        'Rh' => grupoSanguineoCorto($p['grupo_sanguineo']),
        'Teléfono' => $p['telefono'] ?? '',
        'Fecha alta' => $p['fecha_alta'],
    ];
    if ($reporte === 'comision') {
        $filas[array_key_last($filas)]['Comisión de gestión'] = importeEntero($p['comision_arreador']);
    } elseif ($reporte === 'clinica') {
        $filas[array_key_last($filas)]['Ganancia de la clínica'] = importeEntero($p['monto_clinica']);
    }
}

$titulosReporte = [
    'lista' => 'Lista de postulantes',
    'comision' => 'Postulantes y comisión de gestión',
    'clinica' => 'Postulantes y ganancia de la clínica',
];
$columnasTotales = ['comision' => 'Comisión de gestión', 'clinica' => 'Ganancia de la clínica'];
$columnaTotal = $columnasTotales[$reporte] ?? null;
$totalReporte = $columnaTotal === null ? null : array_sum(array_map(
    static fn(array $fila): int => (int) str_replace('.', '', (string) ($fila[$columnaTotal] ?? 0)),
    $filas
));
$reloj = $conexion->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d_%H-%i') AS archivo, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i') AS generado")->fetch_assoc();
$base = $entidad . '-' . $reporte . '-' . $reloj['archivo'];
$tituloReporte = $esPostulantes ? $titulosReporte[$reporte] : 'Lista de pacientes';

$datosCabecera = [];
if ($esPostulantes) {
    $datosCabecera['Arreador'] = $arreadoresReporte === [] ? 'Sin asignar' : implode(', ', array_keys($arreadoresReporte));
    $datosCabecera['Escuela'] = $escuelasReporte === [] ? 'Sin escuela' : implode(', ', array_keys($escuelasReporte));
}

if ($formato === 'xls') {
    exportarExcel($filas, $base, $tituloReporte, $columnaTotal, $totalReporte, $reloj['generado'], $datosCabecera, $filtro);
} else {
    exportarPdf($filas, $base, $filtro, $tituloReporte, $columnaTotal, $totalReporte, $reloj['generado'], $datosCabecera);
}

/* ===========================
   FUNCIONES
=========================== */

function h($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** "A Rh Positivo" -> "A+", igual que en la lista de pantalla. */
function grupoSanguineoCorto(?string $texto): string
{
    $valor = strtoupper(trim((string) $texto));
    if ($valor === '') {
        return '';
    }
    $grupo = str_starts_with($valor, 'AB') ? 'AB' : (in_array($valor[0], ['A', 'B', 'O'], true) ? $valor[0] : '');
    $signo = str_contains($valor, 'POSITIVO') ? '+' : (str_contains($valor, 'NEGATIVO') ? '-' : '');

    return $grupo . $signo;
}

function importeEntero($valor): string
{
    return number_format((float) ($valor ?? 0), 0, ',', '.');
}

function tablaHtml(array $filas, ?string $columnaTotal = null, ?int $total = null): string
{
    $html = '<table class="lista" border="1"><thead><tr>';
    foreach (array_keys($filas[0]) as $cabecera) {
        $html .= '<th>' . h($cabecera) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($filas as $fila) {
        $html .= '<tr>';
        foreach ($fila as $columna => $valor) {
            // Documento y teléfono como texto para que Excel no los convierta en número
            $estilo = in_array($columna, ['Documento', 'Teléfono', 'Código'], true) ? ' style="mso-number-format:\'\@\'"' : '';
            $html .= '<td' . $estilo . '>' . h($valor) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    if ($columnaTotal !== null && $total !== null) {
        $cantidadColumnas = count($filas[0]);
        $html .= '<tfoot><tr><th colspan="' . ($cantidadColumnas - 1) . '" style="text-align:right">TOTAL</th>'
            . '<th>' . h(importeEntero($total)) . '</th></tr></tfoot>';
    }

    return $html . '</table>';
}

function imagenCabeceraExcel(): string
{
    $ruta = __DIR__ . '/../images/img_cabecera_excel.png';
    $datos = is_file($ruta) ? file_get_contents($ruta) : false;
    return $datos === false ? '' : $datos;
}

/**
 * Excel no muestra imágenes "data:" dentro de un .xls HTML, por eso se envía
 * como MHTML (página web de un solo archivo): el HTML y la cabecera viajan juntos.
 * Todo va en una sola tabla para que las celdas combinadas (colspan) coincidan
 * con las columnas de los datos.
 */
function exportarExcel(
    array $filas,
    string $base,
    string $titulo,
    ?string $columnaTotal,
    ?int $total,
    string $generado,
    array $datosCabecera,
    string $filtro
): void
{
    $limite = '----=_SalusLimite_' . bin2hex(random_bytes(8));
    $urlHtml = 'file:///C:/salus/reporte.htm';
    $urlLogo = 'file:///C:/salus/cabecera.png';
    $logo = imagenCabeceraExcel();

    $anchos = [
        'N°' => 40, 'ID' => 45, 'Código' => 70, 'Nombre' => 320, 'Documento' => 90,
        'Fecha nac.' => 85, 'Edad' => 45, 'Sexo' => 90, 'Escuela' => 110, 'Rh' => 45,
        'Teléfono' => 110, 'Fecha alta' => 85,
    ];
    $columnas = $filas === [] ? array_keys($anchos) : array_keys($filas[0]);
    $n = count($columnas);
    $izquierda = (int) ceil($n / 2);
    $derecha = $n - $izquierda;
    $borde = 'border:.5pt solid #9aa9a5;white-space:nowrap;';
    $formatoGs = "mso-number-format:'\\#\\,\\#\\#0';";

    $t = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:10pt">';
    foreach ($columnas as $columna) {
        $t .= '<col width="' . ($anchos[$columna] ?? 140) . '">';
    }
    // Fila 1: imagen de cabecera (3000x638 px escalada).
    $t .= '<tr><td colspan="' . $n . '" style="height:150px;vertical-align:top">'
        . ($logo !== '' ? '<img src="' . $urlLogo . '" width="705" height="150">' : '<b>CLÍNICA MÉDICA FLORENTÍN</b>')
        . '</td></tr>';
    // Fila 2: título.
    $t .= '<tr><td colspan="' . $n . '" style="height:30px;background:#176b5b;color:#ffffff;font-size:14pt;font-weight:bold;text-align:center;vertical-align:middle">'
        . h(mb_strtoupper($titulo, 'UTF-8')) . '</td></tr>';
    // Filas 3 y 4: arreador / escuela a la izquierda, generación / filtro a la derecha.
    $izquierdas = [];
    foreach ($datosCabecera as $etiqueta => $valor) {
        $izquierdas[] = '<b>' . h($etiqueta) . ':</b> ' . h($valor);
    }
    $derechas = ['<b>Generado:</b> ' . h($generado), '<b>Registros:</b> ' . count($filas) . ($filtro !== '' ? ' · ' . h($filtro) : '')];
    $cantidad = max(count($izquierdas), count($derechas));
    for ($i = 0; $i < $cantidad; $i++) {
        $t .= '<tr>'
            . '<td colspan="' . $izquierda . '" style="height:22px;background:#eef4f2;vertical-align:middle">' . ($izquierdas[$i] ?? '') . '</td>'
            . '<td colspan="' . $derecha . '" style="background:#eef4f2;text-align:right;vertical-align:middle">' . ($derechas[$i] ?? '') . '</td>'
            . '</tr>';
    }
    $t .= '<tr><td colspan="' . $n . '"></td></tr>';

    if ($filas === []) {
        $t .= '<tr><td colspan="' . $n . '">Sin registros.</td></tr>';
    } else {
        $t .= '<tr>';
        foreach ($columnas as $columna) {
            $t .= '<th style="' . $borde . 'background:#dfece8;height:24px;vertical-align:middle">' . h($columna) . '</th>';
        }
        $t .= '</tr>';
        foreach ($filas as $indice => $fila) {
            $fondo = $indice % 2 ? 'background:#f6f9f8;' : '';
            $t .= '<tr>';
            foreach ($fila as $columna => $valor) {
                if ($columna === $columnaTotal) {
                    $t .= '<td style="' . $borde . $fondo . $formatoGs . 'text-align:right">' . (int) str_replace('.', '', (string) $valor) . '</td>';
                } elseif ($columna === 'Documento' || $columna === 'Teléfono' || $columna === 'Código') {
                    $t .= '<td style="' . $borde . $fondo . "mso-number-format:'\\@'" . '">' . h($valor) . '</td>';
                } else {
                    $t .= '<td style="' . $borde . $fondo . '">' . h($valor) . '</td>';
                }
            }
            $t .= '</tr>';
        }
        if ($columnaTotal !== null && $total !== null) {
            $t .= '<tr><td colspan="' . ($n - 1) . '" style="' . $borde . 'background:#dfece8;font-weight:bold;text-align:right">TOTAL</td>'
                . '<td style="' . $borde . 'background:#dfece8;font-weight:bold;text-align:right;' . $formatoGs . '">' . $total . '</td></tr>';
        }
    }
    $t .= '</table>';

    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
        . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
        . '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Postulantes</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->'
        . '</head><body>' . $t . '</body></html>';

    $salida = "MIME-Version: 1.0\r\n"
        . "Content-Type: multipart/related; boundary=\"$limite\"; type=\"text/html\"\r\n\r\n"
        . "--$limite\r\n"
        . "Content-Location: $urlHtml\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "Content-Type: text/html; charset=\"utf-8\"\r\n\r\n"
        . chunk_split(base64_encode($html), 76, "\r\n") . "\r\n";
    if ($logo !== '') {
        $salida .= "--$limite\r\n"
            . "Content-Location: $urlLogo\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Type: image/png\r\n\r\n"
            . chunk_split(base64_encode($logo), 76, "\r\n") . "\r\n";
    }
    $salida .= "--$limite--\r\n";

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $base . '.xls"');
    echo $salida;
    exit;
}

function exportarPdf(
    array $filas,
    string $base,
    string $filtro,
    string $titulo,
    ?string $columnaTotal,
    ?int $total,
    string $generado,
    array $datosCabecera = []
): void
{
    require_once __DIR__ . '/../mpdf/vendor/autoload.php';
    require_once __DIR__ . '/../reportes/membrete_clinica.php';

    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_top'    => 14,
        'margin_bottom' => 14,
        'margin_left'   => 10,
        'margin_right'  => 10,
    ]);
    $mpdf->SetTitle($titulo);
    $mpdf->SetHTMLFooter('<div style="font-size:8pt; text-align:right; color:#555">Página {PAGENO} de {nbpg}</div>');

    $html = '
        <style>
            body { font-family: sans-serif; font-size: 8.5pt; }
            h1 { font-size: 14pt; margin: 0 0 2px; }
            .meta { color: #555; margin-bottom: 8px; }
            table.lista { width: 100%; border-collapse: collapse; }
            table.lista th { background: #e8eeec; font-weight: bold; text-align: left; }
            table.lista th, table.lista td { border: 0.5px solid #999; padding: 3px 4px; }
        </style>
        ' . membreteClinicaHtml() . '
        <h1>' . h($titulo) . '</h1>
        <div class="meta">
            ' . implode('', array_map(static fn($k, $v): string => '<b>' . h($k) . ':</b> ' . h($v) . ' · ', array_keys($datosCabecera), $datosCabecera)) . 'Generado: ' . h($generado) . ' · ' . count($filas) . ' registro(s)'
            . ($filtro !== '' ? ' · Filtro: ' . h($filtro) : '') . '
        </div>';
    $html .= $filas === [] ? '<p>Sin registros.</p>' : tablaHtml($filas, $columnaTotal, $total);

    $mpdf->WriteHTML($html);
    $mpdf->Output($base . '.pdf', 'I');
    exit;
}
