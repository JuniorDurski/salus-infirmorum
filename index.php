<?php
include 'conexion.php';
$tituloPagina = 'Panel principal';

// Indicadores, cobros, ganancias y gráficos: solo con el permiso dashboard.ver.
$puedeVerDatos = tienePermiso($usuarioAutenticado, 'dashboard.ver');
$puedeVerPostulantes = $puedeVerDatos && tienePermiso($usuarioAutenticado, 'pacientes.ver');
$contar = static fn(string $sql): float => (float) ($conexion->query($sql)->fetch_row()[0] ?? 0);

$indicadores = [];
$registrosSemana = [];
$escuelasMes = [];
if ($puedeVerPostulantes) {
    $registroActivo = 'FROM postulantes_arreador_postulante ap INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente AND p.activo = 1 AND ap.activo = 1';
    // Pagos de registros desactivados no cuentan.
    $pagosActivos = "FROM postulantes_pago pg INNER JOIN postulantes_arreador_postulante ap ON ap.id_arreador_postulante = pg.id_arreador_postulante AND ap.activo = 1";
    $indicadores[] = ['Registrados hoy', number_format($contar("SELECT COUNT(*) $registroActivo WHERE DATE(ap.derivado_en) = CURDATE()"), 0, ',', '.'), 'postulantes'];
    $indicadores[] = ['Registrados en el mes', number_format($contar("SELECT COUNT(*) $registroActivo WHERE ap.derivado_en >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"), 0, ',', '.'), 'lista'];
    $indicadores[] = ['Cobrado hoy', 'Gs. ' . number_format($contar("SELECT COALESCE(SUM(pg.monto), 0) $pagosActivos WHERE DATE(pg.fecha) = CURDATE()"), 0, ',', '.'), 'dinero'];
    $indicadores[] = ['Cobrado en el mes', 'Gs. ' . number_format($contar("SELECT COALESCE(SUM(pg.monto), 0) $pagosActivos WHERE pg.fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"), 0, ',', '.'), 'grafico'];
    // Ganancia de la clínica: el importe para la clínica de cada convenio, por postulante registrado.
    $conGanancia = "$registroActivo INNER JOIN postulantes_arreador_escuela ae ON ae.id_arreador_escuela = ap.id_arreador_escuela";
    $indicadores[] = ['Ganancia hoy', 'Gs. ' . number_format($contar("SELECT COALESCE(SUM(ae.monto_clinica), 0) $conGanancia WHERE DATE(ap.derivado_en) = CURDATE()"), 0, ',', '.'), 'dinero', true];
    $indicadores[] = ['Ganancia del mes', 'Gs. ' . number_format($contar("SELECT COALESCE(SUM(ae.monto_clinica), 0) $conGanancia WHERE ap.derivado_en >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"), 0, ',', '.'), 'grafico', true];

    $porDia = [];
    $resultado = $conexion->query("SELECT DATE(ap.derivado_en) dia, COUNT(*) total $registroActivo WHERE ap.derivado_en >= CURDATE() - INTERVAL 6 DAY GROUP BY dia");
    while ($fila = $resultado->fetch_assoc()) $porDia[$fila['dia']] = (int) $fila['total'];
    $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    for ($i = 6; $i >= 0; $i--) {
        $fecha = new DateTimeImmutable("-$i day");
        $registrosSemana[] = [
            'etiqueta' => $i === 0 ? 'Hoy' : $diasSemana[(int) $fecha->format('w')] . ' ' . $fecha->format('d'),
            'total' => $porDia[$fecha->format('Y-m-d')] ?? 0,
        ];
    }

    $escuelasMes = $conexion->query(
        "SELECT e.nombre, COUNT(*) total, COALESCE(SUM(ae.monto_clinica), 0) ganancia
         $registroActivo
         INNER JOIN postulantes_arreador_escuela ae ON ae.id_arreador_escuela = ap.id_arreador_escuela
         INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
         WHERE ap.derivado_en >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
         GROUP BY e.id_escuela, e.nombre
         ORDER BY total DESC, e.nombre
         LIMIT 6"
    )->fetch_all(MYSQLI_ASSOC);
}
if ($puedeVerDatos && tienePermiso($usuarioAutenticado, 'usuarios.ver')) {
    $indicadores[] = ['Usuarios activos', number_format($contar('SELECT COUNT(*) FROM usuarios WHERE activo = 1'), 0, ',', '.'), 'usuarios'];
}

