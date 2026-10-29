<?php
header('Content-Type: application/json; charset=utf-8');
require_once "conexion.php";

$q = $_GET['q'] ?? '';

switch ($q) {

    /* ===================== LOGIN ===================== */
    case 'login':

        $ci    = $_POST['ci'] ?? '';
        $clave = $_POST['clave'] ?? '';

        $sql = "SELECT 
                    id_usuario,
                    nombre,
                    cargo,
                    fuente_claro,
                    CASE WHEN fuente_claro = 1 THEN 0 ELSE 1 END AS fuente_oscura
                FROM usuarios
                WHERE ci = ? AND clave = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $ci, $clave);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs->num_rows == 1) {
            session_start();
            $_SESSION['usuario'] = $rs->fetch_assoc();
            echo json_encode(["status"=>"ok"]);
        } else {
            echo json_encode(["status"=>"error","mensaje"=>"Credenciales inválidas"]);
        }
        break;


    /* ===================== CONSULTAR FUENTE ===================== */
    case 'fuente_color':

        //session_start();
        $id = 1;//$_SESSION['usuario']['id_usuario'] ?? 0;

        $sql = "SELECT 
                    fuente_claro
                FROM usuarios
                WHERE id_usuario = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode($stmt->get_result()->fetch_assoc());
        break;


    /* ===================== TOGGLE FUENTE ===================== */
    case 'cambiar_fuente':

        //session_start();
        $id = 1;//$_SESSION['usuario']['id_usuario'] ?? 0;

        $sql = "UPDATE usuarios
                SET fuente_claro = IF(fuente_claro = 1, 0, 1)
                WHERE id_usuario = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status"=>"ok"]);
        } else {
            echo json_encode(["status"=>"error"]);
        }
        break;


    /* ===================== CRUD ===================== */
    case 'listar':

        $sql = "SELECT 
                    id_usuario, nombre, ci, cargo, fuente_claro
                FROM usuarios
                ORDER BY id_usuario DESC";

        $rs = $conexion->query($sql);
        $data = [];
        while ($row = $rs->fetch_assoc()) $data[] = $row;

        echo json_encode($data);
        break;


    case 'guardar':

        $id     = $_POST['id_usuario'] ?? '';
        $nom    = strtoupper(trim($_POST['nombre'] ?? ''));
        $ci     = trim($_POST['ci'] ?? '');
        $clave  = trim($_POST['clave'] ?? '');
        $cargo  = trim($_POST['cargo'] ?? '');

        if ($id != '') {
            $sql = "UPDATE usuarios
                    SET nombre=?, ci=?, clave=?, cargo=?
                    WHERE id_usuario=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssssi", $nom, $ci, $clave, $cargo, $id);
        } else {
            $sql = "INSERT INTO usuarios (nombre, ci, clave, cargo)
                    VALUES (?,?,?,?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssss", $nom, $ci, $clave, $cargo);
        }

        echo json_encode($stmt->execute() ? ["status"=>"ok"] : ["status"=>"error"]);
        break;


    case 'eliminar':

        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM usuarios WHERE id_usuario=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        echo json_encode($stmt->execute() ? ["status"=>"ok"] : ["status"=>"error"]);
        break;


    default:
        echo json_encode(["status"=>"error","mensaje"=>"Operación no válida"]);
}
