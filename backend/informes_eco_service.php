<?php

function plantillasInformeEco(): array
{
    static $plantillas;
    if ($plantillas !== null) return $plantillas;

    $plantillas = [
        'ABDOMINAL_URGENCIA' => [
            'nombre' => 'Abdominal (urgencia)',
            'titulo' => 'INFORME DE ECOGRAFIA ABDOMINAL (URGENCIA)',
            'color' => '#000000',
            'formato' => 'legal',
            'sexo' => null,
            'conclusion_recuadro' => true,
            'conclusion_lista' => false,
            'mostrar_firma' => true,
            'espacio_foto' => true,
            'motivo' => 'CONTROL',
            'introduccion' => "ESTIMADO (A) DOCTOR (A):\nHemos efectuado el estudio ultrasonográfico de acuerdo a sus indicaciones:\nSe observa:",
            'campos' => [
                ['clave' => 'higado', 'etiqueta' => 'HIGADO', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'ecoestructura homogénea, refringencia normal, lóbulo derecho normal, lóbulo izquierdo: normal, HILIO HEPATICO: normal, CONDUCTOS BILIARES INTRA HEPATICOS: normales, VENAS SUPRAS HEPATICAS: normales, no se observa dilatación de conductos ni lesiones ocupantes de espacio.'],
                ['clave' => 'vesicula_biliar', 'etiqueta' => 'VESICULA BILIAR', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'contornos nítidos, contenido homogéneo, sin sombras acústicas de litiasis en su interior.'],
                ['clave' => 'pancreas', 'etiqueta' => 'PANCREAS', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'no se observa por asas interpuestas.'],
                ['clave' => 'rinon_derecho', 'etiqueta' => 'RIÑON DERECHO', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'ecoestructura homogénea, RELACION CORTICO MEDULAR: normal, SENO RENAL: normal, PELVIS RENAL: normal, ausencia de sombras acústicas en su interior.'],
                ['clave' => 'rinon_izquierdo', 'etiqueta' => 'RIÑON IZQUIERDO', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'ecoestructura homogénea, RELACION CORTICO MEDULAR: normal, SENO RENAL: normal, PELVIS RENAL: normal, ausencia de sombras acústicas en su interior.'],
                ['clave' => 'bazo', 'etiqueta' => 'BAZO', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'homogéneo normal.'],
                ['clave' => 'asas_intestinales', 'etiqueta' => 'ASAS INTESTINALES', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'normales con peristaltismo conservado.'],
                ['clave' => 'vejiga', 'etiqueta' => 'VEJIGA', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'de contornos nítidos, contenido homogéneo.'],
                ['clave' => 'apendice', 'etiqueta' => 'APENDICE', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'Pared apendicular preservada.'],
            ],
            'conclusion' => 'ÓRGANOS ESTUDIADOS DENTRO DE PARÁMETROS NORMALES.',
        ],
        'MAMARIA' => [
            'nombre' => 'Mamaria',
            'titulo' => 'INFORME DE ECOGRAFIA MAMARIA',
            'color' => '#000000',
            'formato' => 'legal',
            'sexo' => 'FEMENINO',
            'conclusion_recuadro' => false,
            'conclusion_lista' => true,
            'mostrar_firma' => true,
            'espacio_foto' => false,
            'motivo' => 'CONTROL',
            'introduccion' => "ESTIMADO (A) DOCTOR (A):\nHemos efectuado el estudio ultrasonográfico mamario de acuerdo a sus indicaciones:",
            'campos' => [
                ['clave' => 'mama_derecha', 'etiqueta' => 'MAMA DERECHA', 'tipo' => 'seccion', 'subrayado' => true,
                    'valor' => 'Tejido mamario fibro-adiposo, heterogéneo normal, ausencia de lesiones ocupantes de espacio tipo sólido o quístico.'],
                ['clave' => 'retro_areolar_derecha', 'etiqueta' => 'REGION RETRO AREOLAR', 'tipo' => 'linea', 'subrayado' => true, 'valor' => 'NORMAL.'],
                ['clave' => 'axilar_derecha', 'etiqueta' => 'REGION AXILAR', 'tipo' => 'linea', 'subrayado' => true, 'valor' => 'normal.'],
                ['clave' => 'mama_izquierda', 'etiqueta' => 'MAMA IZQUIERDA', 'tipo' => 'seccion', 'subrayado' => true,
                    'valor' => 'Tejido mamario fibro-adiposo, heterogéneo normal, ausencia de lesiones ocupantes de espacio de tipo sólido o quístico.'],
                ['clave' => 'retro_areolar_izquierda', 'etiqueta' => 'REGION RETRO AREOLAR', 'tipo' => 'linea', 'subrayado' => true, 'valor' => 'normal.'],
                ['clave' => 'axilar_izquierda', 'etiqueta' => 'REGION AXILAR', 'tipo' => 'linea', 'subrayado' => true, 'valor' => 'normal.'],
            ],
            'conclusion' => "AMBAS MAMAS CON TEJIDO FIBRO ADIPOSO NORMAL.\nBIRADS I",
        ],
        'TESTICULAR' => [
            'nombre' => 'Testicular',
            'titulo' => 'ECOGRAFÍA TESTICULAR',
            'color' => '#000000',
            'formato' => 'legal',
            'sexo' => 'MASCULINO',
            'conclusion_recuadro' => false,
            'conclusion_lista' => false,
            'mostrar_firma' => true,
            'espacio_foto' => false,
            'motivo' => 'CONTROL',
            'introduccion' => "ESTIMADO (A) DOCTOR (A):\nHemos efectuado el estudio ultrasonográfico de acuerdo a sus indicaciones, se observa:",
            'campos' => [
                ['clave' => 'ubicacion', 'etiqueta' => 'Ubicación de testículos', 'tipo' => 'parrafo', 'subrayado' => false,
                    'valor' => 'Ambos testículos ubicados en bolsa.'],
                ['clave' => 'testiculo_derecho', 'etiqueta' => 'TESTICULO DERECHO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'de bordes regulares, parénquima homogéneo.'],
                ['clave' => 'testiculo_izquierdo', 'etiqueta' => 'TESTICULO IZQUIERDO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'de bordes regulares, parénquima homogéneo.'],
                ['clave' => 'epididimo_derecho', 'etiqueta' => 'EPIDÍDIMO DERECHO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'tamaño y eco estructura conservada.'],
                ['clave' => 'epididimo_izquierdo', 'etiqueta' => 'EPIDÍDIMO IZQUIERDO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'tamaño y ecoestructura conservada.'],
                ['clave' => 'tunica_derecha', 'etiqueta' => 'TÚNICA DERECHA', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'no se observa imagen de colección líquida.'],
                ['clave' => 'tunica_izquierda', 'etiqueta' => 'TÚNICA IZQUIERDA', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'no se observa imagen de colección líquida, NO HIDROCELE.'],
                ['clave' => 'cordon_espermatico', 'etiqueta' => 'Cordón espermático', 'tipo' => 'parrafo', 'subrayado' => false,
                    'valor' => 'Cordón espermático sin evidencia de dilatación varicosa. NO VARICOCELE.'],
                ['clave' => 'circulacion_doppler', 'etiqueta' => 'Circulación Doppler', 'tipo' => 'parrafo', 'subrayado' => false,
                    'valor' => 'CIRCULACION DOPPLER SIN PATOLOGIA.'],
                ['clave' => 'hernias', 'etiqueta' => 'Hernias inguinales', 'tipo' => 'parrafo', 'subrayado' => false,
                    'valor' => 'NO SE CONSTATA HERNIAS INGUINALES DERECHA NI IZQUIERDA.'],
            ],
            'conclusion' => '',
        ],
        'GINECOLOGICA' => [
            'nombre' => 'Ginecológica',
            'titulo' => 'INFORME DE ECOGRAFIA GINECOLOGICA',
            'color' => '#000000',
            'formato' => 'legal',
            'sexo' => 'FEMENINO',
            'conclusion_recuadro' => true,
            'conclusion_lista' => false,
            'mostrar_firma' => true,
            'espacio_foto' => false,
            'motivo' => 'CONTROL',
            'introduccion' => 'Hemos efectuado el estudio ultrasonografía de acuerdo a sus indicaciones, se observa:',
            'campos' => [
                ['clave' => 'utero', 'etiqueta' => 'UTERO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'de bordes regulares, tamaño normal, situado en posición de AVF.'],
                ['clave' => 'cervix', 'etiqueta' => 'CERVIX', 'tipo' => 'linea', 'subrayado' => false, 'valor' => 'normal.'],
                ['clave' => 'endometrio', 'etiqueta' => 'ENDOMETRIO', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'con interfase endometrial lineal.'],
                ['clave' => 'miometrio', 'etiqueta' => 'MIOMETRIO', 'tipo' => 'linea', 'subrayado' => false, 'valor' => 'homogéneo.'],
                ['clave' => 'douglas', 'etiqueta' => 'E.S. DOUGLAS', 'tipo' => 'linea', 'subrayado' => false,
                    'valor' => 'no se observa líquido.'],
                ['clave' => 'vejiga', 'etiqueta' => 'VEJIGA', 'tipo' => 'linea', 'subrayado' => true,
                    'valor' => 'bordes nítidos, contenido homogéneo, pared fina, sedimento no visible.'],
            ],
            'conclusion' => 'UTERO EN AVF DE TAMAÑO NORMAL, CON INTERFASE ENDOMETRIAL NORMAL',
        ],
    ];

    return $plantillas;
}

function plantillaInformeEco(string $codigo): ?array
{
    $codigo = strtoupper(trim($codigo));
    $plantillas = plantillasInformeEco();
    if (!isset($plantillas[$codigo])) return null;
    $plantilla = $plantillas[$codigo];
    $plantilla['codigo'] = $codigo;
    return $plantilla;
}

function resultadoPredeterminadoInformeEco(array $plantilla): array
{
    $campos = [];
    foreach ($plantilla['campos'] as $campo) {
        $campos[$campo['clave']] = $campo['valor'];
    }
    return [
        'motivo' => $plantilla['motivo'],
        'introduccion' => $plantilla['introduccion'],
        'campos' => $campos,
        'conclusion' => $plantilla['conclusion'],
    ];
}

function normalizarResultadoInformeEco(array $plantilla, array $resultado): array
{
    $predeterminado = resultadoPredeterminadoInformeEco($plantilla);
    $normalizado = [
        'motivo' => textoInformeEco($resultado['motivo'] ?? $predeterminado['motivo'], 500),
        'introduccion' => textoInformeEco($resultado['introduccion'] ?? $predeterminado['introduccion'], 5000),
        'campos' => [],
        'conclusion' => textoInformeEco($resultado['conclusion'] ?? $predeterminado['conclusion'], 8000),
    ];
    $recibidos = is_array($resultado['campos'] ?? null) ? $resultado['campos'] : [];
    foreach ($plantilla['campos'] as $campo) {
        $clave = $campo['clave'];
        $normalizado['campos'][$clave] = textoInformeEco(
            $recibidos[$clave] ?? $predeterminado['campos'][$clave],
            8000
        );
    }
    return $normalizado;
}

function textoInformeEco($valor, int $maximo): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", trim((string) $valor));
    return mb_substr($texto, 0, $maximo, 'UTF-8');
}

