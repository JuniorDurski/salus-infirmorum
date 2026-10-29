<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/informes_eco_service.php';

$identidad = requerirAutenticacion($conexion, 'reportes.eco.imprimir', true);
$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'plantillas':
            listarPlantillasEco();
            break;
        case 'resultados':
            listarResultadosEco($conexion);
            break;
        case 'detalle':
            obtenerDetalleEco($conexion);
            break;
        case 'guardar':
            if (!tienePermiso($identidad, 'reportes.eco.editar')) {
                responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para editar informes ECO.'], 403);
            }
            validarCsrf();
            guardarResultadoEco($conexion, $identidad);
            break;
        default:
            responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
    }
} catch (JsonException $e) {
    responderJson(['status' => 'error', 'mensaje' => 'Los resultados recibidos no contienen un JSON válido.'], 400);
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo guardar el informe ECO.'], 422);
}

function listarPlantillasEco(): void
{
    $respuesta = [];
    foreach (plantillasInformeEco() as $codigo => $plantilla) {
        $respuesta[] = [
            'codigo' => $codigo,
            'nombre' => $plantilla['nombre'],
            'sexo' => $plantilla['sexo'],
        ];
    }
    responderJson(['status' => 'ok', 'plantillas' => $respuesta]);
}

function listarResultadosEco(mysqli $db): void
{
    $plantilla = exigirPlantillaEco($_GET['estudio'] ?? '');
    $ids = idsInformeEco($_GET['ids'] ?? []);
    if ($ids === []) responderJson(['status' => 'ok', 'resultados' => new stdClass()]);

    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id_postulante, actualizado_en
            FROM postulantes_informes_eco
            WHERE codigo_estudio = ? AND id_postulante IN ({$marcas})";
    $stmt = $db->prepare($sql);
    $tipos = 's' . str_repeat('i', count($ids));
    $codigo = $plantilla['codigo'];
    $parametros = array_merge([$codigo], $ids);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    $resultado = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $resultado[(string) $fila['id_postulante']] = ['actualizado_en' => $fila['actualizado_en']];
    }
    responderJson(['status' => 'ok', 'resultados' => $resultado ?: new stdClass()]);
}

function obtenerDetalleEco(mysqli $db): void
{
    $plantilla = exigirPlantillaEco($_GET['estudio'] ?? '');
    $id = (int) ($_GET['id_postulante'] ?? 0);
    exigirPostulanteActivoEco($db, $id, $plantilla);

    $stmt = $db->prepare(
        'SELECT resultados, actualizado_en
         FROM postulantes_informes_eco
         WHERE id_postulante = ? AND codigo_estudio = ?'
    );
    $stmt->bind_param('is', $id, $plantilla['codigo']);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $guardado = $fila !== null;
    $resultado = $guardado ? json_decode($fila['resultados'], true, 512, JSON_THROW_ON_ERROR) : [];
    $resultado = normalizarResultadoInformeEco($plantilla, is_array($resultado) ? $resultado : []);

    $campos = array_map(static fn(array $campo): array => [
        'clave' => $campo['clave'],
        'etiqueta' => $campo['etiqueta'],
    ], $plantilla['campos']);

    responderJson([
        'status' => 'ok',
        'guardado' => $guardado,
        'actualizado_en' => $fila['actualizado_en'] ?? null,
        'plantilla' => [
            'codigo' => $plantilla['codigo'],
            'nombre' => $plantilla['nombre'],
            'campos' => $campos,
        ],
        'resultado' => $resultado,
    ]);
}

function guardarResultadoEco(mysqli $db, array $identidad): void
{
    $plantilla = exigirPlantillaEco($_POST['estudio'] ?? '');
    $id = (int) ($_POST['id_postulante'] ?? 0);
    exigirPostulanteActivoEco($db, $id, $plantilla);
    $json = (string) ($_POST['resultado'] ?? '');
    if ($json === '' || strlen($json) > 100000) {
        responderJson(['status' => 'error', 'mensaje' => 'Los resultados están vacíos o exceden el tamaño permitido.'], 400);
    }
    $recibido = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($recibido)) {
        responderJson(['status' => 'error', 'mensaje' => 'El formato de resultados no es válido.'], 400);
    }
    $resultado = normalizarResultadoInformeEco($plantilla, $recibido);
    $resultadoJson = json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $actor = (int) $identidad['id_usuario'];
    $codigo = $plantilla['codigo'];
    $stmt = $db->prepare(
        'INSERT INTO postulantes_informes_eco
            (id_postulante, codigo_estudio, resultados, actualizado_por)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            resultados = VALUES(resultados),
            actualizado_por = VALUES(actualizado_por),
            actualizado_en = CURRENT_TIMESTAMP'
    );
    $stmt->bind_param('issi', $id, $codigo, $resultadoJson, $actor);
    $stmt->execute();
    responderJson(['status' => 'ok', 'mensaje' => 'Resultado ECO guardado.']);
}

function exigirPlantillaEco($codigo): array
{
    $plantilla = plantillaInformeEco((string) $codigo);
    if ($plantilla === null) responderJson(['status' => 'error', 'mensaje' => 'Seleccione un estudio ECO válido.'], 400);
    return $plantilla;
}

function exigirPostulanteActivoEco(mysqli $db, int $id, array $plantilla): void
{
    if ($id <= 0) responderJson(['status' => 'error', 'mensaje' => 'Postulante no válido.'], 400);
    $stmt = $db->prepare('SELECT es_femenino FROM pacientes WHERE id_paciente = ? AND activo = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ($fila === null) {
        responderJson(['status' => 'error', 'mensaje' => 'No se encontró el postulante activo.'], 404);
    }
    $sexo = (int) $fila['es_femenino'] === 1 ? 'FEMENINO' : 'MASCULINO';
    if ($plantilla['sexo'] !== null && $plantilla['sexo'] !== $sexo) {
        responderJson([
            'status' => 'error',
            'mensaje' => 'El estudio ' . $plantilla['nombre'] . ' no corresponde al sexo del postulante.',
        ], 422);
    }
}

function idsInformeEco($valores): array
{
    if (!is_array($valores)) $valores = [$valores];
    $ids = array_values(array_unique(array_filter(array_map('intval', $valores), static fn(int $id): bool => $id > 0)));
    return array_slice($ids, 0, 1000);
}

