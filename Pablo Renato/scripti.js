// Variável global para armazenar o carrinho
let comanda = [];

// Ao carregar a página, buscar e exibir o cardápio
window.addEventListener('DOMContentLoaded', () => {
  carregarCardapio();
  mostrarComanda();
  carregarPedidos();
});

// Função para carregar cardápio da API e agrupar por categoria
async function carregarCardapio() {
  try {
    const res = await fetch('api.php?endpoint=cardapio');
    const data = await res.json();

    // Agrupar pratos por categoria
    const categorias = {};
    data.forEach(item => {
      if (!categorias[item.categoria]) categorias[item.categoria] = [];
      categorias[item.categoria].push(item);
    });

    const container = document.getElementById('cardapio');
    container.innerHTML = '';

    // Construir HTML para cada categoria e seus pratos
    for (const categoria in categorias) {
      const catDiv = document.createElement('div');
      catDiv.classList.add('categoria');

      const titulo = document.createElement('h2');
      titulo.textContent = categoria;
      catDiv.appendChild(titulo);

      categorias[categoria].forEach(prato => {
        const itemDiv = document.createElement('div');
        itemDiv.classList.add('item');

        itemDiv.innerHTML = `
          <img src="${prato.imagem}" alt="${prato.nome}">
          <div class="item-info">
            <strong>${prato.nome}</strong>
            <p>${prato.descricao}</p>
          </div>
          <div class="item-preco">R$ ${prato.preco.toFixed(2)}</div>
          <input type="number" id="qtd-${prato.prato_id}" value="1" min="1" max="20" style="width:55px; margin-right:10px;">
          <button onclick="adicionarPrato(${prato.prato_id}, '${prato.nome}', ${prato.preco.toFixed(2)})" class="btn-adicionar">Adicionar</button>
        `;

        catDiv.appendChild(itemDiv);
      });

      container.appendChild(catDiv);
    }
  } catch (error) {
    console.error('Erro ao carregar cardápio:', error);
  }
}

// Adicionar prato ao carrinho ou somar quantidade
function adicionarPrato(id, nome, preco) {
  const qtdInput = document.getElementById(`qtd-${id}`);
  const qtd = parseInt(qtdInput.value);
  if (qtd <= 0 || isNaN(qtd)) {
    alert('Quantidade inválida!');
    return;
  }

  const existente = comanda.find(p => p.prato_id === id);
  if (existente) {
    existente.quantidade += qtd;
  } else {
    comanda.push({ prato_id: id, nome, preco, quantidade: qtd });
  }

  mostrarComanda();
  qtdInput.value = 1; // resetar input quantidade
}

// Mostrar o carrinho na tela
function mostrarComanda() {
  const comandaDiv = document.getElementById('comanda');
  comandaDiv.innerHTML = '';

  if (comanda.length === 0) {
    comandaDiv.innerHTML = '<p>Seu carrinho está vazio.</p>';
    return;
  }

  const lista = document.createElement('ul');
  lista.classList.add('lista-comanda');

  let total = 0;

  comanda.forEach((item, index) => {
    const li = document.createElement('li');
    li.innerHTML = `
      ${item.nome} - ${item.quantidade} x R$ ${item.preco.toFixed(2)} = R$ ${(item.preco * item.quantidade).toFixed(2)}
      <button onclick="removerItem(${index})" class="btn-remover" title="Remover">✖</button>
    `;
    lista.appendChild(li);
    total += item.preco * item.quantidade;
  });

  const totalDiv = document.createElement('div');
  totalDiv.classList.add('total');
  totalDiv.textContent = `Total: R$ ${total.toFixed(2)}`;

  const btnFinalizar = document.createElement('button');
  btnFinalizar.textContent = 'Finalizar Pedido';
  btnFinalizar.classList.add('btn-finalizar');
  btnFinalizar.onclick = finalizarPedido;

  comandaDiv.appendChild(lista);
  comandaDiv.appendChild(totalDiv);
  comandaDiv.appendChild(btnFinalizar);
}

// Remover item do carrinho
function removerItem(index) {
  comanda.splice(index, 1);
  mostrarComanda();
}

// Finalizar pedido: pedir nome do cliente e enviar à API
async function finalizarPedido() {
  if (comanda.length === 0) {
    alert('Adicione pelo menos um prato ao pedido!');
    return;
  }

  const cliente = prompt('Digite o nome do cliente:');
  if (!cliente || cliente.trim() === '') {
    alert('Nome do cliente é obrigatório.');
    return;
  }

  // Montar objeto pedido para enviar
  const pedido = {
    cliente: cliente.trim(),
    itens: comanda.map(p => ({
      prato_id: p.prato_id,
      quantidade: p.quantidade
    }))
  };

  try {
    const res = await fetch('api.php?endpoint=pedido', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(pedido)
    });
    const data = await res.json();

    if (res.ok) {
      alert(data.mensagem);
      comanda = [];
      mostrarComanda();
      carregarPedidos();
    } else {
      alert('Erro: ' + (data.erro || 'Erro ao enviar pedido.'));
    }
  } catch (error) {
    alert('Erro ao enviar pedido.');
    console.error(error);
  }
}

// Carregar e mostrar lista de pedidos realizados
async function carregarPedidos() {
  try {
    const res = await fetch('api.php?endpoint=pedidos');
    const pedidos = await res.json();

    const pedidosDiv = document.getElementById('pedidos');
    pedidosDiv.innerHTML = '';

    if (!pedidos || pedidos.length === 0) {
      pedidosDiv.innerHTML = '<p>Nenhum pedido realizado ainda.</p>';
      return;
    }

    pedidos.forEach(pedido => {
      const div = document.createElement('div');
      div.classList.add('pedido');

      const itensText = pedido.itens.map(i => `${i.nome} (${i.quantidade}x)`).join(', ');

      div.innerHTML = `
        <strong>Pedido #${pedido.id} - ${pedido.cliente}</strong> <br>
        <small>${pedido.data}</small>
        <p>${itensText}</p>
        <strong>Total: R$ ${pedido.total.toFixed(2)}</strong>
      `;

      pedidosDiv.appendChild(div);
    });
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error);
  }
}
