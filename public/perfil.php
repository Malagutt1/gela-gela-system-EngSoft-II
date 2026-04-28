<?php
// O PHP DEVE SER A PRIMEIRA COISA NO ARQUIVO (Antes do HTML)
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nome_atual = $_SESSION['nome'] ?? '';

$stmt_user = $pdo->prepare("SELECT tipo FROM usuarios WHERE usuario_id = ?");
$stmt_user->execute([$usuario_id]);
$dados_usuario = $stmt_user->fetch();
$tipo_usuario = $dados_usuario['tipo'] ?? 'Usuário';

// Mensagens
$mensagem = '';
$erro = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $novo_nome       = trim($_POST['nome'] ?? '');
    $senha_atual     = $_POST['senha_atual'] ?? '';
    $nova_senha      = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    $alterou_algo = false;

    // 1. Atualizar Nome
    if (!empty($novo_nome) && $novo_nome !== $nome_atual) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ? WHERE usuario_id = ?");
            $stmt->execute([$novo_nome, $usuario_id]);

            $_SESSION['nome'] = $novo_nome;
            $nome_atual = $novo_nome;
            $alterou_algo = true;
            $mensagem = "Nome atualizado com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar o nome: " . $e->getMessage();
        }
    }

    // 2. Atualizar Senha
    if (!empty($nova_senha)) {
        if ($nova_senha === $confirmar_senha) {
            // CORREÇÃO: Usando 'senha_hash' em vez de 'senha' (conforme o seu SQL)
            $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $user = $stmt->fetch();

            // CORREÇÃO: Verificando a coluna correta do banco
            if ($user && password_verify($senha_atual, $user['senha_hash'])) {
                $hash_nova = password_hash($nova_senha, PASSWORD_DEFAULT);

                // CORREÇÃO: Atualizando 'senha_hash'
                $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE usuario_id = ?");
                $stmt->execute([$hash_nova, $usuario_id]);

                $alterou_algo = true;
                $mensagem = "Senha alterada com sucesso!";
            } else {
                $erro = "Senha atual incorreta.";
            }
        } else {
            $erro = "As novas senhas não coincidem.";
        }
    }

    // 3. Redirecionar se deu tudo certo
    if (empty($erro) && $alterou_algo) {
        $_SESSION['mensagem_sucesso'] = "Alterações salvas com sucesso!";
        header("Location: vendas");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela • Meu Perfil</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="ASSETS/CSS/style-perfil.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="brand-group">
                <div class="brand-icon">
                    <i class="fa-solid fa-ice-cream"></i>
                </div>
                <div>
                    <span class="brand-name">Gela-Gela</span>
                </div>
                <span class="brand-badge"><?= htmlspecialchars($tipo_usuario) ?></span>
            </div>

            <div class="header-title-group">
                <h1 class="page-title">Meu Perfil</h1>
            </div>

            <div class="header-nav">
                <a href="vendas" class="btn-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Voltar ao sistema</span>
                </a>

                <div class="user-widget">
                    <div class="user-info">
                        <p class="user-name"><?= htmlspecialchars($nome_atual) ?></p>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">

            <?php if ($mensagem): ?>
                <div class="alert alert-success" id="alert-success">
                    <i class="fa-solid fa-circle-check alert-icon"></i>
                    <div class="alert-message"><?= htmlspecialchars($mensagem) ?></div>
                    <button class="alert-close" onclick="document.getElementById('alert-success').style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert alert-error" id="alert-error">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div class="alert-message"><?= htmlspecialchars($erro) ?></div>
                    <button class="alert-close" onclick="document.getElementById('alert-error').style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <form method="POST" class="form-layout">

                    <section>
                        <div class="section-header">
                            <i class="fa-solid fa-user section-icon"></i>
                            <h2 class="section-title">Dados pessoais</h2>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="nome">Nome Completo</label>
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    value="<?= htmlspecialchars($nome_atual) ?>"
                                    maxlength="150"
                                    required
                                    class="input-field"
                                    placeholder="Digite seu nome completo">
                            </div>
                        </div>
                    </section>

                    <div class="divider-container">
                        <div class="divider-line"></div>
                        <div class="divider-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            Segurança
                        </div>
                        <div class="divider-line"></div>
                    </div>

                    <section>
                        <div class="section-header">
                            <i class="fa-solid fa-lock section-icon"></i>
                            <h2 class="section-title">Alterar senha</h2>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="senha_atual">Senha Atual</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="senha_atual"
                                    name="senha_atual"
                                    placeholder="••••••••••••"
                                    class="input-field has-icon">
                                <button type="button" class="btn-toggle-password" onclick="togglePassword('senha_atual')">
                                    <i id="icon_senha_atual" class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="nova_senha">Nova Senha</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="nova_senha"
                                    name="nova_senha"
                                    placeholder="Digite a nova senha"
                                    oninput="updatePasswordStrength(); checkPasswordMatch();"
                                    class="input-field has-icon">
                                <button type="button" class="btn-toggle-password" onclick="togglePassword('nova_senha')">
                                    <i id="icon_nova_senha" class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                            <div id="strength-wrapper" class="strength-container">
                                <div class="strength-header">
                                    <span>Força da senha</span>
                                    <span id="strength-label" class="strength-label"></span>
                                </div>
                                <div class="strength-track">
                                    <div id="strength-bar" class="strength-bar"></div>
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="confirmar_senha">Confirmar Nova Senha</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="confirmar_senha"
                                    name="confirmar_senha"
                                    placeholder="Confirme a nova senha"
                                    oninput="checkPasswordMatch()"
                                    class="input-field has-icon">
                                <button type="button" class="btn-toggle-password" onclick="togglePassword('confirmar_senha')">
                                    <i id="icon_confirmar_senha" class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div id="match-feedback" class="match-feedback"></div>
                        </div>
                    </section>

                    <div class="actions-group">
                        <a href="vendas" class="btn btn-outline">
                            <i class="fa-solid fa-arrow-left"></i>
                            Voltar para o Sistema
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <p class="footer-note">
                <i class="fa-solid fa-shield-halved"></i>
                Seus dados são protegidos por criptografia de ponta a ponta • Gela-Gela © 2026
            </p>
        </div>
    </main>

    <script>
        /**
         * Alterna a visibilidade da senha (olhinho)
         */
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);

            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        /**
         * Calcula a força da senha e atualiza a barra de progresso
         */
        function updatePasswordStrength() {
            const password = document.getElementById('nova_senha').value;
            const wrapper = document.getElementById('strength-wrapper');
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');

            if (password.length < 1) {
                wrapper.classList.remove('active');
                return;
            }

            wrapper.classList.add('active');

            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            const percentage = Math.min(score * 25, 100);

            let colorClass = '';
            let textClass = '';
            let text = '';

            if (score <= 1) {
                colorClass = 'bg-danger';
                textClass = 'text-danger';
                text = 'Muito fraca';
            } else if (score === 2) {
                colorClass = 'bg-warning';
                textClass = 'text-warning';
                text = 'Fraca';
            } else if (score === 3) {
                colorClass = 'bg-info';
                textClass = 'text-info';
                text = 'Média';
            } else if (score === 4) {
                colorClass = 'bg-success';
                textClass = 'text-success';
                text = 'Boa';
            } else {
                colorClass = 'bg-primary';
                textClass = 'text-primary';
                text = 'Excelente';
            }

            // Limpa classes antigas e aplica as novas
            bar.className = 'strength-bar ' + colorClass;
            bar.style.width = percentage + '%';

            label.textContent = text;
            label.className = 'strength-label ' + textClass;
        }

        /**
         * Verifica se as senhas coincidem e adiciona bordas coloridas aos inputs
         */
        function checkPasswordMatch() {
            const nova = document.getElementById('nova_senha').value;
            const confirmarInput = document.getElementById('confirmar_senha');
            const feedback = document.getElementById('match-feedback');

            if (confirmarInput.value.length === 0) {
                feedback.innerHTML = '';
                confirmarInput.classList.remove('input-error', 'input-success');
                return;
            }

            if (nova === confirmarInput.value) {
                feedback.innerHTML = '<span class="text-success"><i class="fa-solid fa-check-circle"></i> Senhas coincidem perfeitamente</span>';
                confirmarInput.classList.remove('input-error');
                confirmarInput.classList.add('input-success');
            } else {
                feedback.innerHTML = '<span class="text-danger"><i class="fa-solid fa-xmark-circle"></i> As senhas não coincidem</span>';
                confirmarInput.classList.remove('input-success');
                confirmarInput.classList.add('input-error');
            }
        }
    </script>
</body>

</html>