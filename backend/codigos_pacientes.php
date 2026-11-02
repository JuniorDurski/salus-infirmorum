<?php

/*
 * Id, orden y código de los pacientes.
 *
 * - id: el id de la tabla.
 * - orden: columna pacientes.orden. Los postulantes se numeran por convenio y día desde
 *   el orden_inicio del convenio; el resto, desde 1 entre los pacientes activos
 *   de la misma escuela y el mismo día de registro. El día de registro es el de su
 *   ingreso a postulantes (postulantes_arreador_postulante.derivado_en) o, si nunca
 *   ingresó, el de su alta. Se recalcula con recalcularOrdenPacientes() cada vez que
 *   cambia algo que lo afecta (alta, escuela, activación o ingreso a postulantes).
 * - código: solo se guarda si se carga a mano. Para mostrar e imprimir se usa ese
 *   o, si no hay, el generado "NNN-ID" (orden en tres dígitos y id). Ej.: "003-58".
 */

/** Expresión SQL con la fecha y hora de registro del paciente. */
function sqlMomentoRegistroPaciente(string $alias = 'p'): string
{
    return "COALESCE((SELECT MAX(ap_reg.derivado_en)
                        FROM postulantes_arreador_postulante ap_reg
                       WHERE ap_reg.id_paciente = {$alias}.id_paciente), {$alias}.creado_en)";
}

/** Expresión SQL con el día de registro del paciente (YYYY-MM-DD). */
function sqlFechaRegistroPaciente(string $alias = 'p'): string
{
    return 'DATE(' . sqlMomentoRegistroPaciente($alias) . ')';
}

/** Expresión SQL con el número de orden guardado del paciente. */
function sqlOrdenDiaPaciente(string $alias = 'p'): string
{
    return "COALESCE({$alias}.orden, 0)";
}

/** Expresión SQL con el orden en tres dígitos: 001, 002… */
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

/**
 * Recalcula pacientes.orden y actualiza solo las filas que cambiaron.
 * Los postulantes se numeran por convenio y día desde el orden_inicio del
 * convenio de su último registro; el resto, por escuela y día desde 1.
 */
function recalcularOrdenPacientes(mysqli $db): void
{
    $filas = $db->query(
        'SELECT p.id_paciente, p.orden AS actual,
                IF(p.activo = 1,
                   COALESCE(ult.orden_inicio, 1) - 1 + ROW_NUMBER() OVER (
                       PARTITION BY p.activo,
                                    IF(ult.id_arreador_escuela IS NULL,
                                       CONCAT(\'e\', COALESCE(p.id_escuela, 0)),
                                       CONCAT(\'c\', ult.id_arreador_escuela)),
                                    DATE(COALESCE(ult.derivado_en, p.creado_en))
                       ORDER BY COALESCE(ult.derivado_en, p.creado_en), p.id_paciente
                   ),
                   NULL) AS nuevo
         FROM pacientes p
         LEFT JOIN (
             SELECT x.id_paciente, x.derivado_en, x.id_arreador_escuela, ae.orden_inicio
             FROM (
                 SELECT ap.id_paciente, ap.derivado_en, ap.id_arreador_escuela,
                        ROW_NUMBER() OVER (
                            PARTITION BY ap.id_paciente
                            ORDER BY ap.derivado_en DESC, ap.id_arreador_postulante DESC
                        ) AS rn
                 FROM postulantes_arreador_postulante ap
             ) x
             INNER JOIN postulantes_arreador_escuela ae
                     ON ae.id_arreador_escuela = x.id_arreador_escuela
             WHERE x.rn = 1
         ) ult ON ult.id_paciente = p.id_paciente'
    )->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare('UPDATE pacientes SET orden = ? WHERE id_paciente = ?');
    foreach ($filas as $fila) {
        if ((string) $fila['actual'] === (string) $fila['nuevo']) {
            continue;
        }
        $orden = $fila['nuevo'] === null ? null : (int) $fila['nuevo'];
        $id = (int) $fila['id_paciente'];
        $stmt->bind_param('ii', $orden, $id);
        $stmt->execute();
    }
}
