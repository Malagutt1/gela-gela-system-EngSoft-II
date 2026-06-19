<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once '../conecta.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login');
    exit();
}

// ─── Controle de acesso ───────────────────────────────────────────────
$perfil = strtolower($_SESSION['perfil'] ?? $_SESSION['tipo'] ?? 'funcionario'); // 'gerente' ou 'funcionario'
$is_gerente = ($perfil === 'gerente');

$nome_usuario = $_SESSION['nome'] ?? 'User';
$inicial = strtoupper(substr($nome_usuario, 0, 1));
 
// ─── Período selecionado ──────────────────────────────────────────────
$periodo  = $_GET['periodo']  ?? 'mes';
$tipo     = $_GET['tipo']     ?? 'vendas';
 
// Bloqueia financeiro para funcionário
if (!$is_gerente && $tipo === 'financeiro') {
    $tipo = 'vendas';
}

$exportar_pdf = (($_GET['exportar'] ?? '') === 'pdf');
 
// Define intervalo de datas
switch ($periodo) {
    case 'dia':
        $data_inicio = date('Y-m-d 00:00:00');
        $data_fim    = date('Y-m-d 23:59:59');
        $label_periodo = 'Hoje (' . date('d/m/Y') . ')';
        break;
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('monday this week'));
        $data_fim    = date('Y-m-d', strtotime('sunday this week'));
        $label_periodo = 'Esta Semana';
        break;
    default: // mes
        $data_inicio = date('Y-m-01');
        $data_fim    = date('Y-m-t');
        $label_periodo = 'Este Mês (' . date('m/Y') . ')';
}

function garantirTabelaDespesas(PDO $pdo): bool {
    try {
        $pdo->exec("\n            CREATE TABLE IF NOT EXISTS despesas (\n                despesa_id INT(11) NOT NULL AUTO_INCREMENT,\n                data_despesa DATE NOT NULL,\n                descricao VARCHAR(255) NOT NULL,\n                categoria VARCHAR(100) NOT NULL DEFAULT 'Geral',\n                valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n                usuario_id INT(11) DEFAULT NULL,\n                data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n                PRIMARY KEY (despesa_id),\n                KEY idx_despesas_data (data_despesa),\n                KEY idx_despesas_categoria (categoria),\n                KEY idx_despesas_usuario (usuario_id),\n                CONSTRAINT despesas_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL\n            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n        ");
        return true;
    } catch (PDOException $e) {
        return (bool)$pdo->query("SHOW TABLES LIKE 'despesas'")->fetchColumn();
    }
}

$tem_tabela_despesas = garantirTabelaDespesas($pdo);

function pdf_escape_text(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $converted = false;
    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }
    if ($converted === false || $converted === null) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    }
    if ($converted !== false && $converted !== null) {
        $text = $converted;
    }
    $text = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
    return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $text);
}

function pdf_wrap_lines(string $text, int $maxChars): array {
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        if (strlen($candidate) > $maxChars && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }

    if ($line !== '') {
        $lines[] = $line;
    }

    return $lines ?: [''];
}

function pdf_build_logo_image(string $filePath): ?array {
    if (!function_exists('imagecreatefrompng') || !is_file($filePath)) {
        return null;
    }

    $source = @imagecreatefrompng($filePath);
    if (!$source) {
        return null;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $canvas = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

    $raw = '';
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $color = imagecolorat($canvas, $x, $y);
            $raw .= chr(($color >> 16) & 255) . chr(($color >> 8) & 255) . chr($color & 255);
        }
    }

    imagedestroy($source);
    imagedestroy($canvas);

    return [
        'width' => $width,
        'height' => $height,
        'data' => gzcompress($raw, 9),
    ];
}

function pdf_hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return [
        hexdec(substr($hex, 0, 2)) / 255,
        hexdec(substr($hex, 2, 2)) / 255,
        hexdec(substr($hex, 4, 2)) / 255,
    ];
}

function pdf_rgb_str(string $hex): string {
    [$r, $g, $b] = pdf_hex_to_rgb($hex);
    return number_format($r, 3, '.', '') . ' ' . number_format($g, 3, '.', '') . ' ' . number_format($b, 3, '.', '');
}

