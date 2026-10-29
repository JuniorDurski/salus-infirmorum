<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/menu_service.php';

$identidad = requerirAutenticacion($conexion, null, true);
responderJson(obtenerMenusVisibles($conexion, (int) $identidad['id_usuario']));
