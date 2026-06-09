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
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gela-Gela | Backup</title>
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
                <h1>Backup do Sistema</h1>
            </header>

            <section class="main">

                <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <p><strong>Último backup:</strong> <span id="ultimo-backup">--</span></p>

                    <button class="btn" onclick="fazerBackup()">
                        <i class="fa fa-database"></i> Gerar Backup
                    </button>
                </div>

                <div class="box">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody id="tabela-backup">

                            <tr>
                                <td>30/03/2026 14:32</td>
                                <td><span class="tag-resumo">Automático</span></td>
                                <td><span class="badge ok">Sucesso</span></td>
                                <td>
                                    <button class="btn-icon" onclick="baixarBackup()">
                                        <i class="fa fa-download"></i>
                                    </button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </section>
        </main>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/app.js"></script>

</body>

</html>