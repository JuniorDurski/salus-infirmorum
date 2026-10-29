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
        "HEMATOLOGIA",
        "SEROLOGIA",
        "HECES SIMPLES",
        "ESPECIALES 5 DROGAS"
    ];

    /* ===========================
       CALCULO DE RESULTADOS
    =========================== */
    include 'asignacion_valor.php';

    /* ===========================
       EXÁMENES APLICADOS A CADA PACIENTE
    =========================== */
    $examenes = [
        "ESPECIALES 5 DROGAS" => [
            "" => [        

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
                                            <br>METODO: INMUNOCROMATOGRAFICO"],

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