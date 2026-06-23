<?php
session_start();

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header('Location: vendas');
    exit();
}
$usuarioLembrado = isset($_COOKIE['lembrar_usuario']) ? $_COOKIE['lembrar_usuario'] : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gela-Gela | Acesso ao Sistema</title>
    <link rel="stylesheet" href="ASSETS/CSS/style-login.css">
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/ff4d7d/ice-cream-bowl.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-brand">
            <i class="fa-solid fa-ice-cream brand-icon"></i>
            <h1>Gela-Gela</h1>
            <p>Sistema de Gestão e Vendas para Sorveteria e Açaiteria</p>
        </div>

        <div class="login-form-container">
            <h2>Bem-vindo de volta!</h2>
            <p>Insira suas credenciais para acessar o painel.</p>
            <?php
            // ====================== MENSAGENS DE ERRO ======================
            if (isset($_GET['erro'])) {
                if ($_GET['erro'] == '1') {
                    echo '<div class="alert error">Usuário ou senha incorretos!</div>';
                } elseif ($_GET['erro'] == '2') {
                    echo '<div class="alert error">Preencha todos os campos!</div>';
                } elseif ($_GET['erro'] == '3') {
                    echo '<div class="alert error">Sessão expirada por inatividade (15 min).</div>';
                }
            }
            ?>

            <form method="POST" action="login_processa.php">

                <div class="input-group">
                    <label for="usuario">Usuário</label>
                    <div class="input-wrapper">
                        <input type="text" id="usuario" name="usuario" placeholder="Ex: funcionario" value="<?php echo htmlspecialchars($usuarioLembrado); ?>" required <?php echo empty($usuarioLembrado) ? 'autofocus' : ''; ?>>
                        <i class="fa fa-user"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="senha">Senha de Acesso</label>
                    <div class="input-wrapper" style="position: relative; display: flex; align-items: center;">

                        <i class="fa fa-lock" style="position: absolute !important; left: 15px !important; right: auto !important; top: 50% !important; transform: translateY(-50%) !important; color: #888; pointer-events: none;"></i>

                        <input type="password" id="senha" name="senha" placeholder="••••••••" style="padding-left: 42px !important; padding-right: 45px !important; width: 100%;" required <?php echo !empty($usuarioLembrado) ? 'autofocus' : ''; ?>>

                        <i class="fa fa-eye" id="togglePassword" style="cursor: pointer; position: absolute !important; right: 15px !important; left: auto !important; top: 50% !important; transform: translateY(-50%) !important; color: #888; z-index: 10;"></i>
                    </div>
                </div>

                <div class="options">
                    <label class="remember-me">
                        <input type="checkbox" name="lembrar" <?php echo !empty($usuarioLembrado) ? 'checked' : ''; ?>>
                        <span>Lembrar meu usuário</span>
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    Entrar no Sistema <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>

            </form>

            <div class="login-footer">
                &copy; 2026 Gela-Gela. Acesso restrito a colaboradores.
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#senha');

        togglePassword.addEventListener('click', function() {
            // Alterna o tipo do input entre password e text
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Alterna o ícone (olho aberto / olho cortado)
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>