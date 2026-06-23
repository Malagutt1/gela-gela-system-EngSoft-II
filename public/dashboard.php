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

                <div class="grid-cards">
                    <div class="card">
                       
                    </div>

                    <div class="card">
                     
                    </div>

                    <div class="card">
                   
                    </div>

                    <div class="card" style="border-left-color: var(--secondary);">
                      
                    </div>

                    <div class="card">
                       
                    </div>

                    <div class="card">
                       
                    </div>

                    <div class="card">
                       
                    </div>

                    <div class="card">
                       
                    </div>

                    <div class="card">
                       
                    </div>
                </div>

                <div class="box">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> EVOLUÇÃO DIARIA</h3>

                    
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