include 'menu.php';

/** Hojas del menú visibles para el usuario, para armar los accesos rápidos. */
function accesosDesdeMenus(array $menus, string $grupo = ''): array
{
    $accesos = [];
    foreach ($menus as $menu) {
        $ruta = basename((string) parse_url((string) $menu['url'], PHP_URL_PATH));
        if (!empty($menu['hijos'])) {
            $accesos = array_merge($accesos, accesosDesdeMenus($menu['hijos'], $grupo ?: $menu['etiqueta']));
        } elseif ($menu['url'] && !in_array($ruta, ['index.php', 'perfil.php'], true)) {
            $accesos[] = ['etiqueta' => $menu['etiqueta'], 'url' => $menu['url'], 'icono' => $menu['icono'], 'grupo' => $grupo];
        }
    }
    return $accesos;
}
$accesos = accesosDesdeMenus($menusNavegacion);

$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$fechaHoy = ucfirst($dias[(int) date('w')]) . ', ' . date('j') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$maxSemana = max(1, ...array_column($registrosSemana ?: [['total' => 0]], 'total'));
$maxEscuela = max(1, ...array_map('intval', array_column($escuelasMes ?: [['total' => 0]], 'total')));
?>

<section class="dash-welcome">
    <div>
        <span class="eyebrow"><?= $e($fechaHoy) ?></span>
        <h1><?= $e($saludo) ?>, <?= $e($usuarioAutenticado['nombre']) ?></h1>
        <p><?= $e(implode(' · ', $usuarioAutenticado['roles'])) ?></p>
    </div>
</section>

<?php if ($indicadores): ?>
<section class="dash-kpis">
    <?php foreach ($indicadores as $indicador): [$titulo, $valor, $icono] = $indicador; ?>
        <article class="dash-kpi<?= !empty($indicador[3]) ? ' dash-kpi-destacado' : '' ?>">
            <span class="dash-kpi-icon"><?= iconoSvg($icono) ?></span>
            <div><span><?= $e($titulo) ?></span><strong><?= $e($valor) ?></strong></div>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($puedeVerPostulantes): ?>
<section class="dash-grid">
    <article class="card dash-panel">
        <div class="card-header"><div><h2>Registros de postulantes</h2><p class="help-text">Últimos 7 días</p></div></div>
        <div class="dash-columns" role="img" aria-label="Registros de postulantes por día en los últimos 7 días">
            <?php foreach ($registrosSemana as $dia): ?>
                <div class="dash-column">
                    <span class="dash-column-value"><?= (int) $dia['total'] ?></span>
                    <span class="dash-column-bar" style="height: <?= max(2, round($dia['total'] / $maxSemana * 100)) ?>%"></span>
                    <span class="dash-column-label"><?= $e($dia['etiqueta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="card dash-panel">
        <div class="card-header"><div><h2>Escuelas del mes</h2><p class="help-text">Postulantes registrados y ganancia por escuela</p></div></div>
        <?php if ($escuelasMes): ?>
            <div class="dash-bars">
                <?php foreach ($escuelasMes as $escuela): ?>
                    <div class="dash-bar">
                        <div class="dash-bar-text"><span><?= $e($escuela['nombre']) ?></span><strong><?= (int) $escuela['total'] ?> <small>· Gs. <?= number_format((float) $escuela['ganancia'], 0, ',', '.') ?></small></strong></div>
                        <span class="dash-bar-track"><span style="width: <?= round($escuela['total'] / $maxEscuela * 100) ?>%"></span></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">Todavía no hay registros este mes.</p>
        <?php endif; ?>
    </article>
</section>
<?php endif; ?>

<?php if ($accesos): ?>
<section class="card dash-panel">
    <div class="card-header"><div><h2>Accesos rápidos</h2><p class="help-text">Según sus permisos.</p></div></div>
    <div class="dash-shortcuts">
        <?php foreach ($accesos as $acceso): ?>
            <a class="dash-shortcut" href="<?= $e($acceso['url']) ?>">
                <span class="dash-shortcut-icon"><?= iconoMenuHtml($acceso['icono']) ?></span>
                <span><strong><?= $e(mb_convert_case(mb_strtolower($acceso['etiqueta'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8')) ?></strong><?php if ($acceso['grupo']): ?><small><?= $e(mb_convert_case(mb_strtolower($acceso['grupo'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8')) ?></small><?php endif; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>
