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
    <title>Gela-Gela | Usuários</title>
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
                <h1>Controle de Usuários</h1>
            </header>

            <section class="main">

                <div style="display:flex; justify-content:space-between;">
                    <h2>Usuários do Sistema</h2>
                    <button class="btn" onclick="abrirModalUsuario()">Novo Usuário</button>
                </div>

                <div class="box">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Login</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody id="tabela-usuarios">
                            <tr>
                                <td>Admin-TDS</td>
                                <td>admin-TDS</td>
                                <td><span class="badge danger">Gerentes</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Kauã Malagutti</td>
                                <td>kaua</td>
                                <td><span class="badge danger">Gerentes</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Arthur Moro </td>
                                <td>arthur</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Arthur Moro </td>
                                <td>arthur</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Francisco Lessa</td>
                                <td>francisco</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Luigi Pretto</td>
                                <td>luigi</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Taynan Brighenti</td>
                                <td>taynan</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Samuel Boita </td>
                                <td>samuel</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Pedro</td>
                                <td>pedro</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Rikelme</td>
                                <td>rikelme</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Victor</td>
                                <td>victor</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>Lucas</td>
                                <td>lucas</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>David</td>
                                <td>david</td>
                                <td><span class="badge ok">Funcionario</span></td>
                                <td>
                                    <button class="btn-icon"><i class="fa fa-pen"></i></button>
                                    <button class="btn-icon danger" onclick="excluirUsuario(this)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </section>
        </main>
    </div>

    <!-- MODAL -->
    <div id="modalUsuario" class="modal">
        <div class="modal-content">
            <h3>Novo Usuário</h3>

            <form onsubmit="salvarUsuario(event)">
                <input type="text" id="u-nome" placeholder="Nome completo" required>
                <input type="text" id="u-login" placeholder="Login/Usuário" required>

                <select id="u-tipo" required>
                    <option value="">Selecione o tipo de acesso</option>
                    <option value="funcionario">Funcionário</option>
                    <option value="gerente">Gerente</option>
                </select>

                <button class="btn" type="submit">
                    <i class="fa-solid fa-check"></i> Salvar Usuário
                </button>
            </form>
        </div>
    </div>

    <script src="ASSETS/JS/sidebar.js"></script>
    <script src="ASSETS/JS/app.js"></script>

</body>

</html>