<?php

/*
 * Código de los pacientes para ordenar los papeles impresos.
 *
 * Si el paciente tiene un código cargado a mano se usa ese. Si no, se genera
 * "NNN-ID": NNN es su número de orden dentro del día de alta (por id, desde 001)
 * e ID es su id. Ej.: el 29-10-2026 se cargaron los ids 56 a 65, entonces el id 58
 * es el tercero del día y su código es "003-58".
 *
 * El orden cuenta solo a los pacientes activos: si se desactiva a un paciente,
 * los siguientes del mismo día corren su número (los papeles ya impresos no cambian solos).
 */

/** Expresión SQL con el número de orden del paciente dentro de su día de alta. */
function sqlOrdenDiaPaciente(string $alias = 'p'): string
{
    return "(SELECT COUNT(*) FROM pacientes orden_dia
             WHERE orden_dia.creado_en >= DATE({$alias}.creado_en)
               AND orden_dia.creado_en < DATE({$alias}.creado_en) + INTERVAL 1 DAY
               AND orden_dia.id_paciente <= {$alias}.id_paciente
               AND orden_dia.activo = 1)";
}

/** Expresión SQL con el orden del día en tres dígitos: 001, 002… */
function sqlOrdenDiaPacienteTexto(string $alias = 'p'): string
{
    return 'LPAD(' . sqlOrdenDiaPaciente($alias) . ', 3, \'0\')';
}

/** Expresión SQL con el código a mostrar: el cargado a mano o el generado "NNN-ID". */
function sqlCodigoPaciente(string $alias = 'p'): string
{
    return "COALESCE(NULLIF(TRIM({$alias}.codigo), ''), CONCAT("
        . sqlOrdenDiaPacienteTexto($alias) . ", '-', {$alias}.id_paciente))";
}
