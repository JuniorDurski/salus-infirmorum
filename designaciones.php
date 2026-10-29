<?php require_once __DIR__.'/backend/auth.php'; exigirPermiso($conexion,'escuelas.ver'); $tituloPagina='Escuelas'; require __DIR__.'/menu.php'; ?>
<div class="page-head"><div><h1>Escuelas</h1><p>Instituciones para las evaluaciones y plantillas PDF.</p></div></div>
<div class="grid two-col" data-page="escuelas">
<section class="card"><h2>Datos de la escuela</h2><form id="school-form" class="stack"><input type="hidden" name="id_escuela"><label>Código de plantilla<input name="codigo" placeholder="LICEMIL" required maxlength="30"></label><label>Nombre<input name="nombre" required maxlength="200"></label><label>Descripción<textarea name="descripcion"></textarea></label><div class="actions"><button class="primary">Guardar escuela</button><button type="reset">Limpiar</button></div></form><p class="hint">Códigos disponibles: ACADEMIL, ISEPOL, ACADEPOL, COLEPOL, DISERGEMIL, DISERSANFA, LICEMIL, COMISOE, DIGETREN y DROGAS.</p></section>
<section class="card"><h2>Escuelas registradas</h2><div class="table-wrap"><table><thead><tr><th>Código</th><th>Nombre</th><th>Acciones</th></tr></thead><tbody id="school-rows"></tbody></table></div></section></div>
<?php require __DIR__.'/footer.php'; ?>
