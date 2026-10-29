<?php

/* ===========================
   MATRICES Y LISTAS
=========================== */
function obtenerValoresAleatorios() {
    $matriz = [
        [40,43,48,30,32,48,42,44,40,38],
        [58,55,50,67,66,50,56,55,57,60],
        [2,2,2,3,2,2,2,1,3,2]
    ];

    $c = rand(0, 9);

    return [
        $matriz[0][$c],
        $matriz[1][$c],
        $matriz[2][$c]
    ];
}

function obtenerHorasAleatorias() {
    $matriz = [
        [7,5,6,7,8,3,5,6,7,8,10,12,5,6,7,8,9,9,7,6],
        [10,12,14,12,19,10,15,13,12,13,18,18,14,16,16,17,17,15,15,14]
    ];

    $c = rand(0, count($matriz[0]) - 1);

    return [
        $matriz[0][$c],
        $matriz[1][$c]
    ];
}

function obtenerBillisAleatoria() {
    $matriz = [
        [0.4,0.41,0.5,0.55,0.56,0.77,0.58,0.59,0.6,0.56,0.62,0.65,0.68,0.7,0.68,0.66,0.55,0.78,0.76,0.56],
        [0.17,0.12,0.13,0.14,0.15,0.16,0.17,0.18,0.19,0.20,0.21,0.22,0.23,0.24,0.18,0.22,0.12,0.22,0.21,0.10]
    ];

    $c = rand(0, count($matriz[0]) - 1);

    return [
        $matriz[0][$c],
        $matriz[1][$c]
    ];
}

/* ===========================
   PERFIL DE ORINA
=========================== */
function perfilOrinaAleatorio() {
    $perfiles = [
        ["color"=>"Amarilla","aspecto"=>"Claro","densidad"=>"1015","ph"=>"6.0","reac"=>"Ácida","epi"=>"1 a 2 por Campo","leu"=>"0 a 1 por Campo","hema"=>"0 a 1 por Campo"],
        ["color"=>"Amarillo pálido","aspecto"=>"Claro","densidad"=>"1016","ph"=>"6.5","reac"=>"Neutra","epi"=>"2 a 3 por Campo","leu"=>"1 a 2 por Campo","hema"=>"1 a 2 por Campo"],
        ["color"=>"Amarillo","aspecto"=>"Ligeramente turbia","densidad"=>"1014","ph"=>"6","reac"=>"Ácida","epi"=>"0 a 2 por Campo","leu"=>"0 a 1 por Campo","hema"=>"0 a 1 por Campo"],
        ["color"=>"Amarillo intenso","aspecto"=>"Claro","densidad"=>"1020","ph"=>"7.0","reac"=>"Neutra","epi"=>"1 a 3 por Campo","leu"=>"1 a 2 por Campo","hema"=>"0 a 1 por Campo"],
        ["color"=>"Amarilla","aspecto"=>"Ligeramente turbia","densidad"=>"1018","ph"=>"6.0","reac"=>"Ácida","epi"=>"2 a 4 por Campo","leu"=>"1 a 2 por Campo","hema"=>"1 a 2 por Campo"]
    ];

    return $perfiles[array_rand($perfiles)];
}

/* ===========================
   HORMONAS Y COMPLEMENTO
=========================== */
function valorTSH() { return [2.3,2.0,1.5,1.9,2.8][array_rand([2.3,2.0,1.5,1.9,2.8])]; }
function valorT3()  { return [0.75,1.1,1.3,0.99,1.5][array_rand([0.75,1.1,1.3,0.99,1.5])]; }
function valorT4()  { return [5.9,6.3,7.2,6.7,8.1][array_rand([5.9,6.3,7.2,6.7,8.1])]; }
function valorC3()  { return [0.86,0.90,0.99,1.1,1.2][array_rand([0.86,0.90,0.99,1.1,1.2])]; }
function valorC4()  { return [0.15,0.22,0.31,0.29,0.34][array_rand([0.15,0.22,0.31,0.29,0.34])]; }

?>
