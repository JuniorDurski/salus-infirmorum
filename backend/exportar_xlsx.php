<?php

/*
 * Excel real (.xlsx, Office Open XML) generado sin librerías externas.
 * Lo usa backend/pacientesExportar.php. Arma: imagen de cabecera, título y datos
 * en celdas combinadas, la lista con bordes y el total.
 */

function imagenCabeceraExcel(): string
{
    $ruta = __DIR__ . '/../images/img_cabecera_excel.png';
    $datos = is_file($ruta) ? file_get_contents($ruta) : false;
    return $datos === false ? '' : $datos;
}

/** Letra de columna de Excel para un índice desde 0: 0 → A, 26 → AA. */
function columnaExcel(int $indice): string
{
    $letra = '';
    for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
        $letra = chr(65 + (($n - 1) % 26)) . $letra;
    }
    return $letra;
}

function xmlTexto($valor): string
{
    // Quita caracteres de control que invalidan el XML.
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $valor);
    return htmlspecialchars((string) $texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function celdaTexto(string $ref, $valor, int $estilo): string
{
    return '<c r="' . $ref . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">'
        . xmlTexto($valor) . '</t></is></c>';
}

function celdaNumero(string $ref, $valor, int $estilo): string
{
    return '<c r="' . $ref . '" s="' . $estilo . '"><v>' . (0 + $valor) . '</v></c>';
}

function celdaVacia(string $ref, int $estilo): string
{
    return '<c r="' . $ref . '" s="' . $estilo . '"/>';
}

/** Celda con "Etiqueta:" en negrita y el valor normal. */
function celdaEtiqueta(string $ref, string $etiqueta, string $valor, int $estilo): string
{
    return '<c r="' . $ref . '" s="' . $estilo . '" t="inlineStr"><is>'
        . '<r><rPr><b/><sz val="10"/><rFont val="Arial"/></rPr><t xml:space="preserve">' . xmlTexto($etiqueta) . ': </t></r>'
        . '<r><rPr><sz val="10"/><rFont val="Arial"/></rPr><t xml:space="preserve">' . xmlTexto($valor) . '</t></r>'
        . '</is></c>';
}

function relacionesXml(string $contenido): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $contenido . '</Relationships>';
}

