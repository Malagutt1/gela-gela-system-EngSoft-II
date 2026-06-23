<?php
session_start();
require_once '../components/valida-sessao.php';
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Gerente') {
    header('Location: vendas');
    exit();
}

$usuario_logado_id = (int)($_SESSION['usuario_id'] ?? 0);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ======================================================
// PROCESSAMENTO DE FORMULÁRIOS
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_usuario') {
        $usuario_id = (int)($_POST['usuario_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $loginInput = trim((string) ($_POST['login'] ?? ''));
        $tipoEntrada = strtolower(trim((string) ($_POST['tipo'] ?? '')));
        $tipo = $tipoEntrada === 'gerente' ? 'Gerente' : 'Funcionario';

        if ($nome === '') {
            $_SESSION['flash_erro_usuario'] = 'Informe o nome do usuário.';
        } else {
            try {
                // Definir login: se vazio, pega o primeiro nome em minúsculo
                if ($loginInput === '') {
                    $partesNome = explode(' ', $nome);
                    $login = strtolower(trim($partesNome[0]));
                } else {
                    $login = strtolower($loginInput);
                }

                // Verificar se o login já existe para OUTRO usuário
                $stmtCheck = $pdo->prepare('SELECT usuario_id FROM usuarios WHERE login = ? AND usuario_id != ? LIMIT 1');
                $stmtCheck->execute([$login, $usuario_id]);
                if ($stmtCheck->fetch()) {
                    throw new Exception('Este login já está sendo utilizado por outro usuário.');
                }

                if ($usuario_id > 0) {
                    // Atualizar usuário existente
                    $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, login = ?, tipo = ? WHERE usuario_id = ?');
                    $stmt->execute([$nome, $login, $tipo, $usuario_id]);
                    $_SESSION['flash_sucesso_usuario'] = 'Usuário atualizado com sucesso.';
                } else {
                    // Criar novo usuário com senha padrão "User"
                    $senhaPadrao = 'User';
                    $senhaHash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, login, senha_hash, tipo, ativo) VALUES (?, ?, ?, ?, 1)');
                    $stmt->execute([$nome, $login, $senhaHash, $tipo]);
                    $_SESSION['flash_sucesso_usuario'] = "Usuário criado com sucesso. Login: {$login} | Senha padrão: {$senhaPadrao}";
                }
            } catch (Throwable $e) {
                $_SESSION['flash_erro_usuario'] = $e->getMessage() ?: 'Não foi possível salvar o usuário.';
            }
        }
    }

    // 2. EXCLUIR USUÁRIO
    if ($acao === 'excluir_usuario') {
        $usuario_id = (int)($_POST['usuario_id'] ?? 0);

        if ($usuario_id === $usuario_logado_id) {
            $_SESSION['flash_erro_usuario'] = 'Você não pode excluir a sua própria conta logada.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM usuarios WHERE usuario_id = ?');
                $stmt->execute([$usuario_id]);
                $_SESSION['flash_sucesso_usuario'] = 'Usuário excluído com sucesso.';
            } catch (Throwable $e) {
                $_SESSION['flash_erro_usuario'] = 'Não foi possível excluir o usuário (pode conter registros vinculados).';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

                
                <?php
                require_once '../components/user-menu.php';
                ?>
               

            </header>

            <section class="main">

                <?php if ($flashSucesso !== ''): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> <?= e($flashSucesso) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flashErro !== ''): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-xmark"></i> <?= e($flashErro) ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom: 20px;">
                    <h2 style="color: var(--secondary);"><i class="fa-solid fa-users-gear"></i> Usuários do Sistema</h2>
                    <button class="btn" onclick="abrirModalUsuario()"><i class="fa-solid fa-plus"></i> Novo Usuário</button>
                </div>

                <div class="box">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome Completo</th>
                                    <th>Login</th>
                                    <th>Cargo</th>
                                    <th style="width: 100px; text-align: center;">Ações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($usuarios)): ?>
                                    <?php foreach ($usuarios as $usuario): 
                                        $idUser = (int)$usuario['usuario_id'];
                                        $tipo = (string) ($usuario['tipo'] ?? 'Funcionario');
                                        $classeTipo = $tipo === 'Gerente' ? 'danger' : 'ok';
                                        $rotuloTipo = $tipo === 'Gerente' ? 'Gerente' : 'Funcionário';
                                        
                                        // Monta string JSON segura para passar para o JS do Modal
                                        $usuarioJson = htmlspecialchars(json_encode($usuario, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td><strong><?= e($usuario['nome'] ?? '') ?></strong></td>
                                            <td><?= e($usuario['login'] ?? '') ?></td>
                                            <td><span class="badge <?= e($classeTipo) ?>"><?= e($rotuloTipo) ?></span></td>
                                            <td style="display:flex; gap:6px; justify-content: center;">
                                                
                                                <button class="btn-icon" title="Editar Usuário" data-usuario='<?= $usuarioJson ?>' onclick="abrirModalUsuario(this)">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>

                                                <?php if ($idUser !== $usuario_logado_id): ?>
                                                    <form method="POST" onsubmit="return confirm('Tem certeza de que deseja excluir este usuário definitivamente?');" style="display:inline;">
                                                        <input type="hidden" name="acao" value="excluir_usuario">
                                                        <input type="hidden" name="usuario_id" value="<?= $idUser ?>">
                                                        <button type="submit" class="btn-icon danger" title="Excluir Usuário">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn-icon" style="opacity:0.3; cursor:not-allowed;" title="Você não pode excluir sua própria conta" disabled>
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                <?php endif; ?>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: 25px; color:#777;">Nenhum usuário cadastrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <div id="modalUsuario" class="modal">
        <div class="modal-content box" style="max-width: 500px; width: 100%;">
            
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px;">
                <h3 id="modalUsuarioTitulo" style="margin:0;">Novo Usuário</h3>
                <button type="button" class="btn btn-secondary" style="background:#f0f0f0; color:#555;" onclick="fecharModalUsuario()">Cancelar</button>
            </div>

            <form method="POST" action="user.php">
                <input type="hidden" name="acao" value="salvar_usuario">
                <input type="hidden" name="usuario_id" id="usuario_id">

                <div class="grid-form" style="grid-template-columns: 1fr; gap: 15px;">
                    <div class="form-group">
                        <label style="font-weight:bold; font-size:14px;">Nome Completo</label>
                        <input type="text" name="nome" id="user_nome" placeholder="Ex: Usuario" required>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:bold; font-size:14px;">Login de Acesso</label>
                        <input type="text" name="login" id="user_login" placeholder="Deixe em branco para automático">
                    </div>

                    <div class="form-group">
                        <label style="font-weight:bold; font-size:14px;">Cargo / Nível</label>
                        <select name="tipo" id="user_tipo" required>
                            <option value="">Selecione o cargo</option>
                            <option value="Funcionario">Funcionário</option>
                            <option value="Gerente">Gerente</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:25px; display:flex; justify-content:flex-end;">
                    <button class="btn" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/user-menu.js"></script>
    
    <script>
        function abrirModalUsuario(btn = null) {
            const modal = document.getElementById('modalUsuario');
            const form = modal.querySelector('form');
            const titulo = document.getElementById('modalUsuarioTitulo');
            
            form.reset();
            document.getElementById('usuario_id').value = '';
            titulo.innerText = 'Novo Usuário';

            // Se o botão de edição foi clicado, preenche os campos dinamicamente
            if (btn && btn.dataset.usuario) {
                const u = JSON.parse(btn.dataset.usuario);
                titulo.innerText = 'Editar Usuário';
                document.getElementById('usuario_id').value = u.usuario_id;
                document.getElementById('user_nome').value = u.nome;
                document.getElementById('user_login').value = u.login;
                document.getElementById('user_tipo').value = u.tipo;
            }

            modal.style.display = 'flex';
        }

        function fecharModalUsuario() {
            const modal = document.getElementById('modalUsuario');
            if (modal) modal.style.display = 'none';
        }

        // Fechar clicando fora do box branco do modal
        window.onclick = function (event) {
            const modal = document.getElementById('modalUsuario');
            if (event.target === modal) {
                fecharModalUsuario();
            }
        };

        // Fechar com a tecla ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                fecharModalUsuario();
            }
        });
    </script>

</body>
</html>