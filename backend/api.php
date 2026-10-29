<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function filas(mysqli $db, string $sql, array $params = []): array {
    return $db->execute_query($sql, $params)->fetch_all(MYSQLI_ASSOC);
}
function fila(mysqli $db, string $sql, array $params = []): ?array {
    return $db->execute_query($sql, $params)->fetch_assoc() ?: null;
}
function requerido(string $key): string {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') throw new InvalidArgumentException("Completá $key.");
    return $value;
}
function numero(string $key, int $min = 1): int {
    $raw = $_POST[$key] ?? null;
    if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < $min) throw new InvalidArgumentException("Valor inválido: $key.");
    return (int)$raw;
}
function opcionalId(string $key): ?int {
    $raw = $_POST[$key] ?? null;
    if ($raw === null || $raw === '') return null;
    if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 1) throw new InvalidArgumentException("Valor inválido: $key.");
    return (int)$raw;
}
function json(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function auditarCambio(mysqli $db, int $actor, string $modulo, string $tabla, string $pk, int $id, string $accion, ?array $antes): void {
    $despues = fila($db, "SELECT * FROM $tabla WHERE $pk=?", [$id]);
    auditar($db, $actor, $modulo, $tabla, [$pk => $id], $accion, $antes, $despues);
}
function requirePermisoApi(mysqli $db, int $id, string $codigo): void {
    if (!puede($db, $id, $codigo)) throw new RuntimeException('No tenés permiso para esta operación.', 403);
}

try {
    $user = usuarioActual($conexion);
    if (!$user) throw new RuntimeException('Iniciá sesión.', 401);
    $actor = (int)$user['id_usuario'];
    $resource = (string)($_GET['resource'] ?? '');
    $action = (string)($_GET['action'] ?? 'list');
    $write = $_SERVER['REQUEST_METHOD'] === 'POST';
    if ($write) verificarCsrf();

    if ($resource === 'menus' && !$write) {
        $rows = filas($conexion, 'SELECT m.id_menu, m.id_menu_padre, m.nombre, m.ruta, m.icono FROM menus m WHERE m.eliminado_en IS NULL AND m.activo=1 AND (m.visible_todos=1 OR EXISTS (SELECT 1 FROM menu_permiso mp JOIN permisos p ON p.id_permiso=mp.id_permiso JOIN rol_permiso rp ON rp.id_permiso=p.id_permiso JOIN roles r ON r.id_rol=rp.id_rol JOIN usuario_rol ur ON ur.id_rol=r.id_rol WHERE mp.id_menu=m.id_menu AND ur.id_usuario=? AND mp.eliminado_en IS NULL AND p.eliminado_en IS NULL AND p.activo=1 AND rp.eliminado_en IS NULL AND r.eliminado_en IS NULL AND r.activo=1 AND ur.eliminado_en IS NULL)) ORDER BY m.orden, m.id_menu', [$actor]);
        json(['data' => $rows]);
    }

    if ($resource === 'perfil') {
        if (!$write) json(['data' => fila($conexion, 'SELECT u.id_usuario, u.nombre, u.ci, u.email, c.id_titulo, c.id_universidad, c.numero_registro, c.firma_url, c.firma_sello_url, c.sello_url, c.modo_oscuro FROM usuarios u LEFT JOIN usuario_configuracion c ON c.id_usuario=u.id_usuario AND c.eliminado_en IS NULL WHERE u.id_usuario=?', [$actor])]);
        $before = fila($conexion, 'SELECT * FROM usuario_configuracion WHERE id_usuario=?', [$actor]);
        $dark = !empty($_POST['modo_oscuro']) ? 1 : 0;
        $title = opcionalId('id_titulo'); $uni = opcionalId('id_universidad');
        $reg = trim((string)($_POST['numero_registro'] ?? '')) ?: null;
        $firma = trim((string)($_POST['firma_url'] ?? '')) ?: null;
        $sello = trim((string)($_POST['sello_url'] ?? '')) ?: null;
        $firmaSello = trim((string)($_POST['firma_sello_url'] ?? '')) ?: null;
        $conexion->begin_transaction();
        $conexion->execute_query('INSERT INTO usuario_configuracion (id_usuario, id_titulo, id_universidad, numero_registro, firma_url, sello_url, firma_sello_url, modo_oscuro, creado_por) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id_titulo=VALUES(id_titulo), id_universidad=VALUES(id_universidad), numero_registro=VALUES(numero_registro), firma_url=VALUES(firma_url), sello_url=VALUES(sello_url), firma_sello_url=VALUES(firma_sello_url), modo_oscuro=VALUES(modo_oscuro), eliminado_en=NULL, eliminado_por=NULL, actualizado_por=VALUES(creado_por)', [$actor,$title,$uni,$reg,$firma,$sello,$firmaSello,$dark,$actor]);
        $password = (string)($_POST['nueva_clave'] ?? '');
        if ($password !== '') {
            if (strlen($password) < 12) throw new InvalidArgumentException('La clave debe tener al menos 12 caracteres.');
            $conexion->execute_query('UPDATE usuarios SET password_hash=?, actualizado_por=? WHERE id_usuario=?', [password_hash($password, PASSWORD_DEFAULT),$actor,$actor]);
        }
        auditarCambio($conexion,$actor,'SEGURIDAD','usuario_configuracion','id_usuario',$actor,$before?'ACTUALIZAR':'INSERTAR',$before);
        $conexion->commit();
        json(['ok'=>true]);
    }

    if ($resource === 'titulos' || $resource === 'universidades') {
        exigirUsuario($conexion);
        if (!$write) {
            if ($resource === 'titulos') json(['data'=>filas($conexion,'SELECT id_titulo,nombre,abreviatura,para_hombre,para_mujer FROM titulos WHERE eliminado_en IS NULL AND activo=1 ORDER BY nombre')]);
            json(['data'=>filas($conexion,'SELECT id_universidad,nombre,abreviatura,pais FROM universidades WHERE eliminado_en IS NULL AND activo=1 ORDER BY nombre')]);
        }
        requirePermisoApi($conexion,$actor,'seguridad.editar');
        $table=$resource;$pk=$resource==='titulos'?'id_titulo':'id_universidad';
        $id=opcionalId($pk);$before=$id?fila($conexion,"SELECT * FROM $table WHERE $pk=? AND eliminado_en IS NULL",[$id]):null;
        if($id&&!$before)throw new InvalidArgumentException('Registro inexistente.');
        $conexion->begin_transaction();
        if($action==='delete') {if(!$id)throw new InvalidArgumentException('Falta el registro.');$conexion->execute_query("UPDATE $table SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE $pk=?",[$actor,$actor,$id]);$verb='ELIMINAR';}
        elseif($resource==='titulos') {
            $name=requerido('nombre');$abbrev=requerido('abreviatura');$man=isset($_POST['para_hombre'])?1:0;$woman=isset($_POST['para_mujer'])?1:0;
            if(!$man&&!$woman)throw new InvalidArgumentException('Elegí al menos un género para el título.');
            if($id){$conexion->execute_query('UPDATE titulos SET nombre=?,abreviatura=?,para_hombre=?,para_mujer=?,actualizado_por=? WHERE id_titulo=?',[$name,$abbrev,$man,$woman,$actor,$id]);$verb='ACTUALIZAR';}
            else{$conexion->execute_query('INSERT INTO titulos(nombre,abreviatura,para_hombre,para_mujer,creado_por) VALUES(?,?,?,?,?)',[$name,$abbrev,$man,$woman,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        } else {
            $name=requerido('nombre');$abbrev=trim((string)($_POST['abreviatura']??''))?:null;$country=trim((string)($_POST['pais']??''))?:null;
            if($id){$conexion->execute_query('UPDATE universidades SET nombre=?,abreviatura=?,pais=?,actualizado_por=? WHERE id_universidad=?',[$name,$abbrev,$country,$actor,$id]);$verb='ACTUALIZAR';}
            else{$conexion->execute_query('INSERT INTO universidades(nombre,abreviatura,pais,creado_por) VALUES(?,?,?,?)',[$name,$abbrev,$country,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'CATALOGOS',$table,$pk,$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'escuelas') {
        requirePermisoApi($conexion,$actor,$write?'escuelas.editar':'escuelas.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT id_escuela,codigo,nombre,descripcion FROM escuelas WHERE eliminado_en IS NULL AND activo=1 ORDER BY nombre')]);
        $id=opcionalId('id_escuela');
        $before=$id?fila($conexion,'SELECT * FROM escuelas WHERE id_escuela=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Escuela inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {
            if (!$id) throw new InvalidArgumentException('Falta la escuela.');
            $conexion->execute_query('UPDATE escuelas SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_escuela=? AND eliminado_en IS NULL',[$actor,$actor,$id]);
            $verb='ELIMINAR';
        } else {
            $code=strtoupper(requerido('codigo')); $name=requerido('nombre'); $desc=trim((string)($_POST['descripcion']??''))?:null;
            if ($id) {$conexion->execute_query('UPDATE escuelas SET codigo=?,nombre=?,descripcion=?,actualizado_por=? WHERE id_escuela=?',[$code,$name,$desc,$actor,$id]);$verb='ACTUALIZAR';}
            else {$conexion->execute_query('INSERT INTO escuelas(codigo,nombre,descripcion,creado_por) VALUES(?,?,?,?)',[$code,$name,$desc,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'ESCUELAS','escuelas','id_escuela',$id,$verb,$before);
        $conexion->commit(); json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'pacientes') {
        requirePermisoApi($conexion,$actor,$write?'pacientes.editar':'pacientes.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT p.*, TIMESTAMPDIFF(YEAR,p.fecha_nacimiento,CURDATE()) edad FROM pacientes p WHERE p.eliminado_en IS NULL ORDER BY p.apellidos,p.nombres')]);
        $id=opcionalId('id_paciente');
        $before=$id?fila($conexion,'SELECT * FROM pacientes WHERE id_paciente=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Paciente inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {
            if (!$id) throw new InvalidArgumentException('Falta el paciente.');
            $conexion->execute_query('UPDATE pacientes SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_paciente=? AND eliminado_en IS NULL',[$actor,$actor,$id]);$verb='ELIMINAR';
        } else {
            $nombres=requerido('nombres');$apellidos=requerido('apellidos');$doc=requerido('numero_documento');$born=requerido('fecha_nacimiento');$sex=requerido('sexo');
            if (!in_array($sex,['M','F','X'],true) || !DateTimeImmutable::createFromFormat('!Y-m-d',$born) || DateTimeImmutable::createFromFormat('!Y-m-d',$born)->format('Y-m-d')!==$born || $born>date('Y-m-d')) throw new InvalidArgumentException('Fecha o sexo inválido.');
            $group=trim((string)($_POST['grupo_sanguineo']??''))?:null;$code=trim((string)($_POST['codigo']??''))?:null;$father=opcionalId('id_padre');$mother=opcionalId('id_madre');
            if ($father && ($father===$id || $father===$mother)) throw new InvalidArgumentException('Revisá los padres.');
            if ($mother && $mother===$id) throw new InvalidArgumentException('Revisá los padres.');
            if ($id) {$conexion->execute_query('UPDATE pacientes SET nombres=?,apellidos=?,numero_documento=?,fecha_nacimiento=?,sexo=?,grupo_sanguineo=?,codigo=?,id_padre=?,id_madre=?,actualizado_por=? WHERE id_paciente=?',[$nombres,$apellidos,$doc,$born,$sex,$group,$code,$father,$mother,$actor,$id]);$verb='ACTUALIZAR';}
            else {$conexion->execute_query('INSERT INTO pacientes(nombres,apellidos,numero_documento,fecha_nacimiento,sexo,grupo_sanguineo,codigo,id_padre,id_madre,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?)',[$nombres,$apellidos,$doc,$born,$sex,$group,$code,$father,$mother,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'PACIENTES','pacientes','id_paciente',$id,$verb,$before);
        $conexion->commit();json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'derivadores') {
        requirePermisoApi($conexion,$actor,$write?'derivadores.editar':'derivadores.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT id_derivador,tipo,nombre,numero_documento,numero_registro,telefono,email FROM derivadores WHERE eliminado_en IS NULL AND activo=1 ORDER BY nombre')]);
        $id=opcionalId('id_derivador');$before=$id?fila($conexion,'SELECT * FROM derivadores WHERE id_derivador=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Derivador inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {if (!$id) throw new InvalidArgumentException('Falta el derivador.');$conexion->execute_query('UPDATE derivadores SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_derivador=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else { $type=requerido('tipo');if (!in_array($type,['PERSONA','INSTITUCION'],true)) throw new InvalidArgumentException('Tipo inválido.');$name=requerido('nombre');$doc=trim((string)($_POST['numero_documento']??''))?:null;$reg=trim((string)($_POST['numero_registro']??''))?:null;$tel=trim((string)($_POST['telefono']??''))?:null;$mail=trim((string)($_POST['email']??''))?:null;
            if ($id) {$conexion->execute_query('UPDATE derivadores SET tipo=?,nombre=?,numero_documento=?,numero_registro=?,telefono=?,email=?,actualizado_por=? WHERE id_derivador=?',[$type,$name,$doc,$reg,$tel,$mail,$actor,$id]);$verb='ACTUALIZAR';}
            else {$conexion->execute_query('INSERT INTO derivadores(tipo,nombre,numero_documento,numero_registro,telefono,email,creado_por) VALUES(?,?,?,?,?,?,?)',[$type,$name,$doc,$reg,$tel,$mail,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'DERIVADORES','derivadores','id_derivador',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'derivaciones') {
        requirePermisoApi($conexion,$actor,$write?'derivadores.editar':'derivadores.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT d.id_derivacion,d.id_derivador,d.id_paciente,d.motivo,d.creado_en,r.nombre derivador,CONCAT(p.apellidos,\' \',p.nombres) paciente FROM derivaciones d JOIN derivadores r ON r.id_derivador=d.id_derivador AND r.eliminado_en IS NULL JOIN pacientes p ON p.id_paciente=d.id_paciente AND p.eliminado_en IS NULL WHERE d.eliminado_en IS NULL ORDER BY d.creado_en DESC')]);
        $conexion->begin_transaction();
        if ($action==='delete') {$id=numero('id_derivacion');$before=fila($conexion,'SELECT * FROM derivaciones WHERE id_derivacion=? AND eliminado_en IS NULL',[$id]);if (!$before) throw new InvalidArgumentException('Derivación inexistente.');$conexion->execute_query('UPDATE derivaciones SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_derivacion=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else {$derivador=numero('id_derivador');$paciente=numero('id_paciente');if (!fila($conexion,'SELECT 1 FROM derivadores WHERE id_derivador=? AND eliminado_en IS NULL',[$derivador]) || !fila($conexion,'SELECT 1 FROM pacientes WHERE id_paciente=? AND eliminado_en IS NULL',[$paciente])) throw new InvalidArgumentException('Paciente o derivador inexistente.');$motivo=trim((string)($_POST['motivo']??''))?:null;$conexion->execute_query('INSERT INTO derivaciones(id_derivador,id_paciente,motivo,creado_por) VALUES(?,?,?,?)',[$derivador,$paciente,$motivo,$actor]);$id=$conexion->insert_id;$before=null;$verb='INSERTAR';}
        auditarCambio($conexion,$actor,'DERIVADORES','derivaciones','id_derivacion',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'evaluaciones') {
        requirePermisoApi($conexion,$actor,$write?'evaluaciones.editar':'evaluaciones.ver');
        if (!$write) {
            $where=['e.eliminado_en IS NULL','p.eliminado_en IS NULL','s.eliminado_en IS NULL'];$params=[];
            if (!empty($_GET['escuela'])) {$where[]='e.id_escuela=?';$params[]=(int)$_GET['escuela'];}
            if (!empty($_GET['anio'])) {$where[]='e.periodo_anio=?';$params[]=(int)$_GET['anio'];}
            if (!empty($_GET['mes'])) {$where[]='e.periodo_mes=?';$params[]=(int)$_GET['mes'];}
            if (!empty($_GET['fecha'])) {$where[]='DATE(e.creado_en)=?';$params[]=(string)$_GET['fecha'];}
            if (!empty($_GET['texto'])) {$where[]='(p.nombres LIKE ? OR p.apellidos LIKE ? OR p.numero_documento LIKE ?)';$like='%'.trim((string)$_GET['texto']).'%';array_push($params,$like,$like,$like);}
            $sql='SELECT e.id_evaluacion,e.id_paciente,e.id_escuela,e.codigo,e.periodo,e.periodo_anio,e.periodo_mes,e.estado,e.creado_en,p.nombres,p.apellidos,p.numero_documento,p.fecha_nacimiento,p.sexo,p.grupo_sanguineo,s.nombre escuela,s.codigo escuela_codigo,EXISTS(SELECT 1 FROM resultados_mock m WHERE m.id_evaluacion=e.id_evaluacion AND m.eliminado_en IS NULL) tiene_resultados FROM evaluaciones e JOIN pacientes p ON p.id_paciente=e.id_paciente JOIN escuelas s ON s.id_escuela=e.id_escuela WHERE '.implode(' AND ',$where).' ORDER BY e.creado_en DESC,e.id_evaluacion DESC';
            json(['data'=>filas($conexion,$sql,$params)]);
        }
        $id=opcionalId('id_evaluacion');$before=$id?fila($conexion,'SELECT * FROM evaluaciones WHERE id_evaluacion=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Evaluación inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {if (!$id) throw new InvalidArgumentException('Falta la evaluación.');$conexion->execute_query('UPDATE evaluaciones SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_evaluacion=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else {$patient=numero('id_paciente');$school=numero('id_escuela');$year=numero('anio',1900);$month=numero('mes');if ($year>2200 || $month>12) throw new InvalidArgumentException('Período inválido.');if (!fila($conexion,'SELECT 1 FROM pacientes WHERE id_paciente=? AND eliminado_en IS NULL',[$patient]) || !fila($conexion,'SELECT 1 FROM escuelas WHERE id_escuela=? AND eliminado_en IS NULL',[$school])) throw new InvalidArgumentException('Paciente o escuela inexistente.');$period=json_encode(['anio'=>$year,'mes'=>$month]);$code=trim((string)($_POST['codigo']??''))?:null;
            if ($id) {$conexion->execute_query('UPDATE evaluaciones SET id_paciente=?,id_escuela=?,periodo=?,codigo=?,actualizado_por=? WHERE id_evaluacion=?',[$patient,$school,$period,$code,$actor,$id]);$verb='ACTUALIZAR';}
            else {$conexion->execute_query('INSERT INTO evaluaciones(id_paciente,id_escuela,periodo,codigo,creado_por) VALUES(?,?,?,?,?)',[$patient,$school,$period,$code,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'EVALUACIONES','evaluaciones','id_evaluacion',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }

    if ($resource === 'roles') {
        requirePermisoApi($conexion,$actor,$write?'seguridad.editar':'seguridad.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT id_rol,codigo,nombre,descripcion FROM roles WHERE eliminado_en IS NULL AND activo=1 ORDER BY nombre')]);
        $id=opcionalId('id_rol');$before=$id?fila($conexion,'SELECT * FROM roles WHERE id_rol=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Rol inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {if(!$id)throw new InvalidArgumentException('Falta el rol.');if($before['codigo']==='ADMIN')throw new InvalidArgumentException('No se puede eliminar el rol administrador.');$conexion->execute_query('UPDATE roles SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_rol=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else {$code=strtoupper(requerido('codigo'));$name=requerido('nombre');$description=trim((string)($_POST['descripcion']??''))?:null;
            if($id){$conexion->execute_query('UPDATE roles SET codigo=?,nombre=?,descripcion=?,actualizado_por=? WHERE id_rol=?',[$code,$name,$description,$actor,$id]);$verb='ACTUALIZAR';}
            else{$conexion->execute_query('INSERT INTO roles(codigo,nombre,descripcion,creado_por) VALUES(?,?,?,?)',[$code,$name,$description,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'SEGURIDAD','roles','id_rol',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }
    if ($resource === 'permisos') {
        requirePermisoApi($conexion,$actor,$write?'seguridad.editar':'seguridad.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT id_permiso,codigo,nombre,modulo,descripcion FROM permisos WHERE eliminado_en IS NULL AND activo=1 ORDER BY modulo,codigo')]);
        $id=opcionalId('id_permiso');$before=$id?fila($conexion,'SELECT * FROM permisos WHERE id_permiso=? AND eliminado_en IS NULL',[$id]):null;
        if($id&&!$before)throw new InvalidArgumentException('Permiso inexistente.');
        $conexion->begin_transaction();
        if($action==='delete'){if(!$id)throw new InvalidArgumentException('Falta el permiso.');$conexion->execute_query('UPDATE permisos SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_permiso=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else{$code=requerido('codigo');$name=requerido('nombre');$module=strtoupper(requerido('modulo'));$description=trim((string)($_POST['descripcion']??''))?:null;
            if($id){$conexion->execute_query('UPDATE permisos SET codigo=?,nombre=?,modulo=?,descripcion=?,actualizado_por=? WHERE id_permiso=?',[$code,$name,$module,$description,$actor,$id]);$verb='ACTUALIZAR';}
            else{$conexion->execute_query('INSERT INTO permisos(codigo,nombre,modulo,descripcion,creado_por) VALUES(?,?,?,?,?)',[$code,$name,$module,$description,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'SEGURIDAD','permisos','id_permiso',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }
    if ($resource === 'menus_admin') {
        requirePermisoApi($conexion,$actor,$write?'seguridad.editar':'seguridad.ver');
        if(!$write)json(['data'=>filas($conexion,'SELECT id_menu,id_menu_padre,codigo,nombre,ruta,icono,orden,visible_todos FROM menus WHERE eliminado_en IS NULL ORDER BY orden,id_menu')]);
        $id=opcionalId('id_menu');$before=$id?fila($conexion,'SELECT * FROM menus WHERE id_menu=? AND eliminado_en IS NULL',[$id]):null;
        if($id&&!$before)throw new InvalidArgumentException('Menú inexistente.');
        $conexion->begin_transaction();
        if($action==='delete'){if(!$id)throw new InvalidArgumentException('Falta el menú.');$conexion->execute_query('UPDATE menus SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_menu=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else{$code=strtoupper(requerido('codigo'));$name=requerido('nombre');$route=trim((string)($_POST['ruta']??''))?:null;$icon=trim((string)($_POST['icono']??''))?:null;$order=(int)($_POST['orden']??0);$parent=opcionalId('id_menu_padre');$visible=isset($_POST['visible_todos'])?1:0;
            if($parent===$id&&$id)throw new InvalidArgumentException('Un menú no puede ser su propio padre.');
            if($parent&&!fila($conexion,'SELECT 1 FROM menus WHERE id_menu=? AND eliminado_en IS NULL',[$parent]))throw new InvalidArgumentException('Menú padre inexistente.');
            if($id){$conexion->execute_query('UPDATE menus SET codigo=?,nombre=?,ruta=?,icono=?,orden=?,id_menu_padre=?,visible_todos=?,actualizado_por=? WHERE id_menu=?',[$code,$name,$route,$icon,$order,$parent,$visible,$actor,$id]);$verb='ACTUALIZAR';}
            else{$conexion->execute_query('INSERT INTO menus(codigo,nombre,ruta,icono,orden,id_menu_padre,visible_todos,creado_por) VALUES(?,?,?,?,?,?,?,?)',[$code,$name,$route,$icon,$order,$parent,$visible,$actor]);$id=$conexion->insert_id;$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'MENUS','menus','id_menu',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }
    if ($resource === 'rol_permisos' || $resource === 'menu_permisos') {
        requirePermisoApi($conexion,$actor,$write?'seguridad.editar':'seguridad.ver');
        $table=$resource==='rol_permisos'?'rol_permiso':'menu_permiso';$ownerKey=$resource==='rol_permisos'?'id_rol':'id_menu';$ownerTable=$resource==='rol_permisos'?'roles':'menus';
        if(!$write)json(['data'=>filas($conexion,"SELECT $ownerKey,id_permiso FROM $table WHERE eliminado_en IS NULL")]);
        $owner=numero($ownerKey);$permission=numero('id_permiso');
        if(!fila($conexion,"SELECT 1 FROM $ownerTable WHERE $ownerKey=? AND eliminado_en IS NULL",[$owner])||!fila($conexion,'SELECT 1 FROM permisos WHERE id_permiso=? AND eliminado_en IS NULL',[$permission]))throw new InvalidArgumentException('Relación inválida.');
        $before=fila($conexion,"SELECT * FROM $table WHERE $ownerKey=? AND id_permiso=?",[$owner,$permission]);$conexion->begin_transaction();
        if($action==='delete'){$conexion->execute_query("UPDATE $table SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE $ownerKey=? AND id_permiso=? AND eliminado_en IS NULL",[$actor,$actor,$owner,$permission]);$verb='REVOCAR';}
        else{$conexion->execute_query("INSERT INTO $table($ownerKey,id_permiso,creado_por) VALUES(?,?,?) ON DUPLICATE KEY UPDATE eliminado_en=NULL,eliminado_por=NULL,actualizado_por=VALUES(creado_por)",[$owner,$permission,$actor]);$verb='ASIGNAR';}
        auditar($conexion,$actor,'SEGURIDAD',$table,[$ownerKey=>$owner,'id_permiso'=>$permission],$verb,$before,fila($conexion,"SELECT * FROM $table WHERE $ownerKey=? AND id_permiso=?",[$owner,$permission]));$conexion->commit();json(['ok'=>true]);
    }
    if ($resource === 'usuarios') {
        requirePermisoApi($conexion,$actor,$write?'seguridad.editar':'seguridad.ver');
        if (!$write) json(['data'=>filas($conexion,'SELECT u.id_usuario,u.nombre,u.ci,u.email,u.activo,GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ", ") roles FROM usuarios u LEFT JOIN usuario_rol ur ON ur.id_usuario=u.id_usuario AND ur.eliminado_en IS NULL LEFT JOIN roles r ON r.id_rol=ur.id_rol AND r.eliminado_en IS NULL WHERE u.eliminado_en IS NULL GROUP BY u.id_usuario ORDER BY u.nombre')]);
        $id=opcionalId('id_usuario');$before=$id?fila($conexion,'SELECT * FROM usuarios WHERE id_usuario=? AND eliminado_en IS NULL',[$id]):null;
        if ($id && !$before) throw new InvalidArgumentException('Usuario inexistente.');
        $conexion->begin_transaction();
        if ($action==='delete') {if (!$id || $id===$actor) throw new InvalidArgumentException('No podés eliminar tu propia cuenta.');$conexion->execute_query('UPDATE usuarios SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_usuario=?',[$actor,$actor,$id]);$verb='ELIMINAR';}
        else {$name=requerido('nombre');$ci=requerido('ci');$mail=trim((string)($_POST['email']??''))?:null;$pass=(string)($_POST['clave']??'');$active=isset($_POST['activo'])?1:0;
            if ($pass!=='' && strlen($pass)<12) throw new InvalidArgumentException('La clave debe tener al menos 12 caracteres.');
            if ($id) {$conexion->execute_query('UPDATE usuarios SET nombre=?,ci=?,email=?,activo=?,actualizado_por=? WHERE id_usuario=?',[$name,$ci,$mail,$active,$actor,$id]);if ($pass!=='') $conexion->execute_query('UPDATE usuarios SET password_hash=? WHERE id_usuario=?',[password_hash($pass,PASSWORD_DEFAULT),$id]);$verb='ACTUALIZAR';}
            else {if ($pass==='') throw new InvalidArgumentException('La clave es obligatoria.');$conexion->execute_query('INSERT INTO usuarios(nombre,ci,email,password_hash,activo,creado_por) VALUES(?,?,?,?,?,?)',[$name,$ci,$mail,password_hash($pass,PASSWORD_DEFAULT),$active,$actor]);$id=$conexion->insert_id;$conexion->execute_query('INSERT INTO usuario_configuracion(id_usuario,creado_por) VALUES(?,?)',[$id,$actor]);$verb='INSERTAR';}
        }
        auditarCambio($conexion,$actor,'SEGURIDAD','usuarios','id_usuario',$id,$verb,$before);$conexion->commit();json(['ok'=>true,'id'=>$id]);
    }
    if ($resource === 'usuario_roles' && $write) {
        requirePermisoApi($conexion,$actor,'seguridad.editar');$userId=numero('id_usuario');$roleId=numero('id_rol');
        if (!fila($conexion,'SELECT 1 FROM usuarios WHERE id_usuario=? AND eliminado_en IS NULL',[$userId]) || !fila($conexion,'SELECT 1 FROM roles WHERE id_rol=? AND eliminado_en IS NULL',[$roleId])) throw new InvalidArgumentException('Usuario o rol inexistente.');
        $before=fila($conexion,'SELECT * FROM usuario_rol WHERE id_usuario=? AND id_rol=?',[$userId,$roleId]);$conexion->begin_transaction();
        if ($action==='delete') {$conexion->execute_query('UPDATE usuario_rol SET eliminado_en=NOW(6),eliminado_por=?,actualizado_por=? WHERE id_usuario=? AND id_rol=? AND eliminado_en IS NULL',[$actor,$actor,$userId,$roleId]);$verb='REVOCAR';}
        else {$conexion->execute_query('INSERT INTO usuario_rol(id_usuario,id_rol,creado_por) VALUES(?,?,?) ON DUPLICATE KEY UPDATE eliminado_en=NULL,eliminado_por=NULL,actualizado_por=VALUES(creado_por)',[$userId,$roleId,$actor]);$verb='ASIGNAR';}
        auditar($conexion,$actor,'SEGURIDAD','usuario_rol',['id_usuario'=>$userId,'id_rol'=>$roleId],$verb,$before,fila($conexion,'SELECT * FROM usuario_rol WHERE id_usuario=? AND id_rol=?',[$userId,$roleId]));$conexion->commit();json(['ok'=>true]);
    }
    if ($resource === 'auditoria') {requirePermisoApi($conexion,$actor,'seguridad.ver');json(['data'=>filas($conexion,'SELECT a.id_auditoria,a.actor_identificador,a.modulo,a.entidad,a.clave_registro,a.accion,a.datos_anteriores,a.datos_nuevos,a.creado_en FROM auditoria_seguridad a WHERE a.eliminado_en IS NULL ORDER BY a.id_auditoria DESC LIMIT 200')]);}
    throw new InvalidArgumentException('Operación desconocida.');
} catch (Throwable $e) {
    if ($write ?? false) $conexion->rollback();
    $status=$e->getCode();
    $previousStatus=http_response_code();
    http_response_code(is_int($status) && $status>=400 && $status<500?$status:($previousStatus>=400&&$previousStatus<500?$previousStatus:($e instanceof InvalidArgumentException?422:500)));
    if ($e instanceof mysqli_sql_exception) {error_log($e->getMessage());json(['error'=>'No se pudo completar la operación. Revisá si existe un duplicado o una relación inválida.']);}
    json(['error'=>$e->getMessage()]);
}
