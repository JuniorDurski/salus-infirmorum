<?php
    // Normalizar a array
    $listaIds = [];

    if ($id !== null) {
        $listaIds[] = (int)$id;
    }

    if (!empty($ids) && is_array($ids)) {
        foreach ($ids as $i) {
            $listaIds[] = (int)$i;
        }
    }

    $placeholders = implode(',', array_fill(0, count($listaIds), '?'));

    $sql = "
        SELECT 
            p.id_postulante,
            p.nombre,
            p.ci,
            p.edad,
            p.sexo,
            p.grupo_sanguineo    AS tipi,
            DATE_FORMAT(p.fecha, '%d/%m/%Y') AS fecha,
            CONCAT(codigo, ' - ',d.designacion) AS codigo
        FROM postulantes p
        INNER JOIN designaciones d ON d.id_designacion = p.id_designacion
        WHERE p.id_postulante IN ($placeholders)
        ORDER BY p.id_postulante
    ";

    $stmt = $conexion->prepare($sql);

    /* bind dinámico */
    $types = str_repeat('i', count($listaIds));
    $stmt->bind_param($types, ...$listaIds);

    $stmt->execute();
    $rs = $stmt->get_result();

    $pacientes = [];

    while ($row = $rs->fetch_assoc()) {
        $pacientes[] = [
            "id"     => $row["codigo"],
            "id_postulante"     => $row["id_postulante"],
            "nombre" => $row["nombre"],
            "ci"     => $row["ci"],
            "tipi"   => $row["tipi"] ? $row["tipi"] : "",
            "edad"   => $row["edad"],
            "sexo"   => $row["sexo"],
            "fecha"  => $row["fecha"]
        ];
    }

    if (empty($pacientes)) {
        die('No se encontraron pacientes');
    }

?>