function estilosXlsx(): string
{
    $borde = '<color rgb="FF9AA9A5"/>';
    $xf = static fn(string $atributos, string $alineacion = ''): string =>
        '<xf ' . $atributos . ' xfId="0"' . ($alineacion !== '' ? ' applyAlignment="1">' . $alineacion . '</xf>' : '/>');

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
        . '<fonts count="3">'
        . '<font><sz val="10"/><name val="Arial"/></font>'
        . '<font><b/><sz val="10"/><name val="Arial"/></font>'
        . '<font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
        . '</fonts>'
        . '<fills count="6">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF176B5B"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF4F2"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFDFECE8"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF6F9F8"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2"><border/><border>'
        . '<left style="thin">' . $borde . '</left><right style="thin">' . $borde . '</right>'
        . '<top style="thin">' . $borde . '</top><bottom style="thin">' . $borde . '</bottom>'
        . '</border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="13">'
        // 0 normal
        . $xf('numFmtId="0" fontId="0" fillId="0" borderId="0"')
        // 1 título
        . $xf('numFmtId="0" fontId="2" fillId="2" borderId="0" applyFont="1" applyFill="1"', '<alignment horizontal="center" vertical="center"/>')
        // 2 info izquierda, 3 info derecha
        . $xf('numFmtId="0" fontId="0" fillId="3" borderId="0" applyFill="1"', '<alignment vertical="center"/>')
        . $xf('numFmtId="0" fontId="0" fillId="3" borderId="0" applyFill="1"', '<alignment horizontal="right" vertical="center"/>')
        // 4 encabezado de columna
        . $xf('numFmtId="0" fontId="1" fillId="4" borderId="1" applyFont="1" applyFill="1" applyBorder="1"', '<alignment horizontal="center" vertical="center"/>')
        // 5 texto, 6 importe
        . $xf('numFmtId="49" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1"')
        . $xf('numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1"')
        // 7 total texto, 8 total importe
        . $xf('numFmtId="0" fontId="1" fillId="4" borderId="1" applyFont="1" applyFill="1" applyBorder="1"', '<alignment horizontal="right"/>')
        . $xf('numFmtId="164" fontId="1" fillId="4" borderId="1" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"')
        // 9 entero
        . $xf('numFmtId="1" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1"')
        // 10-12: texto, importe y entero en filas alternas
        . $xf('numFmtId="49" fontId="0" fillId="5" borderId="1" applyNumberFormat="1" applyFill="1" applyBorder="1"')
        . $xf('numFmtId="164" fontId="0" fillId="5" borderId="1" applyNumberFormat="1" applyFill="1" applyBorder="1"')
        . $xf('numFmtId="1" fontId="0" fillId="5" borderId="1" applyNumberFormat="1" applyFill="1" applyBorder="1"')
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function exportarExcel(
    array $filas,
    string $base,
    string $titulo,
    ?string $columnaTotal,
    ?int $total,
    string $generado,
    array $datosCabecera,
    string $filtro
): void
{
    $anchos = [
        'N°' => 5, 'ID' => 6, 'Orden' => 7, 'Código' => 10, 'Nombre' => 44, 'Documento' => 12,
        'Fecha nac.' => 12, 'Edad' => 6, 'Sexo' => 12, 'Escuela' => 16, 'Rh' => 6,
        'Teléfono' => 15, 'Fecha registro' => 14, 'Fecha alta' => 14,
    ];
    $numericas = ['N°', 'ID', 'Edad'];
    $columnas = $filas === [] ? ['Nombre'] : array_keys($filas[0]);
    $n = max(2, count($columnas));
    $ultima = columnaExcel($n - 1);
    $mitad = (int) ceil($n / 2);

    // Índices de estilos (ver estilosXlsx()).
    $sInfoIzq = 2; $sInfoDer = 3; $sCabecera = 4;
    $sTexto = 5; $sImporte = 6; $sTotalTexto = 7; $sTotalImporte = 8; $sEntero = 9;
    $sTextoAlt = 10; $sImporteAlt = 11; $sEnteroAlt = 12;

    $logo = imagenCabeceraExcel();
    $filasXml = [];
    $combinadas = [];

    // Fila 1: espacio para la imagen de cabecera.
    $filasXml[] = $logo !== ''
        ? '<row r="1" ht="56" customHeight="1"/>'
        : '<row r="1">' . celdaTexto('A1', 'CLÍNICA MÉDICA FLORENTÍN', 0) . '</row>';

    // Filas siguientes: informe / escuela a la izquierda, generación a la derecha.
    $izquierdas = [['Informe', $titulo]];
    foreach ($datosCabecera as $etiqueta => $valor) {
        $izquierdas[] = [(string) $etiqueta, (string) $valor];
    }
    $derechas = [
        ['Generado', $generado],
        ['Registros', count($filas) . ($filtro !== '' ? ' · ' . $filtro : '')],
    ];
    $fila = 2;
    $cantidadInfo = max(count($izquierdas), count($derechas));
    for ($i = 0; $i < $cantidadInfo; $i++, $fila++) {
        $xml = '<row r="' . $fila . '" ht="18" customHeight="1">';
        for ($c = 0; $c < $n; $c++) {
            $ref = columnaExcel($c) . $fila;
            if ($c === 0 && isset($izquierdas[$i])) {
                $xml .= celdaEtiqueta($ref, $izquierdas[$i][0], $izquierdas[$i][1], $sInfoIzq);
            } elseif ($c === $mitad && isset($derechas[$i])) {
                $xml .= celdaEtiqueta($ref, $derechas[$i][0], $derechas[$i][1], $sInfoDer);
            } else {
                $xml .= celdaVacia($ref, $c < $mitad ? $sInfoIzq : $sInfoDer);
            }
        }
        $filasXml[] = $xml . '</row>';
        $combinadas[] = 'A' . $fila . ':' . columnaExcel($mitad - 1) . $fila;
        $combinadas[] = columnaExcel($mitad) . $fila . ':' . $ultima . $fila;
    }
    $fila++; // fila en blanco

    // Encabezados de columna.
    $filaCabecera = $fila;
    $xml = '<row r="' . $fila . '" ht="20" customHeight="1">';
    foreach ($columnas as $c => $columna) {
        $xml .= celdaTexto(columnaExcel($c) . $fila, $columna, $sCabecera);
    }
    $filasXml[] = $xml . '</row>';
    $fila++;

    if ($filas === []) {
        $filasXml[] = '<row r="' . $fila . '">' . celdaTexto('A' . $fila, 'Sin registros.', $sTexto) . '</row>';
        $fila++;
    }
    foreach ($filas as $indice => $datos) {
        $alterna = $indice % 2 === 1;
        $xml = '<row r="' . $fila . '">';
        $c = 0;
        foreach ($datos as $columna => $valor) {
            $ref = columnaExcel($c++) . $fila;
            if ($columna === $columnaTotal) {
                $xml .= celdaNumero($ref, (int) str_replace('.', '', (string) $valor), $alterna ? $sImporteAlt : $sImporte);
            } elseif (in_array($columna, $numericas, true) && is_numeric($valor)) {
                $xml .= celdaNumero($ref, $valor, $alterna ? $sEnteroAlt : $sEntero);
            } else {
                $xml .= celdaTexto($ref, $valor, $alterna ? $sTextoAlt : $sTexto);
            }
        }
        $filasXml[] = $xml . '</row>';
        $fila++;
    }
    if ($filas !== [] && $columnaTotal !== null && $total !== null) {
        $xml = '<row r="' . $fila . '" ht="20" customHeight="1">' . celdaTexto('A' . $fila, 'TOTAL', $sTotalTexto);
        for ($c = 1; $c < $n - 1; $c++) {
            $xml .= celdaVacia(columnaExcel($c) . $fila, $sTotalTexto);
        }
        $xml .= celdaNumero($ultima . $fila, $total, $sTotalImporte);
        $filasXml[] = $xml . '</row>';
        $combinadas[] = 'A' . $fila . ':' . columnaExcel($n - 2) . $fila;
    }

    $cols = '';
    foreach ($columnas as $c => $columna) {
        $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . ($anchos[$columna] ?? 20) . '" customWidth="1"/>';
    }
    $merge = '<mergeCells count="' . count($combinadas) . '">'
        . implode('', array_map(static fn(string $r): string => '<mergeCell ref="' . $r . '"/>', $combinadas))
        . '</mergeCells>';

    $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
        . '<sheetViews><sheetView workbookViewId="0" showGridLines="0">'
        . '<pane ySplit="' . $filaCabecera . '" topLeftCell="A' . ($filaCabecera + 1) . '" activePane="bottomLeft" state="frozen"/>'
        . '</sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/>'
        . '<cols>' . $cols . '</cols>'
        . '<sheetData>' . implode('', $filasXml) . '</sheetData>'
        . $merge
        . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
        . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
        . ($logo !== '' ? '<drawing r:id="rId1"/>' : '')
        . '</worksheet>';

    // La imagen original mide 3000x638 px; se muestra a 330x70 px (1 px = 9525 EMU).
    $cx = 330 * 9525;
    $cy = 70 * 9525;
    $dibujo = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<xdr:oneCellAnchor>'
        . '<xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
        . '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>'
        . '<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Cabecera"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
        . '<xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
        . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
        . '</xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';

    $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $tipos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Default Extension="png" ContentType="image/png"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . ($logo !== '' ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
        . '</Types>';
    $libro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="' . $rel . '">'
        . '<sheets><sheet name="Postulantes" sheetId="1" r:id="rId1"/></sheets>'
        // Repite los encabezados de columna en cada hoja impresa.
        . '<definedNames><definedName name="_xlnm.Print_Titles" localSheetId="0">Postulantes!$' . $filaCabecera . ':$' . $filaCabecera . '</definedName></definedNames>'
        . '</workbook>';

    $archivo = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($archivo === false || $zip->open($archivo, ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        exit('No fue posible generar el Excel.');
    }
    $zip->addFromString('[Content_Types].xml', $tipos);
    $zip->addFromString('_rels/.rels', relacionesXml('<Relationship Id="rId1" Type="' . $rel . '/officeDocument" Target="xl/workbook.xml"/>'));
    $zip->addFromString('xl/workbook.xml', $libro);
    $zip->addFromString('xl/_rels/workbook.xml.rels', relacionesXml(
        '<Relationship Id="rId1" Type="' . $rel . '/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="' . $rel . '/styles" Target="styles.xml"/>'
    ));
    $zip->addFromString('xl/styles.xml', estilosXlsx());
    $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
    if ($logo !== '') {
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', relacionesXml('<Relationship Id="rId1" Type="' . $rel . '/drawing" Target="../drawings/drawing1.xml"/>'));
        $zip->addFromString('xl/drawings/drawing1.xml', $dibujo);
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', relacionesXml('<Relationship Id="rId1" Type="' . $rel . '/image" Target="../media/image1.png"/>'));
        $zip->addFromString('xl/media/image1.png', $logo);
    }
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
    header('Content-Length: ' . filesize($archivo));
    readfile($archivo);
    unlink($archivo);
    exit;
}
