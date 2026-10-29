<?php
$tituloPagina='Panel general';
require __DIR__.'/menu.php';
$stats=[];
foreach (['pacientes'=>'Pacientes','escuelas'=>'Escuelas','evaluaciones'=>'Evaluaciones','resultados_mock'=>'Informes generados'] as $table=>$label) {
    $stats[$label]=(int)$conexion->query("SELECT COUNT(*) FROM $table WHERE eliminado_en IS NULL")->fetch_row()[0];
}
?>
<div class="page-head"><div><p class="eyebrow">Bienvenido</p><h1>Panel general</h1><p>Gestión de pacientes, evaluaciones e informes de laboratorio.</p></div></div>
<div class="grid three-col">
<?php foreach ($stats as $label=>$value): ?><div class="card"><p class="muted"><?= htmlspecialchars($label) ?></p><div class="stat"><?= $value ?></div></div><?php endforeach; ?>
</div>
<div class="card" style="margin-top:20px"><h2>Accesos rápidos</h2><div class="actions"><a class="button primary" href="pacientes.php">Nueva evaluación</a><a class="button" href="imprimir.php">Generar PDF</a><a class="button" href="perfil.php">Mi perfil</a></div></div>
<?php require __DIR__.'/footer.php'; ?>
