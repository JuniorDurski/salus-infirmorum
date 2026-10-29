<?php
// Adaptador de datos para los informes existentes. Los ID recibidos son evaluaciones.
require_once __DIR__ . '/auth.php';
exigirPermiso($conexion, 'informes.generar');
verificarCsrf();

$escuelaId = filter_var($_POST['id_escuela'] ?? null, FILTER_VALIDATE_INT);
$listaIds = [];
if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) $listaIds[] = (int)$_POST['id'];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $value) {
        if (filter_var($value, FILTER_VALIDATE_INT) && (int)$value > 0) $listaIds[] = (int)$value;
    }
}
$listaIds = array_values(array_unique($listaIds));
if (!$escuelaId || !$listaIds || count($listaIds) > 2000) {
    http_response_code(422);
    exit('Seleccioná una escuela y entre 1 y 2000 evaluaciones.');
}
$placeholders = implode(',', array_fill(0, count($listaIds), '?'));
$sql = "SELECT e.id_evaluacion AS id_postulante, e.codigo AS codigo_evaluacion,
               e.creado_en, s.codigo AS escuela_codigo, s.nombre AS escuela,
               CONCAT(p.apellidos, ' ', p.nombres) AS nombre, p.numero_documento AS ci,
               TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, DATE(e.creado_en)) AS edad,
               CASE p.sexo WHEN 'M' THEN 'MASCULINO' WHEN 'F' THEN 'FEMENINO' ELSE 'OTRO' END AS sexo,
               p.grupo_sanguineo AS tipi
        FROM evaluaciones e
        JOIN pacientes p ON p.id_paciente=e.id_paciente AND p.eliminado_en IS NULL
        JOIN escuelas s ON s.id_escuela=e.id_escuela AND s.eliminado_en IS NULL
        WHERE e.id_escuela=? AND e.eliminado_en IS NULL AND e.id_evaluacion IN ($placeholders)
        ORDER BY e.id_evaluacion";
$params = array_merge([(int)$escuelaId], $listaIds);
$rows = $conexion->execute_query($sql, $params)->fetch_all(MYSQLI_ASSOC);
if (count($rows) !== count($listaIds)) {
    http_response_code(422);
    exit('La selección contiene evaluaciones inexistentes o de otra escuela.');
}
$pacientes = [];
foreach ($rows as $row) {
    $pacientes[] = [
        'id' => htmlspecialchars(($row['codigo_evaluacion'] ?: $row['id_postulante']) . ' - ' . $row['escuela'], ENT_QUOTES, 'UTF-8'),
        'id_postulante' => (int)$row['id_postulante'],
        'nombre' => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'),
        'ci' => htmlspecialchars($row['ci'], ENT_QUOTES, 'UTF-8'),
        'edad' => (int)$row['edad'],
        'sexo' => $row['sexo'],
        'tipi' => htmlspecialchars($row['tipi'] ?? '', ENT_QUOTES, 'UTF-8'),
        'fecha' => date('d/m/Y', strtotime($row['creado_en']))
    ];
}
