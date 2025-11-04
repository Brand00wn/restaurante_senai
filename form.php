<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

$pratos = [
    ["id" => 1, "nome" => "Sunomono", "descricao" => "Salada de pepino agridoce com gergelim", "preco" => 12.00, "categoria" => "entradas", "imagem" => "img/sunomono.jpeg"],
    ["id" => 2, "nome" => "Missoshiru", "descricao" => "Sopa de missô com tofu, cebolinha e alga", "preco" => 10.00, "categoria" => "entradas", "imagem" => "img/Misso-Shiru-com-Tofu-1.jpg"],
    ["id" => 3, "nome" => "Guioza (4 unidades)", "descricao" => "Pasteizinhos japoneses recheados com carne ou legumes", "preco" => 18.00, "categoria" => "entradas", "imagem" => "img/guioza.webp"],
    ["id" => 4, "nome" => "Combinado Especial (20 unidades)", "descricao" => "Seleção de sushis e sashimis variados com toque da casa", "preco" => 69.90, "categoria" => "prato-principal", "imagem" => "img/combinado especial.jpeg"],
    ["id" => 5, "nome" => "Hot Roll Tradicional (8 unidades)", "descricao" => "Salmão com cream cheese empanado, coberto com molho tarê", "preco" => 28.00, "categoria" => "prato-principal", "imagem" => "img/hot-roll-8.jpeg"],
    ["id" => 6, "nome" => "Sashimi de Salmão (10 fatias)", "descricao" => "Fatias frescas e delicadas de salmão cru", "preco" => 35.00, "categoria" => "prato-principal", "imagem" => "img/sashimi de salmao.jpeg"],
    ["id" => 7, "nome" => "Uramaki Skin (8 unidades)", "descricao" => "Arroz por fora, recheado com pele de salmão crocante", "preco" => 25.00, "categoria" => "prato-principal", "imagem" => "img/uramaki.jpeg"],
    ["id" => 8, "nome" => "Temaki de Salmão", "descricao" => "Cone de alga com salmão fresco, cream cheese e cebolinha", "preco" => 26.00, "categoria" => "prato-principal", "imagem" => "img/temaki salmao.jpeg"],
    ["id" => 9, "nome" => "Tataki de Atum (6 fatias)", "descricao" => "Atum selado servido com molho cítrico ponzu", "preco" => 45.00, "categoria" => "prato-principal", "imagem" => "img/tataki.jpeg"],
    ["id" => 10, "nome" => "Tempurá de Sorvete", "descricao" => "1 bola empanada e frita, com calda quente de chocolate", "preco" => 24.90, "categoria" => "sobremesas", "imagem" => "img/tempurá de sorvete.jpeg"],
    ["id" => 11, "nome" => "Cheesecake de Matcha", "descricao" => "Fatia de cheesecake suave com chá verde japonês e base crocante", "preco" => 21.90, "categoria" => "sobremesas", "imagem" => "img/cheesecake.jpeg"],
    ["id" => 12, "nome" => "Saké Nacional (Dose 50ml)", "descricao" => "Bebida tradicional japonesa feita de arroz fermentado", "preco" => 14.00, "categoria" => "bebidas", "imagem" => "img/sake.jpeg"],
    ["id" => 13, "nome" => "Refrigerante Lata (350ml)", "descricao" => "Coca-Cola, Guaraná, Sprite ou similar", "preco" => 6.00, "categoria" => "bebidas", "imagem" => "img/refrigerante.jpeg"],
    ["id" => 14, "nome" => "Chá Verde Gelado (500ml)", "descricao" => " Chá verde natural, levemente adoçado, servido gelado", "preco" => 9.90, "categoria" => "bebidas", "imagem" => "img/cha-verde.jpeg"],
    ["id" => 15, "nome" => "Drink de Saquê com Morango (300ml)", "descricao" => "Coquetel refrescante com sakê, morango e gelo picado", "preco" => 19.90, "categoria" => "bebidas", "imagem" => "img/saque.jpeg"]
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($pratos);
    exit;
}

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'pratos' :
            echo json_encode($pratos);
            break;
        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'], $data['nome'], $data['preco'])) {
                http_response_code(400);
                echo json_encode(["error" => "Dados inválidos"]);
                exit;
            }
            $id = $data['id'];
            $nome = $data['nome'];
            $preco = $data['preco'];

            $found = false;
            foreach ($_SESSION['carrinho'] as &$item) {
                if ($item['id'] == $id) {
                    $item['quantidade']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['carrinho'][] = [
                    'id' => $id,
                    'nome' => $nome,
                    'preco' => $preco,
                    'quantidade' => 1
                ];
            }

            echo json_encode(["status" => "ok"]);
            exit;

        case 'list':
            $total = 0;
            foreach ($_SESSION['carrinho'] as $item) {
                $total += $item['preco'] * $item['quantidade'];
            }
            echo json_encode([
                'carrinho' => array_values($_SESSION['carrinho']),
                'total' => number_format($total, 2)
            ]);
            exit;

        case 'remove':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(["error" => "ID necessário"]);
                exit;
            }
            $id = $data['id'];
            foreach ($_SESSION['carrinho'] as $i => $item) {
                if ($item['id'] == $id) {
                    unset($_SESSION['carrinho'][$i]);
                    $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
                    break;
                }
            }
            echo json_encode(["status" => "ok"]);
            exit;

        case 'clear':
            $_SESSION['carrinho'] = [];
            echo json_encode(["status" => "ok"]);
            exit;
        case 'finalizar':
            $dados = json_decode(file_get_contents('php://input'), true);
            $nome = trim($dados['nome'] ?? '');
        
            if (!$nome) {
                echo json_encode(['erro' => true, 'mensagem' => 'Nome é obrigatório.']);
                exit;
            }
        
            if (empty($_SESSION['carrinho'])) {
                echo json_encode(['erro' => true, 'mensagem' => 'Carrinho está vazio.']);
                exit;
            }
        
            $total = 0;
            foreach ($_SESSION['carrinho'] as $item) {
                $total += $item['preco'] * $item['quantidade'];
            }
        
            $_SESSION['pedidos'][] = [
                'nome' => $nome,
                'itens' => $_SESSION['carrinho'],
                'total' => number_format($total, 2, ',', '.')
            ];
        
            $_SESSION['carrinho'] = [];
            echo json_encode(['erro' => false, 'mensagem' => 'Pedido finalizado com sucesso!']);
            exit;
        
        case 'pedidos':
            echo json_encode($_SESSION['pedidos'] ?? []);
            exit;
    }
}




http_response_code(400);
echo json_encode(["error" => "Ação inválida"]);
exit;
?>
