<?php

/**
 * Carga masiva de imágenes ECO desde un .zip del ecógrafo.
 *
 * El zip trae una carpeta por postulante nombrada con sus apellidos
 * ("aguilera_gauto20261102-132226-6BDC/ABD.../*.JPG"). Las imágenes se toman
 * en orden de captura (nombre de archivo) y se asignan a los estudios según
 * el sexo: hombres Abdominal, Testicular; mujeres Abdominal, Ginecológica, Mamaria.
 *
 * El zip se conserva en uploads/informes_eco/lotes/; las imágenes se extraen
 * a archivos temporales que se borran al terminar.
 */

const LOTE_ECO_CARPETA = 'uploads/informes_eco/lotes';
const LOTE_ECO_MAX_ENTRADAS = 5000;
const LOTE_ECO_MAX_IMAGEN = 15 * 1024 * 1024;
const LOTE_ECO_SIMILITUD_MINIMA = 0.75;

function estudiosLoteEco(bool $esFemenino): array
{
    return $esFemenino
        ? ['ABDOMINAL_URGENCIA', 'GINECOLOGICA', 'MAMARIA']
        : ['ABDOMINAL_URGENCIA', 'TESTICULAR'];
}

/** Sube el zip, lo guarda y devuelve la propuesta de asignación carpeta → postulante. */
function analizarLoteEco(mysqli $db): void
{
    $archivo = $_FILES['comprimido'] ?? null;
    $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('El comprimido supera el tamaño máximo permitido por el servidor (' . ini_get('upload_max_filesize') . ').');
    }
    if ($archivo === null || $error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $archivo['tmp_name'])) {
        throw new RuntimeException('Seleccione un archivo .zip.');
    }

    $zip = new ZipArchive();
    if ($zip->open($archivo['tmp_name'], ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('El archivo no es un .zip válido.');
    }
    $carpetas = carpetasLoteEco($zip);
    $zip->close();
    if ($carpetas === []) {
        throw new RuntimeException('El comprimido no contiene carpetas con imágenes JPG, PNG o WEBP.');
    }

    $candidatos = candidatosLoteEco($db, idsInformeEco($_POST['ids'] ?? []));
    if ($candidatos === []) {
        throw new RuntimeException('No hay postulantes en la lista para emparejar. Ajuste los filtros de búsqueda.');
    }

    $carpetaAbsoluta = rutaAbsolutaLoteEco(LOTE_ECO_CARPETA);
    if (!is_dir($carpetaAbsoluta) && !mkdir($carpetaAbsoluta, 0755, true) && !is_dir($carpetaAbsoluta)) {
        throw new RuntimeException('No se pudo crear la carpeta para guardar los comprimidos.');
    }
    $original = preg_replace('/[^A-Za-z0-9_.-]+/', '_', pathinfo((string) $archivo['name'], PATHINFO_FILENAME));
    $lote = date('Ymd-His') . '-' . bin2hex(random_bytes(16)) . '-' . substr(trim((string) $original, '_.-') ?: 'eco', 0, 80) . '.zip';
    if (!move_uploaded_file($archivo['tmp_name'], $carpetaAbsoluta . DIRECTORY_SEPARATOR . $lote)) {
        throw new RuntimeException('No se pudo guardar el comprimido.');
    }

    $filas = emparejarLoteEco(array_keys($carpetas), $candidatos);
    foreach ($filas as &$fila) {
        $fila['imagenes'] = count($carpetas[$fila['carpeta']]);
        $fila['entradas'] = $carpetas[$fila['carpeta']];
    }
    unset($fila);

    responderJson(['status' => 'ok', 'lote' => $lote, 'carpetas' => $filas]);
}

function rutaZipLoteEco(string $lote): string
{
    if (!preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{32}-[A-Za-z0-9_.-]{1,80}\.zip$/', $lote)) {
        throw new RuntimeException('Comprimido no válido. Vuelva a subirlo.');
    }
    $rutaZip = rutaAbsolutaLoteEco(LOTE_ECO_CARPETA . '/' . $lote);
    if (!is_file($rutaZip)) throw new RuntimeException('No se encontró el comprimido. Vuelva a subirlo.');
    return $rutaZip;
}

