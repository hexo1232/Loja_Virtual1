<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID de categoria inválido.";
    exit;
}

$id_categoria = intval($_GET['id']);

// Verifica se a categoria existe
$verificar = $conexao->prepare("SELECT * FROM Categoria WHERE id_categoria = ?");
$verificar->bind_param("i", $id_categoria);
$verificar->execute();
$resultado = $verificar->get_result();

if ($resultado->num_rows === 0) {
    echo "Categoria não encontrada.";
    exit;
}

// Verifica se existem produtos associados à categoria
$produtos = $conexao->prepare("SELECT id_produto, nome_produto FROM produto WHERE id_categoria = ?");
$produtos->bind_param("i", $id_categoria);
$produtos->execute();
$res_produtos = $produtos->get_result();

if ($res_produtos->num_rows > 0) {
    echo "<h3 style='color: red;'>❌ Não é possível excluir esta categoria!</h3>";
    echo "<p>Existem produtos cadastrados nesta categoria. Para excluir a categoria, é necessário remover ou alterar a categoria desses produtos.</p>";
    
    echo "<h4>🛒 Produtos associados:</h4>";
    echo "<ul>";
    while ($produto = $res_produtos->fetch_assoc()) {
        echo "<li><strong>{$produto['nome_produto']}</strong> (ID: {$produto['id_produto']})</li>";
    }
    echo "</ul>";
    
    echo '<br><a href="categoria.php"><button>⬅ Voltar</button></a>';
    exit;
}

// Remove vinculações com marcas (sem apagar as marcas)
$remover_vinculos = $conexao->prepare("DELETE FROM Categoria_Marca WHERE id_categoria = ?");
$remover_vinculos->bind_param("i", $id_categoria);
$remover_vinculos->execute();

// Exclui a categoria
$excluir_categoria = $conexao->prepare("DELETE FROM Categoria WHERE id_categoria = ?");
$excluir_categoria->bind_param("i", $id_categoria);

if ($excluir_categoria->execute()) {
    echo "<p style='color: green;'>✅ Categoria excluída com sucesso!</p>";
} else {
    echo "<p style='color: red;'>Erro ao excluir categoria: " . $excluir_categoria->error . "</p>";
}

echo '<br><a href="categoria.php"><button>⬅ Voltar para a lista</button></a>';
?>
