<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nome_usuario = $_SESSION['nome'] ?? 'User';
$inicial = strtoupper(substr($nome_usuario, 0, 1));

?>

<div class="user-menu">

    <div class="avatar" onclick="toggleUserMenu()">F
        <?= htmlspecialchars($inicial) ?>
    </div>

    <div class="dropdown-user" id="userDropdown">
        <p><?= htmlspecialchars($nome_usuario) ?></p>

        <a href="perfil">
            <i class="fa-solid fa-user"></i> Perfil
        </a>

        <a href="logout" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i> Sair do sistema
        </a>
    </div>

</div>