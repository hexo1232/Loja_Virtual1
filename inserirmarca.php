<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_marca = trim($_POST['nome_marca']);
    $categorias = $_POST['categorias'] ?? [];

    // Verifica se a marca já existe
   $verificar = $conexao->prepare("SELECT * FROM marca WHERE nome_marca = ?");
$verificar->bind_param("s", $nome_marca);
$verificar->execute();
    $verifica_result = $verificar->get_result();

    if ($verifica_result->num_rows > 0) {
        echo "<p style='color:red;'>Esta marca já existe.</p>";
    } elseif (empty($categorias)) {
        echo "<p style='color:red;'>Selecione ao menos uma categoria.</p>";
    } else {
        // Insere a marca
        $inserir = $conexao->prepare("INSERT INTO marca (nome_marca) VALUES (?)");
$inserir->bind_param("s", $nome_marca);
$inserir->execute();

        $id_marca = $inserir->insert_id;

        // Associa a marca às categorias selecionadas
$associar = $conexao->prepare("INSERT IGNORE INTO categoria_marca (id_categoria, id_marca) VALUES (?, ?)");
        foreach ($categorias as $id_categoria) {
            $associar->bind_param("ii", $id_categoria, $id_marca);
            $associar->execute();
        }

        echo "<p style='color:green;'>Marca cadastrada com sucesso!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Nova Marca</title>
  
        <script src="logout_auto.js"></script>
    <style>
        body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f7fa;
  color: #333;
}
        body { font-family: Arial; padding: 20px; }
        label { display: block; margin-top: 10px; }
       

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
          padding: 20px;
               color: white;
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

       .texto {
            width: 40%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }

        .end {
  padding: 10px;

  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.end a { text-decoration:none; color:#fff;    }
.end:hover {
  background-color: #06926fff;
   cursor: pointer; 
    transform:scale(1.1);  
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

        select, textarea {
            width:100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }
    </style>
</head>
<body>

    <sidebar class="sidebar">
     
            <h2>Menu Admin</h2>
        
                        <a href="marcas.php">Voltar ás marcas </a>
            <a href="logout.php">Sair</a>
        </sidebar>

        <div class="conteudo">


  


     
    <form method="post">
          <h2>Cadastro de Nova Marca</h2>
        <label>Nome da Marca:</label>
        <input class="texto" type="text" name="nome_marca" required placeholder="Insira aqui o nome da Marca ">

        <label>Associe á Categoria(s):</label>
        <div class="categorias">
            <?php
           $categorias = $conexao->query("SELECT * FROM categoria ORDER BY nome_categoria");
            while ($cat = $categorias->fetch_assoc()) {
                echo "<label><input type='checkbox' name='categorias[]' value='{$cat['id_categoria']}'> {$cat['nome_categoria']}</label>";
            }
            ?>
        </div>

        <br>
        <button class="end" type="submit">Cadastrar Marca</button>
        
    </form>
        </div>
</body>
</html>
