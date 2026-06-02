<?php
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

// ─── Controle de acesso ───────────────────────────────────────────────
$perfil = strtolower($_SESSION['perfil'] ?? $_SESSION['tipo'] ?? 'funcionario'); // 'gerente' ou 'funcionario'
$is_gerente = ($perfil === 'gerente');
 
// ─── Período selecionado ──────────────────────────────────────────────
$periodo  = $_GET['periodo']  ?? 'mes';
$tipo     = $_GET['tipo']     ?? 'vendas';
 
// Bloqueia financeiro para funcionário
if (!$is_gerente && $tipo === 'financeiro') {
    $tipo = 'vendas';
}
 
// Define intervalo de datas
switch ($periodo) {
    case 'dia':
        $data_inicio = date('Y-m-d');
        $data_fim    = date('Y-m-d');
        $label_periodo = 'Hoje (' . date('d/m/Y') . ')';
        break;
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('monday this week'));
        $data_fim    = date('Y-m-d', strtotime('sunday this week'));
        $label_periodo = 'Esta Semana';
        break;
    default: // mes
        $data_inicio = date('Y-m-01');
        $data_fim    = date('Y-m-t');
        $label_periodo = 'Este Mês (' . date('m/Y') . ')';
}
 
// ─── Registrar lançamento (POST) ──────────────────────────────────────
$msg_sucesso = '';
$msg_erro    = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
 
    // Lançar venda
    if ($acao === 'lancar_venda') {
        $data_v          = $_POST['data_venda']     ?? date('Y-m-d');
        $peso            = floatval($_POST['peso_total']  ?? 0);
        $valor           = floatval($_POST['valor_total']  ?? 0);
        $forma_pagamento = $_POST['forma_pagamento'] ?? 'Dinheiro';
        $usuario_id      = $_SESSION['usuario_id'] ?? 1;
 
        $stmt = $pdo->prepare("INSERT INTO vendas (data_venda, usuario_id, peso_total, valor_total, forma_pagamento) VALUES (?,?,?,?,?)");
        if ($stmt->execute([$data_v, $usuario_id, $peso, $valor, $forma_pagamento])) {
            $msg_sucesso = 'Venda registrada com sucesso!';
        } else {
            $msg_erro = 'Erro ao registrar venda.';
        }
    }
 
    // Lançar despesa (gerente)
    if ($acao === 'lancar_despesa' && $is_gerente) {
        if (!$tem_tabela_despesas) {
            $msg_erro = 'Tabela de despesas não encontrada no banco de dados.';
        } else {
            $data_d  = $_POST['data_despesa'] ?? date('Y-m-d');
            $desc    = trim($_POST['descricao'] ?? '');
            $cat     = trim($_POST['categoria_desp'] ?? '');
            $val_d   = floatval($_POST['valor_despesa'] ?? 0);
 
            $stmt = $pdo->prepare("INSERT INTO despesas (data_despesa, descricao, categoria, valor) VALUES (?,?,?,?)");
            if ($stmt->execute([$data_d, $desc, $cat, $val_d])) {
                $msg_sucesso = 'Despesa registrada com sucesso!';
            } else {
                $msg_erro = 'Erro ao registrar despesa.';
            }
        }
    }
}
 
// ─── Queries de dados ─────────────────────────────────────────────────
 
$tem_tabela_despesas = (bool)$pdo->query("SHOW TABLES LIKE 'despesas'")->fetchColumn();
 
