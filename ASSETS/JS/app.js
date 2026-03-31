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

    sidebar.style.display =
        sidebar.style.display === "none" || sidebar.style.display === ""
            ? "block"
            : "none";
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
    if (modal) {
        modal.style.display = 'flex'; // ou 'block', dependendo do seu CSS
        document.getElementById('modalTitle').innerText = 'Cadastrar Novo Produto';
    }
}

function fecharModal() {
    const modal = document.getElementById('modalProduto');
    if (modal) {
        modal.style.display = 'none';
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

    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td><span class="tag-resumo">${cat}</span></td>
            <td>R$ ${venda}/${un}</td>
            <td><strong class="badge ok">${qtd} ${un}</strong></td>
            <td>${validade}</td>
            <td>
                <button class="btn-icon" onclick="alert('Editar funcionalidade simulada')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirProduto(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
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


// ==================== CONTROLE DE ACESSO ====================
function verificarPermissaoFornecedor() {

    // só roda na página de fornecedores
    if (!window.location.pathname.includes("FORNECEDORES")) return;

    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");

    // ❌ não é gerente → bloqueia
    if (!isGerente) {
        document.body.innerHTML = `
            <div style="
                display:flex;
                justify-content:center;
                align-items:center;
                height:100vh;
                flex-direction:column;
                font-family:sans-serif;
                text-align:center;
                background:#f8f9fa;
            ">
                <h1 style="color:#c62828;">⛔ Acesso Negado</h1>
                <p style="margin:10px 0;">
                    Apenas usuários <strong>Gerentes</strong> podem acessar esta página.
                </p>
                <a href="../index.html" style="
                    margin-top:15px;
                    padding:10px 20px;
                    background:#11417b;
                    color:white;
                    text-decoration:none;
                    border-radius:6px;
                ">
                    Voltar ao sistema
                </a>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    verificarPermissaoFornecedor();
});

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
    document.getElementById('modalUsuario').style.display = 'flex';
}

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
                <button class="btn-icon"></button>
                <button class="btn-icon danger" onclick="excluirUsuario(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    alert("Usuário criado!");
}

function excluirUsuario(btn) {
    btn.closest("tr").remove();
    alert("Usuário removido!");
}

function verificarNivel() {
    const params = new URLSearchParams(window.location.search);

    const isGerente = params.has("gerente");

    if (!isGerente) {

        // esconder botões perigosos
        document.querySelectorAll(".btn, .btn-icon").forEach(btn => {
            btn.style.display = "none";
        });

        // bloquear páginas críticas
        const pagina = window.location.pathname;

        if (
            pagina.includes("FORNECEDORES") ||
            pagina.includes("USUARIOS") ||
            pagina.includes("BACKUP")
        ) {
            document.body.innerHTML = `
                <div style="text-align:center; padding:50px;">
                    <h1>⛔ Acesso Restrito</h1>
                    <p>Apenas gerentes podem acessar esta área</p>
                    <a href="../index.html">Voltar</a>
                </div>
            `;
        }
    }
}

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