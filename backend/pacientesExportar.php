<?php

/**
 * Exporta a Excel o PDF los pacientes que se ven en la lista de pacientes.php.
 *
 * POST backend/pacientesExportar.php
 *   formato = xls | pdf
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

$ids = array_values(array_unique(array_filter(
    array_map('intval', explode(',', (string) ($_POST['ids'] ?? ''))),
    static fn(int $id): bool => $id > 0
)));

if ($ids === []) {
    http_response_code(400);
    exit('No hay pacientes en la lista para exportar.');
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
               DATE_FORMAT(p.creado_en, \'%d/%m/%Y\') AS fecha_alta
          FROM pacientes p
          LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
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
foreach ($ids as $id) {
    if (!isset($porId[$id])) {
        continue;
    }
    $p = $porId[$id];
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
}

$base = 'pacientes-' . date('Y-m-d_H-i');

if ($formato === 'xls') {
    exportarExcel($filas, $base);
} else {
    exportarPdf($filas, $base, $filtro);
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

function tablaHtml(array $filas): string
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

    return $html . '</tbody></table>';
}

function exportarExcel(array $filas, string $base): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $base . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo $filas === [] ? '<p>Sin pacientes.</p>' : tablaHtml($filas);
    echo '</body></html>';
    exit;
}

function exportarPdf(array $filas, string $base, string $filtro): void
{
    require_once __DIR__ . '/../mpdf/vendor/autoload.php';

    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_top'    => 14,
        'margin_bottom' => 14,
        'margin_left'   => 10,
        'margin_right'  => 10,
    ]);
    $mpdf->SetTitle('Listado de pacientes');
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
        <h1>Listado de pacientes</h1>
        <div class="meta">
            Generado: ' . h(date('d/m/Y H:i')) . ' · ' . count($filas) . ' paciente(s)'
            . ($filtro !== '' ? ' · Filtro: ' . h($filtro) : '') . '
        </div>';
    $html .= $filas === [] ? '<p>Sin pacientes.</p>' : tablaHtml($filas);

    $mpdf->WriteHTML($html);
    $mpdf->Output($base . '.pdf', 'I');
    exit;
}
