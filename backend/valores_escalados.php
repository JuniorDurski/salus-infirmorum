<?php

/* ===========================
   FUNCIÓN BASE
=========================== */
function valorAleatorioEscalado($inicio, $fin, $salto) {
    $valores = [];

    $inicio = floatval($inicio);
    $fin    = floatval($fin);
    $salto  = floatval($salto);

    for ($v = $inicio; $v <= $fin + 0.0000001; $v += $salto) {
        $valores[] = round($v, 6);
    }

    return $valores[array_rand($valores)];
}

/* ===========================
   VALORES BIOQUÍMICOS
=========================== */
function valorTP()            { return valorAleatorioEscalado(82, 100, 1); }
function valorTTPA()          { return valorAleatorioEscalado(35, 40, 1); }
function valorFibri()         { return valorAleatorioEscalado(210, 350, 1); }

function valorGlu()           { return valorAleatorioEscalado(70, 81, 1); }
function valorUrea()          { return valorAleatorioEscalado(10, 35, 1); }
function valorCre()           { return valorAleatorioEscalado(0.60, 0.80, 0.01); }

function valorAU()            { return valorAleatorioEscalado(1.9, 3.1, 0.1); }

function valorGOT()           { return valorAleatorioEscalado(10, 32, 1); }
function valorGPT()           { return valorAleatorioEscalado(10, 32, 1); }
function valorFA()            { return valorAleatorioEscalado(80, 145, 1); }

function valorCol()           { return valorAleatorioEscalado(110, 165, 1); }
function valorTri()           { return valorAleatorioEscalado(88, 116, 1); }
function valorHDL()           { return valorAleatorioEscalado(46, 56, 1); }
function valorHbA1c()         { return valorAleatorioEscalado(2.6, 3.0, 0.1); }

function valorPlaqueta()      { return valorAleatorioEscalado(200000, 400000, 1000); }
function valorHematocrito()   { return valorAleatorioEscalado(43, 46, 0.1); }
function valorBlancos()       { return valorAleatorioEscalado(4000, 7900, 10); }

function valorHCM()           { return valorAleatorioEscalado(30, 32, 0.1); }
function valorVCM()           { return valorAleatorioEscalado(90, 91, 0.01); }
function valorCHCM()          { return valorAleatorioEscalado(30, 32, 0.1); }

?>
