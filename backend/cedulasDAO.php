<?php

/**
 * Búsqueda en el padrón de cédulas para autocompletar el formulario de pacientes.
 *
 * GET backend/cedulasDAO.php?q=buscar&cedula=1234567
 *
 * Devuelve solo los datos que parecen válidos; el resto va vacío (null) para
 * que el formulario no pise lo que el usuario ya cargó con datos de relleno.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/../db/conexion_cedulas.php';

requerirAutenticacion($conexion, 'pacientes.editar', true);

$q = $_GET['q'] ?? '';

if ($q !== 'buscar') {
    responderCedula(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
}

$cedula = normalizarCedula((string) ($_GET['cedula'] ?? ''));

if ($cedula === '') {
    responderCedula(['status' => 'error', 'mensaje' => 'Ingrese un número de cédula.'], 422);
}

// En el padrón hay "cédulas" de familiares que son solo texto ("X", "SONIA"):
// sin al menos un número no se busca.
if (!preg_match('/\d/', $cedula)) {
    responderCedula(['status' => 'error', 'mensaje' => 'La cédula debe contener números.'], 422);
}

$db = conexionCedulas();

if ($db === null) {
    responderCedula(['status' => 'error', 'mensaje' => 'El padrón de cédulas no está disponible.'], 503);
}

$stmt = $db->prepare(
    'SELECT cedula, nombre_completo, fecha_nacimiento, sexo, origen
       FROM cedulas
      WHERE cedula = ?
      LIMIT 1'
);
$stmt->bind_param('s', $cedula);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();

if (!$fila) {
    responderCedula([
        'status' => 'ok',
        'encontrado' => false,
        'cedula' => $cedula,
    ]);
}

responderCedula([
    'status' => 'ok',
    'encontrado' => true,
    'cedula' => $fila['cedula'],
    'nombre' => limpiarNombre($fila['nombre_completo']),
    'fecha_nacimiento' => limpiarFecha($fila['fecha_nacimiento']),
    'sexo' => in_array($fila['sexo'], ['F', 'M'], true) ? $fila['sexo'] : null,
    // principal: la persona está en el padrón con sus propios datos.
    // padre / madre / conyuge: se conoce por ser familiar de otra persona
    // (sin fecha de nacimiento y con el sexo deducido).
    'origen' => $fila['origen'],
]);

/* ===========================
   FUNCIONES
=========================== */

function responderCedula(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Deja la cédula como está guardada en el padrón:
 * sin espacios ni puntos ("4.085.254" -> "4085254") y sin ceros a la izquierda.
 */
function normalizarCedula(string $cedula): string
{
    $cedula = strtoupper(trim($cedula));
    $cedula = str_replace(['.', ' '], '', $cedula);

    if ($cedula !== '' && ctype_digit($cedula)) {
        $cedula = ltrim($cedula, '0');
    }

    return mb_substr($cedula, 0, 20);
}

/**
 * Quita del nombre lo que no es nombre: textos de relleno ("NO TIENE NOMBRE"),
 * palabras sin letras (números de teléfono o cédula pegados, "*****")
 * y palabras de solo X ("XXXX"). Devuelve null si no queda nada útil.
 */
function limpiarNombre(?string $nombre): ?string
{
    if ($nombre === null) {
        return null;
    }

    $nombre = mb_strtoupper($nombre, 'UTF-8');
    $nombre = str_replace(['NO TIENE NOMBRE', 'NO TIENE APELLIDO', 'SIN NOMBRE', 'SIN APELLIDO'], ' ', $nombre);

    $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY);
    $palabras = array_filter($palabras, static function (string $palabra): bool {
        return preg_match('/\p{L}/u', $palabra) === 1
            && preg_match('/^X+\.?$/u', $palabra) !== 1;
    });

    $nombre = trim(implode(' ', $palabras));

    return $nombre === '' ? null : mb_substr($nombre, 0, 250);
}

/**
 * Devuelve la fecha (YYYY-MM-DD) solo si es creíble. El padrón usa
 * 1900-01-01 y 1911-11-11 como relleno cuando no se conoce la fecha.
 */
function limpiarFecha(?string $fecha): ?string
{
    if ($fecha === null || $fecha === '') {
        return null;
    }

    if (in_array($fecha, ['1900-01-01', '1911-11-11'], true)) {
        return null;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $fecha);

    if (!$d || $d->format('Y-m-d') !== $fecha || $d > new DateTime('today')) {
        return null;
    }

    return $fecha;
}
