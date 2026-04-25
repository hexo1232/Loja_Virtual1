<?php
//editarproduto.php
ob_start();
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";
include "cloudinary_helper.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Produto inválido.";
    exit;
}

$id_produto = intval($_GET['id']);
$mensagem   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome         = $_POST['nome'];
    $descricao    = $_POST['descricao'];
    $preco        = $_POST['preco'];
    $quantidade   = $_POST['quantidade'];
    $id_categoria = $_POST['categoria'];
    $id_marca     = $_POST['marca'];
    $id_fornecedor= $_POST['fornecedor'];

    $stmt = $conexao->prepare("UPDATE produto SET nome_produto=?, descricao=?, preco=?, quantidade_estoque=?, id_categoria=?, id_marca=?, id_fornecedor=? WHERE id_produto=?");
    $stmt->bind_param("ssdiiiii", $nome, $descricao, $preco, $quantidade, $id_categoria, $id_marca, $id_fornecedor, $id_produto);
    $stmt->execute();

    $houveAlteracao = $stmt->affected_rows > 0; // inicialização correcta

 if (isset($_POST['imagem_principal']) && intval($_POST['imagem_principal']) > 0) {
    $conexao->query("UPDATE produto_imagem SET imagem_principal = 0 WHERE id_produto = $id_produto");
    $img_principal = intval($_POST['imagem_principal']);
    $conexao->query("UPDATE produto_imagem SET imagem_principal = 1 WHERE id_imagem = $img_principal AND id_produto = $id_produto");
    $houveAlteracao = true;
}

foreach ($_FILES['imagens']['tmp_name'] as $index => $tmp_name) {
    if ($_FILES['imagens']['error'][$index] === UPLOAD_ERR_OK && !empty($tmp_name)) {
        $url_imagem = enviarParaCloudinary($tmp_name);

        if ($url_imagem) {
            $legenda = $_POST['legenda'][$index] ?? '';
            $imagem_principal = 0;

            // Salvamos a URL permanente no banco
            $stmt_img = $conexao->prepare("INSERT INTO produto_imagem (id_produto, caminho_imagem, legenda, imagem_principal) VALUES (?, ?, ?, ?)");
            $stmt_img->bind_param("issi", $id_produto, $url_imagem, $legenda, $imagem_principal);
            $stmt_img->execute();
            $houveAlteracao = true;
        }
    }
}

    if ($houveAlteracao) {
      header("Location: gerenciarprodutos.php?msg=atualizado&tipo=success");
exit;
    } elseif (!empty($_GET['imagemRemovida'])) {
        $mensagem = "🖼️ Imagem removida com sucesso!";
    } else {
        $mensagem = "ℹ️ Nenhuma modificação foi feita.";
    }
}

$stmt = $conexao->prepare("SELECT * FROM produto WHERE id_produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

$imagens = $conexao->query("SELECT * FROM produto_imagem WHERE id_produto = $id_produto");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto</title>
        <script src="logout_auto.js"></script>
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

       #remove {
  padding: 10px;
       width:40%;
       margin:0 auto;
  background-color: #ee0000ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
#remove a { text-decoration:none; color:#fff;    }
#remove:hover {
  background-color: #7e0e0eff;
   cursor: pointer; 
   transform:scale(1.1);
}

   
.editar {
  padding: 10px;
   
  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.editar a { text-decoration:none; color:#fff;
      }
.editar:hover {
  background-color: #06926fff;
   cursor: pointer; 
   transform:scale(1.1);
}

 .mensagem {
            max-width: 500px;
            margin: 20px auto;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }

        .mensagem.success {
            background-color: #d4edda;
            color: #155724;
        }

        .mensagem.error {
            background-color: #f8d7da;
            color: #721c24;
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
      <?php if ($mensagem): ?>
        <div class="mensagem <?= str_contains($mensagem, '✅') ? 'success' : 'error' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>
    
    <h2>Editar Produto</h2>

    <form method="post" enctype="multipart/form-data">
        <label>Nome:</label><input type="text" name="nome" value="<?= htmlspecialchars($produto['nome_produto']) ?>" required><br>
        <label>Descrição:</label><textarea name="descricao" required><?= htmlspecialchars($produto['descricao']) ?></textarea><br>
        <label>Preço:</label><input type="number" step="0.01" name="preco" value="<?= $produto['preco'] ?>" required><br>
        <label>Quantidade:</label><input type="number" name="quantidade" value="<?= $produto['quantidade_estoque'] ?>" required><br>

        <label>Categoria:</label>
        <select name="categoria" id="categoria" onchange="carregarMarcas()" required>
            <?php
            $categorias = $conexao->query("SELECT * FROM categoria");
            while ($cat = $categorias->fetch_assoc()) {
                $selected = ($cat['id_categoria'] == $produto['id_categoria']) ? "selected" : "";
                echo "<option value='{$cat['id_categoria']}' $selected>{$cat['nome_categoria']}</option>";
            }
            ?>
        </select><br>

        <label>Marca:</label>
        <select name="marca" id="marca" required>
            <?php
            $marcas = $conexao->query("SELECT * FROM marca");
            while ($m = $marcas->fetch_assoc()) {
                $selected = ($m['id_marca'] == $produto['id_marca']) ? "selected" : "";
                echo "<option value='{$m['id_marca']}' $selected>{$m['nome_marca']}</option>";
            }
            ?>
        </select><br>

        <label>Fornecedor:</label>
        <select name="fornecedor" required>
            <?php
            $fornecedores = $conexao->query("SELECT * FROM fornecedor");
            while ($f = $fornecedores->fetch_assoc()) {
                $selected = ($f['id_fornecedor'] == $produto['id_fornecedor']) ? "selected" : "";
                echo "<option value='{$f['id_fornecedor']}' $selected>{$f['nome_fornecedor']}</option>";
            }
            ?>
        </select><br>

        <h4>Imagens Existentes</h4>
        <?php while ($img = $imagens->fetch_assoc()): ?>
            <div>
                <img src="<?= $img['caminho_imagem'] ?>" alt="Imagem" width="100"><br>
                <label>Legenda: <?= htmlspecialchars($img['legenda']) ?></label><br>
                <label>
                    Principal?
                    <input type="radio" name="imagem_principal" value="<?= $img['id_imagem'] ?>" <?= $img['imagem_principal'] ? 'checked' : '' ?>>
                </label><br>
                  <a href="remover_imagem.php?id_imagem=<?= $img['id_imagem'] ?>&id_produto=<?= $id_produto ?>" 
   onclick="return confirm('Deseja remover esta imagem?')">
   <button type="button" id="remove">Remover a imagem</button>
</a>

                <hr>
            </div>
        <?php endwhile; ?>

        <h4>Adicionar Novas Imagens</h4>
        <div id="novas-imagens">
            <input type="file" name="imagens[]">
            <input type="text" name="legenda[]" placeholder="Legenda da nova imagem"><br>
        </div>
        <button type="button" onclick="adicionarCampoImagem()">+ Adicionar mais imagens</button><br><br>

        <button class="editar" type="submit">Atualizar Produto</button>
    </form>
    </div>

    <script>
        function adicionarCampoImagem() {
            const container = document.getElementById('novas-imagens');
            const index = container.children.length;

            const div = document.createElement('div');
            div.innerHTML = `
                <input type="file" name="imagens[]">
                <input type="text" name="legenda[]" placeholder="Legenda da nova imagem"><br>
            `;
            container.appendChild(div);
        }

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
    </script>

</body>
</html>
