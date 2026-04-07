const PRECO_QUILO = 70.00;

const sabores = [
    "Chocolate", "Chocolate Branco", "Pistache", "Brownie", "Red Velvet",
    "Brigadeiro", "Creme", "Céu-Azul", "Caramelo", "Abacaxi", "Coco",
    "Doce de Leite", "Floresta Negra", "Ninho", "Uva",
    "Açaí com Leite Ninho", "Açaí Tradicional"
];

const toppings = [
    "Chocolate Derretido", "Chocolate Branco Derretido", "Balas de Gelatina",
    "Brigadeiro", "Beijinho", "Coco Ralado", "Gotas de Chocolate",
    "Chocoballs", "Caldas Diversas", "Granulado", "Marshmallow",
    "Morango", "Cremes Diversos"
];

// ==================== VARIÁVEIS GLOBAIS ====================
let itensSelecionados = [];

// ==================== FUNÇÕES DE SIDEBAR ====================

function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    if (!sidebar) return;
    const isVisible = window.getComputedStyle(sidebar).display !== "none";     // Pega o estilo computado (CSS real) em vez de inline
    sidebar.style.display = isVisible ? "none" : "block";
}

// ==================== FUNÇÕES DE SELEÇÃO DE ITENS ====================

function toggleItem(element, nome) {
    element.classList.toggle('active');

    if (itensSelecionados.includes(nome)) {
        itensSelecionados = itensSelecionados.filter(i => i !== nome);
    } else {
        itensSelecionados.push(nome);
    }

    atualizarResumo();
}

function atualizarResumo() {
    const container = document.getElementById('resumo-itens');
    if (!container) return;

    if (itensSelecionados.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted); font-size: 14px;">Nenhum item selecionado</p>';
    } else {
        container.innerHTML = itensSelecionados
            .map(i => `<span class="tag-resumo">${i}</span>`)
            .join('');
    }
}

// ==================== FUNÇÕES DE VENDA ====================

function calcularTotal() {
    const pesoInput = document.getElementById('peso-venda');
    const valorTotalEl = document.getElementById('valor-total');

    if (!pesoInput || !valorTotalEl) return;

    const peso = parseFloat(pesoInput.value) || 0;
    const total = peso * PRECO_QUILO;

    valorTotalEl.innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
}

function finalizarVenda() {
    const pesoInput = document.getElementById('peso-venda');
    if (!pesoInput) return;

    const peso = parseFloat(pesoInput.value);

    if (!peso || peso <= 0) {
        alert("Por favor, insira o peso do produto!");
        return;
    }

    alert("Venda realizada com sucesso! Comprovante enviado para a impressora.");
    location.reload(); // Simula reset da venda
}

// ==================== FUNÇÕES DO MODAL ====================

function abrirModal() {
    const modal = document.getElementById('modalProduto');
    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");
    const campoPreco = document.getElementById('p-venda');

    if (modal) {
        modal.classList.add('show');
        document.getElementById('modalTitle').innerText = 'Cadastrar Novo Produto';

        // Se NÃO é gerente, seta preço fixo e desabilita
        if (!isGerente && campoPreco) {
            campoPreco.value = PRECO_QUILO;
            campoPreco.disabled = true;
            campoPreco.style.opacity = '0.5';
            campoPreco.title = 'Apenas gerentes podem alterar preços';
        } else if (campoPreco) {
            campoPreco.disabled = false;
            campoPreco.style.opacity = '1';
        }
    }
}

function fecharModal() {
    const modal = document.getElementById('modalProduto');
    if (modal) {
        modal.classList.remove('show');
    }

    // Resetar formulário
    const form = document.getElementById('formProduto');
    if (form) form.reset();
}

// ==================== CRUD - PRODUTOS ====================

