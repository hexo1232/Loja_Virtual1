<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Categoria inválida.";
    exit;
}

$id_categoria = intval($_GET['id']);

// Atualiza a categoria
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $marcas = $_POST['marcas'] ?? [];

    // Verifica duplicidade de nome (exceto o próprio)
    $verifica = $conexao->prepare("SELECT COUNT(*) FROM Categoria WHERE nome_categoria = ? AND id_categoria != ?");
    $verifica->bind_param("si", $nome, $id_categoria);
    $verifica->execute();
    $verifica->bind_result($existe);
    $verifica->fetch();
    $verifica->close();

    if ($existe > 0) {
        echo "<p style='color:red;'>Já existe uma categoria com esse nome.</p>";
    } else {
        // Atualiza nome e descrição
        $stmt = $conexao->prepare("UPDATE Categoria SET nome_categoria = ?, descricao_categoria = ? WHERE id_categoria = ?");
        $stmt->bind_param("ssi", $nome, $descricao, $id_categoria);
        $stmt->execute();

        // Atualiza marcas associadas
        $conexao->query("DELETE FROM Categoria_Marca WHERE id_categoria = $id_categoria");

        foreach ($marcas as $id_marca) {
            $insere = $conexao->prepare("INSERT INTO Categoria_Marca (id_categoria, id_marca) VALUES (?, ?)");
            $insere->bind_param("ii", $id_categoria, $id_marca);
            $insere->execute();
        }

        echo "<p style='color:green;'>Categoria atualizada com sucesso!</p>";
    }
}

// Busca dados da categoria
$stmt = $conexao->prepare("SELECT * FROM Categoria WHERE id_categoria = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$result = $stmt->get_result();
$categoria = $result->fetch_assoc();

// Marcas associadas
$marcas_associadas = [];
$res = $conexao->query("SELECT id_marca FROM Categoria_Marca WHERE id_categoria = $id_categoria");
while ($linha = $res->fetch_assoc()) {
    $marcas_associadas[] = $linha['id_marca'];
}

// Lista de todas as marcas
$marcas = $conexao->query("SELECT * FROM Marca");
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
    </style>
</head>
<body>

<sidebar class="sidebar">
     
            <h2>Menu Admin</h2>
        
                        <a href="categoria.php">Voltar ás categorias </a>
            <a href="logout.php">Sair</a>
        </sidebar>

        <div class="conteudo">

    <h2>Editar Categoria</h2>
    <form method="post">
        <label>Nome da Categoria:</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($categoria['nome_categoria']) ?>" required><br><br>

        <label>Descrição:</label><br>
        <textarea name="descricao" required><?= htmlspecialchars($categoria['descricao_categoria']) ?></textarea><br><br>

        <label>Marcas Associadas:</label><br>
        <?php while ($m = $marcas->fetch_assoc()): ?>
            <label>
                <input type="checkbox" name="marcas[]" value="<?= $m['id_marca'] ?>" <?= in_array($m['id_marca'], $marcas_associadas) ? 'checked' : '' ?>>
                <?= htmlspecialchars($m['nome_marca']) ?>
            </label><br>
        <?php endwhile; ?><br>

        <button type="submit">Salvar</button>
       
    </form>
        </div>
</body>
</html>
