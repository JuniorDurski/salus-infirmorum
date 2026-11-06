<?php

/**
 * Exporta a Excel o PDF los registros visibles en pacientes.php o postulantes.php.
 *
 * POST backend/pacientesExportar.php
 *   formato = xls | pdf
 *   entidad = pacientes | postulantes
 *   ids     = "12,11,10"  (ids separados por coma; se ordenan por número de orden)
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
               ' . sqlCodigoManualPaciente('p') . ' AS codigo_manual,
               ' . sqlOrdenDiaPaciente('p') . ' AS orden,
               DATE(' . sqlMomentoRegistroPaciente('p') . ') AS dia_registro,
               p.nombre,
               p.documento,
               DATE_FORMAT(p.fecha_nacimiento, \'%d/%m/%Y\') AS fecha_nacimiento,
               ' . sqlEdadRegistroPaciente('p') . ' AS edad,
               IF(p.es_femenino = 1, \'FEMENINO\', \'MASCULINO\') AS sexo,
               e.nombre AS escuela,
               p.grupo_sanguineo,
               finanzas.comision_arreador,
               finanzas.monto_clinica,
               finanzas.id_arreador_escuela
          FROM pacientes p
          LEFT JOIN escuelas e ON e.id_escuela = ' . sqlIdEscuelaPaciente('p') . '
          LEFT JOIN (
              SELECT ap.id_paciente, ap.id_arreador_escuela, ae.comision_arreador, ae.monto_clinica
              FROM postulantes_arreador_postulante ap
              INNER JOIN postulantes_arreador_escuela ae
                      ON ae.id_arreador_escuela = ap.id_arreador_escuela
                     AND ae.anio = YEAR(CURDATE())
              WHERE ap.activo = 1
          ) finanzas ON finanzas.id_paciente = p.id_paciente
         WHERE p.id_paciente IN (' . $placeholders . ')';
$stmt = $conexion->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$pacientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Por orden de menor a mayor (dentro de cada día de registro, si hay varios días).
usort($pacientes, static fn(array $a, array $b): int =>
    [$a['dia_registro'], (int) $a['orden'], (int) $a['id_paciente']]
    <=> [$b['dia_registro'], (int) $b['orden'], (int) $b['id_paciente']]);

// La columna Código solo aparece si algún paciente tiene un código cargado a mano.
$hayCodigo = array_filter(array_column($pacientes, 'codigo_manual')) !== [];

$filas = [];
$escuelasReporte = [];
$conveniosReporte = [];
foreach ($pacientes as $p) {
    if (!empty($p['escuela'])) $escuelasReporte[$p['escuela']] = true;
    if (!empty($p['id_arreador_escuela'])) $conveniosReporte[(int) $p['id_arreador_escuela']] = true;
    $fila = [
        'Orden' => str_pad((string) (int) $p['orden'], 3, '0', STR_PAD_LEFT),
    ];
    if ($hayCodigo) {
        $fila['Código'] = $p['codigo_manual'] ?? '';
    }
    $fila += [
        'Nombre' => $p['nombre'],
        'Documento' => $p['documento'],
        'Fecha nac.' => $p['fecha_nacimiento'],
        'Edad' => $p['edad'],
        'Sexo' => $p['sexo'],
        'Escuela' => $p['escuela'] ?? '',
        'Rh' => grupoSanguineoCorto($p['grupo_sanguineo']),
    ];
    if ($reporte === 'comision') {
        $fila['Comisión'] = importeEntero($p['comision_arreador']);
    } elseif ($reporte === 'clinica') {
        $fila['Ganancia'] = importeEntero($p['monto_clinica']);
    }
    $filas[] = $fila;
}

$titulosReporte = [
    'lista' => 'Lista de postulantes',
    'comision' => 'Postulantes y comisión de gestión',
    'clinica' => 'Postulantes y ganancia de la clínica',
];
$columnasTotales = ['comision' => 'Comisión', 'clinica' => 'Ganancia'];
$columnaTotal = $columnasTotales[$reporte] ?? null;
$totalReporte = $columnaTotal === null ? null : array_sum(array_map(
    static fn(array $fila): int => (int) str_replace('.', '', (string) ($fila[$columnaTotal] ?? 0)),
    $filas
));
$reloj = $conexion->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d_%H-%i') AS archivo, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i') AS generado")->fetch_assoc();
$base = $entidad . '-' . $reporte . '-' . $reloj['archivo'];
$tituloReporte = $esPostulantes ? $titulosReporte[$reporte] : 'Lista de pacientes';

// Convenio: el elegido en pantalla o, si toda la lista es de un mismo convenio, ese.
$convenioDatos = null;
$idConvenio = (int) ($_POST['id_convenio'] ?? 0);
if ($idConvenio <= 0 && count($conveniosReporte) === 1) {
    $idConvenio = (int) array_key_first($conveniosReporte);
}
if ($idConvenio > 0) {
    $stmt = $conexion->prepare(
        'SELECT a.nombre AS arreador, a.institucion, e.nombre AS escuela
         FROM postulantes_arreador_escuela ae
         INNER JOIN postulantes_arreadores a ON a.id_arreador = ae.id_arreador
         INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
         WHERE ae.id_arreador_escuela = ?'
    );
    $stmt->bind_param('i', $idConvenio);
    $stmt->execute();
    $convenioDatos = $stmt->get_result()->fetch_assoc();
}
// Une las partes del convenio con " - ", salteando las vacías (p. ej. sin institución).
$textoConvenio = static function (array $partes) use ($conveniosReporte): string {
    $texto = implode(' - ', array_filter($partes, static fn($parte): bool => trim((string) $parte) !== ''));
    return $texto !== '' ? $texto : (count($conveniosReporte) > 1 ? 'Varios' : 'Sin convenio');
};
$fechaFiltro = trim((string) ($_POST['fecha_registro'] ?? ''));
$fechaFiltro = preg_match('#^\d{2}/\d{2}/\d{4}$#', $fechaFiltro) ? $fechaFiltro : 'Todas';

if ($formato === 'xls') {
    // El Excel ya muestra la escuela en la lista: en la cabecera van convenio y fecha de registro.
    // Convenio como "ARREADOR - INSTITUCIÓN - ESCUELA" (sin institución si no tiene).
    $datosExcel = [];
    if ($esPostulantes) {
        $datosExcel['Convenio'] = $textoConvenio($convenioDatos === null ? []
            : [$convenioDatos['arreador'], $convenioDatos['institucion'], $convenioDatos['escuela']]);
        $datosExcel['Fecha de registro'] = $fechaFiltro;
    }
    exportarExcel($filas, $base, $tituloReporte, $columnaTotal, $totalReporte, $reloj['generado'], $datosExcel, $filtro);
} else {
    // Cabecera del PDF de postulantes en dos columnas:
    //   Escuela            | Fecha de registro
    //   Arreador - Instit. | Generado
    $datosCabecera = [];
    if ($esPostulantes) {
        $datosCabecera = [
            [
                'Escuela' => $escuelasReporte === [] ? 'Sin escuela' : implode(', ', array_keys($escuelasReporte)),
                'Convenio' => $textoConvenio($convenioDatos === null ? []
                    : [$convenioDatos['arreador'], $convenioDatos['institucion']]),
            ],
            [
                'Fecha de registro' => $fechaFiltro,
                'Generado' => $reloj['generado'],
            ],
        ];
    }
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

/** Cédula con puntos de miles ("5860649" -> "5.860.649"); si no es solo números, igual. */
function cedulaConPuntos($valor): string
{
    $texto = trim((string) $valor);
    return preg_match('/^\d+$/', $texto) ? number_format((int) $texto, 0, ',', '.') : $texto;
}

