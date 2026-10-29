<?php

const SESION_INACTIVIDAD_SEGUNDOS = 1800;

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('SALUSSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    $ahora = time();
    $ultimaActividad = (int) ($_SESSION['ultima_actividad'] ?? $ahora);

    if (isset($_SESSION['usuario']) && ($ahora - $ultimaActividad) > SESION_INACTIVIDAD_SEGUNDOS) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['sesion_expirada'] = true;
    }

    $_SESSION['ultima_actividad'] = $ahora;
}
