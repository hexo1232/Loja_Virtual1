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
    $id = $_POST['id_usuário'];
    $nome = $_POST['nome'];
    $apelido = $_POST['apelido'];
    $telefone=$_POST['telefone'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $conf = $_POST['conf'];
    $opc = $_POST['opcao'];
    $idcidade=$_POST['cidade'];
    $idprovincia=$_POST['provincia'];

    if ($senha != $conf) {
        echo "A senha e a confirmação não coincidem";
    } else {
        switch ($opc) {
            case 'Funcionário':
                $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, apelido = ?, telefone=?, email = ?, senha_hash = ?, idprovíncia=?,idcidade=?, idperfil = 2 WHERE id_usuário = ?");
                break;
            case 'Cliente':
                $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, apelido = ?, telefone=?, email = ?, senha_hash = ?, idprovíncia=?,idcidade=?, idperfil = 3 WHERE id_usuário = ?");
                break;
            default:
                echo "Erro! Perfil inválido.";
                exit;
        }

     $stmt->bind_param("ssissiii", $nome, $apelido, $telefone, $email, $senha, $idprovincia, $idcidade, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Usuário atualizado com sucesso!";
               
        } else {
            echo "Nenhuma alteração feita ou usuário inexistente.";
        }

        header("Location:usuarios.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // --- EXIBIÇÃO DO FORMULÁRIO (busca do usuário) ---
    if (!isset($_GET['id_usuário'])) {
        die("ID do usuário não foi informado.");
    }

    $idprov = $usuario['idprovíncia'];
$cidades_usuario = $conexao->prepare("SELECT idcidade, nome_cidade FROM cidade WHERE idprovíncia = ?");
$cidades_usuario->bind_param("i", $idprov);
$cidades_usuario->execute();
$cidades_resultado = $cidades_usuario->get_result();


    $id = $_GET['id_usuário'];

    $stmt = $conexao->prepare("SELECT * FROM usuario WHERE id_usuário = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if (!$usuario) {
        die("Usuário não encontrado.");
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

<sidebar class="sidebar">
        <h2>Menu Admin</h2>
        <a href="usuarios.php">Voltar aos Usuários</a>
        <a href="logout.php">Sair</a>
    </sidebar>


<form method="POST" action="">
  <input type="hidden" name="id_usuário" value="<?php echo $usuario['id_usuário']; ?>">
  
  <label>Nome:</label>
  <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>"><br>
  
  <label>Apelido:</label>
  <input type="text" name="apelido" value="<?php echo htmlspecialchars($usuario['apelido']); ?>"><br>

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
    <option value="">Selecione a Província primeiro</option>
    <?php while ($c = $cidades_resultado->fetch_assoc()) { ?>
        <option value="<?= $c['idcidade'] ?>" <?= ($usuario['idcidade'] == $c['idcidade']) ? 'selected' : '' ?>>
            <?= $c['nome_cidade'] ?>
        </option>
    <?php } ?>
</select>



  
  <label>Senha:</label>
  <input type="password" name="senha" value="<?php echo htmlspecialchars($usuario['senha_hash']); ?>"><br>
  
  <label>Confirmação de Senha:</label>
  <input type="password" name="conf"><br>


  
  <label>Perfil:</label>
  <select name="opcao">
    <option value="Funcionário" <?php if ($usuario['idperfil'] == 2) echo 'selected'; ?>>Funcionário</option>
    <option value="Cliente" <?php if ($usuario['idperfil'] == 3) echo 'selected'; ?>>Cliente</option>
  </select><br><br>

  <input class="editar" type="submit" value="Atualizar">
</form>
    </div>
<?php
} // fim do bloco GET
?>
