<?php

require_once __DIR__ . '/seguridad.php';
requerirAutenticacion($conexion, 'reportes.imprimir');

$listaIds = [];

if (isset($id) && $id !== null && $id !== '') {
    $listaIds[] = (int) $id;
}

if (!empty($ids) && is_array($ids)) {
    foreach ($ids as $i) {
        $listaIds[] = (int) $i;
    }
}

$listaIds = array_values(array_unique(array_filter($listaIds, static function ($valor) {
    return $valor > 0;
})));

if ($listaIds === []) {
    die('No se recibieron pacientes válidos');
}

$placeholders = implode(',', array_fill(0, count($listaIds), '?'));
$sql = "SELECT
            p.id_paciente,
            p.nombre,
            p.documento,
            TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
            IF(p.es_femenino = 1, 'FEMENINO', 'MASCULINO') AS sexo,
            p.grupo_sanguineo AS tipi,
            DATE_FORMAT(p.creado_en, '%d/%m/%Y') AS fecha,
            CONCAT(COALESCE(p.codigo, ''), ' - ', COALESCE(e.nombre, '')) AS codigo
        FROM pacientes p
        LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
        WHERE p.id_paciente IN ({$placeholders})
          AND p.activo = 1
        ORDER BY p.id_paciente";

$stmt = $conexion->prepare($sql);
$types = str_repeat('i', count($listaIds));
$stmt->bind_param($types, ...$listaIds);
$stmt->execute();
$rs = $stmt->get_result();
$pacientes = [];

while ($row = $rs->fetch_assoc()) {
    $pacientes[] = [
        'id' => $row['codigo'],
        // Alias temporal: evita cambiar todos los informes en la misma migración.
        'id_postulante' => $row['id_paciente'],
        'id_paciente' => $row['id_paciente'],
        'nombre' => $row['nombre'],
        'ci' => $row['documento'],
        'tipi' => $row['tipi'] ?: '',
        'edad' => $row['edad'],
        'sexo' => $row['sexo'],
        'fecha' => $row['fecha'],
    ];
}

if ($pacientes === []) {
    die('No se encontraron pacientes activos');
}