// KPIs de Vendas
$kpi_vendas = $pdo->prepare("
    SELECT
        COALESCE(SUM(peso_total),0)       AS total_peso,
        COALESCE(SUM(valor_total),0)      AS total_valor,
        COALESCE(SUM(desconto_aplicado),0) AS total_desconto,
        COUNT(*)                          AS total_registros
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
");
$kpi_vendas->execute([$data_inicio, $data_fim]);
$kv = $kpi_vendas->fetch(PDO::FETCH_ASSOC);
 
// KPIs de Despesas (gerente)
$total_despesas = 0;
if ($is_gerente && $tem_tabela_despesas) {
    $kpi_desp = $pdo->prepare("SELECT COALESCE(SUM(valor),0) AS total FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $kpi_desp->execute([$data_inicio, $data_fim]);
    $total_despesas = $kpi_desp->fetchColumn();
}
 
$lucro_estimado = $kv['total_valor'] - $kv['total_desconto'] - $total_despesas;
 
// Tabela de vendas
$rows_vendas = $pdo->prepare("SELECT * FROM vendas WHERE data_venda BETWEEN ? AND ? ORDER BY data_venda DESC");
$rows_vendas->execute([$data_inicio, $data_fim]);
$lista_vendas = $rows_vendas->fetchAll(PDO::FETCH_ASSOC);
 
// Tabela de estoque
$lista_estoque = $pdo->query("
    SELECT
        e.*,
        p.nome AS produto,
        p.categoria,
        p.unidade_medida AS unidade,
        e.quantidade_disponivel AS quantidade,
        10 AS estoque_minimo,
        DATEDIFF(e.validade, CURDATE()) AS dias_validade,
        CASE
            WHEN e.quantidade_disponivel <= 10 THEN 'danger'
            WHEN DATEDIFF(e.validade, CURDATE()) <= 7 AND e.validade IS NOT NULL THEN 'warn'
            ELSE 'ok'
        END AS status
    FROM estoque e
    JOIN produtos p ON e.produto_id = p.produto_id
    ORDER BY status DESC, p.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);
 
// Tabela de despesas (gerente)
$lista_despesas = [];
if ($is_gerente && $tem_tabela_despesas) {
    $rows_desp = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa BETWEEN ? AND ? ORDER BY data_despesa DESC");
    $rows_desp->execute([$data_inicio, $data_fim]);
    $lista_despesas = $rows_desp->fetchAll(PDO::FETCH_ASSOC);
}
 
// Dados para o gráfico de evolução diária (vendas)
$grafico = $pdo->prepare("
    SELECT data_venda, SUM(valor_total) AS total_dia
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
    GROUP BY data_venda
    ORDER BY data_venda ASC
");
$grafico->execute([$data_inicio, $data_fim]);
$dados_grafico = $grafico->fetchAll(PDO::FETCH_ASSOC);
$labels_grafico = json_encode(array_map(fn($r) => date('d/m', strtotime($r['data_venda'])), $dados_grafico));
$valores_grafico = json_encode(array_map(fn($r) => (float)$r['total_dia'], $dados_grafico));
 
// Helper formatação
function brl($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
function kg($v)  { return number_format($v, 2, ',', '.') . ' kg'; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Relatórios</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
 </head>
 
<body>
<div class="layout">
 
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="logo-area">
            <img src="ASSETS/IMG/icon.png" alt="Logo">
            <span>Gela-Gela</span>
        </div>
        <nav>
            <?php if ($is_gerente): ?>
            <a href="vendas"><i class="fa-solid fa-cart-shopping"></i> Nova Venda</a>
            <?php endif; ?>
            <a href="dashboard"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="produtos"><i class="fa-solid fa-boxes-stacked"></i> Produtos</a>
            <a href="clientes"><i class="fa-solid fa-users"></i> Clientes</a>
            <a href="fornecedores"><i class="fa-solid fa-truck"></i> Fornecedores</a>
            <a href="promo"><i class="fa-solid fa-tags"></i> Promoções</a>
            <a href="user"><i class="fa-solid fa-user-shield"></i> Usuários</a>
            <a href="backup"><i class="fa-solid fa-database"></i> Backup</a>
            <a href="logs"><i class="fa-solid fa-file-lines"></i> Logs</a>
            <a href="relatorio" class="active"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
        </nav>
    </aside>
 
    <!-- CONTEÚDO -->
    <main class="content content-tab-<?= htmlspecialchars($tipo) ?>">
        <header class="topbar">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <h1>Relatórios</h1>
            <?php if ($is_gerente): ?>
                <span style="margin-left:auto; background:var(--soft); color:var(--secondary); font-size:12px; font-weight:700; padding:5px 12px; border-radius:20px;">
                    <i class="fa fa-shield-halved"></i> Gerente
                </span>
            <?php endif; ?>
        </header>
 
        <section class="main">
 
            <?php if ($msg_sucesso): ?>
                <div class="alert success"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($msg_sucesso) ?></div>
            <?php endif; ?>
            <?php if ($msg_erro): ?>
                <div class="alert error"><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($msg_erro) ?></div>
            <?php endif; ?>
 
            <!-- FILTRO DE PERÍODO -->
            <form method="GET" class="filter-bar">
                <div>
                    <label>Período</label>
                    <select name="periodo" onchange="this.form.submit()">
                        <option value="dia"    <?= $periodo==='dia'   ?'selected':'' ?>>Hoje</option>
                        <option value="semana" <?= $periodo==='semana'?'selected':'' ?>>Esta Semana</option>
                        <option value="mes"    <?= $periodo==='mes'   ?'selected':'' ?>>Este Mês</option>
                    </select>
                </div>
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                <span style="font-size:13px; color:var(--text-muted); align-self:center;">
                    <i class="fa fa-calendar-days"></i> <?= $label_periodo ?>
                </span>
            </form>
 
            <!-- KPIs RÁPIDOS -->
            <div class="kpi-grid vendas-kpis">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa fa-scale-balanced"></i> Peso Total Vendido</div>
                    <div class="kpi-valor"><?= kg($kv['total_peso']) ?></div>
                    <div class="kpi-sub"><?= $kv['total_registros'] ?> lançamento(s) no período</div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-label"><i class="fa fa-dollar-sign"></i> Faturamento</div>
                    <div class="kpi-valor"><?= brl($kv['total_valor']) ?></div>
                    <div class="kpi-sub">Valor total das vendas</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label"><i class="fa fa-percentage"></i> Total de Descontos</div>
                    <div class="kpi-valor"><?= brl($kv['total_desconto']) ?></div>
                    <div class="kpi-sub">Descontos aplicados nas vendas</div>
                </div>
                <?php if ($is_gerente): ?>
                <div class="kpi-card green">
                    <div class="kpi-label"><i class="fa fa-arrow-trend-up"></i> Lucro Estimado</div>
                    <div class="kpi-valor" style="color:<?= $lucro_estimado >= 0 ? '#27ae60' : '#e74c3c' ?>">
                        <?= brl($lucro_estimado) ?>
                    </div>
                    <div class="kpi-sub">Faturamento − Insumos − Despesas</div>
                </div>
                <?php endif; ?>
            </div>
 
            <!-- ABAS -->
            <div class="tab-nav">
                <button class="tab-btn <?= $tipo==='vendas'?'active':'' ?>" onclick="mudarAba('vendas', this)">
                    <i class="fa fa-cart-shopping"></i> Vendas
                </button>
                <button class="tab-btn <?= $tipo==='estoque'?'active':'' ?>" onclick="mudarAba('estoque', this)">
                    <i class="fa fa-boxes-stacked"></i> Estoque
                </button>
                <?php if ($is_gerente): ?>
                <button class="tab-btn <?= $tipo==='financeiro'?'active':'' ?>" onclick="mudarAba('financeiro', this)">
                    <i class="fa fa-chart-pie"></i> Financeiro
                </button>
                <?php else: ?>
                <button class="tab-btn locked" title="Apenas gerentes podem acessar" type="button">
                    <i class="fa fa-lock"></i> Financeiro
                </button>
                <?php endif; ?>
            </div>
 
            <!-- ═══ ABA: VENDAS ════════════════════════════════════ -->
            <div class="tab-panel <?= $tipo==='vendas'?'active':'' ?>" id="tab-vendas">
 
                <!-- Gráfico -->
                <div class="chart-wrap">
                    <h3><i class="fa fa-chart-line" style="color:var(--primary)"></i> Evolução das Vendas — <?= $label_periodo ?></h3>
                    <canvas id="grafico-vendas" height="90"></canvas>
                </div>
 
            </div><!-- /tab-vendas -->
 
            <!-- ═══ ABA: ESTOQUE ═══════════════════════════════════ -->
            <div class="tab-panel <?= $tipo==='estoque'?'active':'' ?>" id="tab-estoque">
                <div class="box">
                    <div class="header-box">
                        <h3><i class="fa fa-boxes-stacked" style="color:var(--secondary)"></i> Controle de Estoque</h3>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <span style="font-size:12px; color:var(--text-muted);">
                                <span class="status-dot danger"></span>Crítico &nbsp;
                                <span class="status-dot warn"></span>Atenção &nbsp;
                                <span class="status-dot ok"></span>Normal
                            </span>
                        </div>
                    </div>
 
                    <?php if (empty($lista_estoque)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:30px 0;">
                            <i class="fa fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; color:#ddd"></i>
                            Nenhum produto no estoque cadastrado.
                        </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Quantidade</th>
                                    <th>Mínimo</th>
                                    <th>Validade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_estoque as $e):
                                    $s = $e['status'];
                                    $labels = ['danger'=>'Crítico','warn'=>'Atenção','ok'=>'Normal'];
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($e['produto']) ?></strong></td>
                                    <td><?= htmlspecialchars($e['categoria'] ?: '—') ?></td>
                                    <td><?= number_format($e['quantidade'], 2, ',', '.') ?> <?= $e['unidade'] ?></td>
                                    <td><?= number_format($e['estoque_minimo'], 2, ',', '.') ?> <?= $e['unidade'] ?></td>
                                    <td>
                                        <?php if ($e['validade']): ?>
                                            <?= date('d/m/Y', strtotime($e['validade'])) ?>
                                            <?php if ($e['dias_validade'] !== null && $e['dias_validade'] <= 7): ?>
                                                <span style="font-size:11px; color:#e67e22; font-weight:700;">
                                                    (<?= $e['dias_validade'] ?> dias)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s ?>">
                                            <span class="status-dot <?= $s ?>"></span><?= $labels[$s] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- /tab-estoque -->
 
            <!-- ═══ ABA: FINANCEIRO (só gerente) ══════════════════ -->
            <?php if ($is_gerente): ?>
            <div class="tab-panel <?= $tipo==='financeiro'?'active':'' ?>" id="tab-financeiro">
 
                <!-- Cards financeiros extras -->
                <div class="kpi-grid" style="margin-bottom:20px;">
                    <div class="kpi-card blue">
                        <div class="kpi-label"><i class="fa fa-money-bill-trend-up"></i> Faturamento Bruto</div>
                        <div class="kpi-valor"><?= brl($kv['total_valor']) ?></div>
                        <div class="kpi-sub">Total de vendas no período</div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-label"><i class="fa fa-file-invoice"></i> Total de Despesas</div>
                        <div class="kpi-valor"><?= brl($total_despesas) ?></div>
                        <div class="kpi-sub">Despesas operacionais</div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-label"><i class="fa fa-percentage"></i> Total de Descontos</div>
                        <div class="kpi-valor"><?= brl($kv['total_desconto']) ?></div>
                        <div class="kpi-sub">Descontos aplicados nas vendas</div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-label"><i class="fa fa-sack-dollar"></i> Lucro Líquido</div>
                        <div class="kpi-valor" style="color:<?= $lucro_estimado>=0?'#27ae60':'#e74c3c' ?>">
                            <?= brl($lucro_estimado) ?>
                        </div>
                        <div class="kpi-sub">Faturamento − Insumos − Despesas</div>
                    </div>
                </div>
 
                <!-- Despesas -->
                <div class="box">
                    <div class="header-box">
                        <h3><i class="fa fa-file-invoice-dollar" style="color:var(--primary)"></i> Despesas Operacionais</h3>
                        <?php if ($tem_tabela_despesas): ?>
                        <button class="btn-lancar" onclick="abrirModal('modal-despesa')">
                            <i class="fa fa-plus"></i> Registrar Despesa
                        </button>
                        <?php else: ?>
                        <span style="color:var(--text-muted); font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa fa-triangle-exclamation" style="color:#e67e22"></i> Dados de despesas não disponíveis
                        </span>
                        <?php endif; ?>
                    </div>
 
                    <?php if (!$tem_tabela_despesas): ?>
                        <p style="color:#e67e22; text-align:center; padding:30px 0; font-weight:700;">
                            A tabela de despesas não existe no banco de dados.
                        </p>
                    <?php elseif (empty($lista_despesas)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:30px 0;">
                            <i class="fa fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; color:#ddd"></i>
                            Nenhuma despesa registrada no período.
                        </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_despesas as $d): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($d['data_despesa'])) ?></td>
                                    <td><?= htmlspecialchars($d['descricao']) ?></td>
                                    <td>
                                        <span class="tag-resumo"><?= htmlspecialchars($d['categoria'] ?: 'Geral') ?></span>
                                    </td>
                                    <td style="font-weight:700; color:#e74c3c;"><?= brl($d['valor']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--soft); font-weight:700;">
                                    <td colspan="3">TOTAL DESPESAS</td>
                                    <td style="color:#e74c3c;"><?= brl($total_despesas) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- /tab-financeiro -->
            <?php else: ?>
            <div class="tab-panel" id="tab-financeiro">
                <div class="box acesso-negado">
                    <i class="fa fa-lock"></i>
                    <h3>Acesso Restrito</h3>
                    <p>O relatório financeiro está disponível apenas para gerentes.</p>
                </div>
            </div>
            <?php endif; ?>
 
        </section>
    </main>
</div>
 
<!-- ═══ MODAL: REGISTRAR VENDA ════════════════════════════════════════ -->
<div class="modal" id="modal-venda">
    <div class="modal-content">
        <h3><i class="fa fa-cart-shopping" style="color:var(--primary)"></i> Registrar Venda</h3>
        <form method="POST">
            <input type="hidden" name="acao" value="lancar_venda">
            <div class="grid-form">
                <div class="form-group">
                    <label>Data da Venda</label>
                    <input type="date" name="data_venda" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Peso Vendido (kg)</label>
                    <input type="number" name="peso_total" step="0.01" min="0" placeholder="Ex: 12.50" required>
                </div>
                <div class="form-group">
                    <label>Valor Total (R$)</label>
                    <input type="number" name="valor_total" step="0.01" min="0" placeholder="Ex: 350.00" required>
                </div>
                <div class="form-group">
                    <label>Forma de Pagamento</label>
                    <select name="forma_pagamento" required>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Cartao_Credito">Cartão de Crédito</option>
                        <option value="Cartao_Debito">Cartão de Débito</option>
                        <option value="Pix">Pix</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <button type="submit" class="btn btn-secondary" style="flex:1; background:var(--secondary); color:#fff;">
                    <i class="fa fa-floppy-disk"></i> Salvar
                </button>
                <button type="button" class="btn btn-secondary" style="flex:0.4;" onclick="fecharModal('modal-venda')">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
 
<?php if ($is_gerente): ?>
<!-- ═══ MODAL: REGISTRAR DESPESA ══════════════════════════════════════ -->
<div class="modal" id="modal-despesa">
    <div class="modal-content">
        <h3><i class="fa fa-file-invoice-dollar" style="color:var(--primary)"></i> Registrar Despesa</h3>
        <form method="POST">
            <input type="hidden" name="acao" value="lancar_despesa">
            <div class="grid-form">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_despesa" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria_desp">
                        <option value="Aluguel">Aluguel</option>
                        <option value="Energia">Energia</option>
                        <option value="Folha">Folha de Pagamento</option>
                        <option value="Manutenção">Manutenção</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <input type="text" name="descricao" placeholder="Ex: Aluguel do espaço" required>
            </div>
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" name="valor_despesa" step="0.01" min="0" placeholder="Ex: 1500.00" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <button type="submit" class="btn" style="flex:1; background:var(--secondary); color:#fff;">
                    <i class="fa fa-floppy-disk"></i> Salvar
                </button>
                <button type="button" class="btn btn-secondary" style="flex:0.4;" onclick="fecharModal('modal-despesa')">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
 
<script src="ASSETS/JS/sidebar.js"></script>
<script src="ASSETS/JS/app.js"></script>
<script>
// ── Troca de abas ─────────────────────────────────────────────────────
function mudarAba(aba, button) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
 
    const target = document.getElementById('tab-' + aba);
    if (target) target.classList.add('active');
    if (button) button.classList.add('active');
 
    const content = document.querySelector('.content');
    if (content) {
        content.classList.remove('content-tab-vendas', 'content-tab-estoque', 'content-tab-financeiro');
        content.classList.add('content-tab-' + aba);
    }
 
    // Atualiza URL sem reload
    const url = new URL(window.location.href);
    url.searchParams.set('tipo', aba);
    window.history.replaceState({}, '', url);
}
 
// ── Modais ────────────────────────────────────────────────────────────
function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function fecharModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) fecharModal(this.id);
    });
});
 
// ── Gráfico de Vendas ─────────────────────────────────────────────────
const labels  = <?= $labels_grafico  ?: '[]' ?>;
const valores = <?= $valores_grafico ?: '[]' ?>;
 
if (labels.length > 0) {
    const ctx = document.getElementById('grafico-vendas').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Faturamento (R$)',
                data: valores,
                backgroundColor: 'rgba(198,116,106,0.25)',
                borderColor: '#c6746a',
                borderWidth: 2,
                borderRadius: 6,
                pointBackgroundColor: '#c6746a'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR', {minimumFractionDigits:2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    },
                    grid: { color: '#f0f0f0' }
                },
                x: { grid: { display: false } }
            }
        }
    });
} else {
    const canvas = document.getElementById('grafico-vendas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.height = 60;
        ctx.fillStyle = '#999';
        ctx.font = '14px Segoe UI';
        ctx.textAlign = 'center';
        ctx.fillText('Sem dados de vendas para o período selecionado.', canvas.width / 2, 40);
    }
}
</script>
</body>
</html>