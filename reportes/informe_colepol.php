<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';

include      __DIR__ . '/../backend/calculo_laboratorio.php';
include      'header_footer.php';
include      'css.php';
require_once __DIR__ . '/../backend/examenesDAO.php';

$id  = $_POST['id']  ?? null;
$ids = $_POST['ids'] ?? [];

if ($id === null && empty($ids)) {
    die('No se recibieron pacientes');
}
/* ===========================
   LISTA DE PACIENTES
=========================== */
    include __DIR__ . '/../backend/header_pacientes.php';

/* ===========================
   CONFIGURAR MPDF
=========================== */
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'legal',
    'margin_top'    => 63,
    'margin_bottom' => 68,
    'margin_left'   => 20,
    'margin_right'  => 5
]);

$mpdf->SetHTMLFooter($footer);

// Exámenes que solo se imprimen a pacientes de sexo femenino
$examenesSoloMujer = [
    "BHCG - CUANTITATIVA - SANGRE"
];

foreach ($pacientes as $index => $p) {

    // Salto de página DESPUÉS de estos exámenes:
    // Química + Serología quedan juntas en una página y Citoquímico arranca en otra.
    $esMujer = ($p["sexo"] === "FEMENINO");

    $examenesConSalto = [
        "HEMATOLOGIA",
        "SEROLOGIA"
    ];

    // En mujeres Especiales incluye BHCG (más largo): arranca en página nueva
    if ($esMujer) {
        $examenesConSalto[] = "CITOQUÍMICO URINARIO";
    }

    /* ===========================
       CALCULO DE RESULTADOS
    =========================== */
    include 'asignacion_valor.php';

    $hba1c         = valorHbA1c();
    $colNoHdl      = $col - $hdl;
    $lipidosTot    = round($col * 2.5 + $tri, 1);
    $indiceColHdl  = round($col / $hdl, 1);
    $indiceLdlHdl  = round($ldl / $hdl, 1);

    /* ===========================
       EXÁMENES APLICADOS A CADA PACIENTE
    =========================== */
    $examenes = [
        "HEMATOLOGIA" => [
            "SERIE ROJA" => [
                ["Recuento de Glóbulos Rojos",
                                $rojos, "mill/mm3", "Hombre:(4.5 - 6.0 ) Mujer:( 4.2 - 5.4 )"],
                ["Hemoglobina", $hemoglobina, "g/dL", "Hombre: ( 14 - 18 ) Mujer: ( 13 - 15 )"],
                ["Hematocrito", $hematocrito, "%", "Hombre: ( 42 - 54 ) Mujer: ( 38 - 46 )"],
                ["VCM",         $vcm, "fL", "(79 - 93.3)"],
                ["HCM",         $hcm, "pg", "(26.7 - 31.9)"],
                ["CHCM",        $chcm, "g/dL", "(30,0 - 35,0)"]
            ],
            "SERIE BLANCA" => [
                ["Recuento de Glóbulos Blancos", $blanco, "/uL", "( 4.000 - 10.000 )"],
                ["Linfoblasto",     "0", "", ""],
                ["Mieloblasto",     "0", "", ""],
                ["Promielocito",    "0", "", ""],
                ["Mielocitos",      "0", "", "K/uL"],
                ["Metamielocitos",  "0", "", "K/uL"],
                ["Cayados",         "0", "", "K/uL"],
                ["Segmentados",     $seg, "%", "( 50 - 70)"],
                ["Linfocitos",      $linfo, "%", "( 20 - 40 )"],
                ["Monocitos",       $mono, "%", "( 0 - 10 )"],
                ["Eosinófilos",     "0", "%", "( 0 - 4 )"],
                ["Basófilos",       "0", "%", "( 0 - 2 )"]
            ],
            "SERIE PLAQUETARIA" => [
                ["Recuento de Plaquetas", $plaqueta, "uL", "150.000 - 450.000"],
            ],
            "VELOCIDAD DE SEDIMENTACIÓN GLOBULAR (V.S.G)" => [
                [ "1era Hora",    $hora1, "mm3/hora", "HASTA 12"],
                [ "2da Hora",     $hora2, "mm3/hora", " HASTA 20"]
            ]
        ],
        "QUIMICA SANGUINEA" => [
            "" => [
                ["GLUCOSA",             $glu, "mg/dl", "70 - 110 <br>Prediabetes: 100 - 125 <br>ADA 2020"],
                ["UREA",                $urea, "mg/dl", "10 - 50"],
                ["CREATININA",          $crea, "mg/dl", "0,8 - 1,4"],
                ["GOT (AST) CINETICO",  $got, "U/L", "H: hasta 42 <br>M: hasta 32<br>"],
                ["GPT (ALT) CINETICO",  $gpt, "U/L", "H: hasta 42 <br>M: hasta 32<br>"],
                ["FOSFATASA ALCALINA",  $fa, "UI/L", "Adultos: 65 - 300 Niños: hasta 645"],
                ["HEMOGLOBINA GLICOSILADA", $hba1c, "%", "BUEN CONTROL: HASTA 6.3% <br>MAL CONTROL: MAYOR A 7% <br>NO DIABETICO: MENOR A 3%."]
            ],
            "PERFIL LIPIDICO" => [
                ["COLESTEROL",          $col, "mg/dl", "Deseable: menor a 190, <br>riesgo medio: 190 -239, <br>riesgo alto: > 239"],
                ["TRIGLICERIDOS",       $tri, "mg/dl", "Sospechoso: sobre 150. <br>Elevado: sobre 200"],
                ["COLESTEROL HDL",      $hdl, "mg/dl", "35 - 65"],
                ["COLESTEROL LDL",      $ldl, "mg/dl", "Favorable menor de 150"],
                ["COLESTEROL VLDL",     $vldl, "mg/dl", "5 - 40"],
                ["COLESTEROL NO HDL",   $colNoHdl, "", ""],
                ["LIPIDOS TOTALES",     $lipidosTot, "mg/dl", "400-800"]
            ],
            "INDICE DE RIESGO ATEROGENICO" => [
                ["Colesterol Total/Colesterol HDL", $indiceColHdl, "", "Favorable menor de 4.5"],
                ["Colesterol LDL/Colesterol HDL",   $indiceLdlHdl, "", "Favorable menor de 3.55"]
            ],
            "BILIRRUBINA" => [
                ["BILIRRUBINA TOTAL",       $bilTot, "mg/dl", "Adultos: hasta 1.1 <br>Recién nacido, hasta 5 <br>5 días, hasta 12 <br>1 mes, hasta 1.5"],
                ["BILIRRUBINA DIRECTA",     $bilDir, "mg/dl", "Hasta 0,25"],
                ["BILIRRUBINA INDIRECTA",   $bilInd, "mg/dl", "Hasta 0,85"]
            ]
        ],
        "SEROLOGIA" => [
            "" => [
                ["HIV<br>
                METODO: Inmunoensayo Enzimatico HIV 1-2 ( Ag)",   "NO REACTIVO", "", "NO REACTIVO"],
                ["HEPATITIS C",   "NEGATIVO", "", "NEGATIVO"]
            ]
        ],
        "CITOQUÍMICO URINARIO" => [
            "EXAMEN CUALITATIVO" => [
                ["COLOR",                   $color, "", ""],
                ["ASPECTO",                 $aspecto, "", ""],
                ["DENSIDAD",                $densidad, "", ""],
                ["PH",                      $ph, "", ""],
                ["ESTERASA LEUCOCITARIA",   "-", "", ""],
                ["HEMATIES",                "-", "", ""],
                ["SANGRE (HEMOGLOBINA)",    "-", "", ""],
                ["NITRITOS",                "Negativo", "", ""],
                ["PROTEINAS",               "No Detectable", "", ""],
                ["GLUCOSA",                 "No Detectable", "", ""],
                ["CUERPOS CETONICOS",       "No Detectable", "", ""],
                ["BILIRRUBINA",             "No Detectable", "", ""],
                ["UROBILINOGENO",           "Normal", "", ""],
                ["HEMOGLOBINA",             "No Detectable", "", ""]
            ],
            "EXAMEN MICROSCÓPICO DEL SEDIMENTO" => [
                ["CELULAS EPITELIALES PLANAS",      $epi, "", ""],
                ["LEUCOCITOS",                      $leu, "", ""],
                ["HEMATIES",                        $hema, "", ""],
                ["CILINDROS HIALINOS",              "-", "", ""],
                ["BACTERIAS",                       "( - )", "", ""],
                ["CILINDRO HIALINO GRANULOSO",      "-", "", ""],
                ["CELULAS EPITELIALES REDONDAS",    "-", "", ""],
                ["CILINDROS GRANULOSOS",            "Negativo", "", ""],
                ["MUCUS",                           "( - )", "", ""],
                ["TRICHOMONAS",                     "( - )", "", ""],
                ["PIOCITOS",                        "Negativo", "", ""]
            ]
        ],
        "ESPECIALES" => [
            "" => [
                ["TEST DE LEISHMANIASIS IFI IgG, sangre", "NO REACTIVA", "", "NO REACTIVA"],
                ["TEST DE LEISHMANIASIS IFI IgM, sangre", "NO REACTIVA", "", "NO REACTIVA"],
                ["TOXOPLASMA GONDII, ANTICUERPOS IgG ,SANGRE (IFI)", "NO REACTIVO",
                    "UI", "NO REACTIVO: Valor calculado inferior a 6.4 UI/ml<br>INFECCION ANTIGUA: Muestra valor entre 6.5 - 10.0 UI/ml"],
                ["TOXOPLASMA GONDII, ANTICUERPOS IgM ,SANGRE (IFI)", "NO REACTIVO",
                    "", "NO REACTIVO: HASTA 0.9<br>REACTIVO: (ratio superior o igual a 1.0)<br>Se detectan anticuerpos IgM frente a toxoplasma"],
                ["HEPATITIS B ANTIGENO DE SUPERFICIE (Hbs Ag)", "No Reactivo: Inferior a 1.00",
                    "", "No Reactivo: Inferior a 1.00 <br>Reactivo: Superior a 1.00 <br>METODO: Quimioluminiscencia"],
                ["VDRL (cuantitativo)", "NO REACTIVO", "", "NO REACTIVO
                    <br>-MÉTODO: Floculación
                    <br>-Este resultado indica el estado serológico del paciente y debe ser asociado a la historia clínica y epidemiológica.
                    <br>-FALSO NEGATIVO: Puede ocurrir en la Sífilis Tardía.
                    <br>-FALSO POSITIVO: Puede ocurrir en ancianos, portadores de enfermedades autoinmunes, malaria, mononucleosis, brucelosis, hepatitis, lepra, portadores de HIV, embarazadas, drogadictos, e infecciones bacterianas"],
                ["BHCG - CUANTITATIVA - SANGRE", "0.1",
                    "mUI/mL", "Hombres :< a 3 mUI/ml
                        <br>Mujeres ciclicas: < a 4mUI/ml.
                        <br>Mujeres menopausicas: < a 13 mUI/ml
                        <br>
                        <br>Mujeres embarazadas:
                        <br>4 - 5 sem 1.500 - 23.000
                        <br>5 - 6 sem 3.400 - 135.300
                        <br>6 - 7 sem 10.500 - 161.000
                        <br>7 - 8 sem 18.000 - 209.000
                        <br>8 - 9 sem 37.500 - 219.000
                        <br>9 - 10sem 42.800 - 218.000
                        <br>10 - 11sem 33.700 - 218.700
                        <br>11 - 12sem 21.800 - 193.200
                        <br>12 - 13 sem 20.300 - 166.100
                        <br>13 - 14sem 15.400 - 190.000
                        <br>2ºtrimestre 2.800 - 176.100
                        <br>3ªtrimestre 2.800 - 144.400"],
                ["COCAINA EN ORINA", "NEGATIVO", "", "NEGATIVO
                                            <br>SENSIBILIDAD: 300 ng/mL
                                            <br>METODO: INMUNOCROMATOGRAFICO"],
                ["MARIHUANA EN ORINA", "NEGATIVO", "", "NEGATIVO
                                            <br>SENSIBILIDAD: 50 ng/mL
                                            <br>METODO: INMUNOCROMATOGRAFICO"],
                ["OPIACEOS, MORFINAS EN ORINA", "NEGATIVO", "", "NEGATIVO
                                            <br>SENSIBILIDAD: 300 ng/mL
                                            <br>METODO: INMUNOCROMATOGRAFICO"],
                ["ANFETAMINAS", "NEGATIVO", "", "NEGATIVO
                                            <br>SENSIBILIDAD: 1.000 ng/mL
                                            <br>METODO: INMUNOCROMATOGRAFICO"],
                ["BENZODIAZEPINAS, CUALITATIVO, ORINA (DROGAS DE ABUSO)", "NEGATIVO", "", "NEGATIVO
                                            <br>SENSIBILIDAD: 1.000 ng/mL
                                            <br>METODO: INMUNOCROMATOGRAFICO"]
            ]
        ]
    ];

    $mpdf->SetHTMLHeader(headerPaciente($p));
    // Nueva página por cada paciente excepto el primero
    if ($index > 0) {
        $mpdf->AddPage();
    }

    $html = $css;
    $html .= '<div>'; // contenedor general

    foreach ($examenes as $nombreExamen => $secciones) {
        $html .= '<table class="tabla" >';
        $html .= '<tr><th colspan="4" class="examen-seccion">'.$nombreExamen.'</th></tr>';

        foreach ($secciones as $nombreSeccion => $items) {
            if ($nombreSeccion !== "") {
                $html .= '
                <tr>
                    <td colspan="4" class="titulo-seccion">
                        &emsp;&emsp;
                        <span style="border-bottom: 2px solid #000;">
                            '.$nombreSeccion.'
                        </span>
                    </td>
                </tr>';
            }

            foreach ($items as $fila) {
                $item = trim($fila[0]);

                if (!$esMujer && in_array($item, $examenesSoloMujer, true)) {
                    continue;
                }

                $resultado = trim($fila[1]);

                $examenConcat = $nombreExamen . " | " . $nombreSeccion . " | " . $item;

                // obtener o guardar resultado
                $resultadoFinal = obtenerOGuardarResultado(
                    $conexion,
                    $p['id_postulante'],
                    $examenConcat,
                    $resultado
                );

                $html .= "
                    <tr>
                        <td class='col-examen'>{$fila[0]}</td>
                        <td class='col-resultado'>{$resultadoFinal}</td>
                        <td class='col-unidad'>{$fila[2]}</td>
                        <td class='col-referencia'>{$fila[3]}</td>
                    </tr>";
            }
        }

        $html .= '</table>';

        // APLICAR SALTO ANTES DE ESCRIBIR
        if (in_array($nombreExamen, $examenesConSalto)
            && $nombreExamen !== array_key_last($examenes)) {
            $html .= '<pagebreak />';
        }

        $mpdf->WriteHTML($html);

        // Reiniciar html para el siguiente examen
        $html = "";
    }

    $html .= '</div>';

    $mpdf->WriteHTML($html);
}

$mpdf->Output("Pacientes_Examenes.pdf", "I");

?>
