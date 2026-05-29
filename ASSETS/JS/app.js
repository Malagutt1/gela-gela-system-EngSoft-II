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

let itensSelecionados = [];

// ==================== FUNÇÕES UTILITÁRIAS ====================

function toggleElementDisplay(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    const isVisible = window.getComputedStyle(element).display !== "none";
    element.style.display = isVisible ? "none" : "block";
}

function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('show');
}

function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('show');
}

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
}

function excluirLinha(btn, mensagemConfirmacao = "Deseja excluir este item?") {
    if (confirm(mensagemConfirmacao)) {
        const linha = btn.closest("tr");
        if (linha) linha.remove();
        alert("Item removido!");
    }
}

function adicionarLinha(tabelaId, conteudoHTML) {
    const tabela = document.getElementById(tabelaId);
    if (tabela) tabela.innerHTML += conteudoHTML;
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
    location.reload();
}

// ==================== FUNÇÕES DO MODAL DE PRODUTOS ====================

function abrirModalProduto() {
    const modal = document.getElementById('modalProduto');
    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");
    const campoPreco = document.getElementById('p-venda');

    if (modal) {
        modal.classList.add('show');
        document.getElementById('modalTitle').innerText = 'Cadastrar Novo Produto';

        if (campoPreco) {
            if (!isGerente) {
                campoPreco.value = PRECO_QUILO;
                campoPreco.disabled = true;
                campoPreco.style.opacity = '0.5';
                campoPreco.title = 'Apenas gerentes podem alterar preços';
            } else {
                campoPreco.disabled = false;
                campoPreco.style.opacity = '1';
            }
        }
    }
}

