<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

$usuario = $_SESSION['usuario'];


// Função para carregar cidades via província (usada por AJAX)
if (isset($_GET['ajax']) && $_GET['ajax'] == 'cidades') {
    if (!isset($_GET['provincia']) ) {
        exit;
    }

    $idprovincia = $_GET['provincia'];
 

    $sql = "SELECT idcidade, nome_cidade FROM cidade WHERE idprovíncia = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idprovincia);
    $stmt->execute();

    $result = $stmt->get_result();

    echo '<option value="">Cidade</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['idcidade'] . '">' . $row['nome_cidade'] . '</option>';
    }
    exit;
}
// Preenche selects
$cidades = $conexao->query("SELECT idcidade, nome_cidade FROM cidade");
$províncias = $conexao->query("SELECT idprovíncia, nome_província FROM provincia");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- PROCESSAMENTO DO FORMULÁRIO (atualização do usuário) ---
    $id = $_POST['id_fornecedor'];
    $nome = $_POST['nome_fornecedor'];
    $telefone=$_POST['telefone'];
    $email = $_POST['email'];
       $idcidade=$_POST['cidade'];
    $idprovincia=$_POST['provincia'];

  
       
                $stmt = $conexao->prepare("UPDATE fornecedor SET nome_fornecedor = ?, email = ?, telefone=?, idprovíncia=?,idcidade=? WHERE id_fornecedor = ?");
                

     $stmt->bind_param("sssiii", $nome, $email, $telefone, $idprovincia, $idcidade, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Fornecedor atualizado com sucesso!";
        } else {
            echo "Nenhuma alteração feita ou fornecedor inexistente.";
        }

        echo "<br><a href='fornecedores.php'>Voltar para a lista</a>";
        exit;
    }
 elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // --- EXIBIÇÃO DO FORMULÁRIO (busca do usuário) ---
    if (!isset($_GET['id_fornecedor'])) {
        die("ID do Fornecedor não foi informado.");
    }

    $idprov = $usuario['idprovíncia'];
$cidades_fornecedor = $conexao->prepare("SELECT idcidade, nome_cidade FROM cidade WHERE idprovíncia = ?");
$cidades_fornecedor->bind_param("i", $idprov);
$cidades_fornecedor->execute();
$cidades_resultado = $cidades_fornecedor->get_result();


    $id = $_GET['id_fornecedor'];

    $stmt = $conexao->prepare("SELECT * FROM fornecedor WHERE id_fornecedor = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if (!$usuario) {
        die("Fornecedor não encontrado.");
    }
?>

 <script>
        function carregarCidades() {
            const provincia = document.getElementById("provincia").value;

            if (!provincia) return;

            fetch(`?ajax=cidades&provincia=${provincia}`)
                .then(res => res.text())
                .then(data => document.getElementById("cidade").innerHTML = data)
                .catch(() => alert("Erro ao carregar cidades."));
        }

    </script>


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
               color: white;
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
<div class="conteudo">
<h2>Editar Usuário</h2>

<div class="sidebar">
        <h2>Menu Admin</h2>
        <a href="fornecedores.php">Voltar aos Fornecedores</a>
        <a href="logout.php">Sair</a>
    </div>


<form method="POST" action="">
  <input type="hidden" name="id_fornecedor" value="<?php echo $usuario['id_fornecedor']; ?>">
  
  <label>Nome:</label>
  <input type="text" name="nome_fornecedor" value="<?php echo htmlspecialchars($usuario['nome_fornecedor']); ?>"><br>


  <label>Telefone:</label>
  <input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>"><br>
  
  <label>Email:</label>
  <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>"><br>

  <label>Província:</label>
     <select name="provincia" id="provincia" onchange="carregarCidades()">
            <option value="">Província</option>
           <?php while ($p = $províncias->fetch_assoc()) { ?>
    <option value="<?= $p['idprovíncia'] ?>" <?= ($usuario['idprovíncia'] == $p['idprovíncia']) ? 'selected' : '' ?>>
        <?= $p['nome_província'] ?>
    </option>
<?php } ?>

        </select><br>

      <select name="cidade" id="cidade">
    <option value="">Cidade</option>
    <?php while ($c = $cidades_resultado->fetch_assoc()) { ?>
        <option value="<?= $c['idcidade'] ?>" <?= ($usuario['idcidade'] == $c['idcidade']) ? 'selected' : '' ?>>
            <?= $c['nome_cidade'] ?>
        </option>
    <?php } ?>
</select>






  <input class="editar" type="submit" value="Atualizar">
</form>
    </div>
<?php
} // fim do bloco GET
?>
