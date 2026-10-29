<?php
require_once __DIR__ . '/../backend/conexion.php';
require_once __DIR__ . '/../backend/seguridad.php';
requerirAutenticacion($conexion, 'reportes.imprimir');

// ==========================
// RECIBIR DATOS
// ==========================
$id          = $_POST['id']  ?? '';     // impresión individual
$ids         = $_POST['ids'] ?? [];     // impresión masiva
$designacion = strtoupper(trim($_POST['designacion'] ?? ''));

// ==========================
// VALIDACIONES BÁSICAS
// ==========================
if ($designacion === '') {
    die('Designación no recibida');
}

// ==========================
// MAPA DESIGNACIÓN → INFORME
// ==========================
$mapaInformes = [
    'ACADEMIL' => 'informe_academil.php',
    
    'ISEPOL'   => 'informe_isepol.php',
    'ACADEPOL'   => 'informe_colepol.php',
    'COLEPOL'   => 'informe_colepol.php',

    'DISERGEMIL'   => 'informe_disergemil.php',

    'DISERSANFA'   => 'informe_disersanfa.php',

    'LICEMIL'   => 'informe_licemil.php',
    'LICEMIL | CARDENAS'   => 'informe_licemil.php',
    'LICEMIL | SANTOS'   => 'informe_licemil.php',

    'COMISOE'   => 'informe_comisoe.php',
    'DIGETREN'   => 'informe_digetren.php',

    'DROGAS'   => 'informe_drogas.php',
    // agregar más informes aquí
];

// ==========================
// VERIFICAR DESIGNACIÓN
// ==========================
if (!isset($mapaInformes[$designacion])) {
    die('Designación no válida');
}

$archivo = $mapaInformes[$designacion];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Redirigiendo...</title>
</head>
<body>

<form id="frmRedireccion" method="post" action="<?= htmlspecialchars($archivo) ?>">

    <?php if ($id !== ''): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
    <?php endif; ?>

    <?php if (!empty($ids) && is_array($ids)): ?>
        <?php foreach ($ids as $i): ?>
            <input type="hidden" name="ids[]" value="<?= htmlspecialchars($i) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</form>

<script>
    document.getElementById('frmRedireccion').submit();
</script>

</body>
</html>
