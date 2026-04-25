
<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Verifica se o ID foi enviado
if (!isset($_GET['id_fornecedor'])) {
    die("ID do fornecedor não informado.");
}

$id = $_GET['id_fornecedor'];

// Prepara e executa o DELETE com segurança
$stmt = $conexao->prepare("DELETE FROM fornecedor WHERE id_fornecedor = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Fornecedor excluído com sucesso!";
} else {
    echo "Fornecedor não encontrado ou já foi excluído.";
}

echo "<br><a href='fornecedores.php'>Voltar para a lista de fornecedores</a>";

$conexao->close();
?>
