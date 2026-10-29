<?php

declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/atenciones_service.php';

$identidad = requerirAutenticacion($conexion, 'postulantes_atencion.exportar');
$id = (int) ($_GET['id'] ?? 0);
$formato = strtolower((string) ($_GET['formato'] ?? 'csv'));
if (!in_array($formato, ['csv', 'txt', 'json', 'xls'], true)) {
    http_response_code(400); exit('Formato no válido.');
}
$asignacion = obtenerDetalleAsignacion($conexion, $id, $identidad);
$filas = [];
foreach ($asignacion['postulantes'] as $postulante) {
    $estudios = [];
    foreach ($postulante['estudios'] as $estudio) {
        $estudios[] = $estudio['estudio_referencial'] . ': ' . ((int) $estudio['realizado'] ? 'REALIZADO' : 'PENDIENTE');
    }
    $filas[] = [
        'Documento' => $postulante['documento'],
        'Postulante' => $postulante['nombre'],
        'Escuela' => $postulante['escuela'],
        'Llamado' => (int) $postulante['llamado'] ? 'SÍ' : 'NO',
        'Atendido' => (int) $postulante['atendido'] ? 'SÍ' : 'NO',
        'Pagado' => (int) $postulante['pagado'] ? 'SÍ' : 'NO',
        'Estudios' => implode(' | ', $estudios),
    ];
}
$base = 'atenciones-' . $asignacion['fecha'] . '-' . $id;

if ($formato === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $base . '.json"');
    echo json_encode(['asignacion' => [
        'id' => $id, 'fecha' => $asignacion['fecha'],
        'escuelas' => $asignacion['escuelas'], 'profesionales' => $asignacion['usuarios'],
        'empieza_atender' => (bool) $asignacion['empieza_atender'],
        'pago_realizado' => (bool) $asignacion['pago_realizado'],
    ], 'postulantes' => $filas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($formato === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $base . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="utf-8"></head><body><table border="1"><thead><tr>';
    foreach (array_keys($filas[0] ?? ['Documento' => '']) as $cabecera) echo '<th>' . htmlspecialchars($cabecera, ENT_QUOTES, 'UTF-8') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($filas as $fila) {
        echo '<tr>'; foreach ($fila as $valor) echo '<td>' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') . '</td>'; echo '</tr>';
    }
    echo '</tbody></table></body></html>'; exit;
}

$separador = $formato === 'csv' ? ';' : "\t";
header('Content-Type: ' . ($formato === 'csv' ? 'text/csv' : 'text/plain') . '; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $base . '.' . $formato . '"');
$salida = fopen('php://output', 'wb');
fwrite($salida, "\xEF\xBB\xBF");
if ($filas) {
    fputcsv($salida, array_keys($filas[0]), $separador);
    foreach ($filas as $fila) fputcsv($salida, $fila, $separador);
} else {
    fputcsv($salida, ['Sin postulantes sincronizados'], $separador);
}
fclose($salida);
