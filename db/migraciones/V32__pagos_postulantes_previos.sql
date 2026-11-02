-- Los postulantes que vinieron antes de implementar convenios (29/10 y 31/10/2026)
-- ya habían pagado todo en efectivo. Se registra un único pago en EFECTIVO por el
-- total de su tarifa (hombre o mujer), con la fecha de su registro y saldo 0.
-- Solo toma registros del convenio GENERAL JUAN CARDENAS - ACADEPOL 2026 anteriores
-- al 01/11/2026 que todavía no tienen ningún pago.
INSERT INTO postulantes_pago
    (id_arreador_postulante, id_metodo_pago, fecha, monto, saldo, observacion, registrado_por)
SELECT ap.id_arreador_postulante,
       mp.id_metodo_pago,
       ap.derivado_en,
       CASE WHEN p.es_femenino = 1 THEN ae.monto_total_mujer ELSE ae.monto_total_hombre END,
       0,
       'Pago en efectivo registrado al migrar postulantes previos a los convenios',
       NULL
FROM postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
INNER JOIN postulantes_arreador_escuela ae
        ON ae.id_arreador_escuela = ap.id_arreador_escuela AND ae.anio = 2026
INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela AND e.nombre = 'ACADEPOL'
INNER JOIN postulantes_arreadores a
        ON a.id_arreador = ae.id_arreador AND a.nombre = 'GENERAL JUAN CARDENAS'
INNER JOIN metodos_pago mp ON mp.codigo = 'EFECTIVO'
WHERE ap.derivado_en < '2026-11-01'
  AND NOT EXISTS (
      SELECT 1 FROM postulantes_pago pg
      WHERE pg.id_arreador_postulante = ap.id_arreador_postulante
  );
