<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Relatórios</title>
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
                <a href="vendas"><i class="fa-solid fa-cart-shopping"></i> Nova Venda</a>
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

        <main class="content">
            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                <h1>Relatórios</h1>
            </header>

            <section class="main">
                <div class="box filter-section">
                    <div class="filter-group">
                        <label>Período:</label>
                        <select id="filtro-data">
                            <option value="dia">Hoje</option>
                            <option value="semana">Esta Semana</option>
                            <option value="mes" selected>Este Mês</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Tipo de Relatório:</label>
                        <select id="filtro-tipo">
                            <option value="vendas">Vendas</option>
                            <option value="estoque">Estoque</option>
                            <option value="financeiro">Financeiro</option>
                        </select>
                    </div>

                    <button class="btn-primary" onclick="filtrarRelatorio()">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                </div>

                <div class="box">
                    <div class="header-box">
                        <h3>Resultados do Relatório</h3>
                        <button class="btn-export" onclick="alert('Exportando PDF... (Simulação)')">
                            <i class="fa fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Referência</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor/Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Março/2026</td>
                                    <td>Faturamento Mensal Estimado</td>
                                    <td>Financeiro</td>
                                    <td>R$ 215.475,00</td>
                                </tr>
                                <tr>
                                    <td>Semanal</td>
                                    <td>Compra de Insumos (Sorvetes/Açaí)</td>
                                    <td>Estoque</td>
                                    <td>495 kg</td>
                                </tr>
                                <tr>
                                    <td>Diário</td>
                                    <td>Média de Atendimento</td>
                                    <td>Vendas</td>
                                    <td>255 Clientes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="../ASSETS/JS/app.js"></script>
</body>

</html>