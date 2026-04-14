<?php
session_start();
include 'conecta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha'] ?? '');

if (empty($usuario) || empty($senha)) {
    header("Location: login.php?erro=2");
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
        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['nome']       = $user['nome'];
        $_SESSION['tipo']       = $user['tipo'];
        $_SESSION['logado']     = true;

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