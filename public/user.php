<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Gerente') {
    header('Location: vendas');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function gerarLoginPadrao(PDO $pdo): string
{
    $base = 'user';
    $login = $base;
    $sufixo = 1;

    $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE login = ? LIMIT 1');

    while (true) {
        $stmt->execute([$login]);

        if (!$stmt->fetchColumn()) {
            return $login;
        }

        $login = $base . $sufixo;
        $sufixo++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_usuario') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipoEntrada = strtolower(trim((string) ($_POST['tipo'] ?? '')));
        $tipo = $tipoEntrada === 'gerente' ? 'Gerente' : 'Funcionario';

        if ($nome === '') {
            $_SESSION['flash_erro_usuario'] = 'Informe o nome do usuário.';
        } else {
            try {
                $login = gerarLoginPadrao($pdo);
                $senhaPadrao = 'User';
                $senhaHash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, login, senha_hash, tipo, ativo) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([$nome, $login, $senhaHash, $tipo]);

                $_SESSION['flash_sucesso_usuario'] = 'Usuário criado com sucesso. Login padrão: ' . $login . ' | Senha padrão para funcionário e gerente: ' . $senhaPadrao;
            } catch (Throwable $e) {
                $_SESSION['flash_erro_usuario'] = 'Não foi possível salvar o usuário.';
            }
        }
    }

    header('Location: user.php');
    exit();
}

$flashSucesso = $_SESSION['flash_sucesso_usuario'] ?? '';
$flashErro = $_SESSION['flash_erro_usuario'] ?? '';
unset($_SESSION['flash_sucesso_usuario'], $_SESSION['flash_erro_usuario']);

$stmt = $pdo->query('SELECT usuario_id, nome, login, tipo, ativo FROM usuarios ORDER BY CASE WHEN tipo = "Gerente" THEN 0 ELSE 1 END, nome ASC');
$usuarios = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Usuários</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">

    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <div class="layout">

        <?php require_once '../components/sidebar.php'; ?>

        <main class="content">

            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Controle de Usuários</h1>
            </header>

            <section class="main">

                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <h2>Usuários do Sistema</h2>
                    <button class="btn" onclick="abrirModalUsuario()">Novo Usuário</button>
                </div>

                <?php if ($flashSucesso !== ''): ?>
                    <div class="box" style="margin-top:16px; border-left:4px solid #2ecc71; background:#eefbf2;">
                        <?= e($flashSucesso) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flashErro !== ''): ?>
                    <div class="box" style="margin-top:16px; border-left:4px solid #e74c3c; background:#fdf0ee;">
                        <?= e($flashErro) ?>
                    </div>
                <?php endif; ?>

                <div class="box" style="margin-top:20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Login</th>
                                <th>Cargo</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <?php
                                        $tipo = (string) ($usuario['tipo'] ?? 'Funcionario');
                                        $classeTipo = $tipo === 'Gerente' ? 'danger' : 'ok';
                                        $rotuloTipo = $tipo === 'Gerente' ? 'Gerente' : 'Funcionário';
                                    ?>
                                    <tr>
                                        <td><?= e($usuario['nome'] ?? '') ?></td>
                                        <td><?= e($usuario['login'] ?? '') ?></td>
                                        <td><span class="badge <?= e($classeTipo) ?>"><?= e($rotuloTipo) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center;">Nenhum usuário cadastrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>
        </main>
    </div>

    <div id="modalUsuario" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px;">
                <h3 style="margin:0;">Novo Usuário</h3>
                <button type="button" class="btn btn-secondary" onclick="fecharModalUsuario()">Fechar</button>
            </div>

            <form method="POST" action="user.php">
                <input type="hidden" name="acao" value="salvar_usuario">

                <input type="text" name="nome" placeholder="Nome completo" required>

                <select name="tipo" required>
                    <option value="">Selecione o cargo</option>
                    <option value="Funcionario">Funcionário</option>
                    <option value="Gerente">Gerente</option>
                </select>

                <p style="margin:12px 0 0; font-size:0.9rem; opacity:0.8;">
                    O login será gerado automaticamente no padrão <strong>user</strong>, <strong>user2</strong>, <strong>user3</strong>... e a senha padrão será <strong>User</strong> para funcionário e gerente.
                </p>

                <button class="btn" type="submit" style="margin-top:16px;">
                    <i class="fa-solid fa-check"></i> Salvar Usuário
                </button>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script>
        function abrirModalUsuario() {
            const modal = document.getElementById('modalUsuario');
            const form = modal ? modal.querySelector('form') : null;
            if (form) form.reset();
            if (modal) modal.classList.add('show');
        }

        function fecharModalUsuario() {
            const modal = document.getElementById('modalUsuario');
            const form = modal ? modal.querySelector('form') : null;
            if (modal) modal.classList.remove('show');
            if (form) form.reset();
        }

        document.getElementById('modalUsuario')?.addEventListener('click', function (event) {
            if (event.target === this) {
                fecharModalUsuario();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                fecharModalUsuario();
            }
        });
    </script>

</body>

</html>