<?php
require_once __DIR__ . '/../conecta.php';
session_start();

$empresa = "GELA-GELA";
$slogan = "Gelou, sorriu";
$base = '/gela-gela-system-EngSoft-II/';

// FILTRO
$tipoFiltro = $_GET['tipo'] ?? 'Reclamacao';

// DADOS GERAIS
$total = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes")->fetchColumn();
$resolvidos = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes WHERE resolvido = 1")->fetchColumn();
$resolucao = $total > 0 ? round(($resolvidos / $total) * 100) : 0;

// NOTA REAL (média)
$nota = $pdo->query("SELECT AVG(nota) FROM feedbacks_clientes")->fetchColumn();
$nota = $nota ? round($nota, 1) : 0;

// === NOVO: CONTAGEM POR TIPO (para aparecer nas abas) ===
$total_reclamacao = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes WHERE tipo = 'Reclamacao'")->fetchColumn();
$total_sugestao   = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes WHERE tipo = 'Sugestao'")->fetchColumn();
$total_elogio     = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes WHERE tipo = 'Elogio'")->fetchColumn();
$total_duvida     = $pdo->query("SELECT COUNT(*) FROM feedbacks_clientes WHERE tipo = 'Duvida'")->fetchColumn();

// INSERT
if (isset($_POST['enviar'])) {
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $nota_user = $_POST['nota'] ?: 0;

    $stmt = $pdo->prepare("INSERT INTO feedbacks_clientes (descricao, tipo, nota, resolvido) VALUES (?, ?, ?, 0)");
    $stmt->execute([$descricao, $tipo, $nota_user]);

    header("Location: ?tipo=$tipo");
    exit;
}

