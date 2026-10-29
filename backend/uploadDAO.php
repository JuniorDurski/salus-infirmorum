<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['status' => 'error', 'mensaje' => 'Método no permitido.'], 405);
}

validarCsrf();
$tipo = (string) ($_POST['tipo'] ?? '');
$archivo = $_FILES['archivo'] ?? null;

if ($archivo === null || $archivo['error'] !== UPLOAD_ERR_OK) {
    responderJson(['status' => 'error', 'mensaje' => 'No se recibió una imagen válida.'], 400);
}
if ((int) $archivo['size'] > 5 * 1024 * 1024) {
    responderJson(['status' => 'error', 'mensaje' => 'La imagen no puede superar 5 MB.'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($archivo['tmp_name']);
$extensiones = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (!isset($extensiones[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
    responderJson(['status' => 'error', 'mensaje' => 'Solo se permiten imágenes JPG, PNG o WEBP.'], 400);
}

$raizProyecto = dirname(__DIR__);
$nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];

if (in_array($tipo, ['firma', 'firma_sello', 'sello'], true)) {
    $identidad = requerirAutenticacion($conexion, 'perfil.editar', true);
    $columnas = [
        'firma' => 'url_firma',
        'firma_sello' => 'url_firma_sello',
        'sello' => 'url_sello',
    ];
    $id = (int) $identidad['id_usuario'];
    $carpetaRelativa = 'uploads/usuarios/' . $id;
    $columna = $columnas[$tipo];
    $tabla = 'usuario_configuraciones';
    $campoId = 'id_usuario';
} elseif ($tipo === 'paciente_foto') {
    requerirAutenticacion($conexion, 'pacientes.editar', true);
    $id = (int) ($_POST['id_paciente'] ?? 0);
    if ($id <= 0) {
        responderJson(['status' => 'error', 'mensaje' => 'Paciente no válido.'], 400);
    }
    $carpetaRelativa = 'uploads/pacientes/' . $id;
    $columna = 'url_foto';
    $tabla = 'pacientes';
    $campoId = 'id_paciente';
} else {
    responderJson(['status' => 'error', 'mensaje' => 'Tipo de imagen no permitido.'], 400);
}

$carpetaAbsoluta = $raizProyecto . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $carpetaRelativa);
if (!is_dir($carpetaAbsoluta) && !mkdir($carpetaAbsoluta, 0755, true) && !is_dir($carpetaAbsoluta)) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo crear la carpeta de imágenes.'], 500);
}

$rutaRelativa = $carpetaRelativa . '/' . $nombreArchivo;
$rutaAbsoluta = $carpetaAbsoluta . DIRECTORY_SEPARATOR . $nombreArchivo;
if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo guardar la imagen.'], 500);
}

try {
    if ($tabla === 'usuario_configuraciones') {
        $stmt = $conexion->prepare(
            "INSERT INTO usuario_configuraciones (id_usuario, {$columna}) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE {$columna} = VALUES({$columna})"
        );
    } else {
        $stmt = $conexion->prepare("UPDATE {$tabla} SET {$columna} = ? WHERE {$campoId} = ?");
    }

    if ($tabla === 'usuario_configuraciones') {
        $stmt->bind_param('is', $id, $rutaRelativa);
    } else {
        $stmt->bind_param('si', $rutaRelativa, $id);
    }
    $stmt->execute();
    if ($tabla === 'pacientes' && $stmt->affected_rows === 0) {
        @unlink($rutaAbsoluta);
        responderJson(['status' => 'error', 'mensaje' => 'Paciente no encontrado.'], 404);
    }
} catch (Throwable $e) {
    @unlink($rutaAbsoluta);
    throw $e;
}

responderJson(['status' => 'ok', 'url' => $rutaRelativa]);