function salvarProduto(event) {
    event.preventDefault();

    const nome = document.getElementById('p-nome').value.trim();
    const cat = document.getElementById('p-categoria').value;
    const venda = document.getElementById('p-venda').value;
    const qtd = document.getElementById('p-qtd').value;
    const un = document.getElementById('p-un').value;
    const validade = document.getElementById('p-validade').value;

    if (!nome) {
        alert("O nome do produto é obrigatório!");
        return;
    }

    const tabela = document.getElementById('tabela-produtos');
    if (!tabela) return;

    // Verificar se é gerente
    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");

    // Botões que aparecem dependendo do nível
    let botoesAcao = '';
    if (isGerente) {
        // Gerente vê editar E deletar
        botoesAcao = `
            <button class="btn-icon btn-editar" onclick="editarProduto(this)">
                <i class="fa-solid fa-pen"></i>
            </button>
            <button class="btn-icon danger btn-deletar" onclick="excluirProduto(this)">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
    } else {
        // Funcionário vê apenas editar
        botoesAcao = `
            <button class="btn-icon btn-editar" onclick="editarProduto(this)">
                <i class="fa-solid fa-pen"></i>
            </button>
        `;
    }

    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td><span class="tag-resumo">${cat}</span></td>
            <td>R$ ${venda}/${un}</td>
            <td><strong class="badge ok">${qtd} ${un}</strong></td>
            <td>${validade}</td>
            <td>
                ${botoesAcao}
            </td>
        </tr>
    `;

    tabela.innerHTML += novaLinha;
    alert("Produto salvo com sucesso!");
    fecharModal();
}

function excluirProduto(btn) {
    if (confirm("Tem certeza que deseja excluir este produto?")) {
        const linha = btn.closest('tr');
        if (linha) linha.remove();
        alert("Produto removido!");
    }
}

function editarProduto(btn) {
    const linha = btn.closest('tr');
    const nome = linha.cells[0].innerText;
    const categoria = linha.cells[1].innerText;
    const preco = linha.cells[2].innerText;
    const estoque = linha.cells[3].innerText;
    const validade = linha.cells[4].innerText;

    // Preencher modal com dados atuais
    document.getElementById('p-nome').value = nome;
    document.getElementById('p-categoria').value = categoria.toLowerCase();
    document.getElementById('p-venda').value = preco.replace('R$ ', '').replace('/kg', '').replace('/un', '');
    document.getElementById('p-qtd').value = estoque.replace(' kg', '').replace(' un', '');
    document.getElementById('p-validade').value = validade;

    // Abrir modal para editar
    abrirModal();
}

// ==================== INICIALIZAÇÃO ====================

document.addEventListener('DOMContentLoaded', () => {
    // Popular grid de sabores
    const saboresGrid = document.getElementById('sabores-grid');
    if (saboresGrid) {
        sabores.forEach(sabor => {
            saboresGrid.innerHTML += `
                <button class="item-badge" onclick="toggleItem(this, '${sabor}')">
                    ${sabor}
                </button>`;
        });
    }

    // Popular grid de toppings
    const toppingsGrid = document.getElementById('toppings-grid');
    if (toppingsGrid) {
        toppings.forEach(topping => {
            toppingsGrid.innerHTML += `
                <button class="item-badge topping" onclick="toggleItem(this, '${topping}')">
                    ${topping}
                </button>`;
        });
    }

    // Event listener para calcular total ao digitar peso
    const pesoInput = document.getElementById('peso-venda');
    if (pesoInput) {
        pesoInput.addEventListener('input', calcularTotal);
    }

    // Fechar modal ao clicar fora
    const modal = document.getElementById('modalProduto');
    if (modal) {
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                fecharModal();
            }
        });
    }
});


function abrirModalCliente() {
    document.getElementById('modalCliente').style.display = 'flex';
}

function fecharModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
}

