<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$nome_usuario = $_SESSION['nome'] ?? 'Usuário';
$tipo_usuario = $_SESSION['tipo'] ?? 'Funcionario';
$isGerente = $tipo_usuario === 'Gerente';
$inicial = strtoupper(substr($nome_usuario, 0, 1));

$categorias = ['Sorvete', 'Insumo', 'Ingrediente', 'Consumivel', 'Embalagem', 'Coberturas', 'Adicionais'];
$unidades = ['kg', 'g', 'lt', 'ml', 'un', 'cx', 'pct'];

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function parse_decimal($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') return null;
    return (float) str_replace(',', '.', $valor);
}

$erro = '';
$sucesso = '';

if (isset($_SESSION['sucesso'])) {
    $sucesso = $_SESSION['sucesso'];
    unset($_SESSION['sucesso']);
}
if (isset($_SESSION['erro'])) {
    $erro = $_SESSION['erro'];
    unset($_SESSION['erro']);
}

// ======================================================
// PROCESSAMENTO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        // ======================================================
        // ATIVAR / DESATIVAR PRODUTO
        // ======================================================
        if ($acao === 'toggle_status') {
            if (!$isGerente) {
                throw new Exception('Apenas o gerente pode alterar o status de produtos.');
            }

            $produto_id = (int)($_POST['produto_id'] ?? 0);
            $status_atual = (int)($_POST['status_atual'] ?? 0);
            $novo_status = $status_atual === 1 ? 0 : 1;

            $stmt = $pdo->prepare('UPDATE produtos SET ativo = ? WHERE produto_id = ?');
            $stmt->execute([$novo_status, $produto_id]);

            $acaoLog = $novo_status === 1 ? 'Ativado' : 'Desativado';
            $stmtLog = $pdo->prepare("
                INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                VALUES (?, 'UPDATE', 'produtos', ?, ?)
            ");
            $stmtLog->execute([$usuario_id, $produto_id, "Produto {$acaoLog}"]);

            $_SESSION['sucesso'] = "Status do produto atualizado com sucesso.";
            header('Location: produtos');
            exit();
        }

        // ======================================================
        // SALVAR PRODUTO
        // ======================================================
        if ($acao === 'salvar_produto') {
            $produto_id = (int)($_POST['produto_id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $preco_custo = parse_decimal($_POST['preco_custo'] ?? '');
            $preco_venda_input = parse_decimal($_POST['preco_venda'] ?? '');
            $unidade_medida = trim($_POST['unidade_medida'] ?? '');
            $quantidade_disponivel = parse_decimal($_POST['quantidade_disponivel'] ?? '');
            $validade = trim($_POST['validade'] ?? '');
            $fornecedor_id = trim($_POST['fornecedor_id'] ?? '');

            if ($nome === '' || !in_array($categoria, $categorias, true) || $preco_custo === null || $preco_custo < 0 || $unidade_medida === '' || $quantidade_disponivel === null || $quantidade_disponivel < 0) {
                throw new Exception('Preencha os campos obrigatórios corretamente.');
            }

            $fornecedorIdBanco = $fornecedor_id !== '' ? (int)$fornecedor_id : null;

            // EDITAR
            if ($produto_id > 0) {
                $stmtAtual = $pdo->prepare('SELECT * FROM produtos WHERE produto_id = ?');
                $stmtAtual->execute([$produto_id]);
                $produtoAtual = $stmtAtual->fetch();
                
                if (!$produtoAtual) throw new Exception('Produto não encontrado.');

                $stmtEstoque = $pdo->prepare('SELECT * FROM estoque WHERE produto_id = ? ORDER BY estoque_id DESC LIMIT 1');
                $stmtEstoque->execute([$produto_id]);
                $estoqueAtual = $stmtEstoque->fetch();
                $estoqueId = $estoqueAtual['estoque_id'] ?? null;
                $quantidadeAnterior = $estoqueAtual ? (float)$estoqueAtual['quantidade_disponivel'] : 0.0;

                $preco_venda = $isGerente ? $preco_venda_input : $produtoAtual['preco_venda'];

                $pdo->beginTransaction();

                $stmt = $pdo->prepare('
                    UPDATE produtos
                    SET nome = ?, categoria = ?, preco_custo = ?, preco_venda = ?, unidade_medida = ?
                    WHERE produto_id = ?
                ');
                $stmt->execute([$nome, $categoria, $preco_custo, $preco_venda, $unidade_medida, $produto_id]);

                if ($estoqueId) {
                    $stmt = $pdo->prepare('
                        UPDATE estoque
                        SET quantidade_disponivel = ?, validade = ?, custo_medio = ?, fornecedor_id = ?, data_ultima_atualizacao = CURRENT_TIMESTAMP
                        WHERE estoque_id = ?
                    ');
                    $stmt->execute([$quantidade_disponivel, $validade !== '' ? $validade : null, $preco_custo, $fornecedorIdBanco, $estoqueId]);
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO estoque (produto_id, quantidade_disponivel, validade, custo_medio, fornecedor_id)
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$produto_id, $quantidade_disponivel, $validade !== '' ? $validade : null, $preco_custo, $fornecedorIdBanco]);
                }

                $diferenca = round((float)$quantidade_disponivel - $quantidadeAnterior, 3);
                if (abs($diferenca) > 0.0001) {
                    $stmtMov = $pdo->prepare("
                        INSERT INTO movimentacoes_estoque
                        (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                        VALUES (?, 'Ajuste', ?, ?, ?, ?, 'Ajuste manual')
                    ");
                    $stmtMov->execute([$produto_id, abs($diferenca), $preco_custo, $fornecedorIdBanco, $usuario_id]);
                }

                $stmtLog = $pdo->prepare("INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao) VALUES (?, 'UPDATE', 'produtos', ?, ?)");
                $stmtLog->execute([$usuario_id, $produto_id, "Produto atualizado: $nome"]);

                $pdo->commit();
                $_SESSION['sucesso'] = 'Produto atualizado com sucesso.';
                header('Location: produtos');
                exit();
            }

            // CRIAR NOVO
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO produtos (nome, categoria, preco_custo, preco_venda, unidade_medida, criado_por, ativo)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([$nome, $categoria, $preco_custo, $preco_venda_input, $unidade_medida, $usuario_id]);
            $novoProdutoId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('
                INSERT INTO estoque (produto_id, quantidade_disponivel, validade, custo_medio, fornecedor_id)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$novoProdutoId, $quantidade_disponivel, $validade !== '' ? $validade : null, $preco_custo, $fornecedorIdBanco]);

            if ($quantidade_disponivel > 0) {
                $stmtMov = $pdo->prepare("
                    INSERT INTO movimentacoes_estoque (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                    VALUES (?, 'Entrada', ?, ?, ?, ?, 'Entrada inicial')
                ");
                $stmtMov->execute([$novoProdutoId, $quantidade_disponivel, $preco_custo, $fornecedorIdBanco, $usuario_id]);
            }

            $stmtLog = $pdo->prepare("INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao) VALUES (?, 'INSERT', 'produtos', ?, ?)");
            $stmtLog->execute([$usuario_id, $novoProdutoId, "Produto cadastrado: $nome"]);

            $pdo->commit();
            $_SESSION['sucesso'] = 'Produto cadastrado com sucesso.';
            header('Location: produtos');
            exit();
        }

        // ======================================================
        // MOVIMENTAR ESTOQUE
        // ======================================================
        if ($acao === 'movimentar_estoque') {
            $produto_id = (int)($_POST['produto_id'] ?? 0);
            $tipo_movimentacao = $_POST['tipo_movimentacao'] ?? '';
            $quantidade = parse_decimal($_POST['quantidade'] ?? '');
            $custo_unitario = parse_decimal($_POST['custo_unitario'] ?? '');
            $fornecedor_id = trim($_POST['fornecedor_id'] ?? '');
            $observacao = trim($_POST['observacao'] ?? '');

            if ($produto_id <= 0 || !in_array($tipo_movimentacao, ['Entrada', 'Saida', 'Ajuste'], true) || $quantidade === null || $quantidade <= 0) {
                throw new Exception('Informe um produto, tipo e quantidade válidos.');
            }

            $stmtProduto = $pdo->prepare('SELECT produto_id, nome, preco_custo FROM produtos WHERE produto_id = ? AND ativo = 1');
            $stmtProduto->execute([$produto_id]);
            $produto = $stmtProduto->fetch();
            if (!$produto) throw new Exception('Produto não encontrado ou inativo.');

            $stmtEstoque = $pdo->prepare('SELECT * FROM estoque WHERE produto_id = ? ORDER BY estoque_id DESC LIMIT 1');
            $stmtEstoque->execute([$produto_id]);
            $estoque = $stmtEstoque->fetch();
            
            if (!$estoque) {
                $stmt = $pdo->prepare('INSERT INTO estoque (produto_id, quantidade_disponivel, validade, custo_medio, fornecedor_id) VALUES (?, 0, NULL, ?, NULL)');
                $stmt->execute([$produto_id, $produto['preco_custo']]);
                $quantidadeAtual = 0.0;
                $estoqueId = (int)$pdo->lastInsertId();
            } else {
                $quantidadeAtual = (float)$estoque['quantidade_disponivel'];
                $estoqueId = (int)$estoque['estoque_id'];
            }

            if ($tipo_movimentacao === 'Saida' && $quantidade > $quantidadeAtual) {
                throw new Exception('Quantidade insuficiente para a saída informada.');
            }

            $fornecedorIdBanco = $fornecedor_id !== '' ? (int)$fornecedor_id : null;
            $custoFinal = $custo_unitario !== null ? $custo_unitario : (float)$produto['preco_custo'];
            
            $pdo->beginTransaction();

            $stmtMov = $pdo->prepare('
                INSERT INTO movimentacoes_estoque (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtMov->execute([$produto_id, $tipo_movimentacao, $quantidade, $custoFinal, $fornecedorIdBanco, $usuario_id, $observacao !== '' ? $observacao : null]);

            if ($tipo_movimentacao === 'Ajuste') {
                $stmt = $pdo->prepare('
                    UPDATE estoque
                    SET quantidade_disponivel = ?, custo_medio = ?, fornecedor_id = COALESCE(?, fornecedor_id), data_ultima_atualizacao = CURRENT_TIMESTAMP
                    WHERE estoque_id = ?
                ');
                $stmt->execute([$quantidade, $custoFinal, $fornecedorIdBanco, $estoqueId]);
            }

            $pdo->commit();
            $_SESSION['sucesso'] = 'Movimentação registrada com sucesso.';
            header('Location: produtos');
            exit();
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}

// ======================================================
// FILTROS & LISTAGEM
// ======================================================
$camposFiltro = [];
$sqlProdutos = '
    SELECT p.*, e.estoque_id, e.quantidade_disponivel, e.validade, e.custo_medio, e.fornecedor_id, f.nome AS fornecedor_nome
    FROM produtos p
    LEFT JOIN estoque e ON e.produto_id = p.produto_id
    LEFT JOIN fornecedores f ON f.fornecedor_id = e.fornecedor_id
    WHERE 1 = 1
';

$status_filtro = $_GET['status'] ?? 'ativo';
$categoria_filtro = $_GET['categoria'] ?? 'todas';
$busca = trim($_GET['q'] ?? '');

if ($status_filtro === 'ativo') {
    $sqlProdutos .= ' AND p.ativo = 1';
} elseif ($status_filtro === 'inativo') {
    $sqlProdutos .= ' AND p.ativo = 0';
}

if ($categoria_filtro !== 'todas' && in_array($categoria_filtro, $categorias, true)) {
    $sqlProdutos .= ' AND p.categoria = ?';
    $camposFiltro[] = $categoria_filtro;
}

if ($busca !== '') {
    $sqlProdutos .= ' AND (p.nome LIKE ? OR p.categoria LIKE ?)';
    $camposFiltro[] = '%' . $busca . '%';
    $camposFiltro[] = '%' . $busca . '%';
}

$sqlProdutos .= ' ORDER BY p.ativo DESC, p.nome ASC';
$stmtProdutos = $pdo->prepare($sqlProdutos);
$stmtProdutos->execute($camposFiltro);
$produtos = $stmtProdutos->fetchAll();

$stmtFornecedores = $pdo->query('SELECT fornecedor_id, nome FROM fornecedores ORDER BY nome ASC');
$fornecedores = $stmtFornecedores->fetchAll();

$stmtMov = $pdo->query('
    SELECT m.*, p.nome AS produto_nome, p.unidade_medida, u.nome AS usuario_nome, f.nome AS fornecedor_nome
    FROM movimentacoes_estoque m
    INNER JOIN produtos p ON p.produto_id = m.produto_id
    LEFT JOIN usuarios u ON u.usuario_id = m.usuario_id
    LEFT JOIN fornecedores f ON f.fornecedor_id = m.fornecedor_id
    ORDER BY m.data_movimentacao DESC
    LIMIT 10
');
$movimentacoesRecentes = $stmtMov->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Produtos e Estoque</title>
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
                <h1>Gestão de Produtos</h1>
                <div class="user-menu">
                    <div class="avatar" onclick="toggleUserMenu()">
                        <?= e($inicial) ?>
                    </div>
                    <div class="dropdown-user" id="userDropdown">
                        <p><?= e($nome_usuario) ?></p>
                        <a href="perfil">Perfil</a>
                        <a href="logout" class="logout">Sair</a>
                    </div>
                </div>
            </header>

            <section class="main">

                <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        <?= e($erro) ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <?= e($sucesso) ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;">
                    <h2 style="color: var(--secondary);">
                        <i class="fa-solid fa-boxes-stacked"></i> Produtos e Estoque
                    </h2>
                    <?php if ($isGerente): ?>
                        <button class="btn" onclick="abrirModalProduto()">
                            <i class="fa-solid fa-plus"></i> Novo Produto
                        </button>
                    <?php endif; ?>
                </div>

                <form method="GET" class="box" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end; margin-bottom: 25px;">
                    <div class="form-group" style="flex:1; min-width:200px;">
                        <label style="font-weight:bold; font-size:14px; color:#555;">Busca Rápida</label>
                        <input type="text" name="q" value="<?= e($busca) ?>" placeholder="Nome do produto...">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:bold; font-size:14px; color:#555;">Categoria</label>
                        <select name="categoria">
                            <option value="todas">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $categoria_filtro === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:bold; font-size:14px; color:#555;">Status</label>
                        <select name="status">
                            <option value="ativo" <?= $status_filtro === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                            <option value="inativo" <?= $status_filtro === 'inativo' ? 'selected' : '' ?>>Inativos</option>
                            <option value="todos" <?= $status_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    <a href="produtos" class="btn btn-secondary" style="background:#f0f0f0; color:#555;"><i class="fa-solid fa-rotate"></i> Limpar</a>
                </form>

                <div class="box" style="margin-bottom:25px;">
                    <h3 style="margin-bottom:15px;"><i class="fa-solid fa-list"></i> Lista de Produtos</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produto / Und</th>
                                    <th>Categoria</th>
                                    <th>P. Custo</th>
                                    <th>P. Venda</th>
                                    <th>Estoque</th>
                                    <th>Validade</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$produtos): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:30px; color:#666;">Nenhum produto encontrado.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($produtos as $produto):
                                    $quantidade = (float)($produto['quantidade_disponivel'] ?? 0);
                                    $validade = !empty($produto['validade']) ? (new DateTime($produto['validade']))->format('d/m/Y') : '-';
                                    
                                    // Status do estoque
                                    $badgeEstoque = 'ok';
                                    if ($quantidade <= 0) $badgeEstoque = 'danger';
                                    elseif ($quantidade <= 10) $badgeEstoque = 'pend'; // warning style

                                    $produtoJson = htmlspecialchars(json_encode($produto, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($produto['nome']) ?></strong>
                                            <span style="font-size:12px; color:#888; display:block;"><?= e($produto['unidade_medida']) ?></span>
                                        </td>
                                        <td><?= e($produto['categoria']) ?></td>
                                        <td>R$ <?= number_format((float)$produto['preco_custo'], 2, ',', '.') ?></td>
                                        <td><?= $produto['preco_venda'] ? 'R$ ' . number_format((float)$produto['preco_venda'], 2, ',', '.') : '-' ?></td>
                                        <td><span class="badge <?= $badgeEstoque ?>"><?= number_format($quantidade, 3, ',', '.') ?></span></td>
                                        <td><?= e($validade) ?></td>
                                        <td>
                                            <?php if ((int)$produto['ativo'] === 1): ?>
                                                <span class="badge ok">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge danger">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="display:flex; gap:6px;">
                                            <button class="btn-icon" title="Movimentar Estoque" data-produto='<?= $produtoJson ?>' onclick="abrirModalMovimento(this)">
                                                <i class="fa-solid fa-right-left"></i>
                                            </button>

                                            <button class="btn-icon" title="Editar Produto" data-produto='<?= $produtoJson ?>' onclick="abrirModalProduto(this)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>

                                            <?php if ($isGerente): ?>
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja alterar o status deste produto?');">
                                                    <input type="hidden" name="acao" value="toggle_status">
                                                    <input type="hidden" name="produto_id" value="<?= $produto['produto_id'] ?>">
                                                    <input type="hidden" name="status_atual" value="<?= $produto['ativo'] ?>">
                                                    
                                                    <?php if ((int)$produto['ativo'] === 1): ?>
                                                        <button type="submit" class="btn-icon danger" title="Desativar Produto">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn-icon" style="color:#137333;" title="Ativar Produto">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box">
                    <h3 style="margin-bottom:15px;"><i class="fa-solid fa-clipboard-list"></i> Últimas Movimentações de Estoque</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Qtd.</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$movimentacoesRecentes): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:30px; color:#666;">Nenhuma movimentação recente.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($movimentacoesRecentes as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                        <td><?= e($mov['produto_nome']) ?></td>
                                        <td>
                                            <?php
                                            $tipoClass = 'pend';
                                            if ($mov['tipo_movimentacao'] === 'Entrada') $tipoClass = 'ok';
                                            if ($mov['tipo_movimentacao'] === 'Saida') $tipoClass = 'danger';
                                            ?>
                                            <span class="badge <?= $tipoClass ?>"><?= e($mov['tipo_movimentacao']) ?></span>
                                        </td>
                                        <td><?= number_format($mov['quantidade'], 3, ',', '.') ?> <?= e($mov['unidade_medida']) ?></td>
                                        <td><?= e($mov['usuario_nome'] ?? 'Sistema') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <div id="modalProduto" class="modal">
        <div class="modal-content box" style="max-width: 800px; width: 100%;">
            <h3 id="modalProdutoTitulo" style="margin-bottom: 20px;">Cadastrar Produto</h3>
            
            <form method="POST" id="formProduto">
                <input type="hidden" name="acao" value="salvar_produto">
                <input type="hidden" name="produto_id" id="produto_id">
                
                <div class="grid-form">
                    <div class="form-group">
                        <label>Nome do Produto</label>
                        <input type="text" name="nome" id="nome" required>
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="categoria" id="categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Preço de Custo (R$)</label>
                        <input type="number" step="0.01" min="0" name="preco_custo" id="preco_custo" required>
                    </div>

                    <div class="form-group">
                        <label>Preço de Venda (R$)</label>
                        <input type="number" step="0.01" min="0" name="preco_venda" id="preco_venda">
                    </div>

                    <div class="form-group">
                        <label>Estoque Disponível</label>
                        <input type="number" step="0.001" min="0" name="quantidade_disponivel" id="quantidade_disponivel" required>
                    </div>

                    <div class="form-group">
                        <label>Unidade de Medida</label>
                        <select name="unidade_medida" id="unidade_medida" required>
                            <?php foreach ($unidades as $un): ?>
                                <option value="<?= e($un) ?>"><?= e($un) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Validade</label>
                        <input type="date" name="validade" id="validade">
                    </div>

                    <div class="form-group">
                        <label>Fornecedor</label>
                        <select name="fornecedor_id" id="fornecedor_id">
                            <option value="">Nenhum</option>
                            <?php foreach ($fornecedores as $forn): ?>
                                <option value="<?= $forn['fornecedor_id'] ?>"><?= e($forn['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:25px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalProduto()">Cancelar</button>
                    <button type="submit" class="btn">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalMovimento" class="modal">
        <div class="modal-content box" style="max-width: 600px; width: 100%;">
            <h3 style="margin-bottom: 20px;">Registrar Movimentação</h3>
            
            <form method="POST" id="formMovimento">
                <input type="hidden" name="acao" value="movimentar_estoque">
                <input type="hidden" name="produto_id" id="mov_produto_id">
                
                <div class="grid-form" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>Tipo de Movimentação</label>
                        <select name="tipo_movimentacao" id="mov_tipo" required>
                            <option value="Entrada">Entrada (+)</option>
                            <option value="Saida">Saída (-)</option>
                            <option value="Ajuste">Ajuste de Saldo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quantidade</label>
                        <input type="number" step="0.001" min="0.001" name="quantidade" id="mov_quantidade" required>
                    </div>

                    <div class="form-group">
                        <label>Custo Unitário (Opcional)</label>
                        <input type="number" step="0.01" min="0" name="custo_unitario" id="mov_custo_unitario">
                    </div>

                    <div class="form-group">
                        <label>Observação</label>
                        <input type="text" name="observacao" id="mov_observacao" placeholder="Ex: Ajuste após contagem física...">
                    </div>
                </div>

                <div style="margin-top:25px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalMovimento()">Cancelar</button>
                    <button type="submit" class="btn">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>

    <script>
        const isGerente = <?= $isGerente ? 'true' : 'false' ?>;

        // MODAL PRODUTO
        function abrirModalProduto(btn = null) {
            const form = document.getElementById('formProduto');
            const titulo = document.getElementById('modalProdutoTitulo');
            const campoPrecoVenda = document.getElementById('preco_venda');
            
            form.reset();
            document.getElementById('produto_id').value = '';
            titulo.innerText = 'Cadastrar Produto';
            
            // Controle de permissão no input
            campoPrecoVenda.readOnly = !isGerente;

            if (btn && btn.dataset.produto) {
                const p = JSON.parse(btn.dataset.produto);
                titulo.innerText = 'Editar Produto';
                document.getElementById('produto_id').value = p.produto_id;
                document.getElementById('nome').value = p.nome;
                document.getElementById('categoria').value = p.categoria;
                document.getElementById('preco_custo').value = p.preco_custo;
                document.getElementById('preco_venda').value = p.preco_venda || '';
                document.getElementById('quantidade_disponivel').value = p.quantidade_disponivel || 0;
                document.getElementById('unidade_medida').value = p.unidade_medida;
                document.getElementById('validade').value = p.validade || '';
                document.getElementById('fornecedor_id').value = p.fornecedor_id || '';
            }

            document.getElementById('modalProduto').style.display = 'flex';
        }

        function fecharModalProduto() {
            document.getElementById('modalProduto').style.display = 'none';
        }

        // MODAL MOVIMENTAÇÃO
        function abrirModalMovimento(btn) {
            const form = document.getElementById('formMovimento');
            form.reset();

            if (btn && btn.dataset.produto) {
                const p = JSON.parse(btn.dataset.produto);
                document.getElementById('mov_produto_id').value = p.produto_id;
                document.getElementById('mov_custo_unitario').value = p.custo_medio || p.preco_custo;
            }

            document.getElementById('modalMovimento').style.display = 'flex';
        }

        function fecharModalMovimento() {
            document.getElementById('modalMovimento').style.display = 'none';
        }

        // FECHAR MODAIS CLICANDO FORA
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }

        // USER MENU
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            const menu = document.querySelector('.user-menu');
            if (menu && !menu.contains(e.target)) {
                document.getElementById('userDropdown').classList.remove('active');
            }
        });
    </script>
</body>
</html>