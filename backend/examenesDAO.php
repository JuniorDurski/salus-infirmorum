<?php

function obtenerOGuardarResultado($conn, $id_postulante, $examenConcat, $resultadoCalculado) {

    // 1. Verificar si ya existe
    $sqlCheck = "SELECT resultado 
                 FROM examen 
                 WHERE id_postulante = ? AND examen = ?
                 LIMIT 1";

    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("is", $id_postulante, $examenConcat);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // YA EXISTE → devolver resultado guardado
        return $row['resultado'];
    }

    // 2. No existe → insertar
    $sqlInsert = "INSERT INTO examen (id_postulante, examen, resultado)
                  VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("iss", $id_postulante, $examenConcat, $resultadoCalculado);
    $stmt->execute();

    return $resultadoCalculado;
}


?>