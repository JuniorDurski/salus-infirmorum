<?php

require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/backend/seguridad.php';

$usuarioAutenticado = requerirAutenticacion($conexion);

function spc(){
    echo "&ensp;&ensp;&ensp;";
}
