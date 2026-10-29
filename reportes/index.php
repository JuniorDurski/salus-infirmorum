<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/auth.php';
exigirPermiso($conexion, 'informes.generar');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }
verificarCsrf();
$escuela = filter_var($_POST['id_escuela'] ?? null, FILTER_VALIDATE_INT);
if (!$escuela) { http_response_code(422); exit('Seleccioná una escuela.'); }
$row = $conexion->execute_query('SELECT codigo FROM escuelas WHERE id_escuela=? AND eliminado_en IS NULL AND activo=1', [$escuela])->fetch_assoc();
if (!$row) { http_response_code(404); exit('Escuela inexistente.'); }
$mapa = [
    'ACADEMIL'=>'informe_academil.php', 'ISEPOL'=>'informe_isepol.php',
    'ACADEPOL'=>'informe_isepol.php', 'COLEPOL'=>'informe_isepol.php',
    'DISERGEMIL'=>'informe_disergemil.php', 'DISERSANFA'=>'informe_disersanfa.php',
    'LICEMIL'=>'informe_licemil.php', 'LICEMIL | CARDENAS'=>'informe_licemil.php',
    'LICEMIL | SANTOS'=>'informe_licemil.php', 'COMISOE'=>'informe_comisoe.php',
    'DIGETREN'=>'informe_digetren.php', 'DROGAS'=>'informe_drogas.php'
];
$code = strtoupper(trim($row['codigo']));
if (($_POST['tipo'] ?? '') === 'tipi') $mapa[$code] = 'informe_tipi.php';
if (!isset($mapa[$code])) { http_response_code(422); exit('Esta escuela aún no tiene plantilla de informe.'); }

if (($_POST['alcance'] ?? '') === 'filtered') {
    $where=['e.id_escuela=?','e.eliminado_en IS NULL','p.eliminado_en IS NULL'];$params=[$escuela];
    if (!empty($_POST['anio'])) {$where[]='e.periodo_anio=?';$params[]=(int)$_POST['anio'];}
    if (!empty($_POST['mes'])) {$where[]='e.periodo_mes=?';$params[]=(int)$_POST['mes'];}
    if (!empty($_POST['fecha'])) {$where[]='DATE(e.creado_en)=?';$params[]=(string)$_POST['fecha'];}
    if (!empty($_POST['texto'])) {$where[]='(p.nombres LIKE ? OR p.apellidos LIKE ? OR p.numero_documento LIKE ?)';$like='%'.trim((string)$_POST['texto']).'%';array_push($params,$like,$like,$like);}
    $sql='SELECT e.id_evaluacion FROM evaluaciones e JOIN pacientes p ON p.id_paciente=e.id_paciente WHERE '.implode(' AND ',$where).' ORDER BY e.id_evaluacion LIMIT 2001';
    $_POST['ids']=array_column($conexion->execute_query($sql,$params)->fetch_all(MYSQLI_ASSOC),'id_evaluacion');
}
$ids = $_POST['ids'] ?? [];
if (!is_array($ids) || !$ids || count($ids)>2000) { http_response_code(422); exit('Seleccioná entre 1 y 2000 evaluaciones.'); }
require __DIR__ . '/' . $mapa[$code];
