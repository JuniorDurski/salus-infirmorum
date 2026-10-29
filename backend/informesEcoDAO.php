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
        case 'imagenes':
            if (!tienePermiso($identidad, 'reportes.eco.editar')) {
                responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para editar informes ECO.'], 403);
            }
            validarCsrf();
            guardarImagenesEco($conexion, $identidad);
            break;
        default:
            responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
    }
} catch (JsonException $e) {
    responderJson(['status' => 'error', 'mensaje' => 'Los resultados recibidos no contienen un JSON válido.'], 400);
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo guardar el informe ECO.'], 422);
} catch (RuntimeException $e) {
    responderJson(['status' => 'error', 'mensaje' => $e->getMessage()], 400);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo procesar el informe ECO.'], 500);
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
        'SELECT resultados, imagen_1, imagen_2, actualizado_en
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
        'imagenes' => [
            $fila['imagen_1'] ?? null,
            $fila['imagen_2'] ?? null,
        ],
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
    // Solo actualiza el texto; las imágenes se gestionan en guardarImagenesEco().
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

function guardarImagenesEco(mysqli $db, array $identidad): void
{
    $plantilla = exigirPlantillaEco($_POST['estudio'] ?? '');
    $id = (int) ($_POST['id_postulante'] ?? 0);
    exigirPostulanteActivoEco($db, $id, $plantilla);
    // Si el informe aún no tiene texto guardado se crea con los valores por defecto.
    $resultadoJson = json_encode(
        normalizarResultadoInformeEco($plantilla, []),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    $stmtAnterior = $db->prepare(
        'SELECT imagen_1, imagen_2
         FROM postulantes_informes_eco
         WHERE id_postulante = ? AND codigo_estudio = ?'
    );
    $stmtAnterior->bind_param('is', $id, $plantilla['codigo']);
    $stmtAnterior->execute();
    $filaAnterior = $stmtAnterior->get_result()->fetch_assoc() ?: [];
    $anteriores = [
        1 => $filaAnterior['imagen_1'] ?? null,
        2 => $filaAnterior['imagen_2'] ?? null,
    ];

    $archivos = [];
    foreach ([1, 2] as $posicion) {
        $archivo = $_FILES['imagen_' . $posicion] ?? null;
        if ($archivo !== null && (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $archivos[$posicion] = validarImagenInformeEco($archivo);
        }
    }

    $nuevosArchivos = [];
    $imagenesFinales = $anteriores;
    $transaccionIniciada = false;
    try {
        if ($archivos !== []) {
            $carpetaRelativa = 'uploads/informes_eco/' . $id . '/' . $plantilla['codigo'];
            $carpetaAbsoluta = dirname(__DIR__) . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $carpetaRelativa);
            if (!is_dir($carpetaAbsoluta)
                && !mkdir($carpetaAbsoluta, 0755, true)
                && !is_dir($carpetaAbsoluta)) {
                throw new RuntimeException('No se pudo crear la carpeta para las imágenes ECO.');
            }

            foreach ($archivos as $posicion => $archivo) {
                $nombre = bin2hex(random_bytes(16)) . '.jpg';
                $rutaRelativa = $carpetaRelativa . '/' . $nombre;
                $rutaAbsoluta = $carpetaAbsoluta . DIRECTORY_SEPARATOR . $nombre;
                if (!normalizarImagenInformeEco($archivo, $rutaAbsoluta)) {
                    throw new RuntimeException('No se pudo guardar una de las imágenes ECO.');
                }
                $nuevosArchivos[] = $rutaRelativa;
                $imagenesFinales[$posicion] = $rutaRelativa;
            }
        }

        foreach ([1, 2] as $posicion) {
            if (!isset($archivos[$posicion]) && ($_POST['eliminar_imagen_' . $posicion] ?? '') === '1') {
                $imagenesFinales[$posicion] = null;
            }
        }

        $db->begin_transaction();
        $transaccionIniciada = true;
        $actor = (int) $identidad['id_usuario'];
        $codigo = $plantilla['codigo'];
        $imagen1 = $imagenesFinales[1];
        $imagen2 = $imagenesFinales[2];
        $stmt = $db->prepare(
            'INSERT INTO postulantes_informes_eco
                (id_postulante, codigo_estudio, resultados, imagen_1, imagen_2, actualizado_por)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                imagen_1 = VALUES(imagen_1),
                imagen_2 = VALUES(imagen_2),
                actualizado_por = VALUES(actualizado_por),
                actualizado_en = CURRENT_TIMESTAMP'
        );
        $stmt->bind_param('issssi', $id, $codigo, $resultadoJson, $imagen1, $imagen2, $actor);
        $stmt->execute();
        $db->commit();
        $transaccionIniciada = false;
    } catch (Throwable $e) {
        if ($transaccionIniciada) $db->rollback();
        foreach ($nuevosArchivos as $ruta) eliminarArchivoInformeEco($ruta);
        throw $e;
    }

    foreach ([1, 2] as $posicion) {
        if ($anteriores[$posicion] !== null && $anteriores[$posicion] !== $imagenesFinales[$posicion]) {
            eliminarArchivoInformeEco($anteriores[$posicion]);
        }
    }

    responderJson([
        'status' => 'ok',
        'mensaje' => 'Imágenes ECO guardadas.',
        'imagenes' => [$imagenesFinales[1], $imagenesFinales[2]],
    ]);
}

/**
 * Guarda la imagen como JPG enderezada según su EXIF (fotos de celular),
 * con el lado mayor limitado a 1600 px para que el PDF no pese de más.
 */
function normalizarImagenInformeEco(array $archivo, string $destino): bool
{
    $origen = $archivo['temporal'];
    $imagen = match ($archivo['extension']) {
        'jpg' => @imagecreatefromjpeg($origen),
        'png' => @imagecreatefrompng($origen),
        'webp' => @imagecreatefromwebp($origen),
        default => false,
    };
    if ($imagen === false) return false;

    if ($archivo['extension'] === 'jpg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($origen);
        $giro = ['3' => 180, '6' => -90, '8' => 90][(string) ($exif['Orientation'] ?? '')] ?? 0;
        if ($giro !== 0) {
            $girada = imagerotate($imagen, $giro, 0);
            if ($girada !== false) {
                imagedestroy($imagen);
                $imagen = $girada;
            }
        }
    }

    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);
    $escala = min(1, 1600 / max($ancho, $alto));
    $nuevoAncho = max(1, (int) round($ancho * $escala));
    $nuevoAlto = max(1, (int) round($alto * $escala));
    $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    // Fondo blanco para PNG/WEBP con transparencia.
    imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
    imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
    imagedestroy($imagen);
    $ok = imagejpeg($lienzo, $destino, 88);
    imagedestroy($lienzo);
    return $ok;
}

function validarImagenInformeEco(array $archivo): array
{
    if ((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibió una imagen ECO válida.');
    }
    if ((int) ($archivo['size'] ?? 0) <= 0 || (int) $archivo['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Cada imagen ECO debe pesar como máximo 15 MB.');
    }

    $temporal = (string) ($archivo['tmp_name'] ?? '');
    if (!is_uploaded_file($temporal)) {
        throw new RuntimeException('No se recibió una imagen ECO válida.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporal);
    $extensiones = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensiones[$mime]) || @getimagesize($temporal) === false) {
        throw new RuntimeException('Las imágenes ECO deben ser JPG, PNG o WEBP.');
    }

    return ['temporal' => $temporal, 'extension' => $extensiones[$mime]];
}

function eliminarArchivoInformeEco(?string $rutaRelativa): void
{
    if ($rutaRelativa === null
        || !preg_match('#^uploads/informes_eco/[0-9]+/[A-Z0-9_]+/[a-f0-9]{32}\.(jpg|png|webp)$#', $rutaRelativa)) {
        return;
    }
    $rutaAbsoluta = dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
    if (is_file($rutaAbsoluta)) @unlink($rutaAbsoluta);
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
