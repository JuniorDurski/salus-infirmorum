<?php

require_once __DIR__ . '/calculos/ServicioResultados.php';

/**
 * Adaptador para los informes existentes. Internamente el resultado deja de
 * guardarse en filas texto y pasa a un documento JSON por estudio/paciente.
 */
function obtenerOGuardarResultado(
    mysqli $conn,
    int $id_postulante,
    string $examenConcat,
    $resultadoCalculado
) {
    return ServicioResultados::obtenerOGuardar(
        $conn,
        $id_postulante,
        $examenConcat,
        $resultadoCalculado
    );
}
