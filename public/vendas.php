<?php
session_start();
require_once '../components/valida-sessao.php';
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}


$nome_usuario = $_SESSION['nome'] ?? 'User';
$inicial = strtoupper(substr($nome_usuario, 0, 1));

$erro = '';

// ===================== PROCESSAMENTO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_finalizar'])) {

    $peso_total = filter_input(INPUT_POST, 'peso_total', FILTER_VALIDATE_FLOAT);
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $promocao_id = !empty($_POST['promocao_id']) ? (int) $_POST['promocao_id'] : null;
    $usuario_id = $_SESSION['usuario_id'];

    $itens = json_decode($_POST['itens'] ?? '[]', true);

    $formas_validas = ['Dinheiro', 'Pix', 'Cartao_Credito', 'Cartao_Debito'];
    if (!in_array($forma_pagamento, $formas_validas)) {
        $erro = 'Forma de pagamento inválida';
    }

    if ($peso_total > 0 && in_array($forma_pagamento, $formas_validas)) {

        try {
            $pdo->beginTransaction();

            // ===================== PREÇO DINÂMICO DO BANCO =====================
            $valor_total = 0;
            $sabores = array_filter($itens, fn($i) => $i['tipo'] === 'sabor');
            if (count($sabores) === 0) {
                throw new Exception("Selecione pelo menos um sabor");
            }

            foreach ($sabores as $item) {
                if (!empty($item['id'])) {
                    $stmt = $pdo->prepare("SELECT preco_venda FROM produtos WHERE produto_id = ?");
                    $stmt->execute([$item['id']]);

                    $preco = $stmt->fetchColumn();

                    if ($preco === false) {
                        throw new Exception("Produto não encontrado ou sem preço definido");
                    }

                    $qtdSabores = count($sabores);
                    $pesoPorSabor = $peso_total / $qtdSabores;
                    $valor_total += $pesoPorSabor * $preco;
                }
            }

            // ===================== PROMOÇÃO =====================
            $desconto = 0;
            if ($promocao_id) {
                $stmtPromo = $pdo->prepare("
                    SELECT desconto_percentual, desconto_valor 
                    FROM promocoes 
                    WHERE promocao_id = ?
                    AND ativo = 1
                    AND CURDATE() BETWEEN data_inicio AND data_fim
                ");
                $stmtPromo->execute([$promocao_id]);
                $promo = $stmtPromo->fetch();

                if ($promo) {
                    if (!empty($promo['desconto_percentual'])) {
                        $desconto = ($valor_total * $promo['desconto_percentual']) / 100;
                    } elseif (!empty($promo['desconto_valor'])) {
                        $desconto = $promo['desconto_valor'];
                    }
                }
            }

            $valor_final = max(0, $valor_total - $desconto);

            // ===================== VENDA =====================
            $sql = "INSERT INTO vendas 
                (usuario_id, peso_total, valor_total, desconto_aplicado, forma_pagamento, status, comprovante_gerado, promocao_id) 
                VALUES (?, ?, ?, ?, ?, 'Confirmado', 1, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $peso_total,
                $valor_final,
                $desconto,
                $forma_pagamento,
                $promocao_id
            ]);

            $venda_id = $pdo->lastInsertId();

            // ===================== ITENS ===================== (mantido igual)
            $sqlItem = "INSERT INTO itens_venda 
                (venda_id, produto_id, sabor, quantidade, adicionais, valor_unitario) 
                VALUES (?, ?, ?, ?, ?, ?)";

            $stmtItem = $pdo->prepare($sqlItem);

            $toppings = array_filter($itens, fn($i) => $i['tipo'] === 'topping');
            $qtdSabores = count($sabores);
            $pesoPorSabor = $qtdSabores > 0 ? $peso_total / $qtdSabores : 0;

            foreach ($sabores as $item) {
                $stmtItem->execute([$venda_id, $item['id'], $item['nome'], $pesoPorSabor, null, $preco]);
            }
            foreach ($toppings as $item) {
                $stmtItem->execute([$venda_id, $item['id'] ?? null, null, 0, $item['nome'], 0]);
            }

            // ===================== BAIXA DE ESTOQUE =====================
            foreach ($sabores as $item) {
                if (!empty($item['id'])) {
                    $stmtMov = $pdo->prepare("
                        INSERT INTO movimentacoes_estoque 
                        (produto_id, tipo_movimentacao, quantidade, custo_unitario, usuario_id, referencia_venda_id, observacao)
                        VALUES (?, 'Saida', ?, 70.00, ?, ?, ?)
                    ");
                    $stmtMov->execute([$item['id'], $pesoPorSabor, $usuario_id, $venda_id, "Venda #$venda_id - {$item['nome']}"]);
                }
            }

            // ===================== LOGS =====================
            $stmtLog = $pdo->prepare("
                INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                VALUES (?, 'INSERT', 'vendas', ?, ?)
            ");
            $stmtLog->execute([$usuario_id, $venda_id, "Venda #$venda_id - R$ " . number_format($valor_final, 2, ',', '.') . " - $forma_pagamento"]);

            $pdo->commit();

            $_SESSION['ultima_venda_id'] = $venda_id;
            $_SESSION['sucesso_venda'] = true;
            header("Location: vendas");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $erro = 'Erro ao salvar a venda.';
        }
    } else {
        $erro = 'Dados inválidos.';
    }
}

// ===================== DADOS =====================
$stmtSabores = $pdo->query("
    SELECT produto_id, nome, preco_venda 
    FROM produtos 
    WHERE categoria = 'Sorvete' AND ativo = 1 
    ORDER BY nome ASC
");
$sabores = $stmtSabores->fetchAll();

$stmtToppings = $pdo->query("SELECT produto_id, nome FROM produtos WHERE categoria = 'Adicionais' AND ativo = 1 ORDER BY nome ASC");
$toppings = $stmtToppings->fetchAll();

$stmtPromocoes = $pdo->query("
    SELECT promocao_id, nome, desconto_percentual, desconto_valor
    FROM promocoes 
    WHERE ativo = 1 AND CURDATE() BETWEEN data_inicio AND data_fim
    ORDER BY nome ASC
");
$promocoes = $stmtPromocoes->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Nova Venda</title>
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
                <h1>Visão Geral do Sistema</h1>

                <div class="user-menu">
                    <div class="avatar" onclick="toggleUserMenu()">
                        <?= $inicial ?>
                    </div>

                    <div class="dropdown-user" id="userDropdown">
                        <p><?= htmlspecialchars($nome_usuario) ?></p>
                        <a href="perfil">Perfil</a>
                        <a href="logout" class="logout">Sair do sistema</a>
                    </div>
                </div>
            </header>

            <section class="main">

                <?php if (isset($_SESSION['sucesso_venda'])): ?>
                    <div class="alert-custom">
                        <i class="fa-solid fa-circle-check"></i> Venda realizada com sucesso! <strong>Venda #<?= $_SESSION['ultima_venda_id'] ?? '' ?></strong>
                    </div>
                    <?php unset($_SESSION['sucesso_venda']); ?>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert alert-error"><?= $erro ?></div>
                <?php endif; ?>

                <div class="grid-main">

                    <div class="box">
                        <h3><i class="fa-solid fa-cart-shopping"></i> Montar Pedido</h3>

                        <div class="form-group">
                            <label><strong>1. Sabores:</strong></label>
                            <div class="items-grid">
                                <?php foreach ($sabores as $s): ?>
                                    <span class="item-badge"
                                        data-id="<?= $s['produto_id'] ?>"
                                        data-preco="<?= $s['preco_venda'] ?>"
                                        onclick="toggleItem(this)">
                                        <?= htmlspecialchars($s['nome']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:20px;">
                            <label><strong>2. Toppings:</strong></label>
                            <div class="items-grid">
                                <?php foreach ($toppings as $t): ?>
                                    <span class="item-badge topping"
                                        data-id="<?= $t['produto_id'] ?>"
                                        onclick="toggleItem(this)">
                                        <?= htmlspecialchars($t['nome']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <form method="POST" class="box card-resumo">

                            <h3><i class="fa-solid fa-receipt"></i> Resumo</h3>

                            <div id="resumo-itens">
                                <p>Nenhum item selecionado</p>
                            </div>

                            <input type="hidden" name="itens" id="itens-input">

                            <div class="form-group">
                                <label>Peso (kg):</label>
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

                            <div class="form-group">
                                <label>Promoção:</label>
                                <select name="promocao_id" class="select-field">
                                    <option value="">Nenhuma promoção</option>
                                    <?php foreach ($promocoes as $p): ?>
                                        <option
                                            value="<?= $p['promocao_id'] ?>"
                                            data-percent="<?= $p['desconto_percentual'] ?? 0 ?>"
                                            data-valor="<?= $p['desconto_valor'] ?? 0 ?>">
                                            <?= htmlspecialchars($p['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="btn_finalizar" class="btn btn-venda" onclick="prepararEnvio()">
                                Finalizar Venda
                            </button>

                            <!-- Botão de comprovante aparece só depois da venda -->
                            <?php if (isset($_SESSION['ultima_venda_id'])): ?>
                                <a href="comprovante?id=<?= $_SESSION['ultima_venda_id'] ?>"
                                    target="_blank"
                                    class="btn"
                                    style="width:100%; margin-top:12px; background:#28a745; color:white;">
                                    <i class="fa-solid fa-print"></i> Imprimir Comprovante
                                </a>
                            <?php endif; ?>

                        </form>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <script>
        /*         const PRECO_KILO = 70; */

        function toggleItem(el) {
            el.classList.toggle('active');
            atualizarResumo();
        }

        function calcularTotal() {
            let peso = parseFloat(document.getElementById('peso-venda').value) || 0;
            let total = 0;

            // Pega o preço real de cada sabor selecionado
            document.querySelectorAll('.item-badge.active').forEach(item => {
                if (!item.classList.contains('topping')) {
                    let preco = parseFloat(item.dataset.preco) || 70;
                    let qtdSabores = document.querySelectorAll('.item-badge.active:not(.topping)').length;
                    total += (peso / qtdSabores) * preco;
                }
            });

            let selectPromo = document.querySelector('[name="promocao_id"]');
            let desconto = 0;

            if (selectPromo && selectPromo.value) {
                let option = selectPromo.options[selectPromo.selectedIndex];
                let percent = parseFloat(option.dataset.percent) || 0;
                let valor = parseFloat(option.dataset.valor) || 0;

                if (percent > 0) desconto = (total * percent) / 100;
                else if (valor > 0) desconto = valor;
            }

            let final = Math.max(0, total - desconto);

            document.getElementById('valor-total-exibicao').innerText = 'R$ ' + final.toFixed(2).replace('.', ',');
            document.getElementById('valor_total_hidden').value = final.toFixed(2);
        }

        function atualizarResumo() {
            let itens = document.querySelectorAll('.item-badge.active');
            let container = document.getElementById('resumo-itens');

            if (itens.length === 0) {
                container.innerHTML = '<p>Nenhum item selecionado</p>';
                return;
            }

            container.innerHTML = '';
            itens.forEach(item => {
                container.innerHTML += `<span class="tag-resumo">${item.innerText}</span>`;
            });
        }

        function prepararEnvio() {
            let itens = [];
            let selecionados = document.querySelectorAll('.item-badge.active');

            selecionados.forEach(item => {
                itens.push({
                    id: item.dataset.id,
                    nome: item.innerText,
                    tipo: item.classList.contains('topping') ? 'topping' : 'sabor'
                });
            });

            document.getElementById('itens-input').value = JSON.stringify(itens);
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

        document.addEventListener('DOMContentLoaded', function() {
            const pesoInput = document.getElementById('peso-venda');
            const selectPromo = document.querySelector('[name="promocao_id"]');

            if (pesoInput) pesoInput.addEventListener('input', calcularTotal);
            if (selectPromo) selectPromo.addEventListener('change', calcularTotal);

            calcularTotal();
        });
    </script>

    <script src="ASSETS/JS/sidebar.js"></script>

</body>

</html>