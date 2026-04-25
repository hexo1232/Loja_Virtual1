<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// 🔄 AJAX: Carregar cidades da província
if (isset($_GET['ajax']) && $_GET['ajax'] === 'cidades') {
    $idprovincia = $_GET['provincia'] ?? null;

    if (!$idprovincia) exit;

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

// 🔽 Carrega as províncias para o <select>
$províncias = $conexao->query("SELECT idprovíncia, nome_província FROM provincia");

// 🧾 Processa o formulário de cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Campos do formulário
    $nome = htmlspecialchars(trim($_POST['nome_fornecedor']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telefone = htmlspecialchars(trim($_POST['telefone']));
 
  
    $idcidade = $_POST['cidade'];
    $idprovincia = $_POST['provincia'];

    // Validações
    if (empty($nome) || empty($email) || empty($telefone) || empty($idcidade) || empty($idprovincia)) {
        echo "Todos os campos são obrigatórios!";
    }  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email inválido.";
    } else {
        

        $stmt = $conexao->prepare("INSERT INTO fornecedor (nome_fornecedor, email, telefone, idprovíncia, idcidade) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $nome, $email, $telefone, $idprovincia, $idcidade);

        if ($stmt->execute()) {
            echo "Fornecedor <b>$nome </b> cadastrado com sucesso!";
        } else {
            echo "Erro ao cadastrar: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Fornecedor</title>
    <link rel="stylesheet" href="style.css">

        <script src="logout_auto.js"></script>
  

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
          background: #0056b3;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
          flex-direction:column;
          gap:10px;
      }
 .sidebar a:hover  {
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
        <a href="fornecedores.php"> Voltar aos fornecedores</a>
        <a href="logout.php">Sair</a>
    </sidebar>

    <div class="conteudo">    
    <h2>Cadastro de Novo Fornecedor</h2>
    <form method="post" action="">
        <label>Nome do fornecedor:</label>
        <input type="text" name="nome_fornecedor" required><br>

    <label>Email:</label>
        <input type="email" name="email" required><br>
   

        <label>Telefone:</label>
        <input type="text" name="telefone" required placeholder="84/87/83 *******"><br>

     

    

        <label>Província:</label>
        <select name="provincia" id="provincia" onchange="carregarCidades()" required>
            <option value="">Província</option>
            <?php while ($p = $províncias->fetch_assoc()) { ?>
                <option value="<?= $p['idprovíncia'] ?>"><?= $p['nome_província'] ?></option>
            <?php } ?>
        </select><br>

        <label>Cidade:</label>
        <select name="cidade" id="cidade" required>
            <option value="">Selecione a Província Primeiro</option>
        </select><br>

    

        <button class="cadastrar" type="submit">Cadastrar Fornecedor</button>
    </form>
            </div>
</body>
</html>
