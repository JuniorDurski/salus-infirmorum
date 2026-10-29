<?php
require_once __DIR__ . '/backend/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verificarCsrf();
$user = usuarioActual($conexion);
if ($user) auditar($conexion,(int)$user['id_usuario'],'SEGURIDAD','usuarios',['id_usuario'=>(int)$user['id_usuario']],'CERRAR_SESION',null,null);
$_SESSION = [];
session_destroy();
header('Location: login.php');
