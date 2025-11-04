let carrinho = [];
let cardapioData = [];

window.addEventListener('load', () => {
  const loadingScreen = document.getElementById('loading-screen');
  setTimeout(() => {
    loadingScreen.style.display = 'none';
  }, 4000);
  pedidosfinalizado();
});
function pedidosfinalizado(){
  fetch('api.php?acao=pedidos')
  .then(res => res.json())
  .then(data => {
    renderizarpedidosfin(data);
  });
}
function atualizarPedidosFin() {
  pedidosfinalizado();
}
function pedidosfinalizado(){
  fetch('api.php?acao=pedidos')
    .then(res => res.json())
    .then(data => {
      renderizarpedidosfin(data);
    });
}

function renderizarpedidosfin(data) {
  const div = document.getElementById('pedidosfeitos');
  div.className = 'pedidosfeitos';
  div.innerHTML = '';

  if (data.length === 0) {
    div.innerHTML = '<p>Nenhum pedido finalizado.</p>';
    return;
  }

  data.forEach(pedido => {
    const itensHtml = pedido.itens.map(item =>
      `<li>${item.quantidade}x ${item.nome} - R$ ${item.preco_unitario.toFixed(2)}</li>`
    ).join('');

    div.innerHTML += `
      <div class="pedido">
        <h4>Pedido de ${pedido.cliente}</h4>
        <p><em>${pedido.data}</em></p>
        <ul>${itensHtml}</ul>
        <p><strong>Observações:</strong> ${pedido.observacoes || 'Nenhuma'}</p>
        <p><strong>Total:</strong> R$ ${pedido.total.toFixed(2)}</p>
        <hr>
      </div>
    `;
  });
}


function mostrar(secao) {
  document.querySelectorAll('main section').forEach(s => s.style.display = 'none');
  document.getElementById(secao).style.display = 'block';
  if (secao === 'cardapio') fetchCardapio();
}

function fetchCardapio() {
  fetch('api.php?acao=cardapio')
    .then(res => res.json())
    .then(data => {
      cardapioData = data;
      renderizarCardapio(data);
    });
}

function renderizarCardapio(data) {
  const lista = document.getElementById('lista-cardapio');
  lista.innerHTML = '';
  data.forEach((item) => {
    const div = document.createElement('div');
    div.className = 'cardapio-item';
    div.innerHTML = `
      <img src="${item.imagem}" alt="${item.nome}">
      <h3>${item.nome} - <br> R$ ${item.preco.toFixed(2)}</h3>
      <p>${item.descricao}</p>
      <p><strong>Quantidade:</strong></p>
      <input type="number" id="qtd-${item.id}" value="1" min="1">
      <button onclick="adicionarAoCarrinho(${item.id}, '${item.nome}')">Adicionar</button>`;
    lista.appendChild(div);
  });
}

function filtrarCategoria() {
  const categoria = document.getElementById('filtroCategoria').value;
  const filtrado = categoria ? cardapioData.filter(i => i.categoria === categoria) : cardapioData;
  renderizarCardapio(filtrado);
}

function adicionarAoCarrinho(id, nome) {
  const qtdInput = document.getElementById(`qtd-${id}`);
  const preco = cardapioData.filter(prato => prato.id == id)[0].preco;
  const quantidade = parseInt(qtdInput.value);
  if (quantidade <= 0) return alert('Quantidade inválida.');

  // Verifica se o item já está no carrinho
  const index = carrinho.findIndex(item => item.id === id);
  if (index > -1) {
    carrinho[index].quantidade += quantidade;
  } else {
    carrinho.push({ id, nome, quantidade, preco });
  }

  atualizarCarrinho();
}

function atualizarTotalNoServidor() {
  fetch('api.php', { 
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(carrinho)
  })
  .then(res => res.json())
  .then(data => {
    const totalDiv = document.getElementById('pedido-total');
    totalDiv.textContent = `Total: R$ ${data.total.toFixed(2)}`;
  })
  .catch(err => console.error('Erro ao obter total:', err));
}

function atualizarCarrinho() {
  const div = document.getElementById('carrinho');
  let total = 0;

  if (carrinho.length === 0) {
    div.innerHTML = '<p>Carrinho vazio</p>';
    document.getElementById('pedido-total').textContent = 'Total: R$ 0,00';
    return;
  }

  div.innerHTML = carrinho.map(item => 
    `<p>${item.quantidade}x ${item.nome} - R$ ${item.preco.toFixed(2)}
    <button onclick="removerdoCarrinho(${item.id})" class="botaoremover">Remover Item</button>
    </p>`
  ).join('');

  // Enviar os dados para o PHP para calcular o total
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(carrinho)
  })
  .then(response => response.json())
  .then(data => {
    const total = parseFloat(data.total).toFixed(2).replace('.', ',');
    document.getElementById('pedido-total').textContent = `Total: R$ ${total}`;
  })
  .catch(error => {
    console.error('Erro ao calcular total:', error);
  });
}


function removerdoCarrinho(id) {
  const index = carrinho.findIndex(item => item.id === id);

   if(index >-1) {
    carrinho.splice(index, 1);

    atualizarCarrinho();
     } else{
      alert('Item não encontrado no carrinho!')
     }
}

// Função que abre o popup para pedir o nome do cliente
function finalizarPedido() {
  if (carrinho.length === 0) {
    alert('Seu carrinho está vazio!');
    return;
  }
  document.getElementById('meuPopup').style.display = 'block';
}

// Cancelar o popup
document.getElementById('cancelarNomeBtn').addEventListener('click', () => {
  document.getElementById('meuPopup').style.display = 'none';
});

// Confirmar e enviar pedido
document.getElementById('confirmarNomeBtn').addEventListener('click', () => {
  const nomeCliente = document.getElementById('nomeCliente').value.trim();
  if (!nomeCliente) {
    alert("Por favor, informe seu nome.");
    return;
  }

  const observacoes = document.getElementById('observacoes').value;

  fetch('api.php?acao=pedido', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      nomeCliente,
      itens: carrinho,
      observacoes
    })
  })
  .then(res => res.json())
  .then(data => {
    alert(`${data.mensagem} Total: R$ ${data.total.toFixed(2)}`);
    carrinho = [];
    atualizarCarrinho();
    atualizarPedidosFin();
    document.getElementById('observacoes').value = '';
    document.getElementById('nomeCliente').value = '';
    document.getElementById('meuPopup').style.display = 'none';
  });
});