/** Devuelve una imagen del zip tal cual, para la vista previa antes de cargar. */
function servirImagenLoteEco(): void
{
    $rutaZip = rutaZipLoteEco((string) ($_GET['lote'] ?? ''));
    $entrada = (string) ($_GET['entrada'] ?? '');
    $zip = new ZipArchive();
    if ($zip->open($rutaZip, ZipArchive::RDONLY) !== true) throw new RuntimeException('No se pudo abrir el comprimido.');
    try {
        // Solo se sirven las imágenes que el análisis reconoce, nunca otra entrada del zip.
        $validas = array_merge(...array_values(carpetasLoteEco($zip)));
        $info = $zip->statName($entrada);
        if (!in_array($entrada, $validas, true) || $info === false || (int) $info['size'] > LOTE_ECO_MAX_IMAGEN) {
            throw new RuntimeException('Imagen no encontrada en el comprimido.');
        }
        $contenido = $zip->getFromName($entrada);
    } finally {
        $zip->close();
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer((string) $contenido);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($contenido));
    header('Cache-Control: private, max-age=600');
    header('X-Content-Type-Options: nosniff');
    echo $contenido;
    exit;
}

/** Carga las imágenes del lote con las asignaciones confirmadas por el usuario. */
function aplicarLoteEco(mysqli $db, array $identidad): void
{
    $rutaZip = rutaZipLoteEco((string) ($_POST['lote'] ?? ''));

    $asignaciones = json_decode((string) ($_POST['asignaciones'] ?? '[]'), true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($asignaciones)) throw new RuntimeException('Asignaciones no válidas.');
    $reemplazar = ($_POST['reemplazar'] ?? '1') === '1';

    $zip = new ZipArchive();
    if ($zip->open($rutaZip, ZipArchive::RDONLY) !== true) throw new RuntimeException('No se pudo abrir el comprimido.');
    $carpetas = carpetasLoteEco($zip);

    $porPostulante = [];
    foreach ($asignaciones as $asignacion) {
        $carpeta = (string) ($asignacion['carpeta'] ?? '');
        $id = (int) ($asignacion['id_postulante'] ?? 0);
        if ($id <= 0) continue;
        if (!isset($carpetas[$carpeta])) throw new RuntimeException("La carpeta {$carpeta} no está en el comprimido.");
        if (isset($porPostulante[$id])) throw new RuntimeException('Un mismo postulante quedó asignado a dos carpetas. Revise la lista.');
        $porPostulante[$id] = $carpeta;
    }
    if ($porPostulante === []) throw new RuntimeException('No hay carpetas asignadas a postulantes.');

    $stmtPaciente = $db->prepare('SELECT nombre, es_femenino FROM pacientes WHERE id_paciente = ? AND activo = 1');
    $actor = (int) $identidad['id_usuario'];
    $resumen = ['cargadas' => 0, 'omitidas' => 0, 'postulantes' => 0, 'errores' => []];

    try {
        foreach ($porPostulante as $id => $carpeta) {
            $stmtPaciente->bind_param('i', $id);
            $stmtPaciente->execute();
            $paciente = $stmtPaciente->get_result()->fetch_assoc();
            if ($paciente === null) {
                $resumen['errores'][] = "{$carpeta}: el postulante ya no está activo.";
                continue;
            }
            $codigos = estudiosLoteEco((int) $paciente['es_femenino'] === 1);
            $imagenes = $carpetas[$carpeta];
            if (count($imagenes) > count($codigos)) {
                $resumen['errores'][] = "{$paciente['nombre']}: la carpeta tiene " . count($imagenes)
                    . ' imágenes y se esperaban ' . count($codigos) . '. No se cargó.';
                continue;
            }
            $cargoAlguna = false;
            foreach ($imagenes as $posicion => $entrada) {
                try {
                    if (guardarImagenLoteEco($db, $zip, $entrada, $id, $codigos[$posicion], $actor, $reemplazar)) {
                        $resumen['cargadas']++;
                        $cargoAlguna = true;
                    } else {
                        $resumen['omitidas']++;
                    }
                } catch (RuntimeException $e) {
                    $resumen['errores'][] = "{$paciente['nombre']} ({$entrada}): " . $e->getMessage();
                }
            }
            if ($cargoAlguna) $resumen['postulantes']++;
        }
    } finally {
        $zip->close();
    }

    responderJson(['status' => 'ok'] + $resumen);
}

/**
 * Extrae una imagen a un temporal, la normaliza y la deja como imagen 1 del estudio.
 * Devuelve false si el estudio ya tenía imagen y no se pidió reemplazar.
 */
