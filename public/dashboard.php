<?php 
session_start();
require_once '../components/valida-sessao.php';
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
                        <h4>Faturamento Mensal</h4>
                        <p>R$ 215.475,00</p>
                        <span><i class="fa-solid fa-arrow-trend-up" style="color: #27ae60;"></i> +12% relação ao mês
                            anterior</span>
                    </div>

                    <div class="card">
                        <h4>Lucro Estimado</h4>
                        <p>R$ 60.591,00</p>
                        <span>Despesas já abatidas</span>
                    </div>

                    <div class="card">
                        <h4>Ticket Médio</h4>
                        <p>R$ 32,50</p>
                        <span>~255 clientes por dia</span>
                    </div>

                    <div class="card" style="border-left-color: var(--secondary);">
                        <h4>Giro de Estoque</h4>
                        <p>495 kg</p>
                        <span>Consumo semanal médio</span>
                    </div>
                </div>

                <div class="grid-main">

                    <div class="box">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Últimas Vendas Registradas</h3>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Produtos</th>
                                        <th>Peso (kg)</th>
                                        <th>Valor Total</th>
                                        <th>Pagamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>João Silva</td>
                                        <td>Pistache, Morango + Toppings</td>
                                        <td>1.200</td>
                                        <td>R$ 84,00</td>
                                        <td>Pix</td>
                                    </tr>
                                    <tr>
                                        <td>Maria Alves</td>
                                        <td>Açaí Tradicional + Leite Ninho</td>
                                        <td>0.800</td>
                                        <td>R$ 56,00</td>
                                        <td>Cartão Débito</td>
                                    </tr>
                                    <tr>
                                        <td>Carlos Souza</td>
                                        <td>Chocolate Branco, Brownie</td>
                                        <td>0.500</td>
                                        <td>R$ 35,00</td>
                                        <td>Dinheiro</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 25px;">

                        <div class="box">
                            <h3><i class="fa-solid fa-triangle-exclamation"></i> Alertas de Estoque</h3>
                            <ul class="list">
                                <li>
                                    <span><i class="fa-solid fa-ice-cream"></i> Sorvete de Chocolate</span>
                                    <span class="badge danger">Baixo (5kg)</span>
                                </li>
                                <li>
                                    <span><i class="fa-solid fa-bowl-food"></i> Açaí Tradicional</span>
                                    <span class="badge warn">Atenção (10kg)</span>
                                </li>
                                <li>
                                    <span><i class="fa-solid fa-cookie"></i> Gotas de Chocolate (Topping)</span>
                                    <span class="badge ok">Estoque OK</span>
                                </li>
                            </ul>
                        </div>

                        <div class="box">
                            <h3><i class="fa-solid fa-bolt"></i> Ações Rápidas</h3>
                            <div class="actions">
                                <button class="btn" onclick="location.href='vendas'">
                                    <i class="fa-solid fa-cart-plus"></i> Registrar Nova Venda
                                </button>
                                <button class="btn btn-secondary" onclick="location.href='produtos'">
                                    <i class="fa-solid fa-box-open"></i> Receber Mercadoria
                                </button>
                                <button class="btn btn-secondary" onclick="location.href='relatorio'">
                                    <i class="fa-solid fa-file-pdf"></i> Gerar Relatório (PDF)
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    
</body>

</html>