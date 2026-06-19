<?php 
session_start();
require_once '../conecta.php';

// Busca incluindo o telefone, que existe no seu banco de dados
$sql = "SELECT fornecedor_id, nome, contato, telefone, tipo_produto, prazo_entrega_dias FROM fornecedores ORDER BY nome ASC";
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
            </header>
            <section class="main">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <h2><i class="fa-solid fa-truck"></i> Fornecedores</h2>
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
                                    <th>Contato / Telefone</th>
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
                                        
                                        // Tratamento anti-erro 500 do PHP 8.2 (Evita nulos)
                                        $nomeDb = (string)($row['nome'] ?? '');
                                        $contatoDb = (string)($row['contato'] ?? '');
                                        $telefoneDb = (string)($row['telefone'] ?? '');
                                        $tipoDb = (string)($row['tipo_produto'] ?? '');
                                        $prazoDb = (int)($row['prazo_entrega_dias'] ?? 0);

                                        // Formata visualização de contato
                                        $exibeContato = htmlspecialchars($contatoDb);
                                        if ($telefoneDb !== '') {
                                            $exibeContato .= '<br><small>' . htmlspecialchars($telefoneDb) . '</small>';
                                        }

                                        // addslashes seguro contra valores null
                                        $nomeJS = addslashes($nomeDb);
                                        $contatoJS = addslashes($contatoDb);
                                        $tipoJS = addslashes($tipoDb);
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($nomeDb) ?></td>
                                            <td><?= $exibeContato ?></td>
                                            <td><span class="tag-resumo"><?= htmlspecialchars($tipoDb) ?></span></td>
                                            <td><?= $prazoDb ?> dias</td>
                                            <td>
                                                <button class="btn-icon" onclick="editarFornecedor(<?= $id ?>, '<?= $nomeJS ?>', '<?= $contatoJS ?>', '<?= $tipoJS ?>', '<?= $prazoDb ?>')">
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
            <form id="formFornecedor" onsubmit="salvarFornecedor(event)" method="POST" action="salvar_fornecedor.php">
                <input type="hidden" id="f-id" name="id">
                <div class="grid-form">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" id="f-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Contato</label>
                        <input type="text" id="f-contato" name="contato">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Produto</label>
                        <input type="text" id="f-tipo" name="tipo_produto">
                    </div>
                    <div class="form-group">
                        <label>Prazo de Entrega (dias)</label>
                        <input type="number" id="f-prazo" name="prazo_entrega" placeholder="Ex: 3">
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
    
    <script>
        const modal = document.getElementById('modalFornecedor');
        const form = document.getElementById('formFornecedor');

        function abrirModalFornecedor() {
            document.getElementById('modal-title').innerText = "Cadastrar Fornecedor";
            form.reset(); 
            document.getElementById('f-id').value = "";
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function fecharModalFornecedor() {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }

        function editarFornecedor(id, nome, contato, tipo, prazo) {
            document.getElementById('modal-title').innerText = "Editar Fornecedor";
            document.getElementById('f-id').value = id;
            document.getElementById('f-nome').value = nome;
            document.getElementById('f-contato').value = contato;
            document.getElementById('f-tipo').value = tipo;
            document.getElementById('f-prazo').value = prazo;
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function excluirFornecedor(id) {
            if(confirm("Tem certeza que deseja excluir este fornecedor?")) {
                window.location.href = `deletar_fornecedor.php?id=${id}`;
            }
        }

        function salvarFornecedor(event) {
        }
    </script>
</body>
</html>