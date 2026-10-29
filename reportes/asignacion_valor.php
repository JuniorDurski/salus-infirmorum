<?php
/* ===========================
   CALCULO DE RESULTADOS
=========================== */

/* HEMATOLOGIA */
   $hematocrito = valorHematocrito();
   $hemoglobina = round($hematocrito / 3, 2);
   $rojos       = round($hemoglobina * 0.33, 2);
   $vcm         = valorVCM();
   $hcm         = valorHCM();
   $chcm        = valorCHCM();

   $blanco = valorBlancos();

   $vec   = obtenerValoresAleatorios();
   $linfo = $vec[0];
   $seg   = $vec[1];
   $mono  = $vec[2];

   $plaqueta = $plaqueta_formateada = number_format(valorPlaqueta(), 0, ',', '.');

   $vec   = obtenerHorasAleatorias();
   $hora1 = $vec[0];
   $hora2 = $vec[1];

/* QUIMICA SANGUINEA */
   $glu  = valorGlu();
   $urea = valorUrea();
   $crea = valorCre();
   $au   = valorAU();
   $got  = valorGOT();
   $gpt  = valorGPT();
   $fa   = valorFA();

   $col  = valorCol();
   $tri  = valorTri();
   $hdl  = valorHDL();
   $vldl = $tri / 5;
   $ldl  = $col - $hdl - $vldl;

   $vec    = obtenerBillisAleatoria();
   $bilTot = $vec[0];
   $bilDir = $vec[1];
   $bilInd = $bilTot - $bilDir;

/* CITOQUÍMICO URINARIO */
   $perfil = perfilOrinaAleatorio();

   $color    = $perfil["color"];
   $aspecto  = $perfil["aspecto"];
   $densidad = $perfil["densidad"];
   $ph       = $perfil["ph"];
   $reac     = $perfil["reac"];

   $epi  = $perfil["epi"];
   $leu  = $perfil["leu"];
   $hema = $perfil["hema"];

/* COAGULACION */
   $fibrinogeno = valorFibri();
   $tp          = valorTP() . "%";
   $ttpa        = valorTTPA();
   $tipi        = "" . $p["tipi"];

/* ESPECIALES */
   $tsh = valorTSH();
   $t3  = valorT3();
   $t4  = valorT4();
   $c3  = valorC3();
   $c4  = valorC4();


?>