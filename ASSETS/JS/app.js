function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.style.display =
        sidebar.style.display === "none" ? "block" : "none";
}

const sabores = ["Chocolate", "Chocolate Branco", "Pistache", "Brownie", "Red Velvet", "Brigadeiro", "Creme", "Céu-Azul", "Caramelo", "Abacaxi", "Coco", "Doce de Leite", "Floresta Negra", "Ninho", "Uva", "Açaí com Leite Ninho", "Açaí Tradicional"];
const toppings = ["Chocolate Derretido", "Chocolate Branco Derretido", "Balas de Gelatina", "Brigadeiro", "Beijinho", "Coco Ralado", "Gotas de Chocolate", "Chocoballs", "Caldas Diversas", "Granulado", "Marshmallow", "Morango", "Cremes Diversos"];
const PRECO_QUILO = 70.00;

// Inicializar a tela de venda
document.addEventListener('DOMContentLoaded', () => {
    const saboresGrid = document.getElementById('sabores-grid');
    const toppingsGrid = document.getElementById('toppings-grid');

    if(saboresGrid) {
        sabores.forEach(s => {
            saboresGrid.innerHTML += `<button class="item-badge" onclick="toggleItem(this, '${s}')">${s}</button>`;
        });
        toppings.forEach(t => {
            toppingsGrid.innerHTML += `<button class="item-badge topping" onclick="toggleItem(this, '${t}')">${t}</button>`;
        });
    }
});

let itensSelecionados = [];

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
    if(itensSelecionados.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted); font-size: 14px;">Nenhum item selecionado</p>';
    } else {
        container.innerHTML = itensSelecionados.map(i => `<span class="tag-resumo">${i}</span>`).join('');
    }
}

function calcularTotal() {
    const peso = document.getElementById('peso-venda').value;
    const total = peso * PRECO_QUILO;
    document.getElementById('valor-total').innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
}

function finalizarVenda() {
    const peso = document.getElementById('peso-venda').value;
    if(!peso || peso <= 0) {
        alert("Por favor, insira o peso do produto!");
        return;
    }
    alert("Venda realizada com sucesso! Comprovante enviado para a impressora.");
    location.reload(); // Simula o reset após venda
}


// Funções de Modal
function abrirModal() {
    document.getElementById('modalProduto').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Cadastrar Novo Produto';
}

function fecharModal() {
    document.getElementById('modalProduto').style.display = 'none';
    document.getElementById('formProduto').reset();
}

// Simulação do CRUD
function salvarProduto(event) {
    event.preventDefault();
    
    // Pegando valores
    const nome = document.getElementById('p-nome').value;
    const cat = document.getElementById('p-categoria').value;
    const venda = document.getElementById('p-venda').value;
    const qtd = document.getElementById('p-qtd').value;
    const un = document.getElementById('p-un').value;
    const validade = document.getElementById('p-validade').value;

    // Adicionando na tabela (Simulação)
    const tabela = document.getElementById('tabela-produtos');
    const novaLinha = `
        <tr>
            <td>${nome}</td>
            <td><span class="tag-resumo">${cat}</span></td>
            <td>R$ ${venda}/${un}</td>
            <td><strong class="badge ok">${qtd} ${un}</strong></td>
            <td>${validade}</td>
            <td>
                <button class="btn-icon" onclick="alert('Editar funcionalidade simulada')"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-icon danger" onclick="excluirProduto(this)"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>
    `;
    
    tabela.innerHTML += novaLinha;
    alert("Produto salvo com sucesso!");
    fecharModal();
}

function excluirProduto(btn) {
    if(confirm("Tem certeza que deseja excluir este produto?")) {
        const linha = btn.closest('tr');
        linha.remove();
        alert("Produto removido!");
    }
}



function abrirModal() {
    document.getElementById('modalProduto').style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalProduto').style.display = 'none';
}

// Fecha o modal se o usuário clicar fora da caixa branca
window.onclick = function(event) {
    const modal = document.getElementById('modalProduto');
    if (event.target == modal) {
        fecharModal();
    }
}