/** Tabla del PDF: cédulas con puntos de miles e importes alineados a la derecha. */
function tablaHtml(array $filas, ?string $columnaTotal = null, ?int $total = null): string
{
    $importes = ['Comisión', 'Ganancia'];
    $derecha = ' style="text-align:right"';
    $html = '<table class="lista" border="1"><thead><tr>';
    foreach (array_keys($filas[0]) as $cabecera) {
        $html .= '<th' . (in_array($cabecera, $importes, true) ? $derecha : '') . '>' . h($cabecera) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($filas as $fila) {
        $html .= '<tr>';
        foreach ($fila as $columna => $valor) {
            if ($columna === 'Documento') $valor = cedulaConPuntos($valor);
            $html .= '<td' . (in_array($columna, $importes, true) ? $derecha : '') . '>' . h($valor) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    if ($columnaTotal !== null && $total !== null) {
        $cantidadColumnas = count($filas[0]);
        $html .= '<tfoot><tr><th colspan="' . ($cantidadColumnas - 1) . '" style="text-align:right">TOTAL</th>'
            . '<th' . $derecha . '>' . h(importeEntero($total)) . '</th></tr></tfoot>';
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
        'format'        => 'Legal', // oficio (legal) vertical
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
            table.meta { width: 100%; border-collapse: collapse; }
            table.meta td { padding: 1px 0; vertical-align: top; }
            /* Etiqueta y valor en celdas separadas para que los valores queden alineados.
               Anchos fijos (mPDF ignora nowrap); el bloque derecho queda pegado al margen. */
            table.meta td.meta-etiqueta { font-weight: bold; }
            table.meta td.izq-etiqueta { width: 17mm; }
            table.meta td.der-etiqueta { width: 30mm; }
            table.meta td.der-valor { width: 25mm; }
            table.lista { width: 100%; border-collapse: collapse; }
            table.lista th { background: #e8eeec; font-weight: bold; text-align: left; }
            table.lista th, table.lista td { border: 0.5px solid #999; padding: 3px 4px; }
        </style>
        ' . membreteClinicaHtml() . '
        <h1>' . h($titulo) . '</h1>';
    if ($datosCabecera !== []) {
        // $datosCabecera = [izquierda, derecha], cada una con sus filas "Etiqueta" => valor.
        // En cada bloque los valores empiezan en la misma columna (en las fechas, sus "/" coinciden).
        $pares = static fn(array $bloque): array => array_map(null, array_keys($bloque), array_values($bloque));
        $izquierda = $pares($datosCabecera[0]);
        $derecha = $pares($datosCabecera[1] ?? []);
        $celdas = static fn(?array $par, string $lado): string =>
            '<td class="meta-etiqueta ' . $lado . '-etiqueta">' . ($par ? h($par[0]) . ':' : '') . '</td>'
            . '<td class="' . $lado . '-valor">' . ($par ? h($par[1]) : '') . '</td>';
        $html .= '<table class="meta">';
        for ($fila = 0, $filasMeta = max(count($izquierda), count($derecha)); $fila < $filasMeta; $fila++) {
            $html .= '<tr>' . $celdas($izquierda[$fila] ?? null, 'izq') . $celdas($derecha[$fila] ?? null, 'der') . '</tr>';
        }
        $html .= '</table>';
    } else {
        $html .= '<div class="meta">Generado: ' . h($generado) . ' · ' . count($filas) . ' registro(s)'
            . ($filtro !== '' ? ' · Filtro: ' . h($filtro) : '') . '</div>';
    }
    $html .= $filas === [] ? '<p>Sin registros.</p>' : tablaHtml($filas, $columnaTotal, $total);

    $mpdf->WriteHTML($html);
    $mpdf->Output($base . '.pdf', 'I');
    exit;
}
