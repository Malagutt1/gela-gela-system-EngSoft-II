<?php
session_start();
$_SESSION = array(); // Limpa todas as variáveis da sessão
session_destroy(); // Destrói a sessão no servidor
// Redireciona para a página de login (usando a URL amigável do seu .htaccess)
header("Location: /gela-gela-system-EngSoft-II/");
exit;
