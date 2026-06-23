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
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Logs</title>
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
                <h1>Logs do Sistema</h1>
            </header>

            <section class="main">

                <!-- FILTROS -->
                <div class="box filter-section">

                    <div class="filter-group">
                        <label>Usuário:</label>
                        <select>
                            <option>Todos</option>
                            <option>admin-TDS</option>
                            <option>Victor</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Ação:</label>
                        <select>
                            <option>Todos</option>
                            <option>Cadastro</option>
                            <option>Exclusão</option>
                            <option>Venda</option>
                        </select>
                    </div>

                    <button class="btn-primary" onclick="filtrarLogs()">
                        <i class="fa fa-search"></i> Filtrar
                    </button>

                </div>

                <!-- TABELA -->
                <div class="box">

                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>

                        <tbody id="tabela-logs">

                            <tr>
                                <td>31/03/2026 10:12</td>
                                <td>admin</td>
                                <td><span class="badge ok">Cadastro</span></td>
                                <td>Produto criado</td>
                            </tr>

                            <tr>
                                <td>31/03/2026 11:30</td>
                                <td>victor</td>
                                <td><span class="badge warn">Venda</span></td>
                                <td>Venda realizada</td>
                            </tr>

                            <tr>
                                <td>31/03/2026 12:05</td>
                                <td>admin</td>
                                <td><span class="badge danger">Exclusão</span></td>
                                <td>Produto removido</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </section>
        </main>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>

</body>

</html>