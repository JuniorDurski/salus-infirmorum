<?php

/**
 * Iconos minimalistas de línea (SVG monocromo, toman el color del texto).
 * El campo menus.icono guarda una de estas claves. Cualquier otro texto
 * (por ejemplo un emoji cargado a mano) se muestra tal cual.
 */
function catalogoIconos(): array
{
    return [
        'inicio' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'paciente' => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/><path d="M12 14.5v4M10 16.5h4"/>',
        'estetoscopio' => '<path d="M6 3v6a4 4 0 0 0 8 0V3"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="11" r="2"/>',
        'postulantes' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16a3 3 0 0 1 6 0"/><path d="M15 10h3M15 14h3"/>',
        'carpeta' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'escuela' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4h6v4"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2"/>',
        'documento' => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'convenio' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'lista' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'laboratorio' => '<path d="M9 3h6"/><path d="M10 3v6l-5.5 9.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>',
        'rayos' => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M12 7v10M9 9.5h6M9 12h6M9.5 14.5h5"/>',
        'onda' => '<path d="M3 12h3l2.5-6 4 12 3-9 2 3H21"/>',
        'audio' => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>',
        'etiqueta' => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
        'portapapeles' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 10h6M9 14h6M9 18h3"/>',
        'libro' => '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v18H6.5A2.5 2.5 0 0 1 4 18.5"/><path d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20"/>',
        'graduacion' => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/>',
        'titulo' => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
        'escudo' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.3 7.5 9.5 4.3-1.2 7.5-4.9 7.5-9.5V6z"/>',
        'usuarios' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14a6 6 0 0 1 3.5 6"/>',
        'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'credencial' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M9 6V4h6v2"/><circle cx="9" cy="12" r="1.8"/><path d="M6.5 16a2.5 2.5 0 0 1 5 0"/><path d="M14 11h4M14 14h4"/>',
        'llave' => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2"/><path d="m17 6 3 3"/><path d="m14.5 8.5 2 2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'grafico' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
        'dinero' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
    ];
}

/** Devuelve el SVG de una clave, o null si la clave no existe. */
function iconoSvg(?string $clave, string $clase = 'icon'): ?string
{
    $trazos = catalogoIconos()[trim((string) $clave)] ?? null;
    if ($trazos === null) return null;
    return '<svg class="' . htmlspecialchars($clase, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $trazos . '</svg>';
}

/** HTML del icono de un menú: SVG si es una clave, texto escapado si no, o un punto. */
function iconoMenuHtml(?string $icono): string
{
    $svg = iconoSvg($icono);
    if ($svg !== null) return $svg;
    $texto = trim((string) $icono);
    return $texto === '' ? '<span class="nav-dot"></span>' : htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
