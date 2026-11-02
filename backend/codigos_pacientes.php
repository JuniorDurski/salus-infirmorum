<?php

/*
 * Id, orden y código de los pacientes.
 *
 * - id: el id de la tabla pacientes.
 * - orden: columna postulantes_arreador_postulante.orden. Cada registro en
 *   postulantes se numera dentro de su convenio y día de registro, desde el
 *   orden_inicio del convenio. Se recalcula con recalcularOrdenPacientes() cada
 *   vez que algo lo afecta (nuevo registro, activación o desactivación del
 *   paciente, cambio del orden_inicio). Un paciente que nunca ingresó a
 *   postulantes no tiene orden guardado: se usa su número dentro de su día de alta.
 * - código: solo se guarda si se carga a mano, en postulantes_arreador_postulante.codigo
 *   (al lado del orden, en el último registro del paciente). Para mostrar e imprimir se usa ese
 *   o, si no hay, el generado "NNN-ID" (orden en tres dígitos e id). Ej.: "003-58".
 */

/** Expresión SQL con la fecha y hora de registro del paciente. */
function sqlMomentoRegistroPaciente(string $alias = 'p'): string
{
    return "COALESCE((SELECT MAX(ap_reg.derivado_en)
                        FROM postulantes_arreador_postulante ap_reg
                       WHERE ap_reg.id_paciente = {$alias}.id_paciente AND ap_reg.activo = 1), {$alias}.creado_en)";
}

/**
 * Expresión SQL con la escuela del postulante. No se guarda en pacientes: sale de
 * su último registro activo en postulantes y del convenio de ese registro.
 *   pacientes -> postulantes_arreador_postulante -> postulantes_arreador_escuela -> escuelas
 */
function sqlIdEscuelaPaciente(string $alias = 'p'): string
{
    return "(SELECT ae_esc.id_escuela
               FROM postulantes_arreador_postulante ap_esc
              INNER JOIN postulantes_arreador_escuela ae_esc
                      ON ae_esc.id_arreador_escuela = ap_esc.id_arreador_escuela
              WHERE ap_esc.id_paciente = {$alias}.id_paciente AND ap_esc.activo = 1
              ORDER BY ap_esc.derivado_en DESC, ap_esc.id_arreador_postulante DESC
              LIMIT 1)";
}

/** Expresión SQL con el día de registro del paciente (YYYY-MM-DD). */
function sqlFechaRegistroPaciente(string $alias = 'p'): string
{
    return 'DATE(' . sqlMomentoRegistroPaciente($alias) . ')';
}

/** Expresión SQL con el orden del paciente: el de su último registro en postulantes. */
function sqlOrdenDiaPaciente(string $alias = 'p'): string
{
    return "COALESCE(
        (SELECT ap_ord.orden
           FROM postulantes_arreador_postulante ap_ord
          WHERE ap_ord.id_paciente = {$alias}.id_paciente AND ap_ord.activo = 1
          ORDER BY ap_ord.derivado_en DESC, ap_ord.id_arreador_postulante DESC
          LIMIT 1),
        (SELECT COUNT(*) FROM pacientes p_dia
          WHERE p_dia.creado_en >= DATE({$alias}.creado_en)
            AND p_dia.creado_en < DATE({$alias}.creado_en) + INTERVAL 1 DAY
            AND p_dia.id_paciente <= {$alias}.id_paciente
            AND p_dia.activo = 1))";
}

/** Expresión SQL con el orden en tres dígitos: 001, 002… */
function sqlOrdenDiaPacienteTexto(string $alias = 'p'): string
{
    return 'LPAD(' . sqlOrdenDiaPaciente($alias) . ', 3, \'0\')';
}

/**
 * Expresión SQL con el código cargado a mano (o NULL): el de su último registro
 * en postulantes (postulantes_arreador_postulante.codigo, al lado del orden).
 */
function sqlCodigoManualPaciente(string $alias = 'p'): string
{
    return "(SELECT NULLIF(TRIM(ap_cod.codigo), '')
               FROM postulantes_arreador_postulante ap_cod
              WHERE ap_cod.id_paciente = {$alias}.id_paciente AND ap_cod.activo = 1
              ORDER BY ap_cod.derivado_en DESC, ap_cod.id_arreador_postulante DESC
              LIMIT 1)";
}

/**
 * Condición SQL: el paciente se muestra en los módulos de postulantes. Se oculta
 * cuando todos sus registros en postulantes están desactivados; un paciente sin
 * ningún registro todavía se muestra (está en proceso de registrarse).
 */
function sqlVisibleEnPostulantes(string $alias = 'p'): string
{
    return "(EXISTS (SELECT 1 FROM postulantes_arreador_postulante ap_vis
                      WHERE ap_vis.id_paciente = {$alias}.id_paciente AND ap_vis.activo = 1)
             OR NOT EXISTS (SELECT 1 FROM postulantes_arreador_postulante ap_vis
                             WHERE ap_vis.id_paciente = {$alias}.id_paciente))";
}

/** Expresión SQL con el código a mostrar: el cargado a mano o el generado "NNN-ID". */
function sqlCodigoPaciente(string $alias = 'p'): string
{
    return 'COALESCE(' . sqlCodigoManualPaciente($alias) . ', CONCAT('
        . sqlOrdenDiaPacienteTexto($alias) . ", '-', {$alias}.id_paciente))";
}

/**
 * Expresión SQL con el código que se imprime en los informes de postulantes:
 * solo el orden ("036") si no tiene código cargado, u "orden-código" ("036-A12")
 * si lo tiene.
 */
function sqlCodigoInforme(string $alias = 'p'): string
{
    return 'COALESCE(CONCAT(' . sqlOrdenDiaPacienteTexto($alias) . ", '-', "
        . sqlCodigoManualPaciente($alias) . '), ' . sqlOrdenDiaPacienteTexto($alias) . ')';
}

/**
 * Recalcula postulantes_arreador_postulante.orden y actualiza solo los registros
 * que cambiaron. Se numera por convenio y día desde el orden_inicio del convenio;
 * los registros desactivados (o de pacientes inactivos) quedan sin orden y los
 * demás de su convenio y día se renumeran sin huecos.
 */
function recalcularOrdenPacientes(mysqli $db): void
{
    $filas = $db->query(
        'SELECT ap.id_arreador_postulante, ap.orden AS actual,
                IF(p.activo = 1 AND ap.activo = 1,
                   ae.orden_inicio - 1 + ROW_NUMBER() OVER (
                       PARTITION BY (p.activo = 1 AND ap.activo = 1), ap.id_arreador_escuela, DATE(ap.derivado_en)
                       ORDER BY ap.derivado_en, ap.id_arreador_postulante
                   ),
                   NULL) AS nuevo
         FROM postulantes_arreador_postulante ap
         INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
         INNER JOIN postulantes_arreador_escuela ae
                 ON ae.id_arreador_escuela = ap.id_arreador_escuela'
    )->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare('UPDATE postulantes_arreador_postulante SET orden = ? WHERE id_arreador_postulante = ?');
    foreach ($filas as $fila) {
        if ((string) $fila['actual'] === (string) $fila['nuevo']) {
            continue;
        }
        $orden = $fila['nuevo'] === null ? null : (int) $fila['nuevo'];
        $id = (int) $fila['id_arreador_postulante'];
        $stmt->bind_param('ii', $orden, $id);
        $stmt->execute();
    }
}
