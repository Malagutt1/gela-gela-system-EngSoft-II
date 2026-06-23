<?php
session_start();
// Verifica se há um motivo específico para o logout (ex: timeout de 15 min)
$query_string = "";
if (isset($_GET['motivo']) && $_GET['motivo'] === 'timeout') {
    $query_string = "?erro=3"; // Aciona a mensagem "Sessão expirada"
}
$_SESSION = array(); // Limpa todas as variáveis da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy(); // Destrói a sessão no servidor
header("Location: /gela-gela-system-EngSoft-II/login" . $query_string);
exit;