// LISTAGEM
$stmt = $pdo->prepare("SELECT * FROM feedbacks_clientes WHERE tipo = ? ORDER BY data_registro DESC");
$stmt->execute([$tipoFiltro]);
$lista = $stmt;
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $empresa ?> - Reclame Ali</title>
    <link rel="icon" href="<?= $base ?>ASSETS/IMG/ReclameAli.png">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #c6746a;
            --secondary: #11417b;
            --accent: #b76756;
            --soft: #facee1;
            --bg-body: #f8f9fa;
            --text: #222;
            --gray: #666;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--bg-body);
            color: var(--text);
            line-height: 1.5;
        }

        .container {
            width: 90%;
            max-width: 1280px;
            margin: 0 auto;
        }

        /* HEADER */
        header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 12px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--secondary);
        }

        .logo img {
            height: 52px;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            font-weight: 500;
        }

        .nav-links a {
            color: var(--text);
            text-decoration: none;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            background: var(--primary);
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }

        /* HERO - agora com logo da sorveteria */
        .hero {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            margin: 40px 0 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: flex-start;
            gap: 40px;
        }

        .company-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .company-logo {
            width: 110px;
            height: 110px;
            flex-shrink: 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .company-info h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .company-info p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .verified {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e6f4ea;
            color: #137333;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-top: 12px;
        }

        /* SCORE CIRCLE */
        .score-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 8px solid var(--soft);
            border-radius: 50%;
            width: 160px;
            height: 160px;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(198, 116, 106, 0.15);
        }

        .score {
            font-size: 3.8rem;
            font-weight: 700;
            line-height: 1;
        }

        .score-label {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 500;
        }

        .score-good {
            color: #00a79d;
        }

        .score-regular {
            color: #f5a623;
        }

        .score-bad {
            color: #e74c3c;
        }

        /* STATS */
        .stats {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            flex: 1;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            text-align: center;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }

        /* TABS */
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 24px;
        }

        .tab {
            padding: 16px 24px;
            font-weight: 600;
            color: var(--gray);
            text-decoration: none;
            border-bottom: 4px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: var(--secondary);
            border-bottom: 4px solid var(--secondary);
        }

        /* FORM */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 32px;
        }

        .form-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stars {
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        .stars i {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }

        .stars i.active {
            color: #ffc107;
        }

        textarea,
        select {
            width: 100%;
            padding: 16px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-size: 1.05rem;
            margin-bottom: 16px;
            resize: vertical;
        }

        textarea:focus,
        select:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* LISTA DE FEEDBACKS */
        .feedback-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.3s;
        }

        .feedback-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .feedback-type {
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-ok {
            background: #d4edda;
            color: #137333;
        }

        .badge-pend {
            background: #f8d7da;
            color: #c22;
        }

        .feedback-text {
            font-size: 1.1rem;
            margin: 12px 0 16px;
        }

        .feedback-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* FOOTER */
        footer {
            background: var(--secondary);
            color: #fff;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .company-info {
                flex-direction: column;
            }

            .score-container {
                width: 140px;
                height: 140px;
            }

            .score {
                font-size: 3rem;
            }

            .stats {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <img src="<?= $base ?>ASSETS/IMG/ReclameAli.png" alt="Reclame Ali">
                    <span>Reclame Ali</span>
                </div>

                <div class="header-actions">
                    <a href="#formulario" class="btn">Reclamar agora</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container">

        <!-- HERO -->
        <div class="hero">
            <div class="company-info">
                <div class="company-logo">
                    <img src="<?= $base ?>ASSETS/IMG/icon.png" alt="Gela-Gela Sorvetes">
                </div>
                <div>
                    <h1><?= $empresa ?></h1>
                    <p><?= $slogan ?></p>
                    <div class="verified">
                        <i class="fa fa-check-circle"></i>
                        Empresa verificada
                    </div>
                </div>
            </div>

            <div class="score-container">
                <div class="score <?= $nota >= 8 ? 'score-good' : ($nota >= 5 ? 'score-regular' : 'score-bad') ?>">
                    <?= $nota ?>
                </div>
                <div class="score-label">Nota geral</div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $resolucao ?>%</div>
                <div style="color:#137333;font-weight:600;">Reclamações resolvidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $resolvidos ?></div>
                <div style="color:var(--gray);">Respondidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total ?></div>
                <div style="color:var(--gray);">Total de feedbacks</div>
            </div>
        </div>

        <!-- TABS COM QUANTIDADE (exatamente como você pediu) -->
        <div class="tabs">
            <a href="<?= $base ?>reclameAli/reclamacoes" class="tab <?= $tipoFiltro == 'Reclamacao' ? 'active' : '' ?>">Reclamações (<?= $total_reclamacao ?>)</a>
            <a href="<?= $base ?>reclameAli/sugestoes" class="tab <?= $tipoFiltro == 'Sugestao'   ? 'active' : '' ?>">Sugestões (<?= $total_sugestao ?>)</a>
            <a href="<?= $base ?>reclameAli/elogios" class="tab <?= $tipoFiltro == 'Elogio'     ? 'active' : '' ?>">Elogios (<?= $total_elogio ?>)</a>
            <a href="<?= $base ?>reclameAli/duvidas" class="tab <?= $tipoFiltro == 'Duvida'     ? 'active' : '' ?>">Dúvidas (<?= $total_duvida ?>)</a>
        </div>

        <!-- FORMULÁRIO -->
        <div class="card" id="formulario">
            <div class="form-title">
                <i class="fa fa-comment-dots" style="color:var(--primary);"></i>
                Registrar seu feedback sobre <?= $empresa ?>
            </div>

            <form method="POST">
                <div class="stars">
                    <i class="fa fa-star" data="1"></i>
                    <i class="fa fa-star" data="2"></i>
                    <i class="fa fa-star" data="3"></i>
                    <i class="fa fa-star" data="4"></i>
                    <i class="fa fa-star" data="5"></i>
                </div>
                <input type="hidden" name="nota" id="nota">

                <textarea name="descricao" rows="4" placeholder="Descreva o que aconteceu, sua sugestão ou elogio..." required></textarea>

                <select name="tipo" required>
                    <option value="Reclamacao" <?= $tipoFiltro == 'Reclamacao' ? 'selected' : '' ?>>Reclamação</option>
                    <option value="Sugestao" <?= $tipoFiltro == 'Sugestao'   ? 'selected' : '' ?>>Sugestão</option>
                    <option value="Elogio" <?= $tipoFiltro == 'Elogio'     ? 'selected' : '' ?>>Elogio</option>
                    <option value="Duvida" <?= $tipoFiltro == 'Duvida'     ? 'selected' : '' ?>>Dúvida</option>
                </select>

                <button name="enviar" class="btn" style="width:100%;padding:16px;font-size:1.1rem;">
                    <i class="fa fa-paper-plane"></i> Enviar feedback
                </button>
            </form>
        </div>

        <!-- LISTA DE FEEDBACKS -->
        <div class="card">
            <h3 style="margin-bottom:20px;">Últimos <?= strtolower($tipoFiltro) ?>s</h3>

            <?php if ($lista->rowCount() == 0): ?>
                <p style="text-align:center;padding:40px;color:#999;">Nenhum feedback ainda. Seja o primeiro!</p>
            <?php else: ?>
                <?php while ($row = $lista->fetch()): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <span class="feedback-type" style="background:<?= $row['tipo'] == 'Reclamacao' ? '#fee2e2' : ($row['tipo'] == 'Elogio' ? '#e6f4ea' : '#fef3c7') ?>; color:<?= $row['tipo'] == 'Reclamacao' ? '#c22' : ($row['tipo'] == 'Elogio' ? '#137333' : '#b45309') ?>">
                                <?= $row['tipo'] ?>
                            </span>
                            <span class="badge <?= $row['resolvido'] ? 'badge-ok' : 'badge-pend' ?>">
                                <?= $row['resolvido'] ? '✅ Respondido' : '⏳ Pendente' ?>
                            </span>
                        </div>

                        <div class="feedback-text">
                            <?= htmlspecialchars($row['descricao']) ?>
                        </div>

                        <?php if (!empty($row['observacao_resolucao'])): ?>
                            <div style="background:#f8f9fa;padding:16px;border-radius:12px;margin:16px 0;border-left:4px solid var(--primary);">
                                <strong>Resposta da Gela-Gela:</strong><br>
                                <?= htmlspecialchars($row['observacao_resolucao']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="feedback-footer">
                            <span><i class="fa fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($row['data_registro'])) ?></span>
                            <span style="color:var(--gray);font-size:0.85rem;">Nota: <strong><?= $row['nota'] ?>/5</strong></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container footer-content">
            <p>© 2026 Reclame Ali • Todos os direitos reservados</p>
            <p style="font-size:0.9rem;opacity:0.8;">
                Este é um site inspirado no Reclame Aqui para a empresa <strong>Gela-Gela</strong>
            </p>
            <div>
                <a href="#" style="color:#fff;margin-right:16px;">Sobre</a>
                <a href="#" style="color:#fff;">Termos de uso</a>
            </div>
        </div>
    </footer>

    <script>
        const stars = document.querySelectorAll('.stars i');
        const notaInput = document.getElementById('nota');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data'));
                notaInput.value = value;

                stars.forEach(s => s.classList.remove('active'));
                for (let i = 0; i < value; i++) {
                    stars[i].classList.add('active');
                }
            });

            star.addEventListener('mouseover', () => {
                const value = parseInt(star.getAttribute('data'));
                stars.forEach((s, index) => {
                    if (index < value) s.style.color = '#ffc107';
                });
            });

            star.addEventListener('mouseout', () => {
                stars.forEach(s => {
                    if (!s.classList.contains('active')) s.style.color = '#ddd';
                });
            });
        });
    </script>

</body>

</html>