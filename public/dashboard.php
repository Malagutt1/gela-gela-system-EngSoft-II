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

$nome_usuario = $_SESSION['nome'] ?? 'User';
$inicial = strtoupper(substr($nome_usuario, 0, 1));

$erro = '';


$periodo  = $_GET['periodo']  ?? '7d';
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$data_ini = $_GET['data_ini'] ?? date('Y-m-d', strtotime('-6 days'));

// Atalhos de período
switch ($periodo) {
    case 'hoje': $data_ini = date('Y-m-d');       $data_fim = date('Y-m-d'); break;
    case '7d':   $data_ini = date('Y-m-d', strtotime('-6 days')); $data_fim = date('Y-m-d'); break;
    case '30d':  $data_ini = date('Y-m-d', strtotime('-29 days')); $data_fim = date('Y-m-d'); break;
    case 'mes':  $data_ini = date('Y-m-01');      $data_fim = date('Y-m-d'); break;
}

$dt_ini_sql = $data_ini . ' 00:00:00';
$dt_fim_sql = $data_fim . ' 23:59:59';



$q = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_vendas,
        COALESCE(SUM(valor_total), 0) AS faturamento
    FROM vendas 
    WHERE data_venda BETWEEN :ini AND :fim 
    AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$resultado = $q->fetch();


