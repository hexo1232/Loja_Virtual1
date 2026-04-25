<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Tratamento do formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome_categoria = trim($_POST['nome_categoria']);
    $descricao_categoria = trim($_POST['descricao_categoria']);
    $marcas = $_POST['marcas'] ?? [];

    // Verifica se a categoria já existe
    $stmt = $conexao->prepare("SELECT id_categoria FROM categoria WHERE nome_categoria = ?");
    $stmt->bind_param("s", $nome_categoria);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<p style='color:red;'>Já existe uma categoria com esse nome.</p>";
    } else {
        // Insere a nova categoria
        $stmt = $conexao->prepare("INSERT INTO categoria (nome_categoria, descricao_categoria) VALUES (?, ?)");
$stmt->bind_param("ss", $nome_categoria, $descricao_categoria);
$stmt->execute();

$id_categoria = $stmt->insert_id;

// 2. Corrija o SELECT e o INSERT da tabela associativa (estavam Categoria_Marca)
foreach ($marcas as $id_marca) {
    $verifica = $conexao->prepare("SELECT * FROM categoria_marca WHERE id_categoria = ? AND id_marca = ?");
    $verifica->bind_param("ii", $id_categoria, $id_marca);
    $verifica->execute();
    $res_verifica = $verifica->get_result();

    if ($res_verifica->num_rows == 0) {
        $insere = $conexao->prepare("INSERT INTO categoria_marca (id_categoria, id_marca) VALUES (?, ?)");
        $insere->bind_param("ii", $id_categoria, $id_marca);
        $insere->execute();
    }

        }

        echo "<p style='color:green;'>Categoria cadastrada com sucesso!</p>";
        echo '<br><a href="categoria.php"><button>⬅ Voltar</button></a>';
         exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Categoria</title>
    
        <script src="logout_auto.js"></script>
    
    <style>
        
        
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

        button {
            width: 100%;
            padding: 12px;
            background-color: #00ff88ff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #06926fff;
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

     
   

    </style>
</head>
<body>
    <sidebar class="sidebar">
     
            <h2>Menu Admin</h2>
        
                        <a href="categoria.php">Voltar ás categorias </a>
            <a href="logout.php">Sair</a>
        </sidebar>
<div class="conteudo">

    <h2>Cadastro de Nova Categoria</h2>
    <form method="post">
        <label>Nome da Categoria:</label>
        <input type="text" name="nome_categoria" required><br><br>

        <label>Descrição:</label>
        <textarea name="descricao_categoria" required placeholder="Dê uma descrição breve"></textarea><br>

        <label>Marcas Associadas:</label><br>
        <?php
        $marcas = $conexao->query("SELECT * FROM marca");
        while ($marca = $marcas->fetch_assoc()) {
            echo "<label><input type='checkbox' name='marcas[]' value='{$marca['id_marca']}'> {$marca['nome_marca']}</label><br>";
        }
        ?>
        <br>
        <button type="submit">Cadastrar Categoria</button>
    </form>
    </div>
</body>
</html>
