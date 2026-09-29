<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Remove todos os dados da sessão.
$_SESSION = [];

// Remove também o cookie da sessão no navegador.
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

// Destrói a sessão no servidor.
session_destroy();

header('Location: login.php');
exit;