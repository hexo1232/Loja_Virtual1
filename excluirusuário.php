
<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Verifica se o ID foi enviado
if (!isset($_GET['id_usuário'])) {
    die("ID do produto não informado.");
}

$id = $_GET['id_usuário'];

// Prepara e executa o DELETE com segurança
$stmt = $conexao->prepare("DELETE FROM usuario WHERE id_usuário = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Usuario excluído com sucesso!";
} else {
    echo "Usuario não encontrado ou já foi excluído.";
}

echo "<br><a href='usuarios.php'>Voltar para a lista de usuários</a>";

$conexao->close();
?>
