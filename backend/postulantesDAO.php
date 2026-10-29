<?php
header('Content-Type: application/json; charset=utf-8');
require_once "conexion.php";

$q = $_GET['q'] ?? '';

switch ($q) {

/* ================= LISTAR ================= */
case 'listar':

    $sql = "SELECT p.*,
            p.grupo_sanguineo,
            DATE_FORMAT(p.fecha, 'Dia:%d-%m-%Y Hora:%H:%i') AS fecha, 
            CONCAT(d.designacion , ' <br> ', d.periodo) AS designacion
            FROM postulantes p
            INNER JOIN designaciones d ON d.id_designacion = p.id_designacion
            ORDER BY p.id_postulante DESC";

    $rs = $conexion->query($sql);
    $data = [];

    while ($row = $rs->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    break;

/* ================= GUARDAR ================= */
case 'guardar':

    $id     = $_POST['id_postulante'] ?? '';
    $codigo = $_POST['codigo'] ?? null;
    $nom    = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $ci     = mb_strtoupper(trim($_POST['ci'] ?? ''), 'UTF-8');
    $grupo  = $_POST['grupo_sanguineo'] ?? '';
    $sexo   = mb_strtoupper(trim($_POST['sexo'] ?? ''), 'UTF-8');
    $edad   = $_POST['edad'] ?? 0;
    $des    = $_POST['id_designacion'] ?? 0;

    if ($grupo === '') {
        $grupo = null;
    }


    if ($id != '') {
        $sql = "UPDATE postulantes
                SET codigo=?, nombre=?, ci=?, grupo_sanguineo=?, edad=?, sexo=?, id_designacion=?
                WHERE id_postulante=?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssissi",
            $codigo, $nom, $ci, $grupo, $edad, $sexo, $des, $id
        );

    } else {
        $sql = "INSERT INTO postulantes
                (codigo, nombre, ci, grupo_sanguineo, edad, sexo, fecha, id_designacion)
                VALUES (?,?,?,?,?,?,NOW(),?)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssisi",
            $codigo, $nom, $ci, $grupo, $edad, $sexo, $des
        );

    }

    echo json_encode($stmt->execute()
        ? ["status"=>"ok"]
        : ["status"=>"error"]);
    break;


/* ================= ELIMINAR ================= */
case 'eliminar':

    $id = $_POST['id'] ?? '';

    if ($id == '') {
        echo json_encode(["status"=>"error","mensaje"=>"ID no recibido"]);
        exit;
    }

    $stmt = $conexion->prepare("DELETE FROM postulantes WHERE id_postulante=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status"=>"ok"]);
    } else {
        echo json_encode(["status"=>"error","mensaje"=>$stmt->error]);
    }

    break;

/* ================= BUSCAR ================= */  
case 'buscar':

    $texto = $_GET['texto'] ?? '';
    $des   = $_GET['designacion'] ?? '';
    $fecha = $_GET['fecha'] ?? '';

    $where = [];
    $params = [];
    $types = "";

    if ($texto != '') {
        $where[] = "(p.nombre LIKE ? OR p.ci LIKE ?)";
        $params[] = "%$texto%";
        $params[] = "%$texto%";
        $types .= "ss";
    }

    if ($des != '') {
        $where[] = "p.id_designacion = ?";
        $params[] = $des;
        $types .= "i";
    }

    if ($fecha != '') {
        $where[] = "DATE(p.fecha) = ?";
        $params[] = $fecha;
        $types .= "s";
    }

    $sql = "SELECT p.id_postulante,
                   p.nombre,
                   p.ci,
                   p.edad,
                   p.sexo,
                   p.codigo,
                   DATE_FORMAT(p.fecha, 'Dia:%d-%m-%Y Hora:%H:%i') AS fecha, 
                   CONCAT(d.designacion , ' <br> ', d.periodo) AS designacion,
                   p.grupo_sanguineo
            FROM postulantes p
            INNER JOIN designaciones d ON d.id_designacion = p.id_designacion";

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY p.id_postulante DESC";

    $stmt = $conexion->prepare($sql);

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $rs = $stmt->get_result();

    $data = [];
    while ($row = $rs->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    break;


default:
    echo json_encode(["status"=>"error","mensaje"=>"Operación no válida"]);
}
