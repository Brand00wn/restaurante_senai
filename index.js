
const menuLinks = document.querySelectorAll('nav ul.menu li a');
const welcomeMessage = document.getElementById('welcome-message');
const sections = document.querySelectorAll('main > section');
const logo = document.getElementById('logo');


function esconderTodasSecoes() {
  sections.forEach(sec => {
    sec.style.display = 'none';
  });
}


function mostrarSecao(id) {
  esconderTodasSecoes();
  const sec = document.getElementById(id);
  if (sec) sec.style.display = 'block';
}


menuLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const target = link.dataset.target;
    if (target) {
      mostrarSecao(target);
      welcomeMessage.style.display = 'none';
      if (target === 'carrinho') {
        atualizarCarrinho();
      }
      if (target === 'pedidos') {
        carregarPedidos();
      }
    }
  });
});


logo.addEventListener('click', e => {
  e.preventDefault();
  esconderTodasSecoes();
  welcomeMessage.style.display = 'block';
});


const listaPratos = document.getElementById('lista-pratos');
const listaCarrinho = document.getElementById('lista-carrinho');
const contadorCarrinho = document.getElementById('contador-carrinho');
const totalCarrinho = document.getElementById('total-carrinho');
const carrinhoDiv = document.getElementById('carrinho');
const btnLimpar = document.getElementById('limpar-carrinho');
const btnfinalizar = document.getElementById('btn-finalizar');
let pratos = [];

async function carregarCardapio() {
  try {
    const res = await fetch('form.php?action=pratos');
    pratos = await res.json();
    mostrarPratos();
    document.querySelectorAll('.filtro').forEach(botao => {
      botao.addEventListener('click', () => {
        const categoria = botao.dataset.categoria;
        mostrarPratos(categoria);
      });
    });

  } catch {
    listaPratos.innerHTML = '<p>Erro ao carregar cardápio.</p>';
  }
}


function mostrarPratos(categoria = 'todos') {
  listaPratos.innerHTML = '';
  const pratosFiltrados = categoria === 'todos'
    ? pratos
    : pratos.filter(p => p.categoria === categoria);

  pratosFiltrados.forEach(p => {
    const div = document.createElement('div');
    div.className = 'card-prato';

    div.innerHTML = `
      <img src="${p.imagem}" alt="${p.nome}" />
      <h3>${p.nome}</h3>
      <p>${p.descricao}</p>
      <p>R$ ${p.preco.toFixed(2)}</p>
      <button data-id="${p.id}">Adicionar</button>
    `;
    listaPratos.appendChild(div);
    div.querySelector('button').addEventListener('click', () => adicionarAoCarrinho(p.id));
  });
}


async function adicionarAoCarrinho(id) {
  const produto = pratos.find(p => p.id == id);
  if (!produto) return;

  await fetch('form.php?action=add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: produto.id, nome: produto.nome, preco: produto.preco })
  });
  atualizarCarrinho();

 
  welcomeMessage.style.display = 'none';
}


async function atualizarCarrinho() {
  const res = await fetch('form.php?action=list', { method: 'POST' });
  const data = await res.json();

  listaCarrinho.innerHTML = '';
  if (data.carrinho.length === 0) {
    carrinhoDiv.style.display = 'none';
    document.getElementById('contador-carrinho').textContent = '0'; 
    contadorCarrinho.textContent = '0';
    return;
  }
  carrinhoDiv.style.display = 'block';

  const ul = document.createElement('ul');
  ul.classList.add('lista-carrinho-itens')
  data.carrinho.forEach(item => {
    const li = document.createElement('li');
    li.textContent = `${item.nome} x ${item.quantidade} - R$ ${(item.preco * item.quantidade).toFixed(2)}`;
    const btnRemove = document.createElement('button');
    btnRemove.textContent = 'Remover';
    btnRemove.classList.add('remover-item');
    btnRemove.onclick = () => removerDoCarrinho(item.id);
    li.appendChild(btnRemove);
    ul.appendChild(li);
  });
  totalCarrinho.textContent = data.total;

  listaCarrinho.appendChild(ul);
  const contador = document.getElementById('contador-carrinho');
  const totalItens = data.carrinho.reduce((acc, item) => acc + item.quantidade, 0);
  contador.textContent = totalItens;
}


async function removerDoCarrinho(id) {
  await fetch('form.php?action=remove', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });
  await atualizarCarrinho();
}


btnLimpar.onclick = async () => {
  await fetch('form.php?action=clear', { method: 'POST' });
  await atualizarCarrinho();
}


btnfinalizar.onclick = async () => {
  const nome = prompt('Digite seu nome para finalizar o pedido:');
  if (!nome) return alert('Nome é obrigatório para finalizar.');

  const res = await fetch('form.php?action=finalizar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ nome })
  });

  const data = await res.json();
  if (data.erro) {
    alert(data.mensagem);
  } else {
    alert(data.mensagem);
    await atualizarCarrinho();
    carregarPedidos();
    mostrarSecao('pedidos');
  }
};

async function carregarPedidos() {
  const res = await fetch('form.php?action=pedidos', { method: 'POST' });
  const pedidos = await res.json();

  const divPedidos = document.getElementById('lista-pedidos');
  divPedidos.innerHTML = '';

  if (!pedidos.length) {
    divPedidos.innerHTML = '<p>Nenhum pedido realizado ainda.</p>';
    return;
  }

  pedidos.forEach(pedido => {
    const div = document.createElement('div');
    div.classList.add('card-pedido');
    div.innerHTML = `
      <h3>Cliente: ${pedido.nome}</h3>
      <ul>
        ${pedido.itens.map(item => `<li>${item.nome} x ${item.quantidade} - R$ ${(item.preco * item.quantidade).toFixed(2)}</li>`).join('')}
      </ul>
      <p><strong>Total pago:</strong> R$ ${pedido.total}</p>
    `;
    divPedidos.appendChild(div);
  });
}


carregarCardapio();
carregarPedidos();
atualizarCarrinho();
