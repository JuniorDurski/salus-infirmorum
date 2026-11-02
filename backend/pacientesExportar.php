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
require_once __DIR__ . '/exportar_xlsx.php';

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
               DATE_FORMAT(' . sqlMomentoRegistroPaciente('p') . ', \'%d/%m/%Y\') AS fecha_alta,
               p.orden,
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
$escuelasReporte = [];
foreach ($ids as $id) {
    if (!isset($porId[$id])) {
        continue;
    }
    $p = $porId[$id];
    if (!empty($p['escuela'])) $escuelasReporte[$p['escuela']] = true;
    $filas[] = [
        'N°' => count($filas) + 1,
        'ID' => $p['id_paciente'],
        'Orden' => $p['orden'] === null ? '' : str_pad((string) $p['orden'], 3, '0', STR_PAD_LEFT),
        'Código' => $p['codigo'],
        'Nombre' => $p['nombre'],
        'Documento' => $p['documento'],
        'Fecha nac.' => $p['fecha_nacimiento'],
        'Edad' => $p['edad'],
        'Sexo' => $p['sexo'],
        'Escuela' => $p['escuela'] ?? '',
        'Rh' => grupoSanguineoCorto($p['grupo_sanguineo']),
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
