<?php
session_start();
header("Content-Type: application/json");

// Cardápio com imagens ilustrativas (URLs públicas gratuitas)
$cardapio = [
    ['prato_id'=>1, 'nome'=>'Salada Caesar', 'descricao'=>'Alface, croutons, parmesão e molho caesar.', 'preco'=>18.90, 'categoria'=>'Entradas', 'imagem'=>'img/folha verde.jpg'],
    ['prato_id'=>2, 'nome'=>'Bruschetta', 'descricao'=>'Pão tostado com tomate e manjericão.', 'preco'=>15.50, 'categoria'=>'Entradas', 'imagem'=>'img/bruschetta.jpg'],
    ['prato_id'=>3, 'nome'=>'Sopa de Legumes', 'descricao'=>'Sopa quente de legumes da estação.', 'preco'=>12.00, 'categoria'=>'Entradas', 'imagem'=>'img/sopa.jpg'],

    ['prato_id'=>4, 'nome'=>'Spaghetti à Bolonhesa', 'descricao'=>'Macarrão com molho à bolonhesa.', 'preco'=>32.00, 'categoria'=>'Pratos principais', 'imagem'=>'img/spaghetti.jpg'],
    ['prato_id'=>5, 'nome'=>'Filé de Frango Grelhado', 'descricao'=>'Peito de frango grelhado com arroz e legumes.', 'preco'=>28.50, 'categoria'=>'Pratos principais', 'imagem'=>'img/frango.jpg'],
    ['prato_id'=>6, 'nome'=>'Lasanha de Queijo', 'descricao'=>'Lasanha tradicional com queijo e molho branco.', 'preco'=>35.00, 'categoria'=>'Pratos principais', 'imagem'=>'img/lasanha.jpg'],
    ['prato_id'=>7, 'nome'=>'Risoto de Cogumelos', 'descricao'=>'Risoto cremoso com mix de cogumelos.', 'preco'=>38.00, 'categoria'=>'Pratos principais', 'imagem'=>'img/risotto.jpg'],
    ['prato_id'=>8, 'nome'=>'Bife Acebolado', 'descricao'=>'Bife bovino com cebola refogada.', 'preco'=>30.00, 'categoria'=>'Pratos principais', 'imagem'=>'img/bife.jpg'],
    ['prato_id'=>9, 'nome'=>'Peixe Assado', 'descricao'=>'Peixe assado ao forno com ervas.', 'preco'=>40.00, 'categoria'=>'Pratos principais', 'imagem'=>'img/peixe.jpg'],

    ['prato_id'=>10, 'nome'=>'Pudim de Leite', 'descricao'=>'Pudim tradicional de leite condensado.', 'preco'=>12.00, 'categoria'=>'Sobremesas', 'imagem'=>'img/pudim.jpg'],
    ['prato_id'=>11, 'nome'=>'Mousse de Chocolate', 'descricao'=>'Mousse leve de chocolate amargo.', 'preco'=>15.00, 'categoria'=>'Sobremesas', 'imagem'=>'img/mousse.jpg'],

    ['prato_id'=>12, 'nome'=>'Refrigerante', 'descricao'=>'Refrigerante lata 350ml.', 'preco'=>7.00, 'categoria'=>'Bebidas', 'imagem'=>'img/refrigerante.jpg'],
    ['prato_id'=>13, 'nome'=>'Suco Natural', 'descricao'=>'Suco natural da fruta do dia.', 'preco'=>8.00, 'categoria'=>'Bebidas', 'imagem'=>'img/suco.jpg'],
    ['prato_id'=>14, 'nome'=>'Cerveja', 'descricao'=>'Cerveja nacional 600ml.', 'preco'=>10.00, 'categoria'=>'Bebidas', 'imagem'=>'img/cerveja.jpg'],
    ['prato_id'=>15, 'nome'=>'Água Mineral', 'descricao'=>'Água mineral sem gás 500ml.', 'preco'=>5.00, 'categoria'=>'Bebidas', 'imagem'=>'img/agua.jpg'],
];

// Inicializa pedidos em sessão
if (!isset($_SESSION['pedidos'])) {
    $_SESSION['pedidos'] = [];
}

$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
    case 'cardapio':
        echo json_encode($cardapio);
        break;

    case 'prato':
        $id = intval($_GET['id'] ?? 0);
        $prato = null;
        foreach ($cardapio as $p) {
            if ($p['prato_id'] === $id) {
                $prato = $p;
                break;
            }
        }
        if ($prato) {
            echo json_encode($prato);
        } else {
            http_response_code(404);
            echo json_encode(['erro' => 'Prato não encontrado']);
        }
        break;

    case 'pedido':
        $input = json_decode(file_get_contents('php://input'), true);
        if (
            !$input ||
            empty($input['cliente']) ||
            empty($input['itens']) ||
            !is_array($input['itens'])
        ) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados inválidos']);
            break;
        }

        $cliente = trim($input['cliente']);
        $itens = $input['itens'];

        $total = 0;
        $detalhes_itens = [];

        foreach ($itens as $item) {
            $pid = intval($item['prato_id'] ?? 0);
            $qtd = intval($item['quantidade'] ?? 0);
            if ($pid <= 0 || $qtd <= 0) continue;

            $prato = null;
            foreach ($cardapio as $p) {
                if ($p['prato_id'] === $pid) {
                    $prato = $p;
                    break;
                }
            }
            if ($prato) {
                $subtotal = $prato['preco'] * $qtd;
                $total += $subtotal;
                $detalhes_itens[] = [
                    'prato_id' => $pid,
                    'nome' => $prato['nome'],
                    'quantidade' => $qtd,
                    'preco_unitario' => $prato['preco'],
                    'subtotal' => $subtotal,
                ];
            }
        }

        if (empty($detalhes_itens)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nenhum item válido no pedido']);
            break;
        }

        $novo_pedido = [
            'id' => count($_SESSION['pedidos']) + 1,
            'cliente' => $cliente,
            'itens' => $detalhes_itens,
            'total' => round($total, 2),
            'data' => date('Y-m-d H:i:s'),
        ];

        $_SESSION['pedidos'][] = $novo_pedido;

        echo json_encode(['mensagem' => 'Pedido recebido com sucesso!', 'pedido' => $novo_pedido]);
        break;

    case 'pedidos':
        echo json_encode($_SESSION['pedidos']);
        break;

    default:
        http_response_code(404);
        echo json_encode(['erro' => 'Endpoint não encontrado']);
        break;
}