function salvarCliente(e) {
    e.preventDefault();

    const nome = document.getElementById('c-nome').value;
    const tel = document.getElementById('c-telefone').value;

    const tabela = document.getElementById('tabela-clientes');

    tabela.innerHTML += `
        <tr>
            <td>${nome}</td>
            <td>${tel}</td>
            <td>Hoje</td>
            <td>
                <button class="btn-icon" onclick="abrirFeedback()">
                    <i class="fa-solid fa-comment"></i>
                </button>
                <button class="btn-icon">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirLinha(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    alert("Cliente cadastrado com sucesso!");
    fecharModalCliente();
}

function excluirLinha(btn) {
    if (confirm("Deseja excluir este cliente?")) {
        btn.closest("tr").remove();
        alert("Cliente removido!");
    }
}

/* FEEDBACK */
function abrirFeedback() {
    document.getElementById('modalFeedback').style.display = 'flex';
}

function fecharFeedback() {
    document.getElementById('modalFeedback').style.display = 'none';
}

function salvarFeedback() {
    alert("Feedback registrado com sucesso! (Simulação)");
    fecharFeedback();
}
// ==================== FORNECEDORES ====================

// abrir modal
function abrirModalFornecedor() {
    const modal = document.getElementById('modalFornecedor');
    if (modal) modal.style.display = 'flex';
}

// fechar modal
function fecharModalFornecedor() {
    const modal = document.getElementById('modalFornecedor');
    if (modal) modal.style.display = 'none';
}

// salvar fornecedor
function salvarFornecedor(e) {
    e.preventDefault();

    const nome = document.getElementById('f-nome').value;
    const contato = document.getElementById('f-contato').value;
    const tipo = document.getElementById('f-tipo').value;
    const prazo = document.getElementById('f-prazo').value;

    const tabela = document.getElementById('tabela-fornecedores');
    if (!tabela) return;

    tabela.innerHTML += `
        <tr>
            <td>${nome}</td>
            <td>${contato}</td>
            <td><span class="tag-resumo">${tipo}</span></td>
            <td>${prazo}</td>
            <td>
                <button class="btn-icon">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirFornecedor(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    alert("Fornecedor cadastrado com sucesso!");
    fecharModalFornecedor();
}

// excluir fornecedor
function excluirFornecedor(btn) {
    if (confirm("Deseja excluir este fornecedor?")) {
        const linha = btn.closest("tr");
        if (linha) linha.remove();
        alert("Fornecedor removido!");
    }
}

// ==================== PROMOÇÕES ====================

function abrirModalPromo() {
    document.getElementById('modalPromo').style.display = 'flex';
}

function fecharModalPromo() {
    document.getElementById('modalPromo').style.display = 'none';
}

function salvarPromo(e) {
    e.preventDefault();

    const nome = document.getElementById('promo-nome').value;
    const desc = document.getElementById('promo-desc').value;
    const produto = document.getElementById('promo-produto').value;
    const validade = document.getElementById('promo-validade').value;

    const tabela = document.getElementById('tabela-promocoes');

    tabela.innerHTML += `
        <tr>
            <td>${nome}</td>
            <td>${desc}%</td>
            <td>${produto}</td>
            <td>${validade}</td>
            <td>
                <button class="btn-icon">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirPromo(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    alert("Promoção cadastrada com sucesso!");
    fecharModalPromo();
}

function excluirPromo(btn) {
    if (confirm("Deseja excluir esta promoção?")) {
        btn.closest("tr").remove();
        alert("Promoção removida!");
    }
}

// ==================== USUÁRIOS ====================

function abrirModalUsuario() {
    document.getElementById('modalUsuario').classList.add('show');
}

function fecharModalUsuario() {
    document.getElementById('modalUsuario').classList.remove('show');
}

// Fechar ao clicar fora do modal
document.addEventListener('DOMContentLoaded', () => {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });
});

function salvarUsuario(e) {
    e.preventDefault();

    const nome = document.getElementById('u-nome').value;
    const login = document.getElementById('u-login').value;
    const tipo = document.getElementById('u-tipo').value;

    const tabela = document.getElementById('tabela-usuarios');

    tabela.innerHTML += `
        <tr>
            <td>${nome}</td>
            <td>${login}</td>
            <td><span class="badge ${tipo === 'gerente' ? 'danger' : 'ok'}">${tipo}</span></td>
            <td>
                <button class="btn-icon"><i class="fa fa-pen"></i></button>
                <button class="btn-icon danger" onclick="excluirUsuario(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    alert("Usuário criado!");

    // Resetar formulário
    e.target.reset();

    // Fechar modal
    fecharModalUsuario();
}

function excluirUsuario(btn) {
    btn.closest("tr").remove();
    alert("Usuário removido!");
}

function verificarNivel() {
    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");

    const pathname = window.location.pathname.toUpperCase();

    console.log("Página atual detetada:", pathname);
    console.log("É gerente?", isGerente);

    if (!isGerente) {
        // 1. ESCONDER BOTÕES (Exceto Venda, Produto e Editar)
        document.querySelectorAll(".btn:not(.btn-venda):not(.btn-produto), .btn-icon:not(.btn-editar)").forEach(btn => {
            btn.style.display = "none";
        });

        // 2. LISTA DE BLOQUEIO
        const paginasProibidas = [
            "BACKUP",
            "DASHBOARD",
            "LOGS",
            "PROMO",
            "RELATORIO",
            "USUARIO",
            "FORNECEDOR"
        ];

        const deveBloquear = paginasProibidas.some(p => pathname.includes(p));

        if (deveBloquear) {
            document.body.innerHTML = `
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');
            
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

            .block-icon {
                font-size: 60px;
                color: #e74c3c;
                margin-bottom: 20px;
            }

            .block-card h1 {
                color: #11417b;
                font-size: 26px;
                margin-bottom: 10px;
                margin-top: 0;
            }

            .block-card p {
                color: #666666;
                font-size: 15px;
                line-height: 1.5;
                margin-bottom: 30px;
            }

            .btn-voltar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                padding: 12px 25px;
                background-color: #11417b;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .btn-voltar:hover {
                background-color: #b76756;
                transform: translateY(-2px);
            }
        </style>

        <div class="block-card">
            <div class="block-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Acesso Restrito</h1>
            <p>Esta área é exclusiva para <strong>Gerentes</strong>.<br>O seu nível de acesso não permite visualizar este conteúdo.</p>
            <a href="../" class="btn-voltar">
                <i class="fas fa-house-user"></i> Ir para o Início
            </a>
        </div>
    `;
        }

    }
    // Se for gerente, mostra tudo normalmente
}

// Executa assim que o HTML carregar
document.addEventListener("DOMContentLoaded", verificarNivel);

document.addEventListener("DOMContentLoaded", verificarNivel);

// ==================== BACKUP ====================

function fazerBackup() {
    const tabela = document.getElementById("tabela-backup");
    const agora = new Date();

    const dataFormatada = agora.toLocaleString();

    const sucesso = Math.random() > 0.2; // 80% sucesso

    tabela.innerHTML += `
        <tr>
            <td>${dataFormatada}</td>
            <td><span class="tag-resumo">Manual</span></td>
            <td>
                <span class="badge ${sucesso ? 'ok' : 'danger'}">
                    ${sucesso ? 'Sucesso' : 'Falha'}
                </span>
            </td>
            <td>
                <button class="btn-icon" onclick="baixarBackup()">
                    <i class="fa fa-download"></i>
                </button>
            </td>
        </tr>
    `;

    document.getElementById("ultimo-backup").innerText = dataFormatada;

    alert(sucesso ? "Backup realizado!" : "Erro ao gerar backup!");
}

function baixarBackup() {
    alert("Download iniciado (simulação)");
}

// ==================== LOGS ====================

function filtrarLogs() {
    alert("Filtro aplicado (simulação)");
}

// Função opcional (nível PRO)
function adicionarLog(usuario, acao, descricao) {
    const tabela = document.getElementById("tabela-logs");
    if (!tabela) return;

    const data = new Date().toLocaleString();

    tabela.innerHTML += `
        <tr>
            <td>${data}</td>
            <td>${usuario}</td>
            <td><span class="badge ok">${acao}</span></td>
            <td>${descricao}</td>
        </tr>
    `;
}

/* ===== RELATORIO ==== */
function filtrarRelatorio() {
    const periodo = document.getElementById('filtro-data').value;
    const tipo = document.getElementById('filtro-tipo').value;
    const tabela = document.querySelector('table tbody');

    if (!tabela) {
        alert("Erro: Tabela não encontrada!");
        return;
    }

    let dados = '';

    // SIMULAÇÃO DE FILTRO
    if (tipo === 'vendas') {
        dados = `
            <tr>
                <td>${periodo}</td>
                <td>Total de Vendas</td>
                <td>Vendas</td>
                <td>R$ 12.500,00</td>
            </tr>
            <tr>
                <td>${periodo}</td>
                <td>Clientes Atendidos</td>
                <td>Vendas</td>
                <td>320</td>
            </tr>
        `;
    } else if (tipo === 'estoque') {
        dados = `
            <tr>
                <td>${periodo}</td>
                <td>Consumo de Sorvete</td>
                <td>Estoque</td>
                <td>120 kg</td>
            </tr>
            <tr>
                <td>${periodo}</td>
                <td>Reposição Necessária</td>
                <td>Estoque</td>
                <td>80 kg</td>
            </tr>
        `;
    } else if (tipo === 'financeiro') {
        dados = `
            <tr>
                <td>${periodo}</td>
                <td>Faturamento</td>
                <td>Financeiro</td>
                <td>R$ 25.000,00</td>
            </tr>
            <tr>
                <td>${periodo}</td>
                <td>Lucro Líquido</td>
                <td>Financeiro</td>
                <td>R$ 8.200,00</td>
            </tr>
        `;
    }

    tabela.innerHTML = dados;
    alert("Relatório filtrado com sucesso!");
}

/* ===== LOGS ==== */
function filtrarLogs() {
    const usuario = document.querySelector('select').value; // Primeiro select
    const acao = document.querySelectorAll('select')[1].value; // Segundo select
    const tabela = document.querySelector('table tbody');

    if (!tabela) {
        alert("Erro: Tabela não encontrada!");
        return;
    }

    let dados = '';

    // SIMULAÇÃO DE FILTRO
    if (usuario === 'Todos' && acao === 'Todos') {
        dados = `
            <tr>
                <td>31/03/2026 10:12</td>
                <td>admin</td>
                <td><span class="badge ok">Cadastro</span></td>
                <td>Produto criado</td>
            </tr>
            <tr>
                <td>31/03/2026 11:30</td>
                <td>joao</td>
                <td><span class="badge warn">Venda</span></td>
                <td>Venda realizada</td>
            </tr>
            <tr>
                <td>31/03/2026 12:05</td>
                <td>admin</td>
                <td><span class="badge danger">Exclusão</span></td>
                <td>Produto removido</td>
            </tr>
        `;
    } else if (usuario === 'admin') {
        dados = `
            <tr>
                <td>31/03/2026 10:12</td>
                <td>admin</td>
                <td><span class="badge ok">Cadastro</span></td>
                <td>Produto criado</td>
            </tr>
            <tr>
                <td>31/03/2026 12:05</td>
                <td>admin</td>
                <td><span class="badge danger">Exclusão</span></td>
                <td>Produto removido</td>
            </tr>
        `;
    } else if (usuario === 'joao') {
        dados = `
            <tr>
                <td>31/03/2026 11:30</td>
                <td>joao</td>
                <td><span class="badge warn">Venda</span></td>
                <td>Venda realizada</td>
            </tr>
        `;
    }

    if (acao === 'Cadastro') {
        dados = `
            <tr>
                <td>31/03/2026 10:12</td>
                <td>admin</td>
                <td><span class="badge ok">Cadastro</span></td>
                <td>Produto criado</td>
            </tr>
        `;
    } else if (acao === 'Exclusão') {
        dados = `
            <tr>
                <td>31/03/2026 12:05</td>
                <td>admin</td>
                <td><span class="badge danger">Exclusão</span></td>
                <td>Produto removido</td>
            </tr>
        `;
    } else if (acao === 'Venda') {
        dados = `
            <tr>
                <td>31/03/2026 11:30</td>
                <td>joao</td>
                <td><span class="badge warn">Venda</span></td>
                <td>Venda realizada</td>
            </tr>
        `;
    }

    tabela.innerHTML = dados;
    alert("Logs filtrados com sucesso!");
}