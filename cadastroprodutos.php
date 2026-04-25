<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Inserção do produto e imagens
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
// Responde à requisição AJAX de marcas
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao']) && $_POST['acao'] == 'carregar_marcas') {
    $id_categoria = $_POST['id_categoria'] ?? 0;

    $sql = "SELECT m.id_marca, m.nome_marca
            FROM Categoria_Marca cm
            JOIN Marca m ON cm.id_marca = m.id_marca
            WHERE cm.id_categoria = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();

    $resultado = $stmt->get_result();
    echo '<option value="">Selecione</option>';
    while ($row = $resultado->fetch_assoc()) {
        echo "<option value='{$row['id_marca']}'>{$row['nome_marca']}</option>";
    }
    exit;
}
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $quantidade = $_POST['quantidade'];
    $id_categoria = $_POST['categoria'];
    $id_marca = $_POST['marca'];
    $id_fornecedor = $_POST['fornecedor'];

    $stmt = $conexao->prepare("INSERT INTO produto (nome_produto, descricao, preco, quantidade_estoque, id_categoria, id_marca, id_fornecedor) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiiii", $nome, $descricao, $preco, $quantidade, $id_categoria, $id_marca, $id_fornecedor);
    $stmt->execute();

    $id_produto = $stmt->insert_id;

    // Upload das imagens
    foreach ($_FILES['imagens']['tmp_name'] as $index => $tmp_name) {
        $nome_arquivo = basename($_FILES['imagens']['name'][$index]);
        $destino = "uploads/" . time() . "_" . $nome_arquivo;

        if (move_uploaded_file($tmp_name, $destino)) {
            $legenda = $_POST['legenda'][$index] ?? '';
            $imagem_principal = (isset($_POST['imagem_principal']) && $_POST['imagem_principal'] == $index) ? 1 : 0;

            $stmt_img = $conexao->prepare("INSERT INTO produto_imagem (id_produto, caminho_imagem, legenda, imagem_principal)
                                           VALUES (?, ?, ?, ?)");
            $stmt_img->bind_param("issi", $id_produto, $destino, $legenda, $imagem_principal);
            $stmt_img->execute();
        }
    }

    echo "Produto cadastrado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produto</title>
    <script src="logout_auto.js"></script>
    <script>
        function carregarMarcas() {
            var categoria = document.getElementById('categoria').value;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'acao=carregar_marcas&id_categoria=' + categoria
            })
            .then(res => res.text())
            .then(html => document.getElementById('marca').innerHTML = html);
        }

        function adicionarCampoImagem() {
            const container = document.getElementById('imagens-container');
            const index = container.children.length;

            const div = document.createElement('div');
            div.innerHTML = `
                <input type="file" name="imagens[]" required>
                <input type="text" name="legenda[]" placeholder="Legenda da imagem">
                <label>
                    Principal?
                    <input type="radio" name="imagem_principal" value="${index}">
                </label>
                <br><br>
            `;
            container.appendChild(div);
        }
    </script>

    <style>
        body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f7fa;
  color: #333;
}
        
        form {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, select, textarea {
            width:100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #004a99;
            transform:scale(1.1);  
        }
   h2 {
            text-align: center;
            color: #333;
        }

        
 .sidebar {
          position: fixed;
          left: 0;
          top: 0;
       width: 190px;
          height: 100%;
          background: #0056b3;
               color: white;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
          flex-direction:column;
          gap:10px;
      }
.sidebar a:hover {
   cursor: pointer; 
background-color:  #024185ff; 
   transform:scale(1.1);  

}


.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}


    .conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }

     
   
.cadastrar {
  padding: 10px;
   
  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.cadastrar a { text-decoration:none; color:#fff;
      }
.cadastrar:hover {
  background-color: #06926fff;
   cursor: pointer; 
      transform:scale(1.1);
}
    </style>

</head>
<body>
    
    <sidebar class="sidebar">
                  <h2>Menu Admin</h2>
        
            <a href="gerenciarprodutos.php">Voltar aos Produtos</a>
            
            <a href="logout.php">Sair</a>
        </sidebar>

        <div class="conteudo">
            <h2>Cadastrar Produto</h2>
        
    <form method="post" enctype="multipart/form-data">
        <label>Nome:</label><input type="text" name="nome" required><br>
        <label>Descrição:</label><textarea name="descricao" required placeholder="Caracterize o Produto por completo"></textarea><br>
        <label>Preço:</label><input type="number" step="0.01" name="preco" required><br>
        <label>Quantidade:</label><input type="number" name="quantidade" required><br>

        <label>Categoria:</label>
        <select name="categoria" id="categoria" onchange="carregarMarcas()" required>
            <option value="">Selecione</option>
            <?php
            $categorias = $conexao->query("SELECT * FROM Categoria");
            while ($cat = $categorias->fetch_assoc()) {
                echo "<option value='{$cat['id_categoria']}'>{$cat['nome_categoria']}</option>";
            }
            ?>
        </select><br>

        <label>Marca:</label>
        <select name="marca" id="marca" required>
            <option value="">Selecione uma categoria primeiro</option>
        </select><br>

        <label>Fornecedor:</label>
        <select name="fornecedor" required>
            <option value="">Selecione</option>
            <?php
            $fornecedores = $conexao->query("SELECT * FROM Fornecedor");
            while ($f = $fornecedores->fetch_assoc()) {
                echo "<option value='{$f['id_fornecedor']}'>{$f['nome_fornecedor']}</option>";
            }
            ?>
        </select><br>

        <h4>Imagens do Produto</h4>
        <div id="imagens-container"></div>
        <button type="button" onclick="adicionarCampoImagem()">+ Adicionar Imagem</button><br><br>

        <button class="cadastrar" type="submit">Cadastrar Produto</button>
    </form>
        </div>
</body>
</html>
<?php
include "conexao.php";

