<?php

/** DX que se propone cuando el postulante todavía no tiene uno guardado. */
const DX_AUDIOMETRIA_PREDETERMINADO = 'AUDICIÓN NORMAL PARA AMBOS OÍDOS';

/** Quita espacios sobrantes y unifica los saltos de línea del DX. */
function normalizarDxAudiometria(string $dx): string
{
    $dx = str_replace(["\r\n", "\r"], "\n", $dx);
    $lineas = array_map(static fn(string $linea): string => trim(preg_replace('/[ \t]+/', ' ', $linea)), explode("\n", $dx));
    return trim(implode("\n", $lineas));
}
