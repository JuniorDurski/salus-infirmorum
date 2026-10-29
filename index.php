<?php
include 'conexion.php';
$tituloPagina = 'Panel principal';

$metricas = ['pacientes' => null, 'escuelas' => null, 'estudios' => null, 'usuarios' => null];
foreach ([
    'pacientes' => ['pacientes.ver', 'SELECT COUNT(*) total FROM pacientes WHERE activo = 1'],
    'escuelas' => ['escuelas.ver', 'SELECT COUNT(*) total FROM escuelas WHERE activo = 1'],
    'estudios' => ['estudios.ver', 'SELECT COUNT(*) total FROM estudios'],
    'usuarios' => ['usuarios.ver', 'SELECT COUNT(*) total FROM usuarios WHERE activo = 1'],
] as $clave => [$permiso, $sql]) {
    if (!tienePermiso($usuarioAutenticado, $permiso)) continue;
    $metricas[$clave] = (int) ($conexion->query($sql)->fetch_assoc()['total'] ?? 0);
}

include 'menu.php';
?>

<section class="card welcome">
    <span class="eyebrow">Bienvenido de nuevo</span>
    <h1><?= htmlspecialchars($usuarioAutenticado['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Administre pacientes, estudios, informes y accesos desde un espacio unificado.</p>
</section>

<section style="margin-top:20px">
    <div class="stats-grid">
        <?php if ($metricas['pacientes'] !== null): ?><article class="stat"><span>Pacientes activos</span><strong><?= $metricas['pacientes'] ?></strong></article><?php endif; ?>
        <?php if ($metricas['escuelas'] !== null): ?><article class="stat"><span>Escuelas activas</span><strong><?= $metricas['escuelas'] ?></strong></article><?php endif; ?>
        <?php if ($metricas['estudios'] !== null): ?><article class="stat"><span>Estudios registrados</span><strong><?= $metricas['estudios'] ?></strong></article><?php endif; ?>
        <?php if ($metricas['usuarios'] !== null): ?><article class="stat"><span>Usuarios activos</span><strong><?= $metricas['usuarios'] ?></strong></article><?php endif; ?>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header"><div><h2>Accesos rápidos</h2><p class="help-text">Las opciones se adaptan a sus permisos.</p></div></div>
    <div class="form-actions">
        <?php if (tienePermiso($usuarioAutenticado, 'pacientes.ver') && existeUrlEnMenus($menusNavegacion, 'pacientes.php')): ?><a class="btn" href="pacientes.php">Pacientes</a><?php endif; ?>
        <?php if (tienePermiso($usuarioAutenticado, 'escuelas.ver') && existeUrlEnMenus($menusNavegacion, 'designaciones.php')): ?><a class="btn btn-secondary" href="designaciones.php">Escuelas</a><?php endif; ?>
        <?php if (tienePermiso($usuarioAutenticado, 'reportes.imprimir') && existeUrlEnMenus($menusNavegacion, 'imprimir.php')): ?><a class="btn btn-secondary" href="imprimir.php">Imprimir informes</a><?php endif; ?>
        <?php if (existeUrlEnMenus($menusNavegacion, 'perfil.php')): ?><a class="btn btn-secondary" href="perfil.php">Configurar mi perfil</a><?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>