function guardarImagenLoteEco(mysqli $db, ZipArchive $zip, string $entrada, int $id, string $codigo, int $actor, bool $reemplazar): bool
{
    $stmt = $db->prepare('SELECT imagen_1 FROM postulantes_informes_eco WHERE id_postulante = ? AND codigo_estudio = ?');
    $stmt->bind_param('is', $id, $codigo);
    $stmt->execute();
    $anterior = $stmt->get_result()->fetch_assoc()['imagen_1'] ?? null;
    if ($anterior !== null && $anterior !== '' && !$reemplazar) return false;

    $info = $zip->statName($entrada);
    if ($info === false || (int) $info['size'] <= 0 || (int) $info['size'] > LOTE_ECO_MAX_IMAGEN) {
        throw new RuntimeException('imagen vacía o de más de 15 MB.');
    }

    $temporal = tempnam(sys_get_temp_dir(), 'eco');
    $rutaRelativa = null;
    try {
        $origen = $zip->getStream($entrada);
        if ($origen === false) throw new RuntimeException('no se pudo extraer la imagen.');
        $destino = fopen($temporal, 'wb');
        stream_copy_to_stream($origen, $destino, LOTE_ECO_MAX_IMAGEN + 1);
        fclose($origen);
        fclose($destino);

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if ($extension === null || @getimagesize($temporal) === false) {
            throw new RuntimeException('no es una imagen JPG, PNG o WEBP válida.');
        }

        $carpetaRelativa = 'uploads/informes_eco/' . $id . '/' . $codigo;
        $carpetaAbsoluta = rutaAbsolutaLoteEco($carpetaRelativa);
        if (!is_dir($carpetaAbsoluta) && !mkdir($carpetaAbsoluta, 0755, true) && !is_dir($carpetaAbsoluta)) {
            throw new RuntimeException('no se pudo crear la carpeta de imágenes.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.jpg';
        $rutaRelativa = $carpetaRelativa . '/' . $nombre;
        if (!normalizarImagenInformeEco(['temporal' => $temporal, 'extension' => $extension], $carpetaAbsoluta . DIRECTORY_SEPARATOR . $nombre)) {
            throw new RuntimeException('no se pudo procesar la imagen.');
        }

        $resultadoJson = json_encode(
            normalizarResultadoInformeEco(plantillaInformeEco($codigo), []),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        // Solo toca imagen_1: el texto del informe y la imagen 2 se conservan.
        $stmt = $db->prepare(
            'INSERT INTO postulantes_informes_eco
                (id_postulante, codigo_estudio, resultados, imagen_1, actualizado_por)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                imagen_1 = VALUES(imagen_1),
                actualizado_por = VALUES(actualizado_por),
                actualizado_en = CURRENT_TIMESTAMP'
        );
        $stmt->bind_param('isssi', $id, $codigo, $resultadoJson, $rutaRelativa, $actor);
        $stmt->execute();
    } catch (Throwable $e) {
        if ($rutaRelativa !== null) eliminarArchivoInformeEco($rutaRelativa);
        throw $e instanceof RuntimeException ? $e : new RuntimeException('no se pudo guardar la imagen.');
    } finally {
        if (is_file($temporal)) @unlink($temporal);
    }

    if ($anterior !== null && $anterior !== $rutaRelativa) eliminarArchivoInformeEco($anterior);
    return true;
}

/**
 * Agrupa las imágenes del zip por carpeta de postulante:
 *   ["aguilera_gauto20261102-132226-6BDC" => ["…/0031ABD.JPG", "…/0032ABD.JPG"], …]
 * Cada lista sale ordenada por nombre de archivo, que es el orden de captura.
 */
function carpetasLoteEco(ZipArchive $zip): array
{
    if ($zip->numFiles > LOTE_ECO_MAX_ENTRADAS) {
        throw new RuntimeException('El comprimido tiene demasiados archivos.');
    }
    $rutas = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nombre = str_replace('\\', '/', (string) $zip->getNameIndex($i));
        if (str_ends_with($nombre, '/') || str_contains($nombre, '__MACOSX/')) continue;
        if (!preg_match('/\.(jpe?g|png|webp)$/i', $nombre)) continue;
        $partes = array_values(array_filter(explode('/', $nombre), static fn($p) => $p !== '' && $p !== '.' && $p !== '..'));
        if (count($partes) < 2 || str_starts_with(end($partes), '.')) continue;
        $rutas[$nombre] = array_slice($partes, 0, -1);
    }

    // Si todo viene dentro de una misma carpeta raíz (p. ej. "02-11-2026/"), se ignora.
    $directorios = array_values($rutas);
    while ($directorios !== [] && min(array_map('count', $directorios)) > 1
        && count(array_unique(array_column($directorios, 0))) === 1
        && !preg_match('/[a-z]{3,}.*\d{8}-\d{6}/i', $directorios[0][0])) {
        $directorios = array_map(static fn($d) => array_slice($d, 1), $directorios);
    }

    $carpetas = [];
    foreach (array_keys($rutas) as $i => $nombre) {
        $directorio = $directorios[$i];
        // Prefiere el segmento con forma "apellidos20261102-132226…"; si no, el primero.
        $carpeta = $directorio[0];
        foreach ($directorio as $segmento) {
            if (preg_match('/[a-z]{3,}.*\d{8}-\d{6}/i', $segmento)) { $carpeta = $segmento; break; }
        }
        $carpetas[$carpeta][] = $nombre;
    }
    foreach ($carpetas as &$imagenes) {
        usort($imagenes, static fn($a, $b) => strcmp(basename($a), basename($b)) ?: strcmp($a, $b));
    }
    unset($imagenes);
    ksort($carpetas, SORT_NATURAL | SORT_FLAG_CASE);
    return $carpetas;
}

function candidatosLoteEco(mysqli $db, array $ids): array
{
    if ($ids === []) return [];
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id_paciente, nombre FROM pacientes WHERE activo = 1 AND id_paciente IN ({$marcas})");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $candidatos = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $candidatos[(int) $fila['id_paciente']] = tokensNombreLoteEco($fila['nombre']);
    }
    return $candidatos;
}

/**
 * Propone un postulante por carpeta. Estados:
 *   ok               una sola coincidencia clara
 *   ambiguo          varios postulantes coinciden igual de bien
 *   conflicto        el mismo postulante es la mejor opción de dos carpetas
 *   sin_coincidencia ningún postulante tiene esos apellidos
 */
function emparejarLoteEco(array $carpetas, array $candidatos): array
{
    $filas = [];
    foreach ($carpetas as $carpeta) {
        $tokens = tokensNombreLoteEco(preg_replace('/\d{8}-\d{6}.*$/', '', $carpeta));
        $puntajes = [];
        foreach ($candidatos as $id => $nombre) {
            $puntaje = puntajeLoteEco($tokens, $nombre);
            if ($puntaje > 0) $puntajes[$id] = $puntaje;
        }
        arsort($puntajes);
        $mejores = array_keys($puntajes);
        $estado = 'sin_coincidencia';
        $id = null;
        if ($mejores !== []) {
            $empatados = array_keys(array_filter($puntajes, static fn($p) => abs($p - $puntajes[$mejores[0]]) < 0.001));
            $estado = count($empatados) > 1 ? 'ambiguo' : 'ok';
            $id = $estado === 'ok' ? $mejores[0] : null;
        }
        $filas[] = [
            'carpeta' => $carpeta,
            'apellidos' => implode(' ', $tokens),
            'id_postulante' => $id,
            'estado' => $estado,
            'candidatos' => array_slice($mejores, 0, 5),
        ];
    }

    $usos = array_count_values(array_filter(array_column($filas, 'id_postulante')));
    foreach ($filas as &$fila) {
        if ($fila['id_postulante'] !== null && $usos[$fila['id_postulante']] > 1) {
            $fila['estado'] = 'conflicto';
            $fila['id_postulante'] = null;
        }
    }
    unset($fila);
    return $filas;
}

/** Mayúsculas sin tildes, Ñ → N, solo letras: "Cabañas_Silva" → ["CABANAS", "SILVA"]. */
function tokensNombreLoteEco(string $texto): array
{
    $texto = mb_strtoupper($texto, 'UTF-8');
    $texto = strtr($texto, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
    $texto = preg_replace('/[^A-Z]+/', ' ', $texto);
    return array_values(array_filter(explode(' ', $texto), static fn($t) => strlen($t) >= 2));
}

/**
 * Promedio de similitud si TODOS los apellidos de la carpeta aparecen, en el mismo
 * orden, dentro del nombre del postulante; 0 si falta alguno. Tolera errores de
 * tipeo leves ("LEDESMA" ≈ "LEDEZMA", "BORJA" ≈ "BORJAS").
 */
function puntajeLoteEco(array $carpeta, array $nombre): float
{
    if ($carpeta === []) return 0.0;
    // mejor[i][j]: mejor suma usando los primeros i apellidos dentro de los primeros j tokens.
    $k = count($carpeta);
    $n = count($nombre);
    $mejor = array_fill(0, $k + 1, array_fill(0, $n + 1, -INF));
    for ($j = 0; $j <= $n; $j++) $mejor[0][$j] = 0.0;
    for ($i = 1; $i <= $k; $i++) {
        for ($j = 1; $j <= $n; $j++) {
            $mejor[$i][$j] = $mejor[$i][$j - 1];
            $similitud = similitudLoteEco($carpeta[$i - 1], $nombre[$j - 1]);
            if ($similitud >= LOTE_ECO_SIMILITUD_MINIMA && $mejor[$i - 1][$j - 1] > -INF) {
                $mejor[$i][$j] = max($mejor[$i][$j], $mejor[$i - 1][$j - 1] + $similitud);
            }
        }
    }
    return $mejor[$k][$n] > -INF ? $mejor[$k][$n] / $k : 0.0;
}

function similitudLoteEco(string $a, string $b): float
{
    if ($a === $b) return 1.0;
    if (min(strlen($a), strlen($b)) < 4) return 0.0;
    return 1 - levenshtein($a, $b) / max(strlen($a), strlen($b));
}

function rutaAbsolutaLoteEco(string $rutaRelativa): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
}
