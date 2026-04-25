<?php
session_start();
include "conexao.php";

// Verifica se o ID do produto foi informado
if (!isset($_GET['id_produto'])) {
    header("Location: carrinho.php");
    exit;
}

$id_produto = intval($_GET['id_produto']);

// Se o usuário estiver logado
if (isset($_SESSION['usuario']['id_usuário'])) {
    $id_usuario = $_SESSION['usuario']['id_usuário'];

    // Localiza o carrinho ativo do usuário
    $stmt = $conexao->prepare("SELECT id_carrinho FROM carrinho WHERE id_usuário = ? AND status = 'activo'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $id_carrinho = $resultado->fetch_assoc()['id_carrinho'];

        // Remove o item do carrinho
        $stmt = $conexao->prepare("DELETE FROM item_carrinho WHERE id_carrinho = ? AND id_produto = ?");
        $stmt->bind_param("ii", $id_carrinho, $id_produto);
        $stmt->execute();
    }
}
// Se o usuário NÃO estiver logado (carrinho via sessão)
else {
    if (isset($_SESSION['carrinho'])) {
        foreach ($_SESSION['carrinho'] as $index => $item) {
            if ($item['id_produto'] == $id_produto) {
                unset($_SESSION['carrinho'][$index]);
                break;
            }
        }
        // Reorganiza os índices
        $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
    }
}

header("Location: carrinho.php");
exit;
