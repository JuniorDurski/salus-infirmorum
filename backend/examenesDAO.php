<?php
// Los valores siguen originandose en asignacion_valor.php y las formulas originales.
// Esta capa conserva una copia JSON por evaluacion para reproducir el PDF.
function obtenerOGuardarResultado(mysqli $conn, int $idEvaluacion, string $examenConcat, string $resultadoCalculado): string {
    static $cache = [];
    if (!isset($cache[$idEvaluacion])) {
        $row = $conn->execute_query('SELECT m.id_resultado, m.resultados FROM resultados_mock m WHERE m.id_evaluacion=? AND m.eliminado_en IS NULL ORDER BY m.version_resultado DESC LIMIT 1', [$idEvaluacion])->fetch_assoc();
        if (!$row) {
            $escuela = $conn->execute_query('SELECT s.codigo FROM evaluaciones e JOIN escuelas s ON s.id_escuela=e.id_escuela WHERE e.id_evaluacion=? AND e.eliminado_en IS NULL', [$idEvaluacion])->fetch_assoc();
            if (!$escuela) throw new RuntimeException('Evaluación inexistente.');
            $conn->execute_query('INSERT INTO resultados_mock (id_evaluacion, plantilla, resultados, version_resultado, creado_por) VALUES (?, ?, ?, 1, ?)', [$idEvaluacion, $escuela['codigo'], json_encode(['items'=>new stdClass()]), (int)($_SESSION['usuario_id'] ?? 0)]);
            $row = ['id_resultado'=>$conn->insert_id, 'resultados'=>'{"items":{}}'];
        }
        $document = json_decode($row['resultados'], true, 512, JSON_THROW_ON_ERROR);
        $cache[$idEvaluacion] = ['id'=>(int)$row['id_resultado'], 'items'=>$document['items'] ?? []];
    }
    if (array_key_exists($examenConcat, $cache[$idEvaluacion]['items'])) {
        return (string)$cache[$idEvaluacion]['items'][$examenConcat];
    }
    $cache[$idEvaluacion]['items'][$examenConcat] = $resultadoCalculado;
    $json = json_encode(['items'=>$cache[$idEvaluacion]['items']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $conn->execute_query('UPDATE resultados_mock SET resultados=?, actualizado_por=? WHERE id_resultado=? AND eliminado_en IS NULL', [$json, (int)($_SESSION['usuario_id'] ?? 0), $cache[$idEvaluacion]['id']]);
    $conn->execute_query("UPDATE evaluaciones SET estado='GENERADA' WHERE id_evaluacion=? AND estado='BORRADOR'", [$idEvaluacion]);
    return $resultadoCalculado;
}
