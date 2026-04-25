<?php
session_start();
include "conexao.php";
 include "verifica_login_opcional.php"; 


// Verifica se o ID do produto e quantidade foram enviados
if (!isset($_POST['id_produto'], $_POST['quantidade'])) {
    header("Location: verprodutos.php");
    exit;
}

$id_produto = intval($_POST['id_produto']);
$quantidade = max(1, intval($_POST['quantidade'])); // segurança mínima

// Busca informações do produto
$stmt = $conexao->prepare("SELECT preco FROM produto WHERE id_produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "Produto inválido.";
    exit;
}

$produto = $resultado->fetch_assoc();
$preco = $produto['preco'];
$subtotal = $quantidade * $preco;

// 🟩 Se o usuário estiver logado:
if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['id_usuário'])) {
    $id_usuario = $_SESSION['usuario']['id_usuário'];

    // Verifica se já existe carrinho activo
    $stmt = $conexao->prepare("SELECT id_carrinho FROM carrinho WHERE id_usuário = ? AND status = 'activo'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $id_carrinho = $res->fetch_assoc()['id_carrinho'];
    } else {
        // Cria um novo carrinho
        $stmt = $conexao->prepare("INSERT INTO carrinho (id_usuário, data_criacao, status) VALUES (?, NOW(), 'activo')");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $id_carrinho = $stmt->insert_id;
    }

    // Verifica se o produto já está no carrinho
    $stmt = $conexao->prepare("SELECT id_item_carrinho FROM item_carrinho WHERE id_carrinho = ? AND id_produto = ?");
    $stmt->bind_param("ii", $id_carrinho, $id_produto);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Atualiza a quantidade e subtotal
        $stmt = $conexao->prepare("
            UPDATE item_carrinho 
            SET quantidade = quantidade + ?, subtotal = subtotal + ? 
            WHERE id_carrinho = ? AND id_produto = ?
        ");
        $stmt->bind_param("idii", $quantidade, $subtotal, $id_carrinho, $id_produto);
    } else {
        // Insere novo item
        $stmt = $conexao->prepare("
            INSERT INTO item_carrinho (id_carrinho, id_produto, quantidade, subtotal) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiid", $id_carrinho, $id_produto, $quantidade, $subtotal);
    }
    $stmt->execute();

} else {
    // 🟨 Usuário visitante → salva em sessão
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    if (isset($_SESSION['carrinho'][$id_produto])) {
        $_SESSION['carrinho'][$id_produto]['quantidade'] += $quantidade;
        $_SESSION['carrinho'][$id_produto]['subtotal'] += $subtotal;
    } else {
        $_SESSION['carrinho'][$id_produto] = [
            'id_produto' => $id_produto,
            'quantidade' => $quantidade,
            'preco' => $preco,
            'subtotal' => $subtotal
        ];
    }
}

// ✅ Redireciona para o carrinho
header("Location: carrinho.php");
exit;
