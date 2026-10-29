<?php
header('Content-Type: application/json; charset=utf-8');
require_once "conexion.php";

$q = $_GET['q'] ?? '';

switch ($q) {

    // ===================== LISTAR =====================
    case 'listar':

        $sql = "SELECT id_designacion, designacion, periodo 
                FROM designaciones 
                ORDER BY periodo, designacion";

        $rs = $conexion->query($sql);

        if (!$rs) {
            echo json_encode(["status"=>"error","mensaje"=>$conexion->error]);
            exit;
        }

        $data = [];
        while ($row = $rs->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        break;

    // ===================== GUARDAR =====================
    case 'guardar':

        $id       = $_POST['id_designacion'] ?? '';
        $des      = mb_strtoupper(trim($_POST['designacion'] ?? ''), 'UTF-8');
        $periodo  = mb_strtoupper(trim($_POST['periodo'] ?? ''), 'UTF-8');

        if ($des == '' || $periodo == '') {
            echo json_encode(["status"=>"error","mensaje"=>"Datos incompletos"]);
            exit;
        }

        // EDITAR
        if ($id != '') {

            $sql = "UPDATE designaciones 
                    SET designacion=?, periodo=? 
                    WHERE id_designacion=?";

            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssi", $des, $periodo, $id);

        } else {
        // INSERTAR

            $sql = "INSERT INTO designaciones (designacion, periodo)
                    VALUES (?, ?)";

            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ss", $des, $periodo);
        }

        if ($stmt->execute()) {
            echo json_encode(["status"=>"ok"]);
        } else {
            echo json_encode(["status"=>"error","mensaje"=>$stmt->error]);
        }

        break;

    // ===================== ELIMINAR =====================
    case 'eliminar':

        $id = $_POST['id'] ?? '';

        if ($id == '') {
            echo json_encode(["status"=>"error","mensaje"=>"ID no recibido"]);
            exit;
        }

        $sql = "DELETE FROM designaciones WHERE id_designacion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status"=>"ok"]);
        } else {
            echo json_encode(["status"=>"error","mensaje"=>$stmt->error]);
        }

        break;

    default:
        echo json_encode(["status"=>"error","mensaje"=>"Operación no válida"]);
}
