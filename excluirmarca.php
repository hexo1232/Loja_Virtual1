<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID de categoria inválido.";
    exit;
}

$id_marca = intval($_GET['id']);

// Verifica se a categoria existe
$verificar = $conexao->prepare("SELECT * FROM Marca WHERE id_marca = ?");
$verificar->bind_param("i", $id_marca);
$verificar->execute();
$resultado = $verificar->get_result();

if ($resultado->num_rows === 0) {
    echo "Marca não encontrada.";
    exit;
}

// Verifica se existem produtos associados à categoria
$produtos = $conexao->prepare("SELECT id_produto, nome_produto FROM produto WHERE id_marca = ?");
$produtos->bind_param("i", $id_marca);
$produtos->execute();
$res_produtos = $produtos->get_result();

if ($res_produtos->num_rows > 0) {
    echo "<h3 style='color: red;'>❌ Não é possível excluir esta categoria!</h3>";
    echo "<p>Existem produtos cadastrados nesta Marca. Para excluir a marca, é necessário remover ou alterar a marca desses produtos.</p>";
    
    echo "<h4>🛒 Produtos associados:</h4>";
    echo "<ul>";
    while ($produto = $res_produtos->fetch_assoc()) {
        echo "<li><strong>{$produto['nome_produto']}</strong> (ID: {$produto['id_produto']})</li>";
    }
    echo "</ul>";
    
    echo '<br><a href="marcas.php"><button>⬅ Voltar para a lista de Marcas</button></a>';
    exit;
}

// Remove vinculações com marcas (sem apagar as marcas)
$remover_vinculos = $conexao->prepare("DELETE FROM Categoria_Marca WHERE id_marca = ?");
$remover_vinculos->bind_param("i", $id_marca);
$remover_vinculos->execute();

// Exclui a categoria
$excluir_marca = $conexao->prepare("DELETE FROM Marca WHERE id_marca = ?");
$excluir_marca->bind_param("i", $id_marca);

if ($excluir_marca->execute()) {
    echo "<p style='color: green;'>✅ Marca excluída com sucesso!</p>";
} else {
    echo "<p style='color: red;'>Erro ao excluir marca: " . $excluir_marca->error . "</p>";
}

echo '<br><a href="marcas.php"><button>⬅ Voltar para a lista de Marcas</button></a>';
?>