// Faturamento líquido + descontos
$q = $pdo->prepare("
    SELECT
        COALESCE(SUM(valor_total - desconto_aplicado), 0) AS fat_liquido,
        COALESCE(SUM(desconto_aplicado), 0)               AS total_desc
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$fat = $q->fetch();

// CMV (gastos)
$q = $pdo->prepare("
    SELECT COALESCE(SUM(iv.quantidade * e.custo_medio), 0) AS cmv
    FROM itens_venda iv
    JOIN vendas v ON v.venda_id = iv.venda_id
    JOIN estoque e ON e.produto_id = iv.produto_id
    WHERE v.data_venda BETWEEN :ini AND :fim AND v.status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$gastos = $q->fetch();

// Ticket médio + consumo total
$q = $pdo->prepare("
    SELECT
        COALESCE(AVG(valor_total), 0) AS ticket_medio,
        COALESCE(SUM(peso_total), 0)  AS consumo_kg
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$consumo = $q->fetch();

// Faturamento líquido + descontos
$q = $pdo->prepare("
    SELECT
        COALESCE(SUM(valor_total - desconto_aplicado), 0) AS fat_liquido,
        COALESCE(SUM(desconto_aplicado), 0)               AS total_desc
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$fat = $q->fetch();

// CMV (gastos)
$q = $pdo->prepare("
    SELECT COALESCE(SUM(iv.quantidade * e.custo_medio), 0) AS cmv
    FROM itens_venda iv
    JOIN vendas v ON v.venda_id = iv.venda_id
    JOIN estoque e ON e.produto_id = iv.produto_id
    WHERE v.data_venda BETWEEN :ini AND :fim AND v.status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$gastos = $q->fetch();

// Ticket médio + consumo total
$q = $pdo->prepare("
    SELECT
        COALESCE(AVG(valor_total), 0) AS ticket_medio,
        COALESCE(SUM(peso_total), 0)  AS consumo_kg
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$consumo = $q->fetch();

// Detecta se é 1 dia ou múltiplos
$diff_dias = (int)((strtotime($data_fim) - strtotime($data_ini)) / 86400);
$modo_dia  = ($diff_dias === 0);

// Evolução por dia ou por hora
if ($modo_dia) {
    $q = $pdo->prepare("
        SELECT
            HOUR(data_venda) AS label_raw,
            LPAD(HOUR(data_venda),2,'0') AS label,
            COALESCE(SUM(valor_total), 0) AS faturamento,
            COALESCE(SUM(peso_total), 0)  AS consumo
        FROM vendas
        WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
        GROUP BY HOUR(data_venda)
        ORDER BY label_raw
    ");
} else {
    $q = $pdo->prepare("
        SELECT
            DATE(data_venda) AS label_raw,
            DATE_FORMAT(data_venda,'%d/%m') AS label,
            COALESCE(SUM(valor_total), 0) AS faturamento,
            COALESCE(SUM(peso_total), 0)  AS consumo
        FROM vendas
        WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
        GROUP BY DATE(data_venda)
        ORDER BY label_raw
    ");
}
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$evolucao_rows = $q->fetchAll();

// CMV por label (para o gráfico)
if ($modo_dia) {
    $q = $pdo->prepare("
        SELECT HOUR(v.data_venda) AS label_raw,
               COALESCE(SUM(iv.quantidade * e.custo_medio), 0) AS gasto
        FROM vendas v
        JOIN itens_venda iv ON iv.venda_id = v.venda_id
        JOIN estoque e ON e.produto_id = iv.produto_id
        WHERE v.data_venda BETWEEN :ini AND :fim AND v.status = 'Confirmado'
        GROUP BY HOUR(v.data_venda) ORDER BY label_raw
    ");
} else {
    $q = $pdo->prepare("
        SELECT DATE(v.data_venda) AS label_raw,
               COALESCE(SUM(iv.quantidade * e.custo_medio), 0) AS gasto
        FROM vendas v
        JOIN itens_venda iv ON iv.venda_id = v.venda_id
        JOIN estoque e ON e.produto_id = iv.produto_id
        WHERE v.data_venda BETWEEN :ini AND :fim AND v.status = 'Confirmado'
        GROUP BY DATE(v.data_venda) ORDER BY label_raw
    ");
}
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$cmv_map = [];
foreach ($q->fetchAll() as $r) $cmv_map[$r['label_raw']] = (float)$r['gasto'];

// Serializa para o JS
$js_labels = $js_fat = $js_gastos = $js_consumo = [];
foreach ($evolucao_rows as $r) {
    $js_labels[]  = $r['label'];
    $js_fat[]     = round((float)$r['faturamento'], 2);
    $js_gastos[]  = round((float)($cmv_map[$r['label_raw']] ?? 0), 2);
    $js_consumo[] = round((float)$r['consumo'], 3);
}
?>






<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Dashboard</title>
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
            </header>

            

            <section class="main">

            <div class="box">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Dashboard — Sorveteria</h3>
                    <form method="GET" action="dashboard">

   <form method="GET" action="dashboard">
    <div class="dash-filtro">

        <div class="periodo-btns">
            <a href="dashboard?periodo=hoje" class="pbtn <?= $periodo === 'hoje' ? 'ativo' : '' ?>">Hoje</a>
            <a href="dashboard?periodo=7d"   class="pbtn <?= $periodo === '7d'   ? 'ativo' : '' ?>">7 dias</a>
            <a href="dashboard?periodo=30d"  class="pbtn <?= $periodo === '30d'  ? 'ativo' : '' ?>">30 dias</a>
            <a href="dashboard?periodo=mes"  class="pbtn <?= $periodo === 'mes'  ? 'ativo' : '' ?>">Mês</a>
        </div>

        <div class="periodo-datas">
            <input type="date" name="data_ini" value="<?= $data_ini ?>" onchange="this.form.periodo.value='custom'">
            <span>→</span>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" onchange="this.form.periodo.value='custom'">
        </div>

        <input type="hidden" name="periodo" value="<?= $periodo ?>">
        <button type="submit" class="btn-filtrar"><i class="fa-solid fa-filter"></i> Filtrar</button>

    </div>
</form>
</form>

                    
                </div>


                
             <div class="grid-cards">
                   <div class="card">
    <div class="kpi-label">
        <i class="fa-solid fa-dollar-sign"></i> Faturamento
    </div>
    <div class="kpi-valor">
        R$ <?= number_format($resultado['faturamento'], 2, ',', '.') ?>
    </div>
</div>

                  <div class="card">
    <div class="kpi-label"><i class="fa-solid fa-receipt"></i> Fat. líquido</div>
    <div class="kpi-valor">R$ <?= number_format($fat['fat_liquido'], 2, ',', '.') ?></div>
    <div class="kpi-sub">após descontos</div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-arrow-trend-down"></i> Gastos (CMV)</div>
    <div class="kpi-valor">R$ <?= number_format($gastos['cmv'], 2, ',', '.') ?></div>
    <div class="kpi-sub" style="color:#c0392b;">
        <?= $resultado['faturamento'] > 0 ? number_format($gastos['cmv'] / $resultado['faturamento'] * 100, 1) : 0 ?>% do faturamento
    </div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-ticket"></i> Ticket médio</div>
    <div class="kpi-valor">R$ <?= number_format($consumo['ticket_medio'], 2, ',', '.') ?></div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-weight-scale"></i> Consumo total</div>
    <div class="kpi-valor"><?= number_format($consumo['consumo_kg'], 3, ',', '.') ?> kg</div>
    <div class="kpi-sub">soma de peso_total</div>
</div>
<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-receipt"></i> Fat. líquido</div>
    <div class="kpi-valor">R$ <?= number_format($fat['fat_liquido'], 2, ',', '.') ?></div>
    <div class="kpi-sub">após descontos</div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-arrow-trend-down"></i> Gastos (CMV)</div>
    <div class="kpi-valor">R$ <?= number_format($gastos['cmv'], 2, ',', '.') ?></div>
    <div class="kpi-sub" style="color:#c0392b;">
        <?= $resultado['faturamento'] > 0 ? number_format($gastos['cmv'] / $resultado['faturamento'] * 100, 1) : 0 ?>% do faturamento
    </div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-ticket"></i> Ticket médio</div>
    <div class="kpi-valor">R$ <?= number_format($consumo['ticket_medio'], 2, ',', '.') ?></div>
</div>

<div class="card">
    <div class="kpi-label"><i class="fa-solid fa-weight-scale"></i> Consumo total</div>
    <div class="kpi-valor"><?= number_format($consumo['consumo_kg'], 3, ',', '.') ?> kg</div>
    <div class="kpi-sub">soma de peso_total</div>
</div>
                </div>

                <div class="box">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> EVOLUÇÃO DIARIA</h3>
<div class="box">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
        <h3><i class="fa-solid fa-chart-bar"></i> Faturamento · Gastos · Consumo por <?= $modo_dia ? 'hora' : 'dia' ?></h3>
    </div>
    <div style="display:flex; gap:14px; margin-bottom:10px;">
        <span style="display:flex;align-items:center;gap:5px;font-size:.8rem;color:var(--text-muted);">
            <span style="width:10px;height:10px;background:#11417b;border-radius:2px;display:inline-block;"></span>Faturamento
        </span>
        <span style="display:flex;align-items:center;gap:5px;font-size:.8rem;color:var(--text-muted);">
            <span style="width:10px;height:10px;background:#e74c3c;border-radius:2px;display:inline-block;"></span>Gastos
        </span>
        <span style="display:flex;align-items:center;gap:5px;font-size:.8rem;color:var(--text-muted);">
            <span style="width:10px;height:10px;background:#27ae60;border-radius:2px;display:inline-block;"></span>Consumo kg
        </span>
    </div>
    <div style="position:relative; height:220px;">
        <canvas id="chEv"></canvas>
    </div>
</div>
                    
                </div>
                

               <!-- Título da seção -->
<p class="sec-title">Análise de vendas</p>

<!-- Linha 1: Sabores + Pagamentos -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <div class="box">
        <h3><i class="fa-solid fa-ice-cream"></i> Sabores mais vendidos</h3>
        <!-- gráfico de pizza vai aqui -->
    </div>

    <div class="box">
        <h3><i class="fa-solid fa-credit-card"></i> Formas de pagamento</h3>
        <!-- barras vão aqui -->
    </div>

</div>

<!-- Linha 2: Heatmap + Promoções -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <div class="box">
        <h3><i class="fa-solid fa-clock"></i> Horário de pico</h3>
        <!-- células do heatmap vão aqui -->
    </div>

    <div class="box">
        <h3><i class="fa-solid fa-tag"></i> Vendas com promoção</h3>
        <!-- barra de promoção vai aqui -->
    </div>

</div>

<!-- AVALIAÇÕES -->
<p class="sec-title">Avaliações</p>

<div class="box" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 30px;">
        <div><!-- nota + estrelas --></div>
        <div style="flex: 1;"><!-- gráfico --></div>
    </div>
</div>

<!-- ÚLTIMAS VENDAS -->
<p class="sec-title">Últimas vendas</p>

<div class="box">
    <div style="display: flex; gap: 10px; margin-bottom: 14px;">
        <h3 style="flex: 1;">Vendas recentes</h3>
        <select></select>
        <input type="text" placeholder="buscar">
    </div>
    

</div>

<!-- ESTOQUE -->
<p class="sec-title">Estoque — tempo real, sem filtro de período</p>

<!-- Alertas -->
<div class="alert">2 produtos com estoque crítico — reposição urgente necessária</div>
<div class="alert">1 produto com estoque baixo · 2 produtos com validade próxima (30 dias)</div>

<!-- Grid Sorvetes + Acompanhamentos -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <div class="box">
        <h3>Sorvetes</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <div><!-- item estoque --></div>
            <div><!-- item estoque --></div>
            <div><!-- item estoque --></div>
        </div>
    </div>

    <div class="box">
        <h3>Acompanhamentos</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <div><!-- item estoque --></div>
            <div><!-- item estoque --></div>
            <div><!-- item estoque --></div>
        </div>
    </div>

</div>

<!-- Validades próximas -->
<div class="box">
    <div style="display: flex; justify-content: space-between; margin-bottom: 14px;">
        <h3>Validades próximas</h3>
        <span>próximos 30 dias</span>
    </div>
    <div>
        <div style="display: flex; justify-content: space-between;">
            <span>Nome do produto</span>
            <span>vence 00/00/0000</span>
            <span class="badge">X dias</span>
        </div>
        <!-- mais linhas -->
    </div>
</div>
            </section>
        </main>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
const LABELS  = <?= json_encode($js_labels) ?>;
const FAT     = <?= json_encode($js_fat) ?>;
const GASTOS  = <?= json_encode($js_gastos) ?>;
const CONSUMO = <?= json_encode($js_consumo) ?>;

new Chart(document.getElementById('chEv'), {
    data: {
        labels: LABELS,
        datasets: [
            {
                type: 'bar',
                label: 'Faturamento',
                data: FAT,
                backgroundColor: '#11417b',
                borderRadius: 4,
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: 'Gastos',
                data: GASTOS,
                backgroundColor: '#e74c3c',
                borderRadius: 4,
                yAxisID: 'y'
            },
          {
    type: 'bar',
    label: 'Consumo kg',
    data: CONSUMO,
    backgroundColor: '#27ae60',
    borderRadius: 4,
    yAxisID: 'y2'
}
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { font: { size: 11 } }, grid: { display: false } },
            y: {
                ticks: { callback: v => 'R$' + v.toLocaleString('pt-BR') },
                grid: { color: 'rgba(0,0,0,.05)' }
            },
            y2: {
                position: 'right',
                ticks: { callback: v => v + ' kg' },
                grid: { display: false }
            }
        }
    }
});
</script>