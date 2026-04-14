<?php
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão no servidor
session_destroy();

// Redireciona para a página de login (usando a URL amigável do seu .htaccess)
header("Location: login");
exit;
?>