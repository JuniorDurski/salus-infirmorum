<?php
$css =' 
<style>
    body { font-family: Arial; font-size: 14px; }

    .tabla { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 0px; 
        table-layout: fixed; /* ← CLAVE */
    }

    .tabla th, .tabla td {
        padding: 3px 4px;
        word-wrap: break-word;     /* ← PERMITE DIVIDIR TEXTO */
        overflow-wrap: break-word; /* ← AÚN MÁS SEGURO */
        word-break: break-all;     /* ← OBLIGA A ROMPER PALABRAS LARGAS */
    }

    /* ====== TAMAÑOS FIJOS DE LAS 4 COLUMNAS ====== */
        .col-examen { 
            width: 30%; 
            vertical-align: top !important; 
        }
        .col-resultado { 
            width: 29%; 
            text-align: left; 
            vertical-align: top !important; 
        }
        .col-unidad { 
            width: 11%; 
            text-align: left; 
            vertical-align: top !important; 
        }
        .col-referencia { 
            width: 35%; 
            text-align: left; 
            vertical-align: top !important; 
        }

    .section-title {
        text-align: left;
        font-weight: bold;
        border-bottom: 2px solid #000;
    }

    .examen-seccion { 
        font-weight: bold; 
        font-size: 18px;
    }

    .titulo-seccion { 
        font-weight: bold; 
        margin-top: 12px; 
        text-align: left;
    }
</style>';


?>