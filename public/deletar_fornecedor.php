<?php
session_start();
require_once '../conecta.php';

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE fornecedor_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Se der erro (ex: o fornecedor já está vinculado a um produto no estoque), 
        // o banco bloqueia a exclusão por segurança (Foreign Key).
        echo "<script>
                alert('Não é possível excluir este fornecedor pois ele já possui vínculo com produtos/estoque!');
                window.location.href = 'fornecedores.php';
              </script>";
        exit;
    }
}

// Retorna para a tela
header("Location: fornecedores.php");
exit;
?>