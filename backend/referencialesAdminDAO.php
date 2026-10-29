<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$entidad = strtolower((string) ($_GET['entidad'] ?? ''));
$q = strtolower((string) ($_GET['q'] ?? 'listar'));
$configuracion = [
    'universidades' => ['ver' => 'universidades.ver', 'editar' => 'universidades.editar'],
    'titulos' => ['ver' => 'titulos.ver', 'editar' => 'titulos.editar'],
];

if (!isset($configuracion[$entidad])) {
    responderJson(['status' => 'error', 'mensaje' => 'Referencial no válida.'], 400);
}

try {
    if ($q === 'listar') {
        requerirAutenticacion($conexion, $configuracion[$entidad]['ver'], true);
        $entidad === 'universidades' ? listarUniversidades($conexion) : listarTitulos($conexion);
    }

    requerirAutenticacion($conexion, $configuracion[$entidad]['editar'], true);
    validarCsrf();

    if ($q === 'guardar') {
        $entidad === 'universidades' ? guardarUniversidad($conexion) : guardarTitulo($conexion);
    }
    if ($q === 'cambiar_estado') {
        cambiarEstadoReferencial($conexion, $entidad);
    }

    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $mensaje = $e->getCode() === 1062
        ? 'Ya existe un registro con esos datos.'
        : 'No se pudo completar la operación.';
    responderJson(['status' => 'error', 'mensaje' => $mensaje], 422);
}

function listarUniversidades(mysqli $db): void
{
    $rs = $db->query(
        'SELECT u.id_universidad, u.id_pais, u.nombre, u.abreviatura, u.activo,
                p.nombre AS pais, p.codigo_iso2
         FROM universidades u
         INNER JOIN paises p ON p.id_pais = u.id_pais
         ORDER BY u.activo DESC, u.nombre'
    );
    responderJson($rs->fetch_all(MYSQLI_ASSOC));
}

function guardarUniversidad(mysqli $db): void
{
    $id = (int) ($_POST['id_universidad'] ?? 0);
    $idPais = (int) ($_POST['id_pais'] ?? 0);
    $nombre = mb_strtoupper(trim((string) ($_POST['nombre'] ?? '')), 'UTF-8');
    $abreviaturaTexto = mb_strtoupper(trim((string) ($_POST['abreviatura'] ?? '')), 'UTF-8');
    $abreviatura = $abreviaturaTexto !== '' ? $abreviaturaTexto : null;

    if ($idPais <= 0 || $nombre === '' || mb_strlen($nombre) > 200
        || ($abreviatura !== null && mb_strlen($abreviatura) > 30)) {
        responderJson(['status' => 'error', 'mensaje' => 'País, nombre o abreviatura no son válidos.'], 400);
    }

    if ($id > 0) {
        $stmt = $db->prepare(
            'UPDATE universidades SET id_pais = ?, nombre = ?, abreviatura = ? WHERE id_universidad = ?'
        );
        $stmt->bind_param('issi', $idPais, $nombre, $abreviatura, $id);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO universidades (id_pais, nombre, abreviatura) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $idPais, $nombre, $abreviatura);
    }
    $stmt->execute();
    responderJson(['status' => 'ok', 'id_universidad' => $id ?: $db->insert_id]);
}

function listarTitulos(mysqli $db): void
{
    $rs = $db->query(
        'SELECT id_titulo, titulo, abreviatura, es_femenino, activo
         FROM titulos ORDER BY activo DESC, titulo, es_femenino'
    );
    responderJson($rs->fetch_all(MYSQLI_ASSOC));
}

function guardarTitulo(mysqli $db): void
{
    $id = (int) ($_POST['id_titulo'] ?? 0);
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $abreviatura = trim((string) ($_POST['abreviatura'] ?? ''));
    $esFemenino = (string) ($_POST['es_femenino'] ?? '') === '1' ? 1 : 0;

    if ($titulo === '' || $abreviatura === '' || mb_strlen($titulo) > 100 || mb_strlen($abreviatura) > 20) {
        responderJson(['status' => 'error', 'mensaje' => 'Título y abreviatura son obligatorios.'], 400);
    }

    if ($id > 0) {
        $stmt = $db->prepare(
            'UPDATE titulos SET titulo = ?, abreviatura = ?, es_femenino = ? WHERE id_titulo = ?'
        );
        $stmt->bind_param('ssii', $titulo, $abreviatura, $esFemenino, $id);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO titulos (titulo, abreviatura, es_femenino) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('ssi', $titulo, $abreviatura, $esFemenino);
    }
    $stmt->execute();
    responderJson(['status' => 'ok', 'id_titulo' => $id ?: $db->insert_id]);
}

function cambiarEstadoReferencial(mysqli $db, string $entidad): void
{
    $activo = !empty($_POST['activo']) ? 1 : 0;
    if ($entidad === 'universidades') {
        $id = (int) ($_POST['id_universidad'] ?? 0);
        $sql = 'UPDATE universidades SET activo = ? WHERE id_universidad = ?';
    } else {
        $id = (int) ($_POST['id_titulo'] ?? 0);
        $sql = 'UPDATE titulos SET activo = ? WHERE id_titulo = ?';
    }
    if ($id <= 0) {
        responderJson(['status' => 'error', 'mensaje' => 'Registro no válido.'], 400);
    }
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $activo, $id);
    $stmt->execute();
    responderJson(['status' => 'ok', 'activo' => $activo]);
}
