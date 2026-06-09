<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

$nome_usuario = $_SESSION['nome'] ?? 'Funcionário';

// ====================== PROCESSAR FORMULÁRIOS ======================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? null;

    // Responder ou Atualizar Resposta
    if ($acao === 'responder') {
        $feedback_id = (int)$_POST['feedback_id'];
        $resposta    = trim($_POST['resposta'] ?? '');

        if ($feedback_id && !empty($resposta)) {
            $stmt = $pdo->prepare("UPDATE feedbacks_clientes 
                                   SET observacao_resolucao = ?, 
                                       resolvido = 1 
                                   WHERE feedback_id = ?");
            $stmt->execute([$resposta, $feedback_id]);

            header("Location: clientes?msg=respondido");
            exit;
        }
    }

    // Excluir
if ($acao === 'excluir') {
    if ($_SESSION['tipo'] !== 'Gerente') {
        die('Acesso negado');
    }

    $feedback_id = (int)$_POST['feedback_id'];

    if ($feedback_id) {
        $stmt = $pdo->prepare("DELETE FROM feedbacks_clientes WHERE feedback_id = ?");
        $stmt->execute([$feedback_id]);

        header("Location: clientes?msg=excluido");
        exit;
    }
}
}

// ====================== FILTROS ======================
$status_filtro = $_GET['status'] ?? 'todos';
$tipo_filtro   = $_GET['tipo'] ?? 'todos';

// ====================== BUSCAR DADOS ======================
$sql = "SELECT * FROM feedbacks_clientes WHERE 1=1";

if ($status_filtro === 'pendentes') {
    $sql .= " AND resolvido = 0";
} elseif ($status_filtro === 'respondidos') {
    $sql .= " AND resolvido = 1";
}

if ($tipo_filtro !== 'todos') {
    $sql .= " AND tipo = ?";
}

$sql .= " ORDER BY data_registro DESC";

$stmt = $pdo->prepare($sql);

if ($tipo_filtro !== 'todos') {
    $stmt->execute([$tipo_filtro]);
} else {
    $stmt->execute();
}

