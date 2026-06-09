<?php

$tipo_usuario = $_SESSION['tipo'] ?? 'Funcionario';

$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

// ======================================================
// MENU
// ======================================================

$menus = [

    [
        'titulo' => 'Nova Venda',
        'icone'  => 'fa-cart-shopping',
        'link'   => 'vendas',
        'permissoes' => ['Gerente', 'Funcionario']
    ],

    [
        'titulo' => 'Dashboard',
        'icone'  => 'fa-chart-line',
        'link'   => 'dashboard',
        'permissoes' => ['Gerente']
    ],

    [
        'titulo' => 'Produtos',
        'icone'  => 'fa-boxes-stacked',
        'link'   => 'produtos',
        'permissoes' => ['Gerente', 'Funcionario']
    ],

    [
        'titulo' => 'Clientes',
        'icone'  => 'fa-users',
        'link'   => 'clientes',
        'permissoes' => ['Gerente', 'Funcionario']
    ],

    [
        'titulo' => 'Fornecedores',
        'icone'  => 'fa-truck',
        'link'   => 'fornecedores',
        'permissoes' => ['Gerente']
    ],

    [
        'titulo' => 'Promoções',
        'icone'  => 'fa-tags',
        'link'   => 'promo',
        'permissoes' => ['Gerente', 'Funcionario']
    ],

    [
        'titulo' => 'Usuários',
        'icone'  => 'fa-user-shield',
        'link'   => 'user',
        'permissoes' => ['Gerente']
    ],

    [
        'titulo' => 'Backup',
        'icone'  => 'fa-database',
        'link'   => 'backup',
        'permissoes' => ['Gerente']
    ],

    [
        'titulo' => 'Logs',
        'icone'  => 'fa-file-lines',
        'link'   => 'logs',
        'permissoes' => ['Gerente']
    ],

    [
        'titulo' => 'Relatórios',
        'icone'  => 'fa-chart-pie',
        'link'   => 'relatorio',
        'permissoes' => ['Gerente', 'Funcionario']
    ]

];

?>

<aside class="sidebar" id="sidebar">

    <div class="logo-area">

        <img src="ASSETS/IMG/icon.png" alt="Logo">

        <span>Gela-Gela</span>

    </div>

    <nav>

        <?php foreach ($menus as $menu): ?>

            <?php if (in_array($tipo_usuario, $menu['permissoes'])): ?>

                <a href="<?= $menu['link'] ?>"
                    class="<?= $paginaAtual === $menu['link'] ? 'active' : '' ?>">

                    <i class="fa-solid <?= $menu['icone'] ?>"></i>

                    <?= $menu['titulo'] ?>

                </a>

            <?php endif; ?>

        <?php endforeach; ?>

    </nav>

</aside>