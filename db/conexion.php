
<?php

$host       = "localhost";
$usuario    = "root";
$clave      = "";
$base_datos = "postulantes";

$conexion = new mysqli($host, $usuario, $clave, $base_datos);

if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}
?>