function gerar_pdf_profissional(string $titulo, array $blocos): void {
    $pages = [];
    $page = ['items' => [], 'lines' => 0];
    $maxLines = 30;

    $brandPrimary = pdf_rgb_str('#c6746a');
    $brandSecondary = pdf_rgb_str('#11417b');
    $brandSoft = pdf_rgb_str('#facee1');
    $logoPath = realpath(__DIR__ . '/../ASSETS/IMG/icon.png');
    $logo = $logoPath ? pdf_build_logo_image($logoPath) : null;

    $pushPage = function () use (&$pages, &$page): void {
        $pages[] = $page;
        $page = ['items' => [], 'lines' => 0];
    };

    foreach ($blocos as $bloco) {
        $type = $bloco['type'] ?? 'text';
        $estimatedLines = 1;

        if ($type === 'row') {
            $estimatedLines = count(pdf_wrap_lines($bloco['text'] ?? '', 82));
        } elseif ($type === 'meta') {
            $estimatedLines = count(pdf_wrap_lines(($bloco['label'] ?? '') . ': ' . ($bloco['value'] ?? ''), 76));
        } elseif ($type === 'metric') {
            $estimatedLines = 3;
        } elseif ($type === 'section') {
            $estimatedLines = 2;
        } elseif ($type === 'spacer') {
            $estimatedLines = 1;
        }

        if ($page['lines'] + $estimatedLines > $maxLines && !empty($page['items'])) {
            $pushPage();
        }

        $page['items'][] = $bloco;
        $page['lines'] += $estimatedLines;
    }

    if (!empty($page['items']) || empty($pages)) {
        $pages[] = $page;
    }

    $objects = [];
    $catalogObj = 1;
    $pagesObj = 2;
    $logoObj = $logo ? 3 : null;
    $fontRegularObj = $logo ? 4 : 3;
    $fontBoldObj = $logo ? 5 : 4;
    $nextObj = $logo ? 6 : 5;
    $kids = [];
    $totalPages = count($pages);

    foreach ($pages as $pageIndex => $pageData) {
        $cursorY = 720;
        $content = "q\n";
        $content .= $brandPrimary . " rg\n0 770 595 72 re f\n";
        $content .= $brandSoft . " rg\n0 748 595 22 re f\n";
        $content .= "0.97 0.97 0.97 rg\n40 694 515 24 re f\n";
        if ($logo) {
            $content .= "q\n44 0 0 44 42 782 cm\n/Im1 Do\nQ\n";
        }
        $content .= "BT\n/F2 20 Tf\n1 1 1 rg\n" . ($logo ? '96' : '50') . " 800 Td\n(" . pdf_escape_text('Relatórios Gela-Gela') . ") Tj\nET\n";
        $content .= "BT\n/F1 11 Tf\n1 1 1 rg\n" . ($logo ? '96' : '50') . " 782 Td\n(" . pdf_escape_text($titulo) . ") Tj\nET\n";
        $content .= "BT\n/F1 8 Tf\n0.35 0.35 0.35 rg\n50 754 Td\n(" . pdf_escape_text('Emitido em ' . date('d/m/Y H:i') . '  |  Página ' . ($pageIndex + 1) . ' de ' . $totalPages) . ") Tj\nET\n";

        foreach ($pageData['items'] as $item) {
            $type = $item['type'] ?? 'text';

            if ($type === 'spacer') {
                $cursorY -= 10;
                continue;
            }

            if ($type === 'section') {
                $cursorY -= 18;
                $content .= "BT\n/F2 13 Tf\n0.18 0.18 0.18 rg\n50 {$cursorY} Td\n(" . pdf_escape_text($item['text'] ?? '') . ") Tj\nET\n";
                $cursorY -= 5;
                $content .= $brandPrimary . " RG\n50 {$cursorY} m\n545 {$cursorY} l\nS\n";
                $cursorY -= 14;
                continue;
            }

            if ($type === 'meta') {
                $label = $item['label'] ?? '';
                $value = $item['value'] ?? '';
                $content .= "BT\n/F2 9 Tf\n0.25 0.25 0.25 rg\n50 {$cursorY} Td\n(" . pdf_escape_text($label . ':') . ") Tj\nET\n";
                $content .= "BT\n/F1 9 Tf\n0.18 0.18 0.18 rg\n165 {$cursorY} Td\n(" . pdf_escape_text($value) . ") Tj\nET\n";
                $cursorY -= 13;
                continue;
            }

            if ($type === 'metric') {
                $label = $item['label'] ?? '';
                $value = $item['value'] ?? '';
                $fill = $item['fill'] ?? '0.97 0.97 0.97';
                $content .= $fill . " rg\n45 " . ($cursorY - 14) . " 505 32 re f\n";
                $content .= "0.83 0.83 0.83 RG\n45 " . ($cursorY - 12) . " 505 28 re S\n";
                $content .= "BT\n/F2 9 Tf\n0.20 0.20 0.20 rg\n55 {$cursorY} Td\n(" . pdf_escape_text($label) . ") Tj\nET\n";
                $content .= "BT\n/F1 11 Tf\n" . $brandPrimary . " rg\n320 {$cursorY} Td\n(" . pdf_escape_text($value) . ") Tj\nET\n";
                $cursorY -= 28;
                continue;
            }

            if ($type === 'row') {
                $rowText = $item['text'] ?? '';
                $wrapped = pdf_wrap_lines($rowText, 82);
                foreach ($wrapped as $wrapLine) {
                    $content .= "BT\n/F1 8.5 Tf\n0.18 0.18 0.18 rg\n50 {$cursorY} Td\n(" . pdf_escape_text($wrapLine) . ") Tj\nET\n";
                    $cursorY -= 13;
                }
                $cursorY -= 2;
                continue;
            }
        }
        $contentObj = $nextObj++;
        $pageObj = $nextObj++;
        $kids[] = $pageObj;
        $content .= "Q\n";
        $objects[$contentObj] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $resources = "<< /Font << /F1 {$fontRegularObj} 0 R /F2 {$fontBoldObj} 0 R >>";
        if ($logoObj) {
            $resources .= " /XObject << /Im1 {$logoObj} 0 R >>";
        }
        $resources .= " >>";
        $objects[$pageObj] = "<< /Type /Page /Parent {$pagesObj} 0 R /MediaBox [0 0 595 842] /Resources {$resources} /Contents {$contentObj} 0 R >>";
    }

    $kidsRef = implode(' ', array_map(fn($id) => $id . ' 0 R', $kids));
    $objects[$catalogObj] = "<< /Type /Catalog /Pages {$pagesObj} 0 R >>";
    $objects[$pagesObj] = "<< /Type /Pages /Kids [{$kidsRef}] /Count " . count($kids) . " >>";
    if ($logo && isset($logoObj)) {
        $objects[$logoObj] = "<< /Type /XObject /Subtype /Image /Width {$logo['width']} /Height {$logo['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($logo['data']) . " >>\nstream\n" . $logo['data'] . "\nendstream";
    }
    $objects[$fontRegularObj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[$fontBoldObj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0 => 0];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (max(array_keys($objects)) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= max(array_keys($objects)); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root {$catalogObj} 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="relatorio-' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit();
}
 
// ─── Registrar lançamento (POST) ──────────────────────────────────────
$msg_sucesso = '';
$msg_erro    = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
 
    // Lançar venda
    if ($acao === 'lancar_venda') {
        $data_v          = $_POST['data_venda']     ?? date('Y-m-d');
        $peso            = floatval($_POST['peso_total']  ?? 0);
        $valor           = floatval($_POST['valor_total']  ?? 0);
        $forma_pagamento = $_POST['forma_pagamento'] ?? 'Dinheiro';
        $usuario_id      = $_SESSION['usuario_id'] ?? 1;
 
        $stmt = $pdo->prepare("INSERT INTO vendas (data_venda, usuario_id, peso_total, valor_total, forma_pagamento) VALUES (?,?,?,?,?)");
        if ($stmt->execute([$data_v, $usuario_id, $peso, $valor, $forma_pagamento])) {
            $msg_sucesso = 'Venda registrada com sucesso!';
        } else {
            $msg_erro = 'Erro ao registrar venda.';
        }
    }
 
    // Lançar despesa (gerente)
    if ($acao === 'lancar_despesa' && $is_gerente) {
        if (!$tem_tabela_despesas) {
            $msg_erro = 'Tabela de despesas não encontrada no banco de dados.';
        } else {
            $data_d  = $_POST['data_despesa'] ?? date('Y-m-d');
            $desc    = trim($_POST['descricao'] ?? '');
            $cat     = trim($_POST['categoria_desp'] ?? '');
            $val_d   = floatval($_POST['valor_despesa'] ?? 0);
            $usuario_id = $_SESSION['usuario_id'] ?? null;
 
            $stmt = $pdo->prepare("INSERT INTO despesas (data_despesa, descricao, categoria, valor, usuario_id) VALUES (?,?,?,?,?)");
            if ($stmt->execute([$data_d, $desc, $cat, $val_d, $usuario_id])) {
                $msg_sucesso = 'Despesa registrada com sucesso!';
            } else {
                $msg_erro = 'Erro ao registrar despesa.';
            }
        }
    }
}
 
// ─── Queries de dados ─────────────────────────────────────────────────
// KPIs de Vendas
$kpi_vendas = $pdo->prepare("
    SELECT
        COALESCE(SUM(peso_total),0)       AS total_peso,
        COALESCE(SUM(valor_total),0)      AS total_valor,
        COALESCE(SUM(desconto_aplicado),0) AS total_desconto,
        COUNT(*)                          AS total_registros
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
");
$kpi_vendas->execute([$data_inicio, $data_fim]);
$kv = $kpi_vendas->fetch(PDO::FETCH_ASSOC);
 
// KPIs de Despesas (gerente)
$total_despesas = 0;
if ($is_gerente && $tem_tabela_despesas) {
    $kpi_desp = $pdo->prepare("SELECT COALESCE(SUM(valor),0) AS total FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $kpi_desp->execute([$data_inicio, $data_fim]);
    $total_despesas = $kpi_desp->fetchColumn();
}
 
$lucro_estimado = $kv['total_valor'] - $kv['total_desconto'] - $total_despesas;
 
// Tabela de vendas
$rows_vendas = $pdo->prepare("SELECT * FROM vendas WHERE data_venda BETWEEN ? AND ? ORDER BY data_venda DESC");
$rows_vendas->execute([$data_inicio, $data_fim]);
$lista_vendas = $rows_vendas->fetchAll(PDO::FETCH_ASSOC);

$rows_vendas_detalhadas = $pdo->prepare(<<<SQL
    SELECT
        v.venda_id,
        v.data_venda,
        v.peso_total AS peso_venda,
        v.valor_total,
        v.desconto_aplicado,
        v.forma_pagamento,
        iv.item_venda_id,
        COALESCE(NULLIF(iv.sabor, ''), NULLIF(iv.adicionais, ''), p.nome) AS item_vendido,
        iv.quantidade AS quantidade_item,
        iv.valor_unitario
    FROM vendas v
    LEFT JOIN itens_venda iv ON iv.venda_id = v.venda_id
    LEFT JOIN produtos p ON p.produto_id = iv.produto_id
    WHERE v.data_venda BETWEEN ? AND ?
    ORDER BY v.data_venda DESC, v.venda_id DESC, iv.item_venda_id ASC
SQL);
$rows_vendas_detalhadas->execute([$data_inicio, $data_fim]);
$lista_vendas_detalhadas = $rows_vendas_detalhadas->fetchAll(PDO::FETCH_ASSOC);
 
// Tabela de estoque
$lista_estoque = $pdo->query("
    SELECT
        e.*,
        p.nome AS produto,
        p.categoria,
        p.unidade_medida AS unidade,
        e.quantidade_disponivel AS quantidade,
        10 AS estoque_minimo,
        DATEDIFF(e.validade, CURDATE()) AS dias_validade,
        CASE
            WHEN e.quantidade_disponivel <= 10 THEN 'danger'
            WHEN DATEDIFF(e.validade, CURDATE()) <= 7 AND e.validade IS NOT NULL THEN 'warn'
            ELSE 'ok'
        END AS status,
        CASE
            WHEN e.quantidade_disponivel <= 10 THEN 3
            WHEN DATEDIFF(e.validade, CURDATE()) <= 7 AND e.validade IS NOT NULL THEN 2
            ELSE 1
        END AS prioridade
    FROM estoque e
    JOIN produtos p ON e.produto_id = p.produto_id
    ORDER BY prioridade DESC, p.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);
// Tabela de despesas (gerente)
$lista_despesas = [];
if ($is_gerente && $tem_tabela_despesas) {
    $rows_desp = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa BETWEEN ? AND ? ORDER BY data_despesa DESC");
    $rows_desp->execute([$data_inicio, $data_fim]);
    $lista_despesas = $rows_desp->fetchAll(PDO::FETCH_ASSOC);
}
 
// Dados para o gráfico de evolução diária (vendas)
$grafico = $pdo->prepare("
    SELECT data_venda, SUM(valor_total) AS total_dia
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
    GROUP BY data_venda
    ORDER BY data_venda ASC
");
$grafico->execute([$data_inicio, $data_fim]);
$dados_grafico = $grafico->fetchAll(PDO::FETCH_ASSOC);
$labels_grafico = json_encode(array_map(fn($r) => date('d/m', strtotime($r['data_venda'])), $dados_grafico));
$valores_grafico = json_encode(array_map(fn($r) => (float)$r['total_dia'], $dados_grafico));

if ($exportar_pdf) {
    $titulo_pdf = 'Relatório de Vendas';
    $blocos_pdf = [];

    if ($tipo === 'estoque') {
        $titulo_pdf = 'Relatório de Estoque';
    } elseif ($tipo === 'financeiro' && $is_gerente) {
        $titulo_pdf = 'Relatório Financeiro';
    }

    $blocos_pdf[] = ['type' => 'meta', 'label' => 'Período', 'value' => $label_periodo];
    $blocos_pdf[] = ['type' => 'meta', 'label' => 'Perfil de acesso', 'value' => ucfirst($perfil)];
    $blocos_pdf[] = ['type' => 'meta', 'label' => 'Seção exportada', 'value' => ucfirst($tipo)];
    $blocos_pdf[] = ['type' => 'spacer'];
    $blocos_pdf[] = ['type' => 'section', 'text' => 'Resumo executivo'];
    $blocos_pdf[] = ['type' => 'metric', 'label' => 'Peso total vendido', 'value' => kg($kv['total_peso'])];
    $blocos_pdf[] = ['type' => 'metric', 'label' => 'Faturamento bruto', 'value' => brl($kv['total_valor'])];
    $blocos_pdf[] = ['type' => 'metric', 'label' => 'Total de descontos', 'value' => brl($kv['total_desconto'])];
    $blocos_pdf[] = ['type' => 'metric', 'label' => 'Lançamentos no período', 'value' => (string)$kv['total_registros']];

    if ($is_gerente) {
        $blocos_pdf[] = ['type' => 'metric', 'label' => 'Lucro estimado', 'value' => brl($lucro_estimado), 'fill' => '0.96 0.99 0.96'];
    }

    if ($tipo === 'vendas') {
        $blocos_pdf[] = ['type' => 'section', 'text' => 'Vendas detalhadas'];
        foreach ($lista_vendas_detalhadas as $v) {
            $blocos_pdf[] = ['type' => 'row', 'text' => 'Venda #' . $v['venda_id'] . ' | ' . date('d/m/Y H:i', strtotime($v['data_venda'])) . ' | Item: ' . $v['item_vendido'] . ' | Quantidade: ' . number_format((float)$v['quantidade_item'], 3, ',', '.') . ' kg | Peso da venda: ' . kg($v['peso_venda']) . ' | Valor: ' . brl($v['valor_total']) . ' | Pagamento: ' . $v['forma_pagamento']];
        }
    }

    if ($tipo === 'estoque') {
        $blocos_pdf[] = ['type' => 'section', 'text' => 'Posição de estoque'];
        foreach ($lista_estoque as $e) {
            $blocos_pdf[] = ['type' => 'row', 'text' => $e['produto'] . ' | Categoria: ' . ($e['categoria'] ?: '—') . ' | Quantidade: ' . number_format($e['quantidade'], 2, ',', '.') . ' ' . $e['unidade'] . ' | Validade: ' . ($e['validade'] ? date('d/m/Y', strtotime($e['validade'])) : '—') . ' | Status: ' . ucfirst($e['status'])];
        }
    }

    if ($tipo === 'financeiro' && $is_gerente) {
        $blocos_pdf[] = ['type' => 'section', 'text' => 'Indicadores financeiros'];
        $blocos_pdf[] = ['type' => 'metric', 'label' => 'Faturamento bruto', 'value' => brl($kv['total_valor'])];
        $blocos_pdf[] = ['type' => 'metric', 'label' => 'Total de despesas', 'value' => brl($total_despesas)];
        $blocos_pdf[] = ['type' => 'metric', 'label' => 'Lucro líquido', 'value' => brl($lucro_estimado), 'fill' => '0.96 0.99 0.96'];
        $blocos_pdf[] = ['type' => 'section', 'text' => 'Despesas do período'];
        foreach ($lista_despesas as $d) {
            $blocos_pdf[] = ['type' => 'row', 'text' => date('d/m/Y', strtotime($d['data_despesa'])) . ' | Categoria: ' . $d['categoria'] . ' | Descrição: ' . $d['descricao'] . ' | Valor: ' . brl($d['valor'])];
        }
    }

    gerar_pdf_profissional($titulo_pdf, $blocos_pdf);
}
 
// Helper formatação
function brl($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
function kg($v)  { return number_format($v, 2, ',', '.') . ' kg'; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Relatórios</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
 </head>
 
<body>
<div class="layout">
 
    <!-- SIDEBAR -->
    <?php require_once '../components/sidebar.php'; ?>
 
    <!-- CONTEÚDO -->
    <main class="content content-tab-<?= htmlspecialchars($tipo) ?>">
        <header class="topbar">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
            <h1>Relatórios</h1>
            <div class="user-menu">
                <div class="avatar" onclick="toggleUserMenu()">
                    <?= $inicial ?>
                </div>
                <div class="dropdown-user" id="userDropdown">
                    <p><?= htmlspecialchars($nome_usuario) ?></p>
                    <a href="perfil">Perfil</a>
                    <a href="logout" class="logout">Sair do sistema</a>
                </div>
            </div>
        </header>
 
        <section class="main">
 
            <?php if ($msg_sucesso): ?>
                <div class="alert success"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($msg_sucesso) ?></div>
            <?php endif; ?>
            <?php if ($msg_erro): ?>
                <div class="alert error"><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($msg_erro) ?></div>
            <?php endif; ?>
 
            <!-- FILTRO DE PERÍODO -->
            <form method="GET" class="filter-bar">
                <div>
                    <label>Período</label>
                    <select name="periodo" onchange="this.form.submit()">
                        <option value="dia"    <?= $periodo==='dia'   ?'selected':'' ?>>Hoje</option>
                        <option value="semana" <?= $periodo==='semana'?'selected':'' ?>>Esta Semana</option>
                        <option value="mes"    <?= $periodo==='mes'   ?'selected':'' ?>>Este Mês</option>
                    </select>
                </div>
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                <span style="font-size:13px; color:var(--text-muted); align-self:center;">
                    <i class="fa fa-calendar-days"></i> <?= $label_periodo ?>
                </span>
                <a class="btn btn-secondary" href="?periodo=<?= urlencode($periodo) ?>&tipo=<?= urlencode($tipo) ?>&exportar=pdf" style="align-self:center; text-decoration:none;">
                    <i class="fa fa-file-pdf"></i> Exportar PDF
                </a>
            </form>
 
            <!-- KPIs RÁPIDOS -->
            <div class="kpi-grid vendas-kpis">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa fa-scale-balanced"></i> Peso Total Vendido</div>
                    <div class="kpi-valor"><?= kg($kv['total_peso']) ?></div>
                    <div class="kpi-sub"><?= $kv['total_registros'] ?> lançamento(s) no período</div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-label"><i class="fa fa-dollar-sign"></i> Faturamento</div>
                    <div class="kpi-valor"><?= brl($kv['total_valor']) ?></div>
                    <div class="kpi-sub">Valor total das vendas</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label"><i class="fa fa-percentage"></i> Total de Descontos</div>
                    <div class="kpi-valor"><?= brl($kv['total_desconto']) ?></div>
                    <div class="kpi-sub">Descontos aplicados nas vendas</div>
                </div>
                <?php if ($is_gerente): ?>
                <div class="kpi-card green">
                    <div class="kpi-label"><i class="fa fa-arrow-trend-up"></i> Lucro Estimado</div>
                    <div class="kpi-valor" style="color:<?= $lucro_estimado >= 0 ? '#27ae60' : '#e74c3c' ?>">
                        <?= brl($lucro_estimado) ?>
                    </div>
                    <div class="kpi-sub">Faturamento − Insumos − Despesas</div>
                </div>
                <?php endif; ?>
            </div>
 
            <!-- ABAS -->
            <div class="tab-nav">
                <button class="tab-btn <?= $tipo==='vendas'?'active':'' ?>" onclick="mudarAba('vendas', this)">
                    <i class="fa fa-cart-shopping"></i> Vendas
                </button>
                <button class="tab-btn <?= $tipo==='estoque'?'active':'' ?>" onclick="mudarAba('estoque', this)">
                    <i class="fa fa-boxes-stacked"></i> Estoque
                </button>
                <?php if ($is_gerente): ?>
                <button class="tab-btn <?= $tipo==='financeiro'?'active':'' ?>" onclick="mudarAba('financeiro', this)">
                    <i class="fa fa-chart-pie"></i> Financeiro
                </button>
                <?php else: ?>
                <button class="tab-btn locked" title="Apenas gerentes podem acessar" type="button">
                    <i class="fa fa-lock"></i> Financeiro
                </button>
                <?php endif; ?>
            </div>
 
            <!-- ═══ ABA: VENDAS ════════════════════════════════════ -->
            <div class="tab-panel <?= $tipo==='vendas'?'active':'' ?>" id="tab-vendas">
 
                <!-- Gráfico -->
                <div class="chart-wrap">
                    <h3><i class="fa fa-chart-line" style="color:var(--primary)"></i> Evolução das Vendas — <?= $label_periodo ?></h3>
                    <canvas id="grafico-vendas" height="90"></canvas>
                </div>

                <div class="box" style="margin-top:20px;">
                    <div class="header-box">
                        <h3><i class="fa fa-receipt" style="color:var(--primary)"></i> Detalhamento das Vendas</h3>
                        <span style="font-size:12px; color:var(--text-muted);">Item vendido, quantidade, peso da venda e valor</span>
                    </div>

                    <?php if (empty($lista_vendas_detalhadas)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:30px 0;">
                            <i class="fa fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; color:#ddd"></i>
                            Nenhuma venda encontrada no período.
                        </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Venda</th>
                                    <th>Item vendido</th>
                                    <th>Quantidade</th>
                                    <th>Peso da venda</th>
                                    <th>Valor</th>
                                    <th>Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_vendas_detalhadas as $v): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                    <td>#<?= (int)$v['venda_id'] ?></td>
                                    <td style="max-width:320px; white-space:normal; line-height:1.5;">
                                        <?= htmlspecialchars($v['item_vendido'] ?: 'Sem item') ?>
                                    </td>
                                    <td><?= number_format((float)$v['quantidade_item'], 3, ',', '.') ?> kg</td>
                                    <td><?= kg($v['peso_venda']) ?></td>
                                    <td style="font-weight:700; color:#2c3e50;"><?= brl($v['valor_total']) ?></td>
                                    <td><span class="tag-resumo"><?= htmlspecialchars(str_replace('_', ' ', $v['forma_pagamento'])) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
 
            </div><!-- /tab-vendas -->
 
            <!-- ═══ ABA: ESTOQUE ═══════════════════════════════════ -->
            <div class="tab-panel <?= $tipo==='estoque'?'active':'' ?>" id="tab-estoque">
                <div class="box">
                    <div class="header-box">
                        <h3><i class="fa fa-boxes-stacked" style="color:var(--secondary)"></i> Controle de Estoque</h3>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <span style="font-size:12px; color:var(--text-muted);">
                                <span class="status-dot danger"></span>Crítico &nbsp;
                                <span class="status-dot warn"></span>Atenção &nbsp;
                                <span class="status-dot ok"></span>Normal
                            </span>
                        </div>
                    </div>
 
                    <?php if (empty($lista_estoque)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:30px 0;">
                            <i class="fa fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; color:#ddd"></i>
                            Nenhum produto no estoque cadastrado.
                        </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Quantidade</th>
                                    <th>Mínimo</th>
                                    <th>Validade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_estoque as $e):
                                    $s = $e['status'];
                                    $labels = ['danger'=>'Crítico','warn'=>'Atenção','ok'=>'Normal'];
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($e['produto']) ?></strong></td>
                                    <td><?= htmlspecialchars($e['categoria'] ?: '—') ?></td>
                                    <td><?= number_format($e['quantidade'], 2, ',', '.') ?> <?= $e['unidade'] ?></td>
                                    <td><?= number_format($e['estoque_minimo'], 2, ',', '.') ?> <?= $e['unidade'] ?></td>
                                    <td>
                                        <?php if ($e['validade']): ?>
                                            <?= date('d/m/Y', strtotime($e['validade'])) ?>
                                            <?php if ($e['dias_validade'] !== null && $e['dias_validade'] <= 7): ?>
                                                <span style="font-size:11px; color:#e67e22; font-weight:700;">
                                                    (<?= $e['dias_validade'] ?> dias)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s ?>">
                                            <span class="status-dot <?= $s ?>"></span><?= $labels[$s] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- /tab-estoque -->
 
            <!-- ═══ ABA: FINANCEIRO (só gerente) ══════════════════ -->
            <?php if ($is_gerente): ?>
            <div class="tab-panel <?= $tipo==='financeiro'?'active':'' ?>" id="tab-financeiro">
 
                <!-- Cards financeiros extras -->
                <div class="kpi-grid" style="margin-bottom:20px;">
                    <div class="kpi-card blue">
                        <div class="kpi-label"><i class="fa fa-money-bill-trend-up"></i> Faturamento Bruto</div>
                        <div class="kpi-valor"><?= brl($kv['total_valor']) ?></div>
                        <div class="kpi-sub">Total de vendas no período</div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-label"><i class="fa fa-file-invoice"></i> Total de Despesas</div>
                        <div class="kpi-valor"><?= brl($total_despesas) ?></div>
                        <div class="kpi-sub">Despesas operacionais</div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-label"><i class="fa fa-percentage"></i> Total de Descontos</div>
                        <div class="kpi-valor"><?= brl($kv['total_desconto']) ?></div>
                        <div class="kpi-sub">Descontos aplicados nas vendas</div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-label"><i class="fa fa-sack-dollar"></i> Lucro Líquido</div>
                        <div class="kpi-valor" style="color:<?= $lucro_estimado>=0?'#27ae60':'#e74c3c' ?>">
                            <?= brl($lucro_estimado) ?>
                        </div>
                        <div class="kpi-sub">Faturamento − Insumos − Despesas</div>
                    </div>
                </div>
 
                <!-- Despesas -->
                <div class="box">
                    <div class="header-box">
                        <h3><i class="fa fa-file-invoice-dollar" style="color:var(--primary)"></i> Despesas Operacionais</h3>
                        <?php if ($tem_tabela_despesas): ?>
                        <button class="btn-lancar" onclick="abrirModal('modal-despesa')">
                            <i class="fa fa-plus"></i> Registrar Despesa
                        </button>
                        <?php else: ?>
                        <span style="color:var(--text-muted); font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa fa-triangle-exclamation" style="color:#e67e22"></i> Dados de despesas não disponíveis
                        </span>
                        <?php endif; ?>
                    </div>
 
                    <?php if (!$tem_tabela_despesas): ?>
                        <p style="color:#e67e22; text-align:center; padding:30px 0; font-weight:700;">
                            A tabela de despesas não existe no banco de dados.
                        </p>
                    <?php elseif (empty($lista_despesas)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:30px 0;">
                            <i class="fa fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; color:#ddd"></i>
                            Nenhuma despesa registrada no período.
                        </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_despesas as $d): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($d['data_despesa'])) ?></td>
                                    <td><?= htmlspecialchars($d['descricao']) ?></td>
                                    <td>
                                        <span class="tag-resumo"><?= htmlspecialchars($d['categoria'] ?: 'Geral') ?></span>
                                    </td>
                                    <td style="font-weight:700; color:#e74c3c;"><?= brl($d['valor']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--soft); font-weight:700;">
                                    <td colspan="3">TOTAL DESPESAS</td>
                                    <td style="color:#e74c3c;"><?= brl($total_despesas) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- /tab-financeiro -->
            <?php else: ?>
            <div class="tab-panel" id="tab-financeiro">
                <div class="box acesso-negado">
                    <i class="fa fa-lock"></i>
                    <h3>Acesso Restrito</h3>
                    <p>O relatório financeiro está disponível apenas para gerentes.</p>
                </div>
            </div>
            <?php endif; ?>
 
        </section>
    </main>
</div>
 
<!-- ═══ MODAL: REGISTRAR VENDA ════════════════════════════════════════ -->
<div class="modal" id="modal-venda">
    <div class="modal-content">
        <h3><i class="fa fa-cart-shopping" style="color:var(--primary)"></i> Registrar Venda</h3>
        <form method="POST">
            <input type="hidden" name="acao" value="lancar_venda">
            <div class="grid-form">
                <div class="form-group">
                    <label>Data da Venda</label>
                    <input type="date" name="data_venda" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Peso Vendido (kg)</label>
                    <input type="number" name="peso_total" step="0.01" min="0" placeholder="Ex: 12.50" required>
                </div>
                <div class="form-group">
                    <label>Valor Total (R$)</label>
                    <input type="number" name="valor_total" step="0.01" min="0" placeholder="Ex: 350.00" required>
                </div>
                <div class="form-group">
                    <label>Forma de Pagamento</label>
                    <select name="forma_pagamento" required>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Cartao_Credito">Cartão de Crédito</option>
                        <option value="Cartao_Debito">Cartão de Débito</option>
                        <option value="Pix">Pix</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <button type="submit" class="btn btn-secondary" style="flex:1; background:var(--secondary); color:#fff;">
                    <i class="fa fa-floppy-disk"></i> Salvar
                </button>
                <button type="button" class="btn btn-secondary" style="flex:0.4;" onclick="fecharModal('modal-venda')">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
 
<?php if ($is_gerente): ?>
<!-- ═══ MODAL: REGISTRAR DESPESA ══════════════════════════════════════ -->
<div class="modal" id="modal-despesa">
    <div class="modal-content">
        <h3><i class="fa fa-file-invoice-dollar" style="color:var(--primary)"></i> Registrar Despesa</h3>
        <form method="POST">
            <input type="hidden" name="acao" value="lancar_despesa">
            <div class="grid-form">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_despesa" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria_desp">
                        <option value="Aluguel">Aluguel</option>
                        <option value="Energia">Energia</option>
                        <option value="Folha">Folha de Pagamento</option>
                        <option value="Manutenção">Manutenção</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <input type="text" name="descricao" placeholder="Ex: Aluguel do espaço" required>
            </div>
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" name="valor_despesa" step="0.01" min="0" placeholder="Ex: 1500.00" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <button type="submit" class="btn" style="flex:1; background:var(--secondary); color:#fff;">
                    <i class="fa fa-floppy-disk"></i> Salvar
                </button>
                <button type="button" class="btn btn-secondary" style="flex:0.4;" onclick="fecharModal('modal-despesa')">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
 
<script src="ASSETS/JS/sidebar.js"></script>
<script>
// ── Troca de abas ─────────────────────────────────────────────────────
function mudarAba(aba, button) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
 
    const target = document.getElementById('tab-' + aba);
    if (target) target.classList.add('active');
    if (button) button.classList.add('active');
 
    const content = document.querySelector('.content');
    if (content) {
        content.classList.remove('content-tab-vendas', 'content-tab-estoque', 'content-tab-financeiro');
        content.classList.add('content-tab-' + aba);
    }
 
    // Atualiza URL sem reload
    const url = new URL(window.location.href);
    url.searchParams.set('tipo', aba);
    window.history.replaceState({}, '', url);
}
 
// ── Modais ────────────────────────────────────────────────────────────
function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function fecharModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) fecharModal(this.id);
    });
});
 
