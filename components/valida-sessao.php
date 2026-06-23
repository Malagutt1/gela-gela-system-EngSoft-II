<?php
// Garante que a sessão seja iniciada apenas se já não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// 1. VERIFICAÇÃO DE LOGIN BÁSICA
// ==========================================================
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Se não estiver logado, chuta para o login
    header("Location: /gela-gela-system-EngSoft-II/login");
    exit;
}

// ==========================================================
// 2. CONTROLE DE INATIVIDADE (TIMEOUT)
// ==========================================================
$tempo_limite = 900; // 15 minutos em segundos (15 * 60)

if (isset($_SESSION['ultimo_clique'])) {
    $tempo_inativo = time() - $_SESSION['ultimo_clique'];
    
    if ($tempo_inativo > $tempo_limite) {
        // Sessão expirada por inatividade. Redireciona para o logout seguro.
        header("Location: /gela-gela-system-EngSoft-II/logout?motivo=timeout");
        exit;
    }
}

// ==========================================================
// 3. ATUALIZAÇÃO DO RELÓGIO (HEARTBEAT)
// ==========================================================
// Como o script chegou até aqui, o usuário está ativo e dentro do limite.
$_SESSION['ultimo_clique'] = time();
?>