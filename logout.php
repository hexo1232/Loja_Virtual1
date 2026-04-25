


<?php
session_start();

// Captura o perfil do usuário antes de destruir a sessão
$idperfil = $_SESSION['usuario']['idperfil'] ?? null;

// ✅ Limpa somente os dados de login
unset($_SESSION['usuario']);

// 🔒 Fecha e salva a sessão
session_write_close();

// ✅ Redireciona com base no perfil
if ($idperfil == 1) {
    header("Location: login.php");
} else {
    header("Location: verprodutos.php");
}
exit;
?>


<?php
/*
session_start();
include "conexao.php";

// Captura o perfil e ID do usuário logado
$idperfil = $_SESSION['usuario']['idperfil'] ?? null;
$id_usuario = $_SESSION['usuario']['id_usuário'] ?? null;

if ($id_usuario) {
    // Pega o carrinho activo no banco
    $sql = "SELECT c.id_carrinho, ic.id_produto, ic.quantidade, p.preco 
            FROM carrinho c
            JOIN item_carrinho ic ON c.id_carrinho = ic.id_carrinho
            JOIN produto p ON ic.id_produto = p.id_produto
            WHERE c.id_usuário = ? AND c.status = 'activo'";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    $carrinho_cookie = [];

    while ($row = $res->fetch_assoc()) {
        $carrinho_cookie[] = [
            'id_produto' => $row['id_produto'],
            'quantidade' => $row['quantidade'],
            'preco' => $row['preco'],
            'subtotal' => $row['quantidade'] * $row['preco']
        ];
    }

    // Salva de volta no cookie
    setcookie("carrinho", json_encode($carrinho_cookie), time() + 86400 * 7, "/");
}

// Limpa apenas os dados de login
unset($_SESSION['usuario']);
session_write_close();

// Redireciona
if ($idperfil == 1) {
    header("Location: login.php");
} else {
    header("Location: verprodutos.php");
}
exit;
*/
?>
