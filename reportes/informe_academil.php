<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once __DIR__ . '/../mpdf/vendor/autoload.php';
require_once __DIR__ . '/../backend/conexion.php';

include      __DIR__ . '/../backend/calculo_laboratorio.php';
include      'header_footer.php';
include      'css.php';

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


foreach ($pacientes as $index => $p) {
    
    $examenesConSalto = [
        "COAGULACION",
        "SEROLOGIA",
        "HECES SIMPLES"
    ];

    /* ===========================
       CALCULO DE RESULTADOS
    =========================== */
    include 'asignacion_valor.php';

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
        "COAGULACION" => [
            "" => [
                ["Fibrinogeno",        $fibrinogeno, "mg/dl", "( 200 - 400 )"]
            ],
            "TP Y TTP" => [
                ["Tiempo de Protrombina (TP)",      $tp, "segundos", "11 - 13seg 70 - 100%act"],
                ["Tiempo de Tromboplastina Parcial Activada (TTPA)",    $ttpa, "segundos", "30-40 seg"],
                ["Grupo Sanguineo y Factor Rh",      $tipi, "", ""]
            ]
        ],
        "QUIMICA SANGUINEA" => [
            "" => [
                ["GLUCOSA",             $glu, "mg/dl", "70 - 110 <br>Prediabetes: 100 - 125 <br>ADA 2020"],
                ["UREA",                $urea, "mg/dl", "10 - 50"],
                ["CREATININA",          $crea, "mg/dl", "0,8 - 1,4"],
                ["ACIDO URICO",         $au, "", "H: 2,5 - 6,0 M: 1,5 - 5,0"],
                ["GOT (AST) CINETICO",  $got, "U/L", "H: hasta 42 <br>M: hasta 32<br>"],
                ["GPT (ALT) CINETICO",  $gpt, "U/L", "H: hasta 42 <br>M: hasta 32<br>"],
                ["FOSFATASA ALCALINA",  $fa, "U/L", "Adultos: 65 - 300 Niños: hasta 645"]
            ],
            "PERFIL LIPIDICO" => [
                ["COLESTEROL",          $col, "mg/dl", "Deseable: menor a 190, <br>riesgo medio: 190 -239, <br>riesgo alto: > 239"],
                ["TRIGLICERIDOS",       $tri, "mg/dl", "Sospechoso: sobre 150. <br>Elevado: sobre 200"],
                ["COLESTEROL HDL",      $hdl, "mg/dl", "35 - 65"],
                ["COLESTEROL LDL",      $ldl, "mg/dl", "Favorable menor de 150"],
                ["COLESTEROL VLDL",     $vldl, "mg/dl", "5 - 40"]
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
                METODO: Inmunoensayo Enzimatico HIV 1-2 ( Ag)",   "NO REACTIVO ", "", "NO REACTIVO"],
                ["B-HCG (gonadotropina corionica humana)",   "NEGATIVO", "", "SANGRE - CUALITATIVA <br>METODO: INMUNOCROTOGRAFIA"]
            ]
        ],
        "CITOQUÍMICO URINARIO" => [
            "" => [
                ["COLOR",       $color, "", ""],
                ["ASPECTO",     $aspecto, "", ""],
                ["DENSIDAD",    $densidad, "", ""],
                ["PH",          $ph, "", ""],
                ["REACCION",    $reac, "", ""],
                ["SEDIMENTO MICROSCÓPICO",  "REGULAR", "", ""],
                ["ESTERASA LEUCOCITARIA",   "Negativo", "", ""],
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
                ["CELULAS HEPITELIALES REDONDAS",   "-", "", ""],
                ["CILINDROS GRANULOSOS",            "Negativo", "", ""],
                ["MUCUS",                           "( - )", "", ""],
                ["TRICHOMONAS",                     "( - )", "", ""],
                ["PIOCITOS",                        "Negativo", "", ""]
            ]
        ],
        "HECES SIMPLES" => [
            "EXAMEN PARASITOLOGICO" => [
                ["VERMES",   "NO SE OBSERVARON FORMAS PARASITARIAS EN LA MUESTRA ANALIZADA", 
                    "", ""],
                ["PROTOZOARIOS",    "NO SE OBSERVARON FORMAS PARASITARIAS EN LA MUESTRA ANALIZADA", 
                    "", "POR CAMPO"]

            ]
        ],
        "ESPECIALES" => [
            "" => [
                ["TSH ULTRASENSIBLE, SANGRE ", $tsh,
                    "uUI/ml", "0.35 - 4.94 uUI/ml <br>METODO: CMIA MUESTRA: Suero<br>1ER TRIMESTRE: 0.50 - 2.50 <br>2DO Y 3ER TRIMESTRE: 0.50 - 3.0 <br>-EUTIROIDES: 0.35 - 5.50 <br>-HIPERTIROIDIMO: MENOR A 0.35 <br>-HIPOTIROIDISMO: MAS DE 5.50"],
                ["T3 - Triiodotironina", $t3, 
                    "ng/dL", "0.6 - 1.85 "],
                ["T4 - Tiroxina Total", $t4, 
                    "ug/dL", "Adultos: 4,5 - 12,9 <br>Embrazada: 1,75 - 18,7 <br>Niños: 3,5 - 16,0"],
                ["VDRL (cuantitativo)", "NO REACTIVO", "", "NO REACTIVO 
                    <br>-MÉTODO: Floculación
                    <br>-Este resultado indica el estado serológico del paciente y debe ser asociado a la historia clínica y epidemiológica. 
                    <br>-FALSO NEGATIVO: Puede ocurrir en la Sífilis Tardía.
                    <br>-FALSO POSITIVO: Puede ocurrir en ancianos, portadores de enfermedades autoinmunes, malaria, mononucleosis, brucelosis, hepatitis, lepra, portadores de HIV, embarazadas, drogadictos, e infecciones bacterianas"],
                
                ["C3", $c3, 
                    "g/L", "0.81 - 1.57"],
                ["C4", $c4, 
                    "g/L", "0.13 - 0.39"],

                ["ANTICUERPOS ANTIDNA", "NEGATIVO: Inferior a 20 UI/mL", 
                    "UI/mL", "NEGATIVO: Inferior a 20 UI/mL"],

                ["PCR Proteina C Reactiva - sangre", "Inferior a 6 mg/L", 
                    "mg/L", "Inferior a 6 mg/L <br>METODO: Aglutinacion de latex"],

                ["TOXOPLASMA GONDII, ANTICUERPOS IgG ,SANGRE (IFI)", "NO REACTIVO", 
                    "UI", "NO REACTIVO: Valor calculado inferior a 6.4 UI/ml<br>INFECCION ANTIGUA: Muestra valor entre 6.5 - 10.0 UI/ml"],
                ["TOXOPLASMA GONDII, ANTICUERPOS IgM ,SANGRE (IFI)", "NO REACTIVO", 
                    "", "REACTIVO: (ratio superior o igual a 1.0)<br>Se detectan anticuerpos IgM frente a toxoplasma"],
                ["CHAGAS (TRYPANOSOMA CRUZI), ANTICUERPOS IgM, SANGRE", "NEGATIVO", 
                    "", "NEGATIVO"],
                ["CHAGAS (TRYPANOSOMA CRUZI), ANTICUERPOS IgG, SANGRE", "NEGATIVO", 
                    "", "NEGATIVO"],
                
                ["HEPATITIS B ANTIGENO DE SUPERFICIE (Hbs Ag)", "No Reactivo: Inferior a 1.00 ", 
                    "", "No Reactivo: Inferior a 1.00 <br>Reactivo: Superior a 1.00 <br>METODO: Quimioluminiscencia"],

                ["ANA (Anticuerpos Nucleares) IgG, -sangre", "No reactivo, dilucion 1/40", 
                    "", "No reactivo, dilucion 1/40"],

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
                        <br>3ªtrimestre 2.800 - 144.400"]
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
            if ($nombreSeccion == "") {
                
            }else{
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
                if ($p["sexo"] == "MASCULINO" && 
                    (
                        $fila[0] == "BHCG - CUANTITATIVA - SANGRE" 
                        ||
                        $fila[0] == "B-HCG (gonadotropina corionica humana)"
                    )) {
                    
                }else {
                    include_once __DIR__ . '/../backend/examenesDAO.php';

                    $item      = trim($fila[0]);
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