<?php
ob_start();
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Verifica se foi passado um ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID do produto não especificado.";
    exit;
}

$id_produto = intval($_GET['id']);

// Busca as imagens relacionadas para remover do servidor
$stmt = $conexao->prepare("SELECT caminho_imagem FROM produto_imagem WHERE id_produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$resultado = $stmt->get_result();

while ($img = $resultado->fetch_assoc()) {
    $caminho = $img['caminho_imagem'];
    if (file_exists($caminho)) {
        unlink($caminho); // Remove o arquivo da pasta
    }
}

// Exclui as imagens do banco de dados
$conexao->query("DELETE FROM produto_imagem WHERE id_produto = $id_produto");

// Exclui o produto da tabela
$stmt = $conexao->prepare("DELETE FROM produto WHERE id_produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();

// Redireciona para a listagem com mensagem
header("Location: gerenciarprodutos.php?msg=excluido");
exit;
if (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
    <p style="color: green;">Produto excluído com sucesso!</p>
<?php endif; ?>
