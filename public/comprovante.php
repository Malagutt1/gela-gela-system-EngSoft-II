<?php
session_start();
require_once '../conecta.php';

// 🔒 Proteção de login
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

// 🔥 Pega ID
$venda_id = $_GET['id'] ?? $_SESSION['ultima_venda_id'] ?? null;

// 🔐 Validação básica
$venda_id = (int)$venda_id;
if ($venda_id <= 0) {
    die("<h2 style='text-align:center;margin-top:50px;color:#c6746a;'>ID inválido.</h2>");
}

// 🔐 CONTROLE DE ACESSO (ANTI-IDOR)
if ($_SESSION['tipo'] !== 'Gerente') {
    $stmt = $pdo->prepare("
        SELECT v.*, u.nome as atendente, p.nome as promocao_nome
        FROM vendas v
        LEFT JOIN usuarios u ON v.usuario_id = u.usuario_id
        LEFT JOIN promocoes p ON v.promocao_id = p.promocao_id
        WHERE v.venda_id = ? AND v.usuario_id = ?
    ");
    $stmt->execute([$venda_id, $_SESSION['usuario_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT v.*, u.nome as atendente, p.nome as promocao_nome
        FROM vendas v
        LEFT JOIN usuarios u ON v.usuario_id = u.usuario_id
        LEFT JOIN promocoes p ON v.promocao_id = p.promocao_id
        WHERE v.venda_id = ?
    ");
    $stmt->execute([$venda_id]);
}

$venda = $stmt->fetch();

if (!$venda) {
    die("<h2 style='text-align:center;margin-top:50px;color:#c6746a;'>Venda não encontrada ou acesso negado.</h2>");
}

// ===================== ITENS =====================
$stmtItens = $pdo->prepare("
    SELECT * FROM itens_venda 
    WHERE venda_id = ? 
    ORDER BY sabor IS NULL, sabor ASC
");
$stmtItens->execute([$venda_id]);
$itens = $stmtItens->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante #<?= $venda_id ?> - Gela-Gela</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/receipt.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            color: #333;
        }

        .receipt {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border: 2px dashed #c6746a;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #c6746a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #c6746a;
            font-size: 28px;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 15px;
            color: #666;
        }

        .info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            padding: 8px 0;
            text-align: left;
            border-bottom: 1px dotted #ddd;
        }

        .total-line {
            font-weight: 700;
            font-size: 18px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }

        .btn-print {
            display: block;
            width: 100%;
            padding: 14px;
            background: #11417b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        @media print {

            .btn-print,
            .no-print {
                display: none;
            }

            body {
                background: white;
                padding: 0;
            }

            .receipt {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>

<body>

    <div class="receipt">
        <div class="header">
            <h1>Gela-Gela</h1>
            <p>Sorvetes & Açaí • Chapecó/SC</p>
            <p>Comprovante de Venda</p>
        </div>

        <div class="info">
            <div>
                <strong>Venda #<?= $venda_id ?></strong><br>
                <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?>
            </div>
            <div style="text-align:right">
                Atendente:<br>
                <strong><?= htmlspecialchars($venda['atendente']) ?></strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align:right">Qtd</th>
                    <th style="text-align:right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['sabor'] ?: $item['adicionais']) ?>
                            <?php if ($item['adicionais']): ?> (Topping)<?php endif; ?>
                        </td>
                        <td style="text-align:right">
                            <?= $item['quantidade'] > 0 ? $item['quantidade'] . ' kg' : '-' ?>
                        </td>
                        <td style="text-align:right">
                            R$ <?= number_format($item['valor_total_item'] ?? ($item['quantidade'] * $item['valor_unitario']), 2, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="border-top: 2px solid #c6746a; padding-top: 10px;">
            <div style="display:flex; justify-content:space-between; margin:8px 0;">
                <span>Subtotal</span>
                <span>R$ <?= number_format($venda['valor_total'] + $venda['desconto_aplicado'], 2, ',', '.') ?></span>
            </div>

            <?php if ($venda['desconto_aplicado'] > 0): ?>
                <div style="display:flex; justify-content:space-between; margin:8px 0; color:#c6746a;">
                    <span>Desconto <?= $venda['promocao_nome'] ? '(' . htmlspecialchars($venda['promocao_nome']) . ')' : '' ?></span>
                    <span>- R$ <?= number_format($venda['desconto_aplicado'], 2, ',', '.') ?></span>
                </div>
            <?php endif; ?>

            <div class="total-line" style="display:flex; justify-content:space-between; font-size:22px; margin-top:12px;">
                <span>Total</span>
                <span>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></span>
            </div>

            <div style="text-align:center; margin-top:15px; font-size:15px;">
                Pagamento: <strong><?= str_replace('_', ' ', $venda['forma_pagamento']) ?></strong>
            </div>
        </div>

        <div class="footer">
            <p>Obrigado pela preferência! 💙❄️</p>
            <p>Volte sempre • Gela-Gela Sorvetes</p>
        </div>

        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Imprimir Comprovante
        </button>
    </div>

</body>

</html>