$feedbacks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Feedbacks / Reclame Ali</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .alert-custom {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            background: #e6f4ea;
            color: #137333;
            border-left: 5px solid #137333;
            animation: fadeIn 0.3s ease;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }

        .filtros {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filtro-grupo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filtro-grupo label {
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }

        .filtro-grupo select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            min-width: 180px;
        }

        .btn-limpar {
            padding: 10px 16px;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-limpar:hover {
            background: #e9ecef;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge.pend {
            background: #fff3cd;
            color: #856404;
        }

        .badge.ok {
            background: #d4edda;
            color: #155724;
        }

        .btn-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            color: #555;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <?php require_once '../components/sidebar.php'; ?>

        <!-- CONTEÚDO -->
        <main class="content">

            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Reclame Ali - Feedbacks dos Clientes</h1>

                <div class="user-menu">
                    <div class="avatar" onclick="toggleUserMenu()"><?= strtoupper(substr($nome_usuario, 0, 1)) ?></div>
                    <div class="dropdown-user" id="userDropdown">
                        <p><?= htmlspecialchars($nome_usuario) ?></p>
                        <a href="perfil">Perfil</a>
                        <a href="logout" class="logout">Sair</a>
                    </div>
                </div>
            </header>

            <section class="main">

                <?php if (isset($_GET['msg'])):
                    $texto = $_GET['msg'] === 'respondido' ? '💙 Resposta enviada/atualizada com sucesso!' : '❄️ Feedback removido com sucesso!';
                ?>
                    <div class="alert-custom">
                        <?= $texto ?>
                    </div>
                <?php endif; ?>

                <!-- Cabeçalho Melhorado -->
                <div class="page-header">
                    <h2 style="color: var(--secondary);">
                        <i class="fa-solid fa-comment-dots"></i> Feedbacks dos Clientes
                    </h2>

                    <div class="filtros">
                        <div class="filtro-grupo">
                            <label for="status">Status</label>
                            <select id="status" onchange="aplicarFiltros()">
                                <option value="todos" <?= $status_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                                <option value="pendentes" <?= $status_filtro === 'pendentes' ? 'selected' : '' ?>>Pendentes</option>
                                <option value="respondidos" <?= $status_filtro === 'respondidos' ? 'selected' : '' ?>>Respondidos</option>
                            </select>
                        </div>

                        <div class="filtro-grupo">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" onchange="aplicarFiltros()">
                                <option value="todos" <?= $tipo_filtro === 'todos' ? 'selected' : '' ?>>Todos os tipos</option>
                                <option value="Duvida" <?= $tipo_filtro === 'Duvida' ? 'selected' : '' ?>>Dúvida</option>
                                <option value="Reclamacao" <?= $tipo_filtro === 'Reclamacao' ? 'selected' : '' ?>>Reclamação</option>
                                <option value="Sugestao" <?= $tipo_filtro === 'Sugestao' ? 'selected' : '' ?>>Sugestão</option>
                                <option value="Elogio" <?= $tipo_filtro === 'Elogio' ? 'selected' : '' ?>>Elogio</option>
                            </select>
                        </div>

                        <button class="btn-limpar" onclick="limparFiltros()">
                            <i class="fa-solid fa-rotate"></i> Limpar Filtros
                        </button>
                    </div>
                </div>

                <div class="box">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Resposta</th>
                                    <th>Nota</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbacks)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding:50px; color:#666;">
                                            Nenhum feedback encontrado com os filtros selecionados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedbacks as $f): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($f['data_registro'])) ?></td>
                                            <td><span class="badge"><?= htmlspecialchars($f['tipo']) ?></span></td>
                                            <td><?= htmlspecialchars($f['descricao']) ?></td>
                                            <td>
                                                <?= !empty($f['observacao_resolucao'])
                                                    ? '<span style="color:#137333;">' . htmlspecialchars($f['observacao_resolucao']) . '</span>'
                                                    : '<span style="color:#999;">❄️ Sem resposta ainda</span>' ?>
                                            </td>
                                            <td><?= $f['nota'] ? $f['nota'] . '/5' : '-' ?></td>
                                            <td>
                                                <?= $f['resolvido']
                                                    ? '<span class="badge ok">Respondido</span>'
                                                    : '<span class="badge pend">Pendente</span>' ?>
                                            </td>
                                            <td>
                                                <div style="display:flex; gap:6px;">
                                                    <!-- Botão Responder / Editar -->
                                                    <button class="btn-icon"
                                                        title="<?= $f['resolvido'] ? 'Editar resposta' : 'Responder feedback' ?>"
                                                        data-id="<?= $f['feedback_id'] ?>"
                                                        data-desc="<?= htmlspecialchars($f['descricao'], ENT_QUOTES) ?>"
                                                        data-resposta="<?= htmlspecialchars($f['observacao_resolucao'] ?? '', ENT_QUOTES) ?>"
                                                        onclick="abrirModalResposta(this)">
                                                        <i class="fa-solid <?= $f['resolvido'] ? 'fa-edit' : 'fa-reply' ?>"></i>
                                                    </button>

                                                    <!-- Excluir -->
                                                    <?php if ($_SESSION['tipo'] === 'Gerente'): ?>
                                                        <form method="POST" onsubmit="return confirmarExclusao()">
                                                            <input type="hidden" name="acao" value="excluir">
                                                            <input type="hidden" name="feedback_id" value="<?= $f['feedback_id'] ?>">
                                                            <button type="submit" class="btn-icon" style="color:#c22;" title="Excluir feedback">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL RESPONDER / EDITAR -->
    <div id="modalResposta" class="modal">
        <div class="modal-content">
            <h3 id="modal-titulo"><i class="fa-solid fa-reply"></i> Responder Feedback</h3>
            <p id="feedback-descricao" style="margin-bottom:20px; font-style:italic; color:#555;"></p>

            <form method="POST">
                <input type="hidden" name="acao" value="responder">
                <input type="hidden" name="feedback_id" id="resposta_feedback_id">

                <div class="form-group">
                    <label>Sua Resposta</label>
                    <textarea name="resposta" id="resposta-textarea" rows="7" required
                        placeholder="Escreva ou edite a resposta para o cliente..."></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:25px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalResposta()">Cancelar</button>
                    <button type="submit" class="btn">Salvar Resposta</button>
                </div>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>

    <script>
        function aplicarFiltros() {
            const status = document.getElementById('status').value;
            const tipo = document.getElementById('tipo').value;

            let url = 'clientes?';
            if (status !== 'todos') url += 'status=' + encodeURIComponent(status) + '&';
            if (tipo !== 'todos') url += 'tipo=' + encodeURIComponent(tipo);

            window.location.href = url.replace(/&$/, '');
        }

        function limparFiltros() {
            window.location.href = 'clientes';
        }

        // Modal - agora suporta edição
        function abrirModalResposta(btn) {
            const id = btn.getAttribute('data-id');
            const descricao = btn.getAttribute('data-desc');
            const respostaExistente = btn.getAttribute('data-resposta') || '';

            document.getElementById('resposta_feedback_id').value = id;
            document.getElementById('feedback-descricao').textContent = descricao;
            document.getElementById('resposta-textarea').value = respostaExistente;

            // Atualiza título do modal
            const titulo = document.getElementById('modal-titulo');
            titulo.innerHTML = respostaExistente ?
                '<i class="fa-solid fa-edit"></i> Editar Resposta' :
                '<i class="fa-solid fa-reply"></i> Responder Feedback';

            document.getElementById('modalResposta').style.display = 'flex';
        }

        function fecharModalResposta() {
            document.getElementById('modalResposta').style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }

        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            const menu = document.querySelector('.user-menu');
            if (!menu.contains(e.target)) {
                document.getElementById('userDropdown').classList.remove('active');
            }
        });

        function confirmarExclusao() {
            return confirm("Tem certeza que deseja excluir este feedback?");
        }
    </script>

</body>

</html>