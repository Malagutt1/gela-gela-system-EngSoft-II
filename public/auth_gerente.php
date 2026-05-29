<?php
// Verifica se está logado
if (!isset($_SESSION['nome'])) {
    header('Location: login.php');
    exit;
}

// Verifica se é gerente
if (strtolower($_SESSION['tipo']) !== 'gerente') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Restrito</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                margin: 0; padding: 0; height: 100vh;
                display: flex; justify-content: center; align-items: center;
                background-color: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .block-card {
                background: #ffffff;
                padding: 40px 30px;
                border-radius: 16px;
                box-shadow: 0 15px 35px rgba(17, 65, 123, 0.1);
                text-align: center;
                max-width: 420px;
                width: 90%;
                border-top: 6px solid #e74c3c;
                animation: popIn 0.4s ease-out forwards;
            }
            @keyframes popIn {
                0% { transform: scale(0.9); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
            .block-icon { font-size: 60px; color: #e74c3c; margin-bottom: 20px; }
            .block-card h1 { color: #11417b; font-size: 26px; margin-bottom: 10px; margin-top: 0; }
            .block-card p { color: #666666; font-size: 15px; line-height: 1.5; margin-bottom: 30px; }
            .btn-voltar {
                display: inline-flex; align-items: center; justify-content: center;
                gap: 10px; padding: 12px 25px;
                background-color: #11417b; color: white;
                text-decoration: none; border-radius: 8px;
                font-weight: 600; transition: all 0.3s ease;
            }
            .btn-voltar:hover { background-color: #b76756; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="block-card">
            <div class="block-icon"><i class="fas fa-user-shield"></i></div>
            <h1>Acesso Restrito</h1>
            <p>Esta área é exclusiva para <strong>Gerentes</strong>.<br>
            O seu nível de acesso não permite visualizar este conteúdo.</p>
            <a href="vendas" class="btn-voltar">
                <i class="fas fa-house-user"></i> Ir para o Início
            </a>
        </div>
    </body>
    </html>
    <?php
    exit; // Impede que o resto da página carregue
}
?>