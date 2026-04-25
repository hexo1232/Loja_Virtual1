<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Categoria inválida.";
    exit;
}

$id_marca = intval($_GET['id']);

// Atualiza a categoria
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
   
    $categorias = $_POST['categorias'] ?? [];

    // Verifica duplicidade de nome (exceto o próprio)
    $verifica = $conexao->prepare("SELECT COUNT(*) FROM Marca WHERE nome_marca = ? AND id_marca != ?");
    $verifica->bind_param("si", $nome, $id_marca);
    $verifica->execute();
    $verifica->bind_result($existe);
    $verifica->fetch();
    $verifica->close();

    if ($existe > 0) {
        echo "<p style='color:red;'>Já existe uma Marca com esse nome.</p>";
    } else {
        // Atualiza nome e descrição
        $stmt = $conexao->prepare("UPDATE Marca SET nome_marca = ? WHERE id_marca = ?");
        $stmt->bind_param("si", $nome, $id_marca);
        $stmt->execute();

        // Atualiza marcas associadas
        $conexao->query("DELETE FROM Categoria_Marca WHERE id_marca = $id_marca");

        foreach ($categorias as $id_categoria) {
            $insere = $conexao->prepare("INSERT INTO Categoria_Marca (id_marca, id_categoria) VALUES (?, ?)");
            $insere->bind_param("ii", $id_marca, $id_categoria);
            $insere->execute();
        }

        echo "<p style='color:green;'>Marca atualizada com sucesso!</p>";
    }
}

// Busca dados da categoria
$stmt = $conexao->prepare("SELECT * FROM Marca WHERE id_marca = ?");
$stmt->bind_param("i", $id_marca);
$stmt->execute();
$result = $stmt->get_result();
$marca = $result->fetch_assoc();

// Marcas associadas
$categorias_associadas = [];
$res = $conexao->query("SELECT id_categoria FROM Categoria_Marca WHERE id_marca = $id_marca");
while ($linha = $res->fetch_assoc()) {
    $categorias_associadas[] = $linha['id_categoria'];
}

// Lista de todas as marcas
$categorias = $conexao->query("SELECT * FROM Categoria");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoria</title>

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
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #004a99;
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
            color: white;
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
          <h2>Editar marca</h2>
        <label>Nome da Marca:</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($marca['nome_marca']) ?>" required><br><br>


        <label>Categorias Associadas:</label><br>
        <?php while ($c = $categorias->fetch_assoc()): ?>
            <label>
                <input type="checkbox" name="categorias[]" value="<?= $c['id_categoria'] ?>" <?= in_array($c['id_categoria'], $categorias_associadas) ? 'checked' : '' ?>>
                <?= htmlspecialchars($c['nome_categoria']) ?>
            </label><br>
        <?php endwhile; ?><br>

        <button type="submit">Salvar</button>
    
    </form>
        </div>
</body>
</html>
