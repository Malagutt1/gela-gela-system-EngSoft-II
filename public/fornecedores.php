<?php 
session_start();
require_once '../components/valida-sessao.php';
require_once '../conecta.php';

if (isset($_GET['acao']) && $_GET['acao'] === 'deletar') {
    $id = $_GET['id'] ?? '';

    if (!empty($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE fornecedor_id = ?");
            $stmt->execute([$id]);
            
            header("Location: fornecedores.php");
            exit;
        } catch (PDOException $e) {
            echo "<script>
                    alert('Não é possível excluir este fornecedor pois ele já possui vínculo com produtos/estoque!');
                    window.location.href = 'fornecedores.php';
                  </script>";
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $contato = $_POST['contato'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';       
    $endereco = $_POST['endereco'] ?? ''; 
    $tipo = $_POST['tipo_produto'] ?? '';
    $prazo = (int)($_POST['prazo_entrega'] ?? 0);

    try {
        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome = ?, contato = ?, telefone = ?, email = ?, endereco = ?, tipo_produto = ?, prazo_entrega_dias = ? WHERE fornecedor_id = ?");
            $stmt->execute([$nome, $contato, $telefone, $email, $endereco, $tipo, $prazo, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, contato, telefone, email, endereco, tipo_produto, prazo_entrega_dias) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $contato, $telefone, $email, $endereco, $tipo, $prazo]);
        }
        
        header("Location: fornecedores.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao salvar no banco de dados: " . $e->getMessage());
    }
}

$sql = "SELECT fornecedor_id, nome, contato, telefone, email, endereco, tipo_produto, prazo_entrega_dias FROM fornecedores ORDER BY nome ASC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Fornecedores</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link rel="stylesheet" href="ASSETS/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .form-group-full {
            grid-column: span 2;
        }
        @media (max-width: 600px) {
            .form-group-full {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php require_once '../components/sidebar.php'; ?>
        <main class="content">
            <header class="topbar">
                <button class="menu-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1>Gestão de Fornecedores</h1>
                <?php require_once '../components/user-menu.php'; ?>
            </header>
            <section class="main">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <h2 style="color: var(--secondary);"><i class="fa-solid fa-truck"></i> Fornecedores</h2>
                    <button class="btn" onclick="abrirModalFornecedor()">
                        <i class="fa-solid fa-plus"></i> Novo Fornecedor
                    </button>
                </div>
                <div class="box">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Contatos</th>
                                    <th>Tipo de Produto</th>
                                    <th>Prazo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-fornecedores">
                                <?php
                                if ($result && $result->rowCount() > 0) {
                                    while ($row = $result->fetch()) {
                                        $id = $row['fornecedor_id'];
                                        
                                        $nomeDb = (string)($row['nome'] ?? '');
                                        $contatoDb = (string)($row['contato'] ?? '');
                                        $telefoneDb = (string)($row['telefone'] ?? '');
                                        $emailDb = (string)($row['email'] ?? '');
                                        $enderecoDb = (string)($row['endereco'] ?? '');
                                        $tipoDb = (string)($row['tipo_produto'] ?? '');
                                        $prazoDb = (int)($row['prazo_entrega_dias'] ?? 0);

                                        $exibeContato = '<strong>' . htmlspecialchars($contatoDb) . '</strong>';
                                        if ($telefoneDb !== '') {
                                            $exibeContato .= '<br><small><i class="fa-solid fa-phone"></i> ' . htmlspecialchars($telefoneDb) . '</small>';
                                        }
                                        if ($emailDb !== '') {
                                            $exibeContato .= '<br><small><i class="fa-solid fa-envelope"></i> ' . htmlspecialchars($emailDb) . '</small>';
                                        }

                                        $nomeJS = addslashes($nomeDb);
                                        $contatoJS = addslashes($contatoDb);
                                        $telefoneJS = addslashes($telefoneDb);
                                        $emailJS = addslashes($emailDb);
                                        $enderecoJS = addslashes($enderecoDb);
                                        $tipoJS = addslashes($tipoDb);
                                ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($nomeDb) ?>
                                                <?php if($enderecoDb !== ''): ?>
                                                    <br><small style="color: #777;"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($enderecoDb) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $exibeContato ?></td>
                                            <td><span class="tag-resumo"><?= htmlspecialchars($tipoDb) ?></span></td>
                                            <td><?= $prazoDb ?> dias</td>
                                            <td>
                                                <button class="btn-icon" onclick="editarFornecedor(<?= $id ?>, '<?= $nomeJS ?>', '<?= $contatoJS ?>', '<?= $telefoneJS ?>', '<?= $emailJS ?>', '<?= $enderecoJS ?>', '<?= $tipoJS ?>', '<?= $prazoDb ?>')">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button class="btn-icon danger" onclick="excluirFornecedor(<?= $id ?>)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align: center;'>Nenhum fornecedor cadastrado.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="modalFornecedor" class="modal">
        <div class="modal-content box">
            <h3 id="modal-title">Cadastrar Fornecedor</h3>
            <form id="formFornecedor" method="POST" action="fornecedores.php">
                <input type="hidden" id="f-id" name="id">
                <div class="grid-form">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" id="f-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Contato (Setor)</label>
                        <input type="text" id="f-contato" name="contato" placeholder="Ex: Administrativo, Comercial">
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" id="f-telefone" name="telefone" placeholder="Ex: (49) 99999-9999">
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" id="f-email" name="email" placeholder="Ex: fornecedor@email.com">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Produto</label>
                        <input type="text" id="f-tipo" name="tipo_produto">
                    </div>
                    <div class="form-group">
                        <label>Prazo de Entrega (dias)</label>
                        <input type="number" id="f-prazo" name="prazo_entrega" placeholder="Ex: 3">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Endereço</label>
                        <input type="text" id="f-endereco" name="endereco" placeholder="Ex: Av. Getúlio Vargas, 1000 - Centro">
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalFornecedor()">Cancelar</button>
                    <button type="submit" class="btn">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/user-menu.js"></script>
    
    <script>
        const modal = document.getElementById('modalFornecedor');
        const form = document.getElementById('formFornecedor');

        function abrirModalFornecedor() {
            document.getElementById('modal-title').innerText = "Cadastrar Fornecedor";
            form.reset(); 
            document.getElementById('f-id').value = "";
            document.getElementById('f-telefone').value = "";
            document.getElementById('f-email').value = "";
            document.getElementById('f-endereco').value = "";
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function fecharModalFornecedor() {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }

        function editarFornecedor(id, nome, contato, telefone, email, endereco, tipo, prazo) {
            document.getElementById('modal-title').innerText = "Editar Fornecedor";
            document.getElementById('f-id').value = id;
            document.getElementById('f-nome').value = nome;
            document.getElementById('f-contato').value = contato;
            document.getElementById('f-telefone').value = telefone;
            document.getElementById('f-email').value = email;      
            document.getElementById('f-endereco').value = endereco;  
            document.getElementById('f-tipo').value = tipo;
            document.getElementById('f-prazo').value = prazo;
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function excluirFornecedor(id) {
            if(confirm("Tem certeza que deseja excluir este fornecedor?")) {
                window.location.href = `fornecedores.php?acao=deletar&id=${id}`;
            }
        }
    </script>
</body>
</html>