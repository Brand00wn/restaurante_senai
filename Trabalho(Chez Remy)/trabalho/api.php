<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$acao = $_GET['acao'] ?? '';

// === 1. Se for POST e NÃO tiver ação, calcula total do carrinho ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$acao) {
    $dadosJSON = file_get_contents("php://input");
    $carrinho = json_decode($dadosJSON, true);

    $total = 0;
    foreach ($carrinho as $item) {
        $quantidade = intval($item['quantidade']);
        $preco = floatval($item['preco']);
        $total += $quantidade * $preco;
    }

    echo json_encode(['total' => $total]);
    exit;
}

// === 2. Cardápio fixo (sua lista original completa) ===
$cardapio = [
    ['id' => 0, 'nome' => 'Escargots de Bourgogne', 'descricao' => 'Caracóis ao molho de manteiga, alho e ervas.', 'imagem' => 'img/prato/Escargots.jpg', 'categoria' => 'Entrada', 'preco' => 29.90],
    ['id'=> 1,'nome' => 'Salada Niçoise', 'descricao' => 'Clássica salada francesa com atum.', 'imagem' => 'img/prato/SaladaNiçoise.jpg', 'categoria' => 'Entrada', 'preco' => 22.50],
    ['id'=> 2,'nome' => 'Sopa de Cebola', 'descricao' => 'Tradicional sopa francesa gratinada.', 'imagem' => 'img/prato/sopa.jpg', 'categoria' => 'Entrada', 'preco' => 19.00],
    ['id'=> 3,'nome' => 'Ratatouille Clássico', 'descricao' => 'O prato icônico.', 'imagem' => 'img/prato/prato1.jpg', 'categoria' => 'Principal', 'preco' => 35.90],
    ['id'=> 4,'nome' => 'Espaguete Linguini', 'descricao' => 'Feito pelo chef.', 'imagem' => 'img/prato/espaguete.jpg', 'categoria' => 'Principal', 'preco' => 29.90],
    ['id'=> 5,'nome' => 'Confit de Canard', 'descricao' => 'Pato confitado ao molho de vinho.', 'imagem' => 'img/prato/confit.jpg', 'categoria' => 'Principal', 'preco' => 49.90],
    ['id'=> 6,'nome' => 'Bouillabaisse', 'descricao' => 'Caldeirada de peixe com especiarias.', 'imagem' => 'img/prato/bouillabaisse.jpg', 'categoria' => 'Principal', 'preco' => 45.00],
    ['id'=> 7,'nome' => 'Risoto do Remy', 'descricao' => 'Cremoso e perfeito.', 'imagem' => 'img/prato/risoto.jpg', 'categoria' => 'Principal', 'preco' => 32.50],
    ['id'=> 8, 'nome' => 'Tarte Tatin', 'descricao' => 'Sobremesa clássica.', 'imagem' => 'img/prato/tarte.jpg', 'categoria' => 'Sobremesa', 'preco' => 18.00],
    ['id'=> 9, 'nome' => 'Crème Brûlée', 'descricao' => 'Deliciosa com casquinha crocante.', 'imagem' => 'img/prato/creme_brulee.jpg', 'categoria' => 'Sobremesa', 'preco' => 16.50],
    ['id'=> 10, 'nome' => 'Macarons', 'descricao' => 'Diversos sabores, direto de Paris.', 'imagem' => 'img/prato/macarons.jpg', 'categoria' => 'Sobremesa', 'preco' => 12.00],
    ['id'=> 11, 'nome' => 'Champagne', 'descricao' => 'Bebida espumante premium.', 'imagem' => 'img/prato/champagne.jpg', 'categoria' => 'Bebida', 'preco' => 75.00],
    ['id'=> 12, 'nome' => 'Vinho Francês', 'descricao' => 'O melhor da França.', 'imagem' => 'img/prato/vinho.jpg', 'categoria' => 'Bebida', 'preco' => 55.00],
    ['id'=> 13, 'nome' => 'Água Mineral', 'descricao' => 'Água Natural.', 'imagem' => 'img/prato/agua.jpg', 'categoria' => 'Bebida', 'preco' => 5.00]
];

// === 3. Rotas de ação ===
if ($acao == 'cardapio') {
    echo json_encode($cardapio);
    exit;
} 

elseif ($acao == 'pedido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['nomeCliente']) || !isset($data['itens'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados inválidos.']);
        exit;
    }

    $nomeCliente = trim($data['nomeCliente']);
    $itensPedido = $data['itens']; // array {id, quantidade}
    $observacoes = $data['observacoes'] ?? '';

    $total = 0;
    $detalhesItens = [];

    foreach ($itensPedido as $item) {
        $produto = null;
        foreach ($cardapio as $p) {
            if ($p['id'] === $item['id']) {
                $produto = $p;
                break;
            }
        }
        if (!$produto) continue;

        $quantidade = max(1, (int)$item['quantidade']);
        $subtotal = $produto['preco'] * $quantidade;
        $total += $subtotal;

        $detalhesItens[] = [
            'id' => $produto['id'],
            'nome' => $produto['nome'],
            'quantidade' => $quantidade,
            'preco_unitario' => $produto['preco'],
            'subtotal' => $subtotal
        ];
    }

    $pedido = [
        'cliente' => $nomeCliente,
        'itens' => $detalhesItens,
        'observacoes' => $observacoes,
        'total' => round($total, 2),
        'data' => date('Y-m-d H:i:s')
    ];

    if (!isset($_SESSION['pedidos'])) {
        $_SESSION['pedidos'] = [];
    }
    $_SESSION['pedidos'][] = $pedido;

    echo json_encode([
        'mensagem' => "Pedido recebido com sucesso!",
        'total' => round($total, 2)
    ]);
    exit;
}

elseif ($acao == 'reserva') {
    echo json_encode("Reserva efetuada com sucesso!");
} 

elseif ($acao == 'contato') {
    echo json_encode("Mensagem enviada com sucesso!");
}
elseif ($acao == 'pedidos') {
    // Retorna os pedidos finalizados da sessão
    $pedidos = $_SESSION['pedidos'] ?? [];
    echo json_encode($pedidos);
    exit;
}
else {
    echo json_encode(['erro' => 'Ação inválida']);
}
?>
