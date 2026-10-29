<?php

require_once __DIR__ . '/backend/menu_service.php';
$identidadMenu = $usuarioAutenticado ?? requerirAutenticacion($conexion);
$menusNavegacion = obtenerMenusVisibles($conexion, (int) $identidadMenu['id_usuario']);
$rutaActual = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? 'index.php', PHP_URL_PATH));

$stmtTema = $conexion->prepare('SELECT modo_oscuro FROM usuario_configuraciones WHERE id_usuario = ?');
$idTema = (int) $identidadMenu['id_usuario'];
$stmtTema->bind_param('i', $idTema);
$stmtTema->execute();
$temaOscuro = (int) ($stmtTema->get_result()->fetch_assoc()['modo_oscuro'] ?? 0) === 1;

function menuContieneRuta(array $menu, string $rutaActual): bool
{
    $rutaMenu = $menu['url'] ? basename((string) parse_url($menu['url'], PHP_URL_PATH)) : '';
    if ($rutaMenu !== '' && $rutaMenu === $rutaActual) return true;
    foreach ($menu['hijos'] ?? [] as $hijo) {
        if (menuContieneRuta($hijo, $rutaActual)) return true;
    }
    return false;
}

function existeUrlEnMenus(array $menus, string $ruta): bool
{
    foreach ($menus as $menu) {
        $rutaMenu = $menu['url'] ? basename((string) parse_url($menu['url'], PHP_URL_PATH)) : '';
        if ($rutaMenu === $ruta || existeUrlEnMenus($menu['hijos'] ?? [], $ruta)) return true;
    }
    return false;
}

function renderizarMenus(array $menus, string $rutaActual, bool $esSubmenu = false): void
{
    if (!$menus) return;
    if ($esSubmenu) echo '<div class="nav-children">';
    foreach ($menus as $menu) {
        $etiqueta = htmlspecialchars($menu['etiqueta'], ENT_QUOTES, 'UTF-8');
        $icono = htmlspecialchars($menu['icono'] ?: '•', ENT_QUOTES, 'UTF-8');
        $url = $menu['url'] ? htmlspecialchars($menu['url'], ENT_QUOTES, 'UTF-8') : null;
        $hijos = $menu['hijos'] ?? [];
        $activo = menuContieneRuta($menu, $rutaActual);
        if ($hijos) {
            echo '<details' . ($activo ? ' open' : '') . '>';
            echo '<summary><span class="nav-icon">' . $icono . '</span><span>' . $etiqueta . '</span></summary>';
            echo '<div class="nav-children">';
            if ($url !== null) {
                echo '<a class="parent-link' . ($activo && basename((string) parse_url($menu['url'], PHP_URL_PATH)) === $rutaActual ? ' active' : '') . '" href="' . $url . '">Abrir ' . $etiqueta . '</a>';
            }
            renderizarMenus($hijos, $rutaActual, false);
            echo '</div></details>';
        } elseif ($url !== null) {
            $rutaMenu = basename((string) parse_url($menu['url'], PHP_URL_PATH));
            echo '<a' . ($rutaMenu === $rutaActual ? ' class="active" aria-current="page"' : '') . ' href="' . $url . '">';
            echo '<span class="nav-icon">' . $icono . '</span><span>' . $etiqueta . '</span></a>';
        }
    }
    if ($esSubmenu) echo '</div>';
}

$titulosRuta = [
    'index.php' => 'Panel principal', 'designaciones.php' => 'Escuelas',
    'pacientes.php' => 'Pacientes', 'imprimir.php' => 'Informe LAB',
    'informe_rx.php' => 'Informe RX',
    'informe_eco.php' => 'Informe ECO',
    'informe_audiometria.php' => 'Informe audiometría',
    'usuarios.php' => 'Usuarios', 'roles.php' => 'Roles',
    'permisos.php' => 'Permisos', 'menus.php' => 'Menús',
    'universidades.php' => 'Universidades', 'titulos.php' => 'Títulos profesionales',
    'atenciones.php' => 'Asignaciones de atención',
    'estudios_referenciales.php' => 'Estudios referenciales',
    'perfil.php' => 'Mi perfil',
];
$tituloActual = $tituloPagina ?? ($titulosRuta[$rutaActual] ?? 'Salus Infirmorum');
?>
<!doctype html>
<html lang="es" data-csrf="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($tituloActual, ENT_QUOTES, 'UTF-8') ?> | Salus Infirmorum</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js"></script>
</head>
<body class="<?= $temaOscuro ? 'theme-dark' : '' ?>">
<div class="app-shell">
    <div class="sidebar-backdrop" aria-hidden="true"></div>
    <aside class="sidebar" aria-label="Navegación principal">
        <a class="brand" href="index.php">
            <img src="images/icono_menu.png" alt="">
            <span><strong>Salus Infirmorum</strong><small>Gestión clínica</small></span>
        </a>
        <div class="nav-label">Navegación</div>
        <nav class="side-nav"><?php renderizarMenus($menusNavegacion, $rutaActual); ?></nav>
        <div class="sidebar-user">
            <strong><?= htmlspecialchars($identidadMenu['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
            <small>@<?= htmlspecialchars($identidadMenu['usuario'], ENT_QUOTES, 'UTF-8') ?></small>
            <small><?= htmlspecialchars(implode(' · ', $identidadMenu['roles']), ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    </aside>
    <div class="app-frame">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-button mobile-menu" type="button" data-sidebar-toggle aria-label="Abrir menú">☰</button>
                <div class="topbar-title"><strong><?= htmlspecialchars($tituloActual, ENT_QUOTES, 'UTF-8') ?></strong><small>Sistema de gestión clínica</small></div>
            </div>
            <div class="topbar-actions">
                <button class="icon-button" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span class="theme-label">◐</span></button>
                <?php if (existeUrlEnMenus($menusNavegacion, 'perfil.php')): ?><a class="btn btn-secondary profile-link" href="perfil.php">Mi perfil</a><?php endif; ?>
                <button class="btn btn-secondary" type="button" data-logout>Salir</button>
            </div>
        </header>
        <div class="page-content">
