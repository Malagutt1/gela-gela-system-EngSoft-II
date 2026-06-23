<?php
// O PHP DEVE SER A PRIMEIRA COISA NO ARQUIVO (Antes do HTML)
session_start();
require_once '../components/valida-sessao.php';
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
            $mensagem = "Nome updated com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar o nome: " . $e->getMessage();
        }
    }

    // 2. Atualizar Senha
    if (!empty($nova_senha)) {
        if ($nova_senha === $confirmar_senha) {
            $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_atual, $user['senha_hash'])) {
                $hash_nova = password_hash($nova_senha, PASSWORD_DEFAULT);

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

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="ASSETS/CSS/style-perfil.css">
</head>

<body>

    <div class="dashboard-wrapper">
        <div class="flex-grow-1">
            <div class="profile-container">

                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center" style="gap: 20px;">
                        <div style="background: var(--primary); color: white; width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(198,116,106,0.25);">
                            <i class="fa-solid fa-ice-cream"></i>
                        </div>
                        <div>
                            <h1 class="h4 mb-0 font-weight-bold" style="color: var(--secondary); letter-spacing: -0.5px;">Gela-Gela</h1>
                            <p class="text-muted mb-0 small">Painel administrativo corporativo</p>
                        </div>
                    </div>

                    <a href="vendas" class="btn btn-outline d-flex align-items-center gap-2">
                        <i class="fa-solid fa-arrow-left-long"></i>
                        <span>Voltar ao Sistema</span>
                    </a>
                </div>

                <?php if ($mensagem): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fa-solid fa-circle-check mr-2"></i> <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="close" data-dismiss="alert">×</button>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= htmlspecialchars($erro) ?>
                        <button type="button" class="close" data-dismiss="alert">×</button>
                    </div>
                <?php endif; ?>

                <div class="row match-height">

                    <div class="col-xl-4 col-lg-5 mb-4">
                        <div class="profile-sidebar-card">
                            <div class="avatar-circle">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>
                            <h2 class="h4 font-weight-bold mb-2 text-white"><?= htmlspecialchars($nome_atual) ?></h2>

                            <span class="badge badge-pill px-3 py-2 mb-4" style="background: rgba(250, 206, 225, 0.2); color: var(--soft); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem;">
                                <?= htmlspecialchars($tipo_usuario) ?>
                            </span>

                            <p class="mb-0 text-white-50 small text-center px-3">
                                Última sincronização de segurança ativa. Altere seus dados ao lado.
                            </p>
                        </div>
                    </div>

                    <div class="col-xl-8 col-lg-7 mb-4">
                        <div class="profile-card">
                            <form method="POST" class="mb-0">

                                <div class="section-header">
                                    <i class="fa-solid fa-id-card fa-lg" style="color: var(--primary);"></i>
                                    <h5 class="mb-0 font-weight-bold">Dados de Identificação</h5>
                                </div>

                                <div class="form-block">
                                    <label class="input-label" for="nome">Nome Completo do Operador</label>
                                    <input
                                        type="text"
                                        id="nome"
                                        name="nome"
                                        value="<?= htmlspecialchars($nome_atual) ?>"
                                        maxlength="150"
                                        required
                                        class="input-field"
                                        placeholder="Digite o nome completo completo">
                                </div>

                                <div class="section-header">
                                    <i class="fa-solid fa-shield-halved fa-lg" style="color: var(--primary);"></i>
                                    <h5 class="mb-0 font-weight-bold">Credenciais de Acesso</h5>
                                </div>

                                <div class="form-block border-bottom">
                                    <div class="row">
                                        <div class="col-md-12 mb-4">
                                            <label class="input-label" for="senha_atual">Senha Atual do Sistema</label>
                                            <div class="password-input-wrapper">
                                                <input
                                                    type="password"
                                                    id="senha_atual"
                                                    name="senha_atual"
                                                    class="input-field"
                                                    placeholder="••••••••">
                                                <button type="button" class="eye-toggle-btn" onclick="togglePassword('senha_atual')">
                                                    <i id="icon_senha_atual" class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="input-label" for="nova_senha">Nova Senha Ultra-Segura</label>
                                            <div class="password-input-wrapper">
                                                <input
                                                    type="password"
                                                    id="nova_senha"
                                                    name="nova_senha"
                                                    class="input-field"
                                                    placeholder="Definir nova senha"
                                                    oninput="updatePasswordStrength(); checkPasswordMatch();">
                                                <button type="button" class="eye-toggle-btn" onclick="togglePassword('nova_senha')">
                                                    <i id="icon_nova_senha" class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>

                                            <div id="strength-wrapper" class="mt-3" style="display: none;">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted font-weight-bold">Análise de Complexidade:</small>
                                                    <small id="strength-label" class="font-weight-bold"></small>
                                                </div>
                                                <div class="strength-track">
                                                    <div id="strength-bar" class="strength-bar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="input-label" for="confirmar_senha">Confirmar Nova Senha</label>
                                            <div class="password-input-wrapper">
                                                <input
                                                    type="password"
                                                    id="confirmar_senha"
                                                    name="confirmar_senha"
                                                    class="input-field"
                                                    placeholder="Repita a nova senha"
                                                    oninput="checkPasswordMatch()">
                                                <button type="button" class="eye-toggle-btn" onclick="togglePassword('confirmar_senha')">
                                                    <i id="icon_confirmar_senha" class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <div id="match-feedback" class="mt-2 font-weight-bold small"></div>
                                        </div>
                                    </div>

                                    <div id="password-checklist-box" class="mt-2 p-3 bg-light rounded" style="display: none;">
                                        <div class="text-muted font-weight-bold small mb-2">A nova senha deve conter:</div>
                                        <div class="row">
                                            <div class="col-sm-6 req-item text-muted" id="req-length">
                                                <i class="fa-regular fa-circle"></i> Pelo menos 8 caracteres (+=8)
                                            </div>
                                            <div class="col-sm-6 req-item text-muted" id="req-case">
                                                <i class="fa-regular fa-circle"></i> Letras Maiúsculas e Minúsculas (Mm)
                                            </div>
                                            <div class="col-sm-6 req-item text-muted" id="req-number">
                                                <i class="fa-regular fa-circle"></i> Ao menos um número (Ex: 848)
                                            </div>
                                            <div class="col-sm-6 req-item text-muted" id="req-special">
                                                <i class="fa-regular fa-circle"></i> Símbolo ou Especial (Ex: @, #, +, =)
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="px-4 py-3 bg-light d-flex justify-content-end align-items-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                                        Salvar Configurações da Conta
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function updatePasswordStrength() {
            const password = document.getElementById('nova_senha').value;
            const wrapper = document.getElementById('strength-wrapper');
            const checklistBox = document.getElementById('password-checklist-box');
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');

            if (password.length < 1) {
                wrapper.style.display = 'none';
                checklistBox.style.display = 'none';
                return;
            }
            wrapper.style.display = 'block';
            checklistBox.style.display = 'block';

            // Captura dos elementos da checklist
            const reqLength = document.getElementById('req-length');
            const reqCase = document.getElementById('req-case');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');

            // Regras individuais para validação visual das dicas
            const isLengthOk = password.length >= 8;
            const isCaseOk = /[A-Z]/.test(password) && /[a-z]/.test(password);
            const isNumberOk = /[0-9]/.test(password);
            const isSpecialOk = /[^A-Za-z0-9]/.test(password);

            // Atualiza cada item da checklist de dicas dinamicamente
            updateChecklistItem(reqLength, isLengthOk);
            updateChecklistItem(reqCase, isCaseOk);
            updateChecklistItem(reqNumber, isNumberOk);
            updateChecklistItem(reqSpecial, isSpecialOk);

            // Cálculo do score da barra
            let score = 0;
            if (isLengthOk) score++;
            if (password.length >= 12) score++;
            if (isCaseOk) score++;
            if (isNumberOk) score++;
            if (isSpecialOk) score++;

            const percentage = Math.min((score * 20), 100);
            bar.style.width = percentage + '%';

            let colorClass = 'bg-danger';
            let labelText = 'Muito fraca';
            let textClass = 'text-danger';

            if (score >= 5) {
                colorClass = 'bg-success';
                labelText = 'Excelente';
                textClass = 'text-success';
            } else if (score >= 4) {
                colorClass = 'bg-primary';
                labelText = 'Forte';
                textClass = 'text-primary';
            } else if (score >= 3) {
                colorClass = 'bg-info';
                labelText = 'Média';
                textClass = 'text-info';
            } else if (score >= 2) {
                colorClass = 'bg-warning';
                labelText = 'Fraca';
                textClass = 'text-warning';
            }

            bar.className = `strength-bar ${colorClass}`;
            label.textContent = labelText;
            label.className = `font-weight-bold ${textClass}`;
        }

        // Função auxiliar para mudar os ícones e cores da checklist de dicas de senha
        function updateChecklistItem(element, isValid) {
            const icon = element.querySelector('i');
            if (isValid) {
                element.className = "col-sm-6 req-item text-success font-weight-bold";
                icon.className = "fa-solid fa-circle-check";
            } else {
                element.className = "col-sm-6 req-item text-muted";
                icon.className = "fa-regular fa-circle";
            }
        }

        function checkPasswordMatch() {
            const nova = document.getElementById('nova_senha').value;
            const confirmarInput = document.getElementById('confirmar_senha');
            const feedback = document.getElementById('match-feedback');

            if (confirmarInput.value.length === 0) {
                feedback.innerHTML = '';
                confirmarInput.style.borderColor = 'var(--border-light)';
                return;
            }

            if (nova === confirmarInput.value) {
                feedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Senhas coincidem';
                feedback.className = "mt-2 small text-success";
                confirmarInput.style.borderColor = '#28a745';
            } else {
                feedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> As senhas não batem';
                feedback.className = "mt-2 small text-danger";
                confirmarInput.style.borderColor = '#dc3545';
            }
        }
    </script>

</body>

</html>