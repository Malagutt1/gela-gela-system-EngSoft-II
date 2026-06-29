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

// Sabores mais vendidos
$q = $pdo->prepare("
    SELECT iv.sabor, COALESCE(SUM(iv.quantidade), 0) AS total_kg
    FROM itens_venda iv
    JOIN vendas v ON v.venda_id = iv.venda_id
    WHERE v.data_venda BETWEEN :ini AND :fim
      AND v.status = 'Confirmado'
      AND iv.sabor IS NOT NULL
    GROUP BY iv.sabor
    ORDER BY total_kg DESC
    LIMIT 6
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$sabores_rows = $q->fetchAll();

$js_sab_labels = array_column($sabores_rows, 'sabor');
$js_sab_data   = array_map(fn($r) => round((float)$r['total_kg'], 3), $sabores_rows);
// Formas de pagamento
$q = $pdo->prepare("
    SELECT forma_pagamento, COUNT(*) AS qtd
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
    GROUP BY forma_pagamento
    ORDER BY qtd DESC
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$pagamentos_rows = $q->fetchAll();
$total_pag = array_sum(array_column($pagamentos_rows, 'qtd'));
// Heatmap
$q = $pdo->prepare("
    SELECT HOUR(data_venda) AS hora, COUNT(*) AS qtd
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
    GROUP BY HOUR(data_venda)
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$heat_map = [];
foreach ($q->fetchAll() as $r) $heat_map[(int)$r['hora']] = (int)$r['qtd'];
$heat_max = $heat_map ? max($heat_map) : 1;

// Promoções
// Promoções (query própria)
$q = $pdo->prepare("
    SELECT
        COUNT(CASE WHEN promocao_id IS NOT NULL THEN 1 END) AS com_promo,
        COALESCE(SUM(CASE WHEN promocao_id IS NOT NULL THEN desconto_aplicado ELSE 0 END), 0) AS total_desc_promo,
        COUNT(*) AS total_vendas
    FROM vendas
    WHERE data_venda BETWEEN :ini AND :fim AND status = 'Confirmado'
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$promo = $q->fetch();

$desc_medio = $promo['com_promo'] > 0 ? $promo['total_desc_promo'] / $promo['com_promo'] : 0;
$sem_promo  = $promo['total_vendas'] - $promo['com_promo'];
$pct_promo  = $promo['total_vendas'] > 0 ? round($promo['com_promo'] / $promo['total_vendas'] * 100) : 0;

// Distribuição de avaliações
$q = $pdo->prepare("
    SELECT nota, COUNT(*) AS qtd
    FROM feedbacks_clientes
    WHERE data_registro BETWEEN :ini AND :fim
    GROUP BY nota ORDER BY nota DESC
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$dist_map = [];
foreach ($q->fetchAll() as $r) $dist_map[(int)$r['nota']] = (int)$r['qtd'];
$js_aval = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
foreach ($dist_map as $n => $q2) $js_aval[(int)$n] = (int)$q2;

// Avaliações
$q = $pdo->prepare("
    SELECT COALESCE(AVG(nota), 0) AS media, COUNT(*) AS total
    FROM feedbacks_clientes
    WHERE data_registro BETWEEN :ini AND :fim
");
$q->execute([':ini' => $dt_ini_sql, ':fim' => $dt_fim_sql]);
$aval = $q->fetch();

// Últimas vendas
$lim_vendas = max(5, min(100, (int)($_GET['lim'] ?? 20)));
$q = $pdo->prepare("
    SELECT v.venda_id, v.data_venda, v.peso_total, v.valor_total,
           v.forma_pagamento, v.desconto_aplicado,
           GROUP_CONCAT(COALESCE(iv.sabor, iv.adicionais) ORDER BY iv.item_venda_id SEPARATOR ', ') AS itens
    FROM vendas v
    LEFT JOIN itens_venda iv ON iv.venda_id = v.venda_id
    WHERE v.status = 'Confirmado'
    GROUP BY v.venda_id
    ORDER BY v.data_venda DESC
    LIMIT :lim
");
$q->bindValue(':lim', $lim_vendas, PDO::PARAM_INT);
$q->execute();
$ultimas_rows = $q->fetchAll();

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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <div class="box">
        <h3><i class="fa-solid fa-ice-cream"></i> Sabores mais vendidos</h3>
        <div style="position:relative; height:200px;">
            <canvas id="chSab"></canvas>
        </div>
    </div>

    <div class="box">
        <h3><i class="fa-solid fa-credit-card"></i> Formas de pagamento</h3>
        <?php
        $cores = ['#11417b', '#27ae60', '#f39c12', '#c6746a', '#9b59b6'];
        foreach ($pagamentos_rows as $i => $p):
            $pct = $total_pag > 0 ? round($p['qtd'] / $total_pag * 100) : 0;
            $cor = $cores[$i % count($cores)];
        ?>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <div style="width:80px; font-size:.83rem; color:var(--text-muted); text-align:right;">
                <?= htmlspecialchars($p['forma_pagamento']) ?>
            </div>
            <div style="flex:1; height:13px; background:#f0f0f0; border-radius:4px; overflow:hidden;">
                <div style="width:<?= $pct ?>%; height:100%; background:<?= $cor ?>; border-radius:4px;"></div>
            </div>
            <div style="width:34px; font-size:.83rem; color:var(--text-muted);"><?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <div class="box">
        <h3><i class="fa-solid fa-clock"></i> Horário de pico</h3>
        <div style="display:grid; grid-template-columns:repeat(12,1fr); gap:4px;">
            <?php foreach([8,9,10,11,12,13,14,15,16,17,18,19] as $h):
                $qtd = $heat_map[$h] ?? 0;
                $pct_h = $heat_max > 0 ? $qtd / $heat_max : 0;
                $r = (int)(198*(1-$pct_h) + 17*$pct_h);
                $g = (int)(206*(1-$pct_h) + 65*$pct_h);
                $b = (int)(234*(1-$pct_h) + 123*$pct_h);
                $tc = $pct_h > 0.5 ? '#fff' : 'var(--text-muted)';
            ?>
            <div style="text-align:center;">
                <div style="font-size:.6rem; color:var(--text-muted); margin-bottom:2px;"><?= $h ?>h</div>
                <div style="height:34px; border-radius:6px; background:rgb(<?=$r?>,<?=$g?>,<?=$b?>); color:<?=$tc?>; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600;">
                    <?= $qtd ?: '' ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="font-size:.7rem; color:var(--text-muted); margin-top:7px;">número de vendas por faixa horária</div>
    </div>

    <div class="box">
        <h3><i class="fa-solid fa-tag"></i> Vendas com promoção</h3>
        <div style="font-size:.78rem; color:var(--text-muted); margin-bottom:6px;">Com promoção vs. sem promoção</div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
            <div style="font-size:.8rem; color:var(--text-muted); width:60px;">Com promo</div>
            <div style="flex:1; height:20px; background:#f0f0f0; border-radius:6px; overflow:hidden; display:flex;">
                <div style="width:<?= $pct_promo ?>%; background:#11417b; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; color:#fff;">
                    <?= $promo['com_promo'] ?>
                </div>
                <div style="flex:1; display:flex; align-items:center; justify-content:center; font-size:.75rem; color:#999;">
                    <?= $sem_promo ?>
                </div>
            </div>
            <span style="font-size:.8rem; color:var(--text-muted);"><?= $pct_promo ?>%</span>
        </div>
        <div style="font-size:.8rem; color:var(--text-muted);">Desconto médio concedido</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--secondary); margin-top:3px;">
            R$ <?= number_format($desc_medio, 2, ',', '.') ?>
        </div>
        <div style="font-size:.75rem; color:var(--text-muted); margin-top:2px;">por venda com promoção</div>
    </div>

</div>

<p class="sec-title">Avaliações</p>

<div class="box" style="margin-bottom: 20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3><i class="fa-solid fa-comments"></i> Feedbacks dos clientes</h3>
        <span style="font-size:.83rem; color:var(--text-muted);"><?= $aval['total'] ?> feedbacks no período</span>
    </div>
    <div style="display:flex; align-items:center; gap:30px;">
        <div style="text-align:center; flex-shrink:0;">
            <div style="font-size:2.8rem; font-weight:800; color:var(--secondary);">
                <?= number_format($aval['media'], 1, ',', '.') ?>
            </div>
            <div style="display:flex; gap:2px; justify-content:center; margin:4px 0;">
                <?php for($i=1; $i<=5; $i++): ?>
                <span style="font-size:1.25rem; color:<?= $i <= round($aval['media']) ? '#f39c12' : '#ddd' ?>;">★</span>
                <?php endfor; ?>
            </div>
            <div style="font-size:.78rem; color:var(--text-muted);">média geral</div>
        </div>
        <div style="flex:1; position:relative; height:105px;">
            <canvas id="chAval"></canvas>
        </div>
    </div>
</div>

<!-- ÚLTIMAS VENDAS -->
<p class="sec-title"><i class="fa-solid fa-list"></i> Últimas vendas</p>

<div class="box">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
        <h3 style="flex:1; margin-bottom:0; border:none; padding:0;">
            <i class="fa-solid fa-clock-rotate-left"></i> Vendas recentes
        </h3>
        <select onchange="location='dashboard?data_ini=<?=$data_ini?>&data_fim=<?=$data_fim?>&periodo=<?=$periodo?>&lim='+this.value"
                style="border:1px solid #ddd; border-radius:8px; padding:6px 10px; font-size:.83rem;">
            <?php foreach([10,20,50,100] as $op): ?>
            <option value="<?=$op?>" <?=$lim_vendas==$op?'selected':''?>><?=$op?> registros</option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="buscaVenda" placeholder="buscar" oninput="filtrarVendas()"
               style="border:1px solid #ddd; border-radius:8px; padding:6px 12px; font-size:.83rem; width:120px;">
    </div>

    <div style="overflow-x:auto;">
        <table id="tblVendas">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Horário</th>
                    <th>Sabores</th>
                    <th>Peso</th>
                    <th>Valor</th>
                    <th>Pagamento</th>
                    <th>Desconto</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($ultimas_rows as $i => $v):
                $fp  = strtolower($v['forma_pagamento'] ?? '');
                $cls = str_contains($fp,'pix') ? 'pix' : (str_contains($fp,'dinheiro') ? 'din' : 'crt');
                $oculto = $i >= 5 ? 'class="linha-extra" style="display:none;"' : '';
            ?>
            <tr <?= $oculto ?>>
                <td>#<?= $v['venda_id'] ?></td>
                <td><?= date('d/m H:i', strtotime($v['data_venda'])) ?></td>
                <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                    title="<?= htmlspecialchars($v['itens'] ?? '') ?>">
                    <?= htmlspecialchars($v['itens'] ?? '—') ?>
                </td>
                <td><?= number_format($v['peso_total'],3,',','.') ?> kg</td>
                <td>R$ <?= number_format($v['valor_total'],2,',','.') ?></td>
                <td><span class="badge-pag <?=$cls?>"><?= htmlspecialchars($v['forma_pagamento'] ?? '—') ?></span></td>
                <td><?= $v['desconto_aplicado'] > 0 ? 'R$ '.number_format($v['desconto_aplicado'],2,',','.') : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($ultimas_rows)): ?>
            <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:20px;">Nenhuma venda encontrada.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Botão expandir -->
    <?php if(count($ultimas_rows) > 5): ?>
    <div style="text-align:center; margin-top:14px;">
        <button onclick="toggleVendas(this)" style="background:none; border:1px solid #ddd; border-radius:8px; padding:7px 20px; cursor:pointer; color:var(--secondary); font-size:.83rem; font-weight:600;">
            <i class="fa-solid fa-chevron-down"></i> Ver mais <?= count($ultimas_rows)-5 ?> vendas
        </button>
    </div>
    <?php endif; ?>
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

const SAB_L = <?= json_encode($js_sab_labels) ?>;
const SAB_D = <?= json_encode($js_sab_data) ?>;

if (SAB_D.length) {
    new Chart(document.getElementById('chSab'), {
        type: 'doughnut',
        data: {
            labels: SAB_L,
            datasets: [{
                data: SAB_D,
                backgroundColor: ['#11417b','#c6746a','#27ae60','#f39c12','#9b59b6','#1abc9c'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { font: { size: 11 }, boxWidth: 12, padding: 10 }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.label + ': ' + ctx.parsed.toFixed(3) + ' kg'
                    }
                }
            }
        }
    });
}
const AV_D = [<?= $js_aval[5] ?>, <?= $js_aval[4] ?>, <?= $js_aval[3] ?>, <?= $js_aval[2] ?>, <?= $js_aval[1] ?>];

new Chart(document.getElementById('chAval'), {
    type: 'bar',
    data: {
        labels: ['5★','4★','3★','2★','1★'],
        datasets: [{
            data: AV_D,
            backgroundColor: ['#11417b','#378ADD','#85B7EB','#f39c12','#e74c3c'],
            borderRadius: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { font: { size: 10 }, stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } },
            y: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});
function toggleVendas(btn) {
    const extras = document.querySelectorAll('.linha-extra');
    const aberto = extras[0].style.display !== 'none';
    extras.forEach(tr => tr.style.display = aberto ? 'none' : '');
    btn.innerHTML = aberto
        ? '<i class="fa-solid fa-chevron-down"></i> Ver mais <?= count($ultimas_rows)-5 ?> vendas'
        : '<i class="fa-solid fa-chevron-up"></i> Ver menos';
}

function filtrarVendas() {
    const termo = document.getElementById('buscaVenda').value.toLowerCase();
    document.querySelectorAll('#tblVendas tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(termo) ? '' : 'none';
    });
}

</script>