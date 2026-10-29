<?php

/*
 * Membrete y fuentes compartidos por los informes ECO y de audiometría.
 */

/** Agrega Times New Roman y Calibri de Windows a la configuración de mPDF, si existen. */
function configurarFuentesInforme(array $configuracion): array
{
    $fuentesWindows = 'C:/Windows/Fonts';
    if (!is_file($fuentesWindows . '/times.ttf')) return $configuracion;

    $variables = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $variablesFuentes = (new \Mpdf\Config\FontVariables())->getDefaults();
    $configuracion['fontDir'] = array_merge($variables['fontDir'], [$fuentesWindows]);
    $configuracion['fontdata'] = $variablesFuentes['fontdata'] + [
        'timesnewroman' => [
            'R' => 'times.ttf', 'B' => 'timesbd.ttf',
            'I' => 'timesi.ttf', 'BI' => 'timesbi.ttf',
        ],
    ];
    // Nombres propios ("win…") para que mPDF no los reemplace por sus fuentes internas.
    $opcionales = [
        'arialblack' => ['ariblk.ttf', ['R' => 'ariblk.ttf', 'B' => 'ariblk.ttf']],
        'winarial' => ['arial.ttf', ['R' => 'arial.ttf', 'B' => 'arialbd.ttf']],
        'winarialnarrow' => ['ARIALN.TTF', ['R' => 'ARIALN.TTF', 'B' => 'ARIALNB.TTF']],
        'wincambria' => ['cambria.ttc', ['R' => 'cambria.ttc', 'TTCfontID' => ['R' => 1]]],
    ];
    foreach ($opcionales as $nombre => [$archivo, $definicion]) {
        if (is_file($fuentesWindows . '/' . $archivo)) $configuracion['fontdata'][$nombre] = $definicion;
    }
    if (is_file($fuentesWindows . '/calibri.ttf')) {
        $configuracion['fontdata']['calibri'] = [
            'R' => 'calibri.ttf', 'B' => 'calibrib.ttf',
            'I' => 'calibrii.ttf', 'BI' => 'calibriz.ttf',
        ];
    }
    return $configuracion;
}

/**
 * Encabezado en negro: logo a la izquierda, nombre, sucursales y celular a la derecha.
 * Estilos en línea: mPDF no aplica bien las clases anidadas dentro de celdas.
 */
function membreteClinicaHtml(): string
{
    $logo = htmlspecialchars(
        str_replace('\\', '/', dirname(__DIR__) . '/images/img_logo_negro.png'),
        ENT_QUOTES,
        'UTF-8'
    );
    $linea = 'font-size:10pt;';
    return '<table style="border-bottom:0.6mm solid #000; border-collapse:collapse; margin-bottom:3mm; table-layout:fixed; width:100%;"><tr>'
        . '<td width="22%" style="padding:0 0 1.5mm; vertical-align:middle;"><img src="' . $logo . '" style="width:30mm; height:24mm;"></td>'
        . '<td width="78%" style="color:#000; font-family:calibri, freesans, sans-serif; padding:0 0 2mm; text-align:right; vertical-align:middle;">'
        . '<span style="font-family:timesnewroman, freeserif, serif; font-size:17pt; font-weight:bold;">CLÍNICA MÉDICA FLORENTÍN</span><br>'
        . '<span style="' . $linea . '">Villa Elisa: Calle 7 y Von Polesky</span><br>'
        . '<span style="' . $linea . '">Guajayvi: Barrio Centro Santa Rosa</span><br>'
        . '<span style="' . $linea . '">Celular: +595 987 67 88 88</span>'
        . '</td></tr></table>';
}