// ── Gráfico de Vendas ─────────────────────────────────────────────────
const labels  = <?= $labels_grafico  ?: '[]' ?>;
const valores = <?= $valores_grafico ?: '[]' ?>;
 
if (labels.length > 0) {
    const ctx = document.getElementById('grafico-vendas').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Faturamento (R$)',
                data: valores,
                backgroundColor: 'rgba(198,116,106,0.25)',
                borderColor: '#c6746a',
                borderWidth: 2,
                borderRadius: 6,
                pointBackgroundColor: '#c6746a'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR', {minimumFractionDigits:2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    },
                    grid: { color: '#f0f0f0' }
                },
                x: { grid: { display: false } }
            }
        }
    });
} else {
    const canvas = document.getElementById('grafico-vendas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.height = 60;
        ctx.fillStyle = '#999';
        ctx.font = '14px Segoe UI';
        ctx.textAlign = 'center';
        ctx.fillText('Sem dados de vendas para o período selecionado.', canvas.width / 2, 40);
    }
}


        // ======================================================
        // USER MENU
        // ======================================================

        function toggleUserMenu() {

            document.getElementById('userDropdown')
                .classList.toggle('active');
        }

        document.addEventListener('click', function(e) {

            const menu = document.querySelector('.user-menu');

            if (!menu.contains(e.target)) {

                document.getElementById('userDropdown')
                    .classList.remove('active');
            }
        });
</script>
</body>
</html>