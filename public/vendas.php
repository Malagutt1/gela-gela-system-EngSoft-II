<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../login');
    exit();
}

$nome_usuario = $_SESSION['nome'] ?? 'User';
$inicial = strtoupper(substr($nome_usuario, 0, 1));

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_finalizar'])) {
    $peso_total = filter_input(INPUT_POST, 'peso_total', FILTER_VALIDATE_FLOAT);
    $valor_total = filter_input(
        INPUT_POST,
        'valor_total_hidden',
        FILTER_VALIDATE_FLOAT
    );
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $usuario_id = $_SESSION['usuario_id'];

    if ($peso_total > 0 && $valor_total > 0 && !empty($forma_pagamento)) {
        try {
            $sql = "INSERT INTO vendas (usuario_id, peso_total, valor_total, forma_pagamento, status, comprovante_gerado) 
                    VALUES (?, ?, ?, ?, 'Confirmado', 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $peso_total,
                $valor_total,
                $forma_pagamento,
            ]);
            $mensagem = 'Venda registrada com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar a venda: ' . $e->getMessage();
        }
    } else {
        $erro =
            'Por favor, insira um peso válido e escolha a forma de pagamento.';
    }
}

$stmtSabores = $pdo->query(
    "SELECT produto_id, nome FROM produtos WHERE categoria = 'Sorvete' AND ativo = 1 ORDER BY nome ASC"
);
$sabores = $stmtSabores->fetchAll();

$stmtToppings = $pdo->query(
    "SELECT produto_id, nome FROM produtos WHERE categoria = 'Adicionais' AND ativo = 1 ORDER BY nome ASC"
);
$toppings = $stmtToppings->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Nova Venda</title>

    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="../ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <div class="layout">

        <aside class="sidebar" id="sidebar">
            <div class="logo-area">
                <img src="../ASSETS/IMG/icon.png" alt="Logo">
                <span>Gela-Gela</span>
            </div>

            <nav>
                <a href="vendas" class="active"><i class="fa-solid fa-cart-shopping"></i> Nova Venda</a>
                <a href="dashboard"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                <a href="produtos"><i class="fa-solid fa-boxes-stacked"></i> Produtos</a>
                <a href="clientes"><i class="fa-solid fa-users"></i> Clientes</a>
                <a href="fornecedores"><i class="fa-solid fa-truck"></i> Fornecedores</a>
                <a href="promo"><i class="fa-solid fa-tags"></i> Promoções</a>
                <a href="user"><i class="fa-solid fa-user-shield"></i> Usuários</a>
                <a href="backup"><i class="fa-solid fa-database"></i> Backup</a>
                <a href="logs"><i class="fa-solid fa-file-lines"></i> Logs</a>
                <a href="relatorio"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
            </nav>
        </aside>

        <main class="content">

            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Visão Geral do Sistema</h1>

                <div class="user-menu">
                    <div class="avatar" onclick="toggleUserMenu()">
                        <?= $inicial ?>
                    </div>

                    <div class="dropdown-user" id="userDropdown">
                        <p><?= htmlspecialchars($nome_usuario) ?></p>
                        <a href="../logout" class="logout">Sair do sistema</a>
                    </div>
                </div>
            </header>

            <section class="main">

                <?php if ($mensagem): ?>
                    <div class="alert alert-success" style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= $mensagem ?></span>
                    </div>
                <?php endif; ?>
                <?php if (
                    $erro
                ): ?><div class="alert alert-error"><?= $erro ?></div><?php endif; ?>

                <div class="grid-main">

                    <div class="box">
                        <h3><i class="fa-solid fa-cart-shopping"></i> Montar Pedido</h3>

                        <div class="form-group">
                            <label><strong>1. Escolha os Sabores:</strong></label>
                            <div class="items-grid" id="sabores-grid">
                                <?php foreach ($sabores as $s): ?>
                                    <span class="item-badge" onclick="toggleItem(this)">
                                        <?= htmlspecialchars($s['nome']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label><strong>2. Escolha os Toppings:</strong></label>
                            <div class="items-grid" id="toppings-grid">
                                <?php foreach ($toppings as $t): ?>
                                    <span class="item-badge topping" onclick="toggleItem(this)">
                                        <?= htmlspecialchars($t['nome']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <form method="POST" class="box card-resumo">

                            <h3><i class="fa-solid fa-receipt"></i> Resumo</h3>

                            <div id="resumo-itens" class="resumo-lista">
                                <p id="texto-vazio">Nenhum item selecionado</p>
                            </div>

                            <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--soft);">

                            <div class="form-group">
                                <label>Peso do Prato (kg):</label>
                                <input type="number" id="peso-venda" name="peso_total" step="0.001" required oninput="calcularTotal()">
                            </div>

                            <div class="total-display">
                                <span>Total a Pagar:</span>
                                <strong id="valor-total-exibicao">R$ 0,00</strong>
                            </div>

                            <input type="hidden" id="valor_total_hidden" name="valor_total_hidden">

                            <div class="form-group">
                                <label>Forma de Pagamento:</label>
                                <select name="forma_pagamento" class="select-field" required>
                                    <option value="Dinheiro">Dinheiro</option>
                                    <option value="Pix">Pix</option>
                                    <option value="Cartao_Credito">Cartão de Crédito</option>
                                    <option value="Cartao_Debito">Cartão de Débito</option>
                                </select>
                            </div>

                            <button type="submit" name="btn_finalizar" class="btn btn-venda">
                                <i class="fa-solid fa-check"></i> Finalizar Venda
                            </button>

                        </form>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script>
        const PRECO_KILO = 70;

        function toggleItem(el) {
            el.classList.toggle('active');
            atualizarResumo();
        }

        function calcularTotal() {
            let peso = parseFloat(document.getElementById('peso-venda').value) || 0;
            let total = peso * PRECO_KILO;

            document.getElementById('valor-total-exibicao').innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
            document.getElementById('valor_total_hidden').value = total.toFixed(2);
        }

        function atualizarResumo() {
            let itens = document.querySelectorAll('.item-badge.active');
            let container = document.getElementById('resumo-itens');

            if (itens.length === 0) {
                container.innerHTML = '<p id="texto-vazio">Nenhum item selecionado</p>';
                return;
            }

            container.innerHTML = '';
            itens.forEach(item => {
                container.innerHTML += `<span class="tag-resumo">${item.innerText}</span>`;
            });
        }

        // ✅ MENU DO USUÁRIO (FORA DO RESUMO)
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('active');
        }

        // ✅ FECHAR AO CLICAR FORA
        document.addEventListener('click', function(e) {
            const menu = document.querySelector('.user-menu');
            if (!menu.contains(e.target)) {
                document.getElementById('userDropdown').classList.remove('active');
            }
        });
    </script>

    <script src="../ASSETS/JS/sidebar.js"></script>

</body>

</html>