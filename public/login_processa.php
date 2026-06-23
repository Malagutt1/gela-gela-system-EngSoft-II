<?php
session_start();
require_once __DIR__ . '/../conecta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login");
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$lembrar = isset($_POST['lembrar']); // Verifica se o checkbox foi marcado

if (empty($usuario) || empty($senha)) {
    header("Location: login?erro=2");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT usuario_id, nome, login, senha_hash, tipo, ativo 
                           FROM usuarios 
                           WHERE login = ? AND ativo = TRUE LIMIT 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        // Login OK
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['nome']       = $user['nome'];
        $_SESSION['login']      = $user['login'];
        $_SESSION['tipo']       = $user['tipo'];
        $_SESSION['logado']     = true;

        // Inicia o cronômetro para o Timeout de 15 minutos
        $_SESSION['ultimo_clique'] = time();
        if ($lembrar) {
            setcookie('lembrar_usuario', $usuario, time() + (86400 * 30) , "/"); 
        } else {
            setcookie('lembrar_usuario', '', time() - 3600, "/");
        }

        // Atualiza último acesso
        $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE usuario_id = ?")
            ->execute([$user['usuario_id']]);

        header("Location: vendas");
        exit;
    } else {
        header("Location: login?erro=1");
        exit;
    }
} catch (Exception $e) {
    echo "Erro interno no sistema: " . htmlspecialchars($e->getMessage());
    exit;
}
?>