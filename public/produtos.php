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

$mensagem = '';
$erro = '';
$abrir_modal_produto = false;
$abrir_modal_movimento = false;

$produto_form = [
    'produto_id' => '',
    'nome' => '',
    'categoria' => 'Sorvete',
    'preco_custo' => '',
    'preco_venda' => '',
    'unidade_medida' => 'kg',
    'quantidade_disponivel' => '',
    'validade' => '',
    'fornecedor_id' => ''
];

$movimento_form = [
    'produto_id' => '',
    'tipo_movimentacao' => 'Entrada',
    'quantidade' => '',
    'custo_unitario' => '',
    'fornecedor_id' => '',
    'observacao' => ''
];

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function decimal_input($valor)
{
    $valor = trim((string)$valor);
    if ($valor === '') {
        return null;
    }

    return (float) str_replace(',', '.', $valor);
}

function format_currency($valor)
{
    if ($valor === null || $valor === '') {
        return 'N/A';
    }

    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function format_quantity($valor, $unidade)
{
    return number_format((float) $valor, 3, ',', '.') . ' ' . $unidade;
}

function estoque_badge_class($quantidade)
{
    if ($quantidade <= 0) {
        return 'danger';
    }

    if ($quantidade <= 10) {
        return 'warn';
    }

    return 'ok';
}

$categorias = ['Sorvete', 'Insumo', 'Ingrediente', 'Consumivel', 'Embalagem', 'Coberturas', 'Adicionais'];
$unidades = ['kg', 'g', 'lt', 'ml', 'un', 'cx', 'pct'];

$status_filtro = $_GET['status'] ?? 'ativo';
$categoria_filtro = $_GET['categoria'] ?? 'todas';
$busca = trim($_GET['q'] ?? '');

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar_produto') {
            $produto_id = (int)($_POST['produto_id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $preco_custo = decimal_input($_POST['preco_custo'] ?? '');
            $preco_venda_post = decimal_input($_POST['preco_venda'] ?? '');
            $unidade_medida = trim($_POST['unidade_medida'] ?? '');
            $quantidade_disponivel = decimal_input($_POST['quantidade_disponivel'] ?? '');
            $validade = trim($_POST['validade'] ?? '');
            $fornecedor_id = trim($_POST['fornecedor_id'] ?? '');

            $produto_form = [
                'produto_id' => $produto_id,
                'nome' => $nome,
                'categoria' => $categoria,
                'preco_custo' => $_POST['preco_custo'] ?? '',
                'preco_venda' => $_POST['preco_venda'] ?? '',
                'unidade_medida' => $unidade_medida,
                'quantidade_disponivel' => $_POST['quantidade_disponivel'] ?? '',
                'validade' => $validade,
                'fornecedor_id' => $fornecedor_id
            ];

            if ($nome === '' || !in_array($categoria, $categorias, true) || $preco_custo === null || $preco_custo < 0 || $unidade_medida === '' || $quantidade_disponivel === null || $quantidade_disponivel < 0) {
                throw new Exception('Preencha nome, categoria, custo, unidade e quantidade inicial corretamente.');
            }

            if ($validade !== '') {
                $dataValidade = DateTime::createFromFormat('Y-m-d', $validade);
                if (!$dataValidade || $dataValidade->format('Y-m-d') !== $validade) {
                    throw new Exception('Informe uma validade válida.');
                }
            }

            $fornecedorIdBanco = $fornecedor_id !== '' ? (int) $fornecedor_id : null;
            if ($fornecedorIdBanco !== null) {
                $stmtFornecedor = $pdo->prepare('SELECT fornecedor_id FROM fornecedores WHERE fornecedor_id = ?');
                $stmtFornecedor->execute([$fornecedorIdBanco]);
                if (!$stmtFornecedor->fetchColumn()) {
                    throw new Exception('Fornecedor informado não existe.');
                }
            }

            if ($produto_id > 0) {
                $stmtAtual = $pdo->prepare('SELECT * FROM produtos WHERE produto_id = ?');
                $stmtAtual->execute([$produto_id]);
                $produtoAtual = $stmtAtual->fetch();

                if (!$produtoAtual) {
                    throw new Exception('Produto não encontrado.');
                }

                $stmtEstoqueAtual = $pdo->prepare('SELECT * FROM estoque WHERE produto_id = ? ORDER BY estoque_id DESC LIMIT 1');
                $stmtEstoqueAtual->execute([$produto_id]);
                $estoqueAtual = $stmtEstoqueAtual->fetch();
                $estoqueId = $estoqueAtual['estoque_id'] ?? null;
                $quantidadeAtual = $estoqueAtual ? (float) $estoqueAtual['quantidade_disponivel'] : 0.0;

                $preco_venda = $preco_venda_post;
                if (!$isGerente) {
                    $preco_venda = $produtoAtual['preco_venda'];
                }

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

                $diferenca = round((float) $quantidade_disponivel - (float) $quantidadeAtual, 3);
                if (abs($diferenca) > 0.0001) {
                    $stmt = $pdo->prepare('
                        INSERT INTO movimentacoes_estoque
                        (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                        VALUES (?, \'Ajuste\', ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $produto_id,
                        abs($diferenca),
                        $preco_custo,
                        $fornecedorIdBanco,
                        $usuario_id,
                        'Ajuste manual de estoque após conferência física'
                    ]);
                }

                $stmtLog = $pdo->prepare('
                    INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                    VALUES (?, \'UPDATE\', \'produtos\', ?, ?)
                ');
                $stmtLog->execute([$usuario_id, $produto_id, 'Produto atualizado: ' . $nome]);

                $pdo->commit();
                $_SESSION['flash_success'] = 'Produto atualizado com sucesso.';
                header('Location: produtos');
                exit();
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO produtos
                (nome, categoria, preco_custo, preco_venda, unidade_medida, criado_por, ativo)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([
                $nome,
                $categoria,
                $preco_custo,
                $preco_venda_post,
                $unidade_medida,
                $usuario_id
            ]);

            $novoProdutoId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('
                INSERT INTO estoque (produto_id, quantidade_disponivel, validade, custo_medio, fornecedor_id)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$novoProdutoId, $quantidade_disponivel, $validade !== '' ? $validade : null, $preco_custo, $fornecedorIdBanco]);

            if ($quantidade_disponivel > 0) {
                $stmt = $pdo->prepare('
                    INSERT INTO movimentacoes_estoque
                    (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                    VALUES (?, \'Entrada\', ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $novoProdutoId,
                    $quantidade_disponivel,
                    $preco_custo,
                    $fornecedorIdBanco,
                    $usuario_id,
                    'Entrada inicial após conferência manual do estoque'
                ]);
            }

            $stmtLog = $pdo->prepare('
                INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                VALUES (?, \'INSERT\', \'produtos\', ?, ?)
            ');
            $stmtLog->execute([$usuario_id, $novoProdutoId, 'Produto cadastrado: ' . $nome]);

            $pdo->commit();
            $_SESSION['flash_success'] = 'Produto cadastrado com sucesso.';
            header('Location: produtos');
            exit();
        }

        if ($acao === 'movimentar_estoque') {
            $produto_id = (int)($_POST['produto_id'] ?? 0);
            $tipo_movimentacao = $_POST['tipo_movimentacao'] ?? '';
            $quantidade = decimal_input($_POST['quantidade'] ?? '');
            $custo_unitario = decimal_input($_POST['custo_unitario'] ?? '');
            $fornecedor_id = trim($_POST['fornecedor_id'] ?? '');
            $observacao = trim($_POST['observacao'] ?? '');

            $movimento_form = [
                'produto_id' => $produto_id,
                'tipo_movimentacao' => $tipo_movimentacao,
                'quantidade' => $_POST['quantidade'] ?? '',
                'custo_unitario' => $_POST['custo_unitario'] ?? '',
                'fornecedor_id' => $fornecedor_id,
                'observacao' => $observacao
            ];

            if ($produto_id <= 0 || !in_array($tipo_movimentacao, ['Entrada', 'Saida', 'Ajuste'], true) || $quantidade === null || $quantidade <= 0) {
                throw new Exception('Informe um produto, tipo e quantidade válidos.');
            }

            $stmtProduto = $pdo->prepare('SELECT produto_id, nome, preco_custo FROM produtos WHERE produto_id = ? AND ativo = 1');
            $stmtProduto->execute([$produto_id]);
            $produto = $stmtProduto->fetch();

            if (!$produto) {
                throw new Exception('Produto não encontrado ou inativo.');
            }

            $stmtEstoque = $pdo->prepare('SELECT * FROM estoque WHERE produto_id = ? ORDER BY estoque_id DESC LIMIT 1');
            $stmtEstoque->execute([$produto_id]);
            $estoque = $stmtEstoque->fetch();

            if (!$estoque) {
                $stmt = $pdo->prepare('INSERT INTO estoque (produto_id, quantidade_disponivel, validade, custo_medio, fornecedor_id) VALUES (?, 0, NULL, ?, NULL)');
                $stmt->execute([$produto_id, $produto['preco_custo']]);
                $estoqueId = (int) $pdo->lastInsertId();
                $quantidadeAtual = 0.0;
            } else {
                $estoqueId = (int) $estoque['estoque_id'];
                $quantidadeAtual = (float) $estoque['quantidade_disponivel'];
            }

            $fornecedorIdBanco = $fornecedor_id !== '' ? (int) $fornecedor_id : null;
            if ($fornecedorIdBanco !== null) {
                $stmtFornecedor = $pdo->prepare('SELECT fornecedor_id FROM fornecedores WHERE fornecedor_id = ?');
                $stmtFornecedor->execute([$fornecedorIdBanco]);
                if (!$stmtFornecedor->fetchColumn()) {
                    throw new Exception('Fornecedor informado não existe.');
                }
            }

            $custoFinal = $custo_unitario !== null ? $custo_unitario : (float) $produto['preco_custo'];

            if ($tipo_movimentacao === 'Saida' && $quantidade > $quantidadeAtual) {
                throw new Exception('Quantidade insuficiente para a saída informada.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO movimentacoes_estoque
                (produto_id, tipo_movimentacao, quantidade, custo_unitario, fornecedor_id, usuario_id, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $produto_id,
                $tipo_movimentacao,
                $quantidade,
                $custoFinal,
                $fornecedorIdBanco,
                $usuario_id,
                $observacao !== '' ? $observacao : null
            ]);

            if ($tipo_movimentacao === 'Ajuste') {
                $stmt = $pdo->prepare('
                    UPDATE estoque
                    SET quantidade_disponivel = ?, custo_medio = ?, fornecedor_id = COALESCE(?, fornecedor_id), data_ultima_atualizacao = CURRENT_TIMESTAMP
                    WHERE estoque_id = ?
                ');
                $stmt->execute([$quantidade, $custoFinal, $fornecedorIdBanco, $estoqueId]);
            }

            $stmtLog = $pdo->prepare('
                INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                VALUES (?, ?, \'movimentacoes_estoque\', ?, ?)
            ');
            $stmtLog->execute([
                $usuario_id,
                $tipo_movimentacao,
                (int) $pdo->lastInsertId(),
                $tipo_movimentacao . ' registrada para o produto ' . $produto['nome']
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = 'Movimentação registrada com sucesso.';
            header('Location: produtos');
            exit();
        }

        if ($acao === 'excluir_produto') {
            if (!$isGerente) {
                throw new Exception('Apenas o gerente pode excluir produtos.');
            }

            $produto_id = (int)($_POST['produto_id'] ?? 0);
            if ($produto_id <= 0) {
                throw new Exception('Produto inválido.');
            }

            $stmtProduto = $pdo->prepare('SELECT nome FROM produtos WHERE produto_id = ?');
            $stmtProduto->execute([$produto_id]);
            $produtoNome = $stmtProduto->fetchColumn();

            if (!$produtoNome) {
                throw new Exception('Produto não encontrado.');
            }

            try {
                $stmt = $pdo->prepare('DELETE FROM produtos WHERE produto_id = ?');
                $stmt->execute([$produto_id]);
                $descricaoExclusao = 'Produto excluído: ' . $produtoNome;
            } catch (Exception $e) {
                $stmt = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE produto_id = ?');
                $stmt->execute([$produto_id]);
                $descricaoExclusao = 'Produto inativado por vínculo existente: ' . $produtoNome;
            }

            $stmtLog = $pdo->prepare('
                INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, descricao)
                VALUES (?, \'DELETE\', \'produtos\', ?, ?)
            ');
            $stmtLog->execute([$usuario_id, $produto_id, $descricaoExclusao]);

            $_SESSION['flash_success'] = 'Produto removido com sucesso.';
            header('Location: produtos');
            exit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $erro = $e->getMessage();

        if ($acao === 'salvar_produto') {
            $abrir_modal_produto = true;
        } elseif ($acao === 'movimentar_estoque') {
            $abrir_modal_movimento = true;
        }
    }
}

$sqlProdutos = '
    SELECT
        p.*,
        e.estoque_id,
        e.quantidade_disponivel,
        e.validade,
        e.custo_medio,
        e.fornecedor_id,
        f.nome AS fornecedor_nome
    FROM produtos p
    LEFT JOIN estoque e ON e.produto_id = p.produto_id
    LEFT JOIN fornecedores f ON f.fornecedor_id = e.fornecedor_id
    WHERE 1 = 1
';

$paramsProdutos = [];

if ($status_filtro === 'ativo') {
    $sqlProdutos .= ' AND p.ativo = 1';
} elseif ($status_filtro === 'inativo') {
    $sqlProdutos .= ' AND p.ativo = 0';
}

if ($categoria_filtro !== 'todas' && in_array($categoria_filtro, $categorias, true)) {
    $sqlProdutos .= ' AND p.categoria = ?';
    $paramsProdutos[] = $categoria_filtro;
}

if ($busca !== '') {
    $sqlProdutos .= ' AND (p.nome LIKE ? OR p.categoria LIKE ?)';
    $paramsProdutos[] = '%' . $busca . '%';
    $paramsProdutos[] = '%' . $busca . '%';
}

$sqlProdutos .= ' ORDER BY p.ativo DESC, p.nome ASC';

$stmtProdutos = $pdo->prepare($sqlProdutos);
$stmtProdutos->execute($paramsProdutos);
$produtos = $stmtProdutos->fetchAll();

$stmtFornecedores = $pdo->query('SELECT fornecedor_id, nome FROM fornecedores ORDER BY nome ASC');
$fornecedores = $stmtFornecedores->fetchAll();

$stmtMovimentacoes = $pdo->query('
    SELECT
        m.*,
        p.nome AS produto_nome,
        p.unidade_medida,
        u.nome AS usuario_nome,
        f.nome AS fornecedor_nome
    FROM movimentacoes_estoque m
    INNER JOIN produtos p ON p.produto_id = m.produto_id
    LEFT JOIN usuarios u ON u.usuario_id = m.usuario_id
    LEFT JOIN fornecedores f ON f.fornecedor_id = m.fornecedor_id
    ORDER BY m.data_movimentacao DESC
    LIMIT 10
');
$movimentacoesRecentes = $stmtMovimentacoes->fetchAll();

$produtosAtivos = 0;
$estoqueCritico = 0;
$validadesProximas = 0;

foreach ($produtos as $produto) {
    if ((int) $produto['ativo'] === 1) {
        $produtosAtivos++;
    }

    $quantidade = isset($produto['quantidade_disponivel']) ? (float) $produto['quantidade_disponivel'] : 0.0;
    if ($quantidade <= 10) {
        $estoqueCritico++;
    }

    if (!empty($produto['validade'])) {
        $hoje = new DateTime();
        $validadeProduto = new DateTime($produto['validade']);
        $diff = (int) $hoje->diff($validadeProduto)->format('%r%a');
        if ($diff >= 0 && $diff <= 10) {
            $validadesProximas++;
        }
    }
}
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
    <style>
        .hero-panel {
            background: linear-gradient(135deg, #11417b 0%, #1d5ea8 55%, #c6746a 100%);
            color: #fff;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 18px 35px rgba(17, 65, 123, 0.18);
        }
        .hero-panel h2, .hero-panel p { margin: 0; }
        .hero-panel h2 { margin-bottom: 8px; font-size: 1.8rem; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(17, 65, 123, 0.08);
            border-top: 4px solid var(--primary);
        }
        .summary-card h4 {
            margin: 0 0 8px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .summary-card strong { font-size: 1.8rem; color: var(--secondary); }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .toolbar form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }
        .toolbar input,
        .toolbar select,
        .modal-content input,
        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dde3ea;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
            background: var(--primary);
            color: #fff;
        }
        .btn:hover, .btn-icon:hover { transform: translateY(-1px); }
        .btn-secondary, .btn-outline { background: #e9eef5; color: var(--secondary); }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-icon {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 10px;
            background: #f4f7fb;
            color: var(--secondary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 6px;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            vertical-align: middle;
        }
        th { color: var(--secondary); font-size: 0.92rem; }
        .alert-custom {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .alert-success-custom {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 5px solid #2e7d32;
        }
        .alert-error-custom {
            background: #ffebee;
            color: #b71c1c;
            border-left: 5px solid #c62828;
        }
        .note-box {
            background: #f7fbff;
            border: 1px solid #d7e7f7;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 18px;
            color: #26415f;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-status.ok { background: #e8f5e9; color: #2e7d32; }
        .badge-status.warn { background: #fff3cd; color: #856404; }
        .badge-status.danger { background: #ffebee; color: #c62828; }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 65, 123, 0.45);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 24px;
        }
        .modal.show { display: flex; }
        .modal-content {
            width: min(980px, 100%);
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
            padding: 24px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .modal-header h3 { margin: 0; color: var(--secondary); }
        .grid-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-weight: 600;
            color: #455468;
            font-size: 14px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .muted { color: var(--text-muted); font-size: 14px; }
        .tag-resumo {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--soft);
            color: var(--secondary);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .small-help { font-size: 12px; color: var(--text-muted); }
        @media (max-width: 768px) {
            .grid-form { grid-template-columns: 1fr; }
            .toolbar form > * { width: 100%; }
            .topbar h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php require_once '../components/sidebar.php'; ?>

        <main class="content">
            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()" type="button"><i class="fa-solid fa-bars"></i></button>
                <h1>Produtos e Estoque</h1>

                <div class="user-menu">
                    <div class="avatar" onclick="toggleUserMenu()"><?= e($inicial) ?></div>
                    <div class="dropdown-user" id="userDropdown">
                        <p><?= e($nome_usuario) ?></p>
                        <a href="perfil">Perfil</a>
                        <a href="logout" class="logout">Sair</a>
                    </div>
                </div>
            </header>

            <section class="main">
                <div class="hero-panel">
                    <h2><i class="fa-solid fa-boxes-stacked"></i> Controle de Produtos e Estoque</h2>
                    <p>Cadastro, edição, movimentação e conferência física do estoque de sorvetes, insumos, ingredientes, consumíveis e demais itens da sorveteria.</p>
                </div>

                <?php if ($flash_success): ?>
                    <div class="alert-custom alert-success-custom"><?= e($flash_success) ?></div>
                <?php endif; ?>

                <?php if ($flash_error || $erro): ?>
                    <div class="alert-custom alert-error-custom"><?= e($flash_error ?: $erro) ?></div>
                <?php endif; ?>

                <div class="summary-grid">
                    <div class="summary-card"><h4>Produtos ativos</h4><strong><?= (int) $produtosAtivos ?></strong></div>
                    <div class="summary-card"><h4>Estoque crítico</h4><strong><?= (int) $estoqueCritico ?></strong></div>
                    <div class="summary-card"><h4>Validade próxima</h4><strong><?= (int) $validadesProximas ?></strong></div>
                    <div class="summary-card"><h4>Fornecedores cadastrados</h4><strong><?= count($fornecedores) ?></strong></div>
                </div>

                <div class="note-box">
                    <strong>RN.005-A:</strong> o lançamento do estoque deve ser feito após conferência manual do estoque físico. Use o formulário de produto para registrar o saldo inicial ou ajuste a quantidade disponível quando houver conferência.
                </div>

                <div class="toolbar">
                    <form method="get" action="produtos">
                        <input type="text" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou categoria">
                        <select name="categoria">
                            <option value="todas" <?= $categoria_filtro === 'todas' ? 'selected' : '' ?>>Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= e($categoria) ?>" <?= $categoria_filtro === $categoria ? 'selected' : '' ?>><?= e($categoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status">
                            <option value="ativo" <?= $status_filtro === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                            <option value="inativo" <?= $status_filtro === 'inativo' ? 'selected' : '' ?>>Inativos</option>
                            <option value="todos" <?= $status_filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                        </select>
                        <button class="btn btn-outline" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    </form>

                    <button class="btn" type="button" onclick="abrirModalProdutoTela()"><i class="fa-solid fa-plus"></i> Novo Produto</button>
                </div>

                <div class="box" style="margin-bottom: 24px;">
                    <div class="section-title">
                        <div>
                            <h3 style="margin-bottom: 4px;"><i class="fa-solid fa-box-open"></i> Cadastro de Produtos</h3>
                            <div class="muted">Nome, categoria, preços, unidade de medida e estoque inicial.</div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Custo</th>
                                    <th>Venda</th>
                                    <th>Estoque</th>
                                    <th>Validade</th>
                                    <th>Fornecedor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$produtos): ?>
                                    <tr><td colspan="9">Nenhum produto encontrado.</td></tr>
                                <?php endif; ?>

                                <?php foreach ($produtos as $produto): ?>
                                    <?php
                                        $quantidade = isset($produto['quantidade_disponivel']) ? (float) $produto['quantidade_disponivel'] : 0.0;
                                        $estoqueClass = estoque_badge_class($quantidade);
                                        $validade = !empty($produto['validade']) ? (new DateTime($produto['validade']))->format('d/m/Y') : 'N/A';
                                        $produtoJson = htmlspecialchars(json_encode($produto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td><strong><?= e($produto['nome']) ?></strong><br><span class="small-help"><?= e($produto['unidade_medida']) ?></span></td>
                                        <td><span class="tag-resumo"><?= e($produto['categoria']) ?></span></td>
                                        <td><?= format_currency($produto['preco_custo']) ?></td>
                                        <td><?= $produto['preco_venda'] !== null && $produto['preco_venda'] !== '' ? format_currency($produto['preco_venda']) : 'N/A' ?></td>
                                        <td><span class="badge-status <?= e($estoqueClass) ?>"><?= e(format_quantity($quantidade, $produto['unidade_medida'])) ?></span></td>
                                        <td><?= e($validade) ?></td>
                                        <td><?= e($produto['fornecedor_nome'] ?? 'N/A') ?></td>
                                        <td><?= ((int) $produto['ativo'] === 1) ? '<span class="badge-status ok">Ativo</span>' : '<span class="badge-status danger">Inativo</span>' ?></td>
                                        <td>
                                            <button class="btn-icon" type="button" title="Registrar movimentação" data-produto='<?= $produtoJson ?>' onclick="abrirModalMovimentoTela(this)"><i class="fa-solid fa-right-left"></i></button>
                                            <button class="btn-icon" type="button" title="Editar produto" data-produto='<?= $produtoJson ?>' onclick="abrirModalProdutoTela(this)"><i class="fa-solid fa-pen"></i></button>
                                            <?php if ($isGerente): ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                                    <input type="hidden" name="acao" value="excluir_produto">
                                                    <input type="hidden" name="produto_id" value="<?= (int) $produto['produto_id'] ?>">
                                                    <button class="btn-icon btn-danger" type="submit" title="Excluir produto"><i class="fa-solid fa-trash"></i></button>
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
                    <div class="section-title">
                        <div>
                            <h3 style="margin-bottom: 4px;"><i class="fa-solid fa-clipboard-list"></i> Últimas Movimentações</h3>
                            <div class="muted">Registro de entrada, saída e ajuste do estoque.</div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Usuário</th>
                                    <th>Fornecedor</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$movimentacoesRecentes): ?>
                                    <tr><td colspan="7">Nenhuma movimentação registrada.</td></tr>
                                <?php endif; ?>

                                <?php foreach ($movimentacoesRecentes as $movimento): ?>
                                    <tr>
                                        <td><?= e((new DateTime($movimento['data_movimentacao']))->format('d/m/Y H:i')) ?></td>
                                        <td><?= e($movimento['produto_nome']) ?></td>
                                        <td><span class="tag-resumo"><?= e($movimento['tipo_movimentacao']) ?></span></td>
                                        <td><?= e(format_quantity($movimento['quantidade'], $movimento['unidade_medida'])) ?></td>
                                        <td><?= e($movimento['usuario_nome'] ?? 'Sistema') ?></td>
                                        <td><?= e($movimento['fornecedor_nome'] ?? 'N/A') ?></td>
                                        <td><?= e($movimento['observacao'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="modalProduto" class="modal <?= $abrir_modal_produto ? 'show' : '' ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalProdutoTitulo">Cadastrar Produto</h3>
                <button class="btn btn-secondary" type="button" onclick="fecharModalProdutoTela()">Fechar</button>
            </div>

            <form method="post" id="formProduto">
                <input type="hidden" name="acao" value="salvar_produto">
                <input type="hidden" name="produto_id" id="produto_id" value="<?= e($produto_form['produto_id']) ?>">

                <div class="grid-form">
                    <div class="form-group">
                        <label for="nome">Nome do produto</label>
                        <input type="text" name="nome" id="nome" value="<?= e($produto_form['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoria</label>
                        <select name="categoria" id="categoria" required>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= e($categoria) ?>" <?= $produto_form['categoria'] === $categoria ? 'selected' : '' ?>><?= e($categoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="preco_custo">Preço de custo</label>
                        <input type="number" step="0.01" min="0" name="preco_custo" id="preco_custo" value="<?= e($produto_form['preco_custo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="preco_venda">Preço de venda</label>
                        <input type="number" step="0.01" min="0" name="preco_venda" id="preco_venda" value="<?= e($produto_form['preco_venda']) ?>">
                        <span class="small-help">Funcionários podem cadastrar produtos, mas a alteração de preço em edição fica restrita ao gerente.</span>
                    </div>
                    <div class="form-group">
                        <label for="quantidade_disponivel">Quantidade disponível</label>
                        <input type="number" step="0.001" min="0" name="quantidade_disponivel" id="quantidade_disponivel" value="<?= e($produto_form['quantidade_disponivel']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="unidade_medida">Unidade de medida</label>
                        <select name="unidade_medida" id="unidade_medida" required>
                            <?php foreach ($unidades as $unidade): ?>
                                <option value="<?= e($unidade) ?>" <?= $produto_form['unidade_medida'] === $unidade ? 'selected' : '' ?>><?= e($unidade) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="validade">Validade</label>
                        <input type="date" name="validade" id="validade" value="<?= e($produto_form['validade']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="fornecedor_id">Fornecedor</label>
                        <select name="fornecedor_id" id="fornecedor_id">
                            <option value="">Selecione</option>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?= (int) $fornecedor['fornecedor_id'] ?>" <?= (string) $produto_form['fornecedor_id'] === (string) $fornecedor['fornecedor_id'] ? 'selected' : '' ?>><?= e($fornecedor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalProdutoTela()">Cancelar</button>
                    <button type="submit" class="btn">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalMovimento" class="modal <?= $abrir_modal_movimento ? 'show' : '' ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Registrar Movimentação</h3>
                <button class="btn btn-secondary" type="button" onclick="fecharModalMovimentoTela()">Fechar</button>
            </div>

            <form method="post" id="formMovimento">
                <input type="hidden" name="acao" value="movimentar_estoque">
                <input type="hidden" name="produto_id" id="mov_produto_id" value="<?= e($movimento_form['produto_id']) ?>">

                <div class="grid-form">
                    <div class="form-group">
                        <label for="mov_tipo">Tipo de movimentação</label>
                        <select name="tipo_movimentacao" id="mov_tipo" required>
                            <option value="Entrada" <?= $movimento_form['tipo_movimentacao'] === 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                            <option value="Saida" <?= $movimento_form['tipo_movimentacao'] === 'Saida' ? 'selected' : '' ?>>Saída</option>
                            <option value="Ajuste" <?= $movimento_form['tipo_movimentacao'] === 'Ajuste' ? 'selected' : '' ?>>Ajuste</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mov_quantidade">Quantidade</label>
                        <input type="number" step="0.001" min="0" name="quantidade" id="mov_quantidade" value="<?= e($movimento_form['quantidade']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mov_custo_unitario">Custo unitário</label>
                        <input type="number" step="0.01" min="0" name="custo_unitario" id="mov_custo_unitario" value="<?= e($movimento_form['custo_unitario']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="mov_fornecedor_id">Fornecedor</label>
                        <select name="fornecedor_id" id="mov_fornecedor_id">
                            <option value="">Selecione</option>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?= (int) $fornecedor['fornecedor_id'] ?>" <?= (string) $movimento_form['fornecedor_id'] === (string) $fornecedor['fornecedor_id'] ? 'selected' : '' ?>><?= e($fornecedor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="mov_observacao">Observação</label>
                        <textarea name="observacao" id="mov_observacao" rows="4" placeholder="Motivo da entrada, saída ou ajuste"><?= e($movimento_form['observacao']) ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalMovimentoTela()">Cancelar</button>
                    <button type="submit" class="btn">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script>
        function abrirModalProdutoTela(botao) {
            const modal = document.getElementById('modalProduto');
            const titulo = document.getElementById('modalProdutoTitulo');
            const formulario = document.getElementById('formProduto');
            const campoPrecoVenda = document.getElementById('preco_venda');

            if (botao && botao.dataset && botao.dataset.produto) {
                const produto = JSON.parse(botao.dataset.produto);
                titulo.innerText = 'Editar Produto';
                document.getElementById('produto_id').value = produto.produto_id || '';
                document.getElementById('nome').value = produto.nome || '';
                document.getElementById('categoria').value = produto.categoria || 'Sorvete';
                document.getElementById('preco_custo').value = produto.preco_custo || '';
                document.getElementById('preco_venda').value = produto.preco_venda || '';
                document.getElementById('quantidade_disponivel').value = produto.quantidade_disponivel || '';
                document.getElementById('unidade_medida').value = produto.unidade_medida || 'kg';
                document.getElementById('validade').value = produto.validade || '';
                document.getElementById('fornecedor_id').value = produto.fornecedor_id || '';

                <?php if (!$isGerente): ?>
                campoPrecoVenda.disabled = true;
                campoPrecoVenda.title = 'Somente o gerente pode alterar o preço de venda em edição';
                <?php endif; ?>
            } else {
                titulo.innerText = 'Cadastrar Produto';
                formulario.reset();
                document.getElementById('produto_id').value = '';
                document.getElementById('unidade_medida').value = 'kg';
                document.getElementById('categoria').value = 'Sorvete';
                campoPrecoVenda.disabled = false;
                campoPrecoVenda.title = '';
            }

            modal.classList.add('show');
        }

        function fecharModalProdutoTela() {
            document.getElementById('modalProduto').classList.remove('show');
        }

        function abrirModalMovimentoTela(botao) {
            const modal = document.getElementById('modalMovimento');
            const formulario = document.getElementById('formMovimento');

            if (botao && botao.dataset && botao.dataset.produto) {
                const produto = JSON.parse(botao.dataset.produto);
                document.getElementById('mov_produto_id').value = produto.produto_id || '';
                document.getElementById('mov_fornecedor_id').value = produto.fornecedor_id || '';
                document.getElementById('mov_custo_unitario').value = produto.custo_medio || produto.preco_custo || '';
                document.getElementById('mov_tipo').value = 'Entrada';
                document.getElementById('mov_quantidade').value = '';
                document.getElementById('mov_observacao').value = 'Registro de movimentação manual';
            } else {
                formulario.reset();
            }

            modal.classList.add('show');
        }

        function fecharModalMovimentoTela() {
            document.getElementById('modalMovimento').classList.remove('show');
        }

        window.addEventListener('click', function (event) {
            const modalProduto = document.getElementById('modalProduto');
            const modalMovimento = document.getElementById('modalMovimento');
            if (event.target === modalProduto) {
                fecharModalProdutoTela();
            }
            if (event.target === modalMovimento) {
                fecharModalMovimentoTela();
            }
        });

        <?php if ($abrir_modal_produto): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('modalProduto').classList.add('show');
        });
        <?php endif; ?>

        <?php if ($abrir_modal_movimento): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('modalMovimento').classList.add('show');
        });
        <?php endif; ?>
    </script>
</body>
</html>