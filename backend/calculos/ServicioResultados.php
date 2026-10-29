<?php

require_once __DIR__ . '/RegistroCalculos.php';

final class ServicioResultados
{
    private const PLANTILLA_MOCK = 'LABORATORIO_MOCK';

    public static function obtenerOGuardar(
        mysqli $db,
        int $idPaciente,
        string $claveResultado,
        $resultadoPropuesto
    ) {
        $db->begin_transaction();

        try {
            $plantilla = self::obtenerPlantilla($db);
            $idEstudio = self::obtenerOCrearEstudio(
                $db,
                $idPaciente,
                (int) $plantilla['id_plantilla'],
                $plantilla['cabecera']
            );
            $resultado = self::bloquearResultado($db, $idEstudio);
            $documento = $resultado === null
                ? ['plantilla_version' => (int) $plantilla['version'], 'valores' => []]
                : json_decode($resultado['resultado'], true, 512, JSON_THROW_ON_ERROR);

            if (array_key_exists($claveResultado, $documento['valores'] ?? [])) {
                $db->commit();
                return $documento['valores'][$claveResultado];
            }

            $calculador = RegistroCalculos::resolver($plantilla['calculador']);
            $valor = $calculador([
                'id_paciente' => $idPaciente,
                'clave' => $claveResultado,
                'resultado_propuesto' => $resultadoPropuesto,
                'plantilla' => json_decode($plantilla['plantilla'], true, 512, JSON_THROW_ON_ERROR),
            ]);
            $documento['valores'][$claveResultado] = $valor;
            $json = json_encode(
                $documento,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            if ($resultado === null) {
                $stmt = $db->prepare(
                    'INSERT INTO resultados_estudio (id_estudio, resultado) VALUES (?, ?)'
                );
                $stmt->bind_param('is', $idEstudio, $json);
            } else {
                $idResultado = (int) $resultado['id_resultado'];
                $stmt = $db->prepare(
                    'UPDATE resultados_estudio SET resultado = ?, fecha = CURRENT_TIMESTAMP(6)
                     WHERE id_resultado = ?'
                );
                $stmt->bind_param('si', $json, $idResultado);
            }

            $stmt->execute();
            $db->commit();

            return $valor;
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    private static function obtenerPlantilla(mysqli $db): array
    {
        $codigo = self::PLANTILLA_MOCK;
        $stmt = $db->prepare(
            'SELECT id_plantilla, version, cabecera, plantilla, calculador
             FROM plantillas_estudio
             WHERE codigo = ? AND activo = 1
             ORDER BY version DESC LIMIT 1'
        );
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $plantilla = $stmt->get_result()->fetch_assoc();

        if ($plantilla === null) {
            throw new RuntimeException('No existe una plantilla activa para los resultados simulados.');
        }

        return $plantilla;
    }

    private static function obtenerOCrearEstudio(
        mysqli $db,
        int $idPaciente,
        int $idPlantilla,
        string $cabeceraPlantilla
    ): int {
        $actor = isset($_SESSION['usuario']['id_usuario'])
            ? (int) $_SESSION['usuario']['id_usuario']
            : null;
        $cabecera = json_decode($cabeceraPlantilla, true, 512, JSON_THROW_ON_ERROR);
        $cabecera['origen'] = 'reporte';
        $cabecera = json_encode(
            $cabecera,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $stmt = $db->prepare(
            'INSERT INTO estudios (id_paciente, id_plantilla, solicitado_por, cabecera)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id_estudio = LAST_INSERT_ID(id_estudio)'
        );
        $stmt->bind_param('iiis', $idPaciente, $idPlantilla, $actor, $cabecera);
        $stmt->execute();

        return (int) $db->insert_id;
    }

    private static function bloquearResultado(mysqli $db, int $idEstudio): ?array
    {
        $stmt = $db->prepare(
            'SELECT id_resultado, resultado
             FROM resultados_estudio WHERE id_estudio = ? FOR UPDATE'
        );
        $stmt->bind_param('i', $idEstudio);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();

        return $resultado ?: null;
    }
}
