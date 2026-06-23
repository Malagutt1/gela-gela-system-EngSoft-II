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

// --- Processa filtros via GET ---
$f_usuario = $_GET['usuario'] ?? 'Todos';
$f_acao = $_GET['acao'] ?? 'Todos';

// Busca opções de filtro (usuários e ações)
try {
    $stmtUsers = $pdo->query("SELECT DISTINCT u.usuario_id, u.nome FROM logs_auditoria l JOIN usuarios u ON l.usuario_id = u.usuario_id ORDER BY u.nome");
    $usuarios = $stmtUsers->fetchAll();

    $stmtAcoes = $pdo->query("SELECT DISTINCT acao FROM logs_auditoria ORDER BY acao");
    $acoes = $stmtAcoes->fetchAll();

    // Monta query principal com filtros
    $where = [];
    $params = [];

    if ($f_usuario !== 'Todos') {
        $where[] = 'l.usuario_id = :usuario_id';
        $params[':usuario_id'] = (int)$f_usuario;
    }

    if ($f_acao !== 'Todos') {
        $where[] = 'l.acao = :acao';
        $params[':acao'] = $f_acao;
    }

    $sql = "SELECT l.*, u.nome AS usuario_nome FROM logs_auditoria l LEFT JOIN usuarios u ON l.usuario_id = u.usuario_id";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY l.data_hora DESC LIMIT 1000';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // --- Remove duplicatas óbvias (ação + descrição + data_hora) ---
    $seen = [];
    $uniqueLogs = [];
    foreach ($logs as $l) {
        $key = md5(strtolower(trim(($l['acao'] ?? '') . '|' . ($l['descricao'] ?? '') . '|' . ($l['data_hora'] ?? ''))));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $uniqueLogs[] = $l;
        }
    }
    $logs = $uniqueLogs;

} catch (Exception $e) {
    $logs = [];
    $usuarios = [];
    $acoes = [];
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
                <?php
                    require_once '../components/user-menu.php';
                ?>
            </header>

            <section class="main">

                <!-- FILTROS -->
                <div class="box filter-section">

                    <form method="get" class="filters-form" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">

                        <div class="filter-group">
                            <label>Usuário:</label>
                            <select name="usuario">
                                <option value="Todos">Todos</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= htmlspecialchars($u['usuario_id']) ?>" <?= ($f_usuario == $u['usuario_id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Ação:</label>
                            <select name="acao">
                                <option value="Todos">Todos</option>
                                <?php foreach ($acoes as $a): ?>
                                    <?php $valor = $a['acao']; ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($f_acao === $valor) ? 'selected' : '' ?>><?= htmlspecialchars($valor) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <button class="btn-primary" type="submit">
                                <i class="fa fa-search"></i> Filtrar
                            </button>
                        </div>

                    </form>

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
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="4">Nenhum log encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                        $acao = $log['acao'] ?? '';
                                        // Mapeia label de exibição (troca INSERT -> Venda)
                                        $displayAcao = (strtoupper(trim($acao)) === 'INSERT') ? 'Venda' : $acao;

                                        // Mapeia classes de badge por tipo de ação (usa o rótulo de exibição)
                                        $badgeClass = 'badge';
                                        $acaoLower = strtolower($displayAcao);
                                        if (strpos($acaoLower, 'venda') !== false || strpos($acaoLower, 'insert') !== false || strpos($acaoLower, 'cadast') !== false) {
                                            $badgeClass .= ' ok';
                                        } elseif (strpos($acaoLower, 'delete') !== false || strpos($acaoLower, 'exclus') !== false || strpos($acaoLower, 'saida') !== false) {
                                            $badgeClass .= ' danger';
                                        } else {
                                            $badgeClass .= ' warn';
                                        }

                                        $data = date('d/m/Y H:i', strtotime($log['data_hora'] ?? $log['data'] ?? 'now'));
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($data) ?></td>
                                        <td><?= htmlspecialchars($log['usuario_nome'] ?? ('#' . ($log['usuario_id'] ?? ''))) ?></td>
                                        <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($displayAcao) ?></span></td>
                                        <td><?= htmlspecialchars($log['descricao'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>

                </div>

            </section>
        </main>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/user-menu.js"></script>

</body>

</html>