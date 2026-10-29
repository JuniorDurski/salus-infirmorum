<?php
include "conexion.php";
include "menu.php";
?>

<center>
	<h1>C:\User\<?= htmlspecialchars($usuarioAutenticado['usuario']) ?>> BIENVENIDO</h1>
	<h2><?= htmlspecialchars($usuarioAutenticado['nombre']) ?></h2>
	<img src="images/hola_mundo.png" height="500">
</center>


<?php
include "footer.php";
?>