function fecharModalProduto() {
    fecharModal('modalProduto');
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

    const params = new URLSearchParams(window.location.search);
    const isGerente = params.has("gerente");

    let botoesAcao = `
        <button class="btn-icon btn-editar" onclick="editarProduto(this)">
            <i class="fa-solid fa-pen"></i>
        </button>
    `;

    if (isGerente) {
        botoesAcao += `
            <button class="btn-icon danger btn-deletar" onclick="excluirProduto(this)">
                <i class="fa-solid fa-trash"></i>
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
            <td>${botoesAcao}</td>
        </tr>
    `;

    adicionarLinha('tabela-produtos', novaLinha);
    alert("Produto salvo com sucesso!");
    fecharModalProduto();
}

function excluirProduto(btn) {
    excluirLinha(btn, "Tem certeza que deseja excluir este produto?");
}

function editarProduto(btn) {
    const linha = btn.closest('tr');
    const nome = linha.cells[0].innerText;
    const categoria = linha.cells[1].innerText;
    const preco = linha.cells[2].innerText;
    const estoque = linha.cells[3].innerText;
    const validade = linha.cells[4].innerText;

    document.getElementById('p-nome').value = nome;
    document.getElementById('p-categoria').value = categoria.toLowerCase();
    document.getElementById('p-venda').value = preco.replace('R$ ', '').replace('/kg', '').replace('/un', '');
    document.getElementById('p-qtd').value = estoque.replace(' kg', '').replace(' un', '');
    document.getElementById('p-validade').value = validade;

    abrirModalProduto();
}

// ==================== CRUD - CLIENTES ====================

function abrirModalCliente() {
    toggleModal('modalCliente');
}

function salvarCliente(e) {
    e.preventDefault();

    const nome = document.getElementById('c-nome').value;
    const tel = document.getElementById('c-telefone').value;

    const novaLinha = `
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
                <button class="btn-icon danger" onclick="excluirLinha(this, 'Deseja excluir este cliente?')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    adicionarLinha('tabela-clientes', novaLinha);
    alert("Cliente cadastrado com sucesso!");
    toggleModal('modalCliente');
    e.target.reset();
}

// ==================== FEEDBACK ====================

function abrirFeedback() {
    toggleModal('modalFeedback');
}

function salvarFeedback() {
    alert("Feedback registrado com sucesso! (Simulação)");
    toggleModal('modalFeedback');
}

// ==================== CRUD - FORNECEDORES ====================

function abrirModalFornecedor() {
    toggleModal('modalFornecedor');
}

function salvarFornecedor(e) {
    e.preventDefault();

    const nome = document.getElementById('f-nome').value;
    const contato = document.getElementById('f-contato').value;
    const tipo = document.getElementById('f-tipo').value;
    const prazo = document.getElementById('f-prazo').value;

    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td>${contato}</td>
            <td><span class="tag-resumo">${tipo}</span></td>
            <td>${prazo}</td>
            <td>
                <button class="btn-icon">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirLinha(this, 'Deseja excluir este fornecedor?')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    adicionarLinha('tabela-fornecedores', novaLinha);
    alert("Fornecedor cadastrado com sucesso!");
    toggleModal('modalFornecedor');
    e.target.reset();
}

// ==================== CRUD - PROMOÇÕES ====================

function abrirModalPromo() {
    toggleModal('modalPromo');
}

function salvarPromo(e) {
    e.preventDefault();

    const nome = document.getElementById('promo-nome').value;
    const desc = document.getElementById('promo-desc').value;
    const produto = document.getElementById('promo-produto').value;
    const validade = document.getElementById('promo-validade').value;

    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td>${desc}%</td>
            <td>${produto}</td>
            <td>${validade}</td>
            <td>
                <button class="btn-icon">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon danger" onclick="excluirLinha(this, 'Deseja excluir esta promoção?')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    adicionarLinha('tabela-promocoes', novaLinha);
    alert("Promoção cadastrada com sucesso!");
    toggleModal('modalPromo');
    e.target.reset();
}

// ==================== CRUD - USUÁRIOS ====================

function abrirModalUsuario() {
    document.getElementById('modalUsuario').classList.add('show');
}

function fecharModalUsuario() {
    document.getElementById('modalUsuario').classList.remove('show');
}

function salvarUsuario(e) {
    e.preventDefault();

    const nome = document.getElementById('u-nome').value;
    const login = document.getElementById('u-login').value;
    const tipo = document.getElementById('u-tipo').value;

    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td>${login}</td>
            <td><span class="badge ${tipo === 'gerente' ? 'danger' : 'ok'}">${tipo}</span></td>
            <td>
                <button class="btn-icon"><i class="fa fa-pen"></i></button>
                <button class="btn-icon danger" onclick="excluirLinha(this, 'Deseja excluir este usuário?')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    adicionarLinha('tabela-usuarios', novaLinha);
    alert("Usuário criado!");
    e.target.reset();
    fecharModalUsuario();
}

// ==================== BACKUP ====================

function fazerBackup() {
    const agora = new Date();
    const dataFormatada = agora.toLocaleString();
    const sucesso = Math.random() > 0.2;

    const novaLinha = `
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

    adicionarLinha('tabela-backup', novaLinha);
    document.getElementById("ultimo-backup").innerText = dataFormatada;
    alert(sucesso ? "Backup realizado!" : "Erro ao gerar backup!");
}

function baixarBackup() {
    alert("Download iniciado (simulação)");
}

// ==================== LOGS ====================

function filtrarLogs() {
    const usuario = document.querySelector('select').value;
    const acao = document.querySelectorAll('select')[1].value;
    const tabela = document.querySelector('table tbody');

    if (!tabela) {
        alert("Erro: Tabela não encontrada!");
        return;
    }

    let dados = '';

    const logsPorAcao = {
        'Todos': [
            { data: '31/03/2026 10:12', usuario: 'admin-TDS', acao: 'Cadastro', descricao: 'Produto criado' },
            { data: '31/03/2026 11:30', usuario: 'Victor', acao: 'Venda', descricao: 'Venda realizada' },
            { data: '31/03/2026 12:05', usuario: 'admin-TDS', acao: 'Exclusão', descricao: 'Produto removido' }
        ],
        'Cadastro': [
            { data: '31/03/2026 10:12', usuario: 'admin-TDS', acao: 'Cadastro', descricao: 'Produto criado' }
        ],
        'Exclusão': [
            { data: '31/03/2026 12:05', usuario: 'admin-TDS', acao: 'Exclusão', descricao: 'Produto removido' }
        ],
        'Venda': [
            { data: '31/03/2026 11:30', usuario: 'Victor', acao: 'Venda', descricao: 'Venda realizada' }
        ]
    };

    const logsPorUsuario = {
        'Todos': logsPorAcao['Todos'],
        'admin-TDS': [
            { data: '31/03/2026 10:12', usuario: 'admin-TDS', acao: 'Cadastro', descricao: 'Produto criado' },
            { data: '31/03/2026 12:05', usuario: 'admin-TDS', acao: 'Exclusão', descricao: 'Produto removido' }
        ],
        'Victor': [
            { data: '31/03/2026 11:30', usuario: 'Victor', acao: 'Venda', descricao: 'Venda realizada' }
        ]
    };

    let logsBase = usuario !== 'Todos' ? logsPorUsuario[usuario] : logsPorAcao['Todos'];
    
    if (acao !== 'Todos') {
        logsBase = logsBase.filter(log => log.acao === acao);
    }

    dados = logsBase.map(log => `
        <tr>
            <td>${log.data}</td>
            <td>${log.usuario}</td>
            <td><span class="badge ${log.acao === 'Cadastro' ? 'ok' : log.acao === 'Exclusão' ? 'danger' : 'warn'}">${log.acao}</span></td>
            <td>${log.descricao}</td>
        </tr>
    `).join('');

    tabela.innerHTML = dados;
    alert("Logs filtrados com sucesso!");
}

function adicionarLog(usuario, acao, descricao) {
    const tabela = document.getElementById("tabela-logs");
    if (!tabela) return;

    const data = new Date().toLocaleString();
    const novaLinha = `
        <tr>
            <td>${data}</td>
            <td>${usuario}</td>
            <td><span class="badge ok">${acao}</span></td>
            <td>${descricao}</td>
        </tr>
    `;

    adicionarLinha('tabela-logs', novaLinha);
}

// ==================== RELATÓRIO ====================

function filtrarRelatorio() {
    const periodo = document.getElementById('filtro-data').value;
    const tipo = document.getElementById('filtro-tipo').value;
    const tabela = document.querySelector('table tbody');

    if (!tabela) {
        alert("Erro: Tabela não encontrada!");
        return;
    }

    const relatorios = {
        'vendas': [
            { periodo, titulo: 'Total de Vendas', categoria: 'Vendas', valor: 'R$ 12.500,00' },
            { periodo, titulo: 'Clientes Atendidos', categoria: 'Vendas', valor: '320' }
        ],
        'estoque': [
            { periodo, titulo: 'Consumo de Sorvete', categoria: 'Estoque', valor: '120 kg' },
            { periodo, titulo: 'Reposição Necessária', categoria: 'Estoque', valor: '80 kg' }
        ],
        'financeiro': [
            { periodo, titulo: 'Faturamento', categoria: 'Financeiro', valor: 'R$ 25.000,00' },
            { periodo, titulo: 'Lucro Líquido', categoria: 'Financeiro', valor: 'R$ 8.200,00' }
        ]
    };

    const dados = (relatorios[tipo] || [])
        .map(item => `
            <tr>
                <td>${item.periodo}</td>
                <td>${item.titulo}</td>
                <td>${item.categoria}</td>
                <td>${item.valor}</td>
            </tr>
        `).join('');

    tabela.innerHTML = dados;
    alert("Relatório filtrado com sucesso!");
}

// ==================== SEGURANÇA - VERIFICAÇÃO DE NÍVEL ====================



// ==================== INICIALIZAÇÃO ====================

document.addEventListener('DOMContentLoaded', () => {
    // Popular grids
    const saboresGrid = document.getElementById('sabores-grid');
    if (saboresGrid) {
        sabores.forEach(sabor => {
            saboresGrid.innerHTML += `<button class="item-badge" onclick="toggleItem(this, '${sabor}')">${sabor}</button>`;
        });
    }

    const toppingsGrid = document.getElementById('toppings-grid');
    if (toppingsGrid) {
        toppings.forEach(topping => {
            toppingsGrid.innerHTML += `<button class="item-badge topping" onclick="toggleItem(this, '${topping}')">${topping}</button>`;
        });
    }

    // Event listeners
    const pesoInput = document.getElementById('peso-venda');
    if (pesoInput) {
        pesoInput.addEventListener('input', calcularTotal);
    }

    // Fechar modal ao clicar fora
    const modal = document.getElementById('modalProduto');
    if (modal) {
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                fecharModalProduto();
            }
        });
    }

    // Fechar modais ao clicar fora
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    // Verificar nível de acesso
    verificarNivel();
});
