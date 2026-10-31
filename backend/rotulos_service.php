<?php

/**
 * Catálogo central de formatos de rótulo.
 *
 * La pantalla usa código y nombre para crear los botones. El reporte usa los
 * campos y el tamaño de letra para construir el PDF. Para agregar otro formato
 * alcanza con incorporarlo aquí, sin duplicar el flujo de selección e impresión.
 *
 * @return array<string, array{codigo:string,nombre:string,campos:array<int,string>,tamano_letra:int}>
 */
function formatosRotulos(): array
{
    return [
        'acadepol' => [
            'codigo' => 'acadepol',
            'nombre' => 'Rótulo ACADEPOL',
            'campos' => ['nombre', 'cedula'],
            'tamano_letra' => 25,
        ],
        'licemil' => [
            'codigo' => 'licemil',
            'nombre' => 'Rótulo LICEMIL',
            'campos' => ['codigo', 'nombre', 'cedula'],
            'tamano_letra' => 25,
        ],
    ];
}

/** @return array{codigo:string,nombre:string,campos:array<int,string>,tamano_letra:int}|null */
function obtenerFormatoRotulo(string $codigo): ?array
{
    $formatos = formatosRotulos();
    return $formatos[strtolower(trim($codigo))] ?? null;
}

/** @return array<int, array{codigo:string,nombre:string}> */
function catalogoPublicoFormatosRotulos(): array
{
    return array_values(array_map(
        static fn(array $formato): array => [
            'codigo' => $formato['codigo'],
            'nombre' => $formato['nombre'],
        ],
        formatosRotulos()
    ));
}
