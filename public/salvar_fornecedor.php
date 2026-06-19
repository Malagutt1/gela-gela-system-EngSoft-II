<?php
session_start();
require_once '../conecta.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Resgata os dados enviados pelo formulário
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $contato = $_POST['contato'] ?? '';
    $tipo = $_POST['tipo_produto'] ?? '';
    $prazo = (int)($_POST['prazo_entrega'] ?? 0);

    try {
        if (!empty($id)) {
            // Se tem ID, é uma EDIÇÃO (UPDATE)
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome = ?, contato = ?, tipo_produto = ?, prazo_entrega_dias = ? WHERE fornecedor_id = ?");
            $stmt->execute([$nome, $contato, $tipo, $prazo, $id]);
        } else {
            // Se não tem ID, é um NOVO CADASTRO (INSERT)
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, contato, tipo_produto, prazo_entrega_dias) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $contato, $tipo, $prazo]);
        }
    } catch (PDOException $e) {
        die("Erro ao salvar no banco de dados: " . $e->getMessage());
    }
    
    // Redireciona de volta para a tela de fornecedores
    header("Location: fornecedores.php");
    exit;
}