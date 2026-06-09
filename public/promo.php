<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

$nome_usuario = $_SESSION['nome'] ?? 'User';
$tipo_usuario = $_SESSION['tipo'] ?? 'Funcionario';
$usuario_id = $_SESSION['usuario_id'];

$inicial = strtoupper(substr($nome_usuario, 0, 1));

$erro = '';
$sucesso = '';

// ======================================================
// PROCESSAMENTO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ======================================================
    // CRIAR PROMOÇÃO
    // ======================================================

    if (isset($_POST['btn_salvar'])) {

        if ($tipo_usuario !== 'Gerente') {

            $erro = 'Você não possui permissão.';
        } else {

            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);

            $desconto_percentual = !empty($_POST['desconto_percentual'])
                ? (float) $_POST['desconto_percentual']
                : null;

            $desconto_valor = !empty($_POST['desconto_valor'])
                ? (float) $_POST['desconto_valor']
                : null;

            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];

            // ======================================================
            // VALIDAÇÕES
            // ======================================================

            if (
                !empty($desconto_percentual) &&
                !empty($desconto_valor)
            ) {

                $erro = 'Escolha apenas UM tipo de desconto.';
            } elseif (
                empty($desconto_percentual) &&
                empty($desconto_valor)
            ) {

                $erro = 'Informe um desconto.';
            } elseif ($data_fim < $data_inicio) {

                $erro = 'A data final não pode ser menor que a inicial.';
            } else {

                try {

                    $sql = "
                        INSERT INTO promocoes
                        (
                            nome,
                            descricao,
                            desconto_percentual,
                            desconto_valor,
                            data_inicio,
                            data_fim,
                            ativo,
                            criado_por
                        )
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?)
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $nome,
                        $descricao,
                        $desconto_percentual,
                        $desconto_valor,
                        $data_inicio,
                        $data_fim,
                        $usuario_id
                    ]);

                    $promocao_id = $pdo->lastInsertId();

                    // LOG
                    $stmtLog = $pdo->prepare("
                        INSERT INTO logs_auditoria
                        (
                            usuario_id,
                            acao,
                            tabela_afetada,
                            registro_id,
                            descricao
                        )
                        VALUES (?, 'INSERT', 'promocoes', ?, ?)
                    ");

                    $stmtLog->execute([
                        $usuario_id,
                        $promocao_id,
                        "Promoção criada: $nome"
                    ]);

                    $_SESSION['sucesso'] = 'Promoção cadastrada com sucesso!';

                    header('Location: promo');
                    exit();
                } catch (Exception $e) {

                    $erro = 'Erro ao cadastrar promoção.';
                }
            }
        }
    }

    // ======================================================
    // EDITAR PROMOÇÃO
    // ======================================================

    if (isset($_POST['btn_editar'])) {

        if ($tipo_usuario !== 'Gerente') {

            $erro = 'Você não possui permissão.';
        } else {

            $promocao_id = (int) $_POST['promocao_id'];

            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);

            $desconto_percentual = !empty($_POST['desconto_percentual'])
                ? (float) $_POST['desconto_percentual']
                : null;

            $desconto_valor = !empty($_POST['desconto_valor'])
                ? (float) $_POST['desconto_valor']
                : null;

            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];

            // ======================================================
            // VALIDAÇÕES
            // ======================================================

            if (
                !empty($desconto_percentual) &&
                !empty($desconto_valor)
            ) {

                $erro = 'Escolha apenas UM tipo de desconto.';
            } elseif (
                empty($desconto_percentual) &&
                empty($desconto_valor)
            ) {

                $erro = 'Informe um desconto.';
            } elseif ($data_fim < $data_inicio) {

                $erro = 'A data final não pode ser menor que a inicial.';
            } else {

                try {

                    $sql = "
                        UPDATE promocoes
                        SET
                            nome = ?,
                            descricao = ?,
                            desconto_percentual = ?,
                            desconto_valor = ?,
                            data_inicio = ?,
                            data_fim = ?
                        WHERE promocao_id = ?
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $nome,
                        $descricao,
                        $desconto_percentual,
                        $desconto_valor,
                        $data_inicio,
                        $data_fim,
                        $promocao_id
                    ]);

                    // LOG
                    $stmtLog = $pdo->prepare("
                        INSERT INTO logs_auditoria
                        (
                            usuario_id,
                            acao,
                            tabela_afetada,
                            registro_id,
                            descricao
                        )
                        VALUES (?, 'UPDATE', 'promocoes', ?, ?)
                    ");

                    $stmtLog->execute([
                        $usuario_id,
                        $promocao_id,
                        "Promoção editada: $nome"
                    ]);

                    $_SESSION['sucesso'] = 'Promoção atualizada com sucesso!';

                    header('Location: promo');
                    exit();
                } catch (Exception $e) {

                    $erro = 'Erro ao editar promoção.';
                }
            }
        }
    }

    // ======================================================
    // DESATIVAR PROMOÇÃO
    // ======================================================

    if (isset($_POST['btn_excluir'])) {

        if ($tipo_usuario !== 'Gerente') {

            $erro = 'Você não possui permissão.';
        } else {

            $promocao_id = (int) $_POST['promocao_id'];

            try {

                $stmt = $pdo->prepare("
                    UPDATE promocoes
                    SET ativo = 0
                    WHERE promocao_id = ?
                ");

                $stmt->execute([$promocao_id]);

                // LOG
                $stmtLog = $pdo->prepare("
                    INSERT INTO logs_auditoria
                    (
                        usuario_id,
                        acao,
                        tabela_afetada,
                        registro_id,
                        descricao
                    )
                    VALUES (?, 'UPDATE', 'promocoes', ?, ?)
                ");

                $stmtLog->execute([
                    $usuario_id,
                    $promocao_id,
                    "Promoção desativada"
                ]);

                $_SESSION['sucesso'] = 'Promoção desativada com sucesso!';

                header('Location: promo');
                exit();
            } catch (Exception $e) {

                $erro = 'Erro ao desativar promoção.';
            }
        }
    }
}

// ======================================================
// MENSAGENS
// ======================================================

if (isset($_SESSION['sucesso'])) {

    $sucesso = $_SESSION['sucesso'];

    unset($_SESSION['sucesso']);
}

// ======================================================
// LISTAGEM
// ======================================================

$stmtPromocoes = $pdo->query("
    SELECT
        p.*,
        u.nome AS criado_por_nome
    FROM promocoes p
    LEFT JOIN usuarios u
        ON u.usuario_id = p.criado_por
    ORDER BY p.promocao_id DESC
");

$promocoes = $stmtPromocoes->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Gela-Gela | Promoções</title>

    <link rel="icon"
        type="image/png"
        href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">

    <link rel="stylesheet"
        href="ASSETS/CSS/style.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

                <h1>Gestão de Promoções</h1>

                <div class="user-menu">

                    <div class="avatar"
                        onclick="toggleUserMenu()">

                        <?= $inicial ?>

                    </div>

                    <div class="dropdown-user" id="userDropdown">

                        <p><?= htmlspecialchars($nome_usuario) ?></p>

                        <a href="perfil">Perfil</a>

                        <a href="logout" class="logout">
                            Sair
                        </a>

                    </div>

                </div>

            </header>

            <section class="main">

                <?php if ($erro): ?>

                    <div class="alert alert-danger">
                        <?= $erro ?>
                    </div>

                <?php endif; ?>

                <?php if ($sucesso): ?>

                    <div class="alert alert-success">
                        <?= $sucesso ?>
                    </div>

                <?php endif; ?>

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:25px;
                ">

                    <h2 style="color: var(--secondary);">
                        <i class="fa-solid fa-tags"></i>
                        Promoções
                    </h2>

                    <?php if ($tipo_usuario === 'Gerente'): ?>

                        <button class="btn"
                            onclick="abrirModalPromo()">

                            <i class="fa-solid fa-plus"></i>
                            Nova Promoção

                        </button>

                    <?php endif; ?>

                </div>

                <!-- TABELA -->
                <div class="box">

                    <div class="table-container">

                        <table>

                            <thead>

                                <tr>

                                    <th>Nome</th>
                                    <th>Desconto</th>
                                    <th>Descrição</th>
                                    <th>Validade</th>
                                    <th>Status</th>
                                    <th>Criado Por</th>

                                    <?php if ($tipo_usuario === 'Gerente'): ?>
                                        <th>Ações</th>
                                    <?php endif; ?>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($promocoes as $promo): ?>

                                    <?php

                                    date_default_timezone_set('America/Sao_Paulo');

                                    $status = 'Ativa';

                                    // Data final REAL da promoção
                                    $dataExpiracao = new DateTime(
                                        $promo['data_fim'] . ' 23:59:59'
                                    );

                                    // Data/hora atual
                                    $agora = new DateTime();

                                    if (!$promo['ativo']) {

                                        $status = 'Desativada';
                                    } elseif ($agora > $dataExpiracao) {

                                        // Só expira depois de passar 23:59:59
                                        $status = 'Expirada';
                                    }

                                    ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars($promo['nome']) ?>
                                        </td>

                                        <td>

                                            <?php if (!empty($promo['desconto_percentual'])): ?>

                                                <?= $promo['desconto_percentual'] ?>%

                                            <?php else: ?>

                                                R$
                                                <?= number_format($promo['desconto_valor'], 2, ',', '.') ?>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= htmlspecialchars($promo['descricao']) ?>
                                        </td>

                                        <td>
                                            <?= date('d/m/Y', strtotime($promo['data_fim'])) ?>
                                        </td>

                                        <td>

                                            <?php if ($status === 'Ativa'): ?>

                                                <span class="badge ok">
                                                    <?= $status ?>
                                                </span>

                                            <?php else: ?>

                                                <span class="badge danger">
                                                    <?= $status ?>
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= htmlspecialchars($promo['criado_por_nome']) ?>
                                        </td>

                                        <?php if ($tipo_usuario === 'Gerente'): ?>

                                            <td style="display:flex; gap:8px;">

                                                <?php if ($promo['ativo']): ?>

                                                    <!-- EDITAR -->
                                                    <button
                                                        type="button"
                                                        class="btn-icon"
                                                        onclick='abrirEditar(
                                                            <?= $promo["promocao_id"] ?>,
                                                            <?= json_encode($promo["nome"]) ?>,
                                                            <?= json_encode($promo["descricao"]) ?>,
                                                            <?= json_encode($promo["desconto_percentual"]) ?>,
                                                            <?= json_encode($promo["desconto_valor"]) ?>,
                                                            <?= json_encode($promo["data_inicio"]) ?>,
                                                            <?= json_encode($promo["data_fim"]) ?>
                                                        )'>

                                                        <i class="fa-solid fa-pen"></i>

                                                    </button>

                                                    <!-- DESATIVAR -->
                                                    <form method="POST"
                                                        onsubmit="return confirm('Deseja desativar esta promoção?');">

                                                        <input type="hidden"
                                                            name="promocao_id"
                                                            value="<?= $promo['promocao_id'] ?>">

                                                        <button type="submit"
                                                            name="btn_excluir"
                                                            class="btn-icon danger">

                                                            <i class="fa-solid fa-trash"></i>

                                                        </button>

                                                    </form>

                                                <?php else: ?>

                                                    <span style="color:gray;">
                                                        Inativa
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        <?php endif; ?>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        </main>

    </div>

    <!-- MODAL -->
    <?php if ($tipo_usuario === 'Gerente'): ?>

        <div id="modalPromo" class="modal">

            <div class="modal-content box">

                <h3 id="tituloModal">
                    Cadastrar Promoção
                </h3>

                <form method="POST" autocomplete="off">

                    <input type="hidden"
                        name="promocao_id"
                        id="promocao_id">

                    <div class="grid-form">

                        <div class="form-group">

                            <label>Nome da Promoção</label>

                            <input type="text"
                                name="nome"
                                id="nome"
                                required>

                        </div>

                        <div class="form-group">

                            <label>Descrição</label>

                            <input type="text"
                                name="descricao"
                                id="descricao">

                        </div>

                        <div class="form-group">

                            <label>Desconto (%)</label>

                            <input type="number"
                                step="0.01"
                                min="0"
                                name="desconto_percentual"
                                id="desconto_percentual">

                        </div>

                        <div class="form-group">

                            <label>Desconto Fixo (R$)</label>

                            <input type="number"
                                step="0.01"
                                min="0"
                                name="desconto_valor"
                                id="desconto_valor">

                        </div>

                        <div class="form-group">

                            <label>Data Início</label>

                            <input type="date"
                                name="data_inicio"
                                id="data_inicio"
                                required>

                        </div>

                        <div class="form-group">

                            <label>Data Fim</label>

                            <input type="date"
                                name="data_fim"
                                id="data_fim"
                                required>

                        </div>

                    </div>

                    <div style="
                        margin-top:20px;
                        display:flex;
                        justify-content:flex-end;
                        gap:10px;
                    ">

                        <button type="button"
                            class="btn btn-secondary"
                            onclick="fecharModalPromo()">

                            Cancelar

                        </button>

                        <button type="submit"
                            id="btnSubmit"
                            name="btn_salvar"
                            class="btn">

                            Salvar Promoção

                        </button>

                    </div>

                </form>

            </div>

        </div>

    <?php endif; ?>

    <script src="ASSETS/JS/sidebar.js"></script>

    <script>
        const descontoPercentual =
            document.getElementById('desconto_percentual');

        const descontoValor =
            document.getElementById('desconto_valor');

        // ======================================================
        // BLOQUEIO ENTRE % E R$
        // ======================================================

        descontoPercentual.addEventListener('input', function() {

            if (this.value !== '') {

                descontoValor.disabled = true;
                descontoValor.value = '';

            } else {

                descontoValor.disabled = false;
            }
        });

        descontoValor.addEventListener('input', function() {

            if (this.value !== '') {

                descontoPercentual.disabled = true;
                descontoPercentual.value = '';

            } else {

                descontoPercentual.disabled = false;
            }
        });

        // ======================================================
        // MODAL NOVO
        // ======================================================

        function abrirModalPromo() {

            document.getElementById('modalPromo').style.display = 'flex';

            document.getElementById('tituloModal').innerText =
                'Cadastrar Promoção';

            document.getElementById('promocao_id').value = '';

            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';

            descontoPercentual.value = '';
            descontoValor.value = '';

            descontoPercentual.disabled = false;
            descontoValor.disabled = false;

            document.getElementById('data_inicio').value = '';
            document.getElementById('data_fim').value = '';

            const btn = document.getElementById('btnSubmit');

            btn.name = 'btn_salvar';
            btn.innerText = 'Salvar Promoção';
        }

        // ======================================================
        // MODAL EDITAR
        // ======================================================

        function abrirEditar(
            id,
            nome,
            descricao,
            descontoPercentualValor,
            descontoValorFixo,
            dataInicio,
            dataFim
        ) {

            document.getElementById('modalPromo').style.display = 'flex';

            document.getElementById('tituloModal').innerText =
                'Editar Promoção';

            document.getElementById('promocao_id').value = id;

            document.getElementById('nome').value = nome;

            document.getElementById('descricao').value = descricao;

            descontoPercentual.disabled = false;
            descontoValor.disabled = false;

            descontoPercentual.value =
                descontoPercentualValor ?? '';

            descontoValor.value =
                descontoValorFixo ?? '';

            // BLOQUEIA O OUTRO INPUT
            if (
                descontoPercentualValor !== null &&
                descontoPercentualValor !== ''
            ) {

                descontoValor.disabled = true;
            }

            if (
                descontoValorFixo !== null &&
                descontoValorFixo !== ''
            ) {

                descontoPercentual.disabled = true;
            }

            document.getElementById('data_inicio').value =
                dataInicio;

            document.getElementById('data_fim').value =
                dataFim;

            const btn = document.getElementById('btnSubmit');

            btn.name = 'btn_editar';

            btn.innerText = 'Salvar Alterações';
        }

        // ======================================================
        // FECHAR MODAL
        // ======================================================

        function fecharModalPromo() {

            document.getElementById('modalPromo').style.display =
                'none';
        }

        window.onclick = function(event) {

            const modal = document.getElementById('modalPromo');

            if (event.target == modal) {

                modal.style.display = 'none';
            }
        }

        // ======================================================
        // USER MENU
        // ======================================================

        function toggleUserMenu() {

            document.getElementById('userDropdown')
                .classList.toggle('active');
        }

        document.addEventListener('click', function(e) {

            const menu = document.querySelector('.user-menu');

            if (!menu.contains(e.target)) {

                document.getElementById('userDropdown')
                    .classList.remove('active');
            }
        });
    </script>

</body>

</html>