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

// 🔽 Carrega as províncias
$províncias = $conexao->query("SELECT idprovíncia, nome_província FROM provincia");

// 🔐 Lógica principal
$mensagem = "";
$redirecionar = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars(trim($_POST['nome']));
    $apelido = htmlspecialchars(trim($_POST['apelido']));
    $numero = htmlspecialchars(trim($_POST['numero']));
    $email = htmlspecialchars(trim($_POST['email']));
    $senha = trim($_POST['senha']);
    $conf = trim($_POST['conf']);
    $opc = $_POST['opcao'];
    $idcidade = $_POST['cidade'];
    $idprovincia = $_POST['provincia'];

    if (empty($nome) || empty($apelido) || empty($numero) || empty($email) || empty($senha) || empty($conf) || empty($opc) || empty($idcidade) || empty($idprovincia)) {
        $mensagem = "⚠️ Todos os campos são obrigatórios!";
    } elseif ($senha !== $conf) {
        $mensagem = "❌ A senha e a confirmação não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "❌ Email inválido.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,}$/', $senha)) {
        $mensagem = "❌ A senha deve ter pelo menos 6 caracteres, uma letra maiúscula, uma minúscula e um número.";
    } else {
        // Verificar duplicidade de email
        $verificar = $conexao->prepare("SELECT id_usuário FROM usuario WHERE email = ?");
        $verificar->bind_param("s", $email);
        $verificar->execute();
        $resultado = $verificar->get_result();

        if ($resultado->num_rows > 0) {
            $mensagem = "❌ Este e-mail já está cadastrado.";
        } else {
            // Conversor: caso a senha não seja hash (evita texto puro)
            if (password_get_info($senha)['algoName'] === 'unknown') {
                $senha = password_hash($senha, PASSWORD_DEFAULT);
            }

            $perfil = ($opc === "Funcionário") ? 2 : 3;

            $stmt = $conexao->prepare("INSERT INTO usuario (nome, apelido, telefone, email, senha_hash, idprovíncia, idcidade, idperfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $nome, $apelido, $numero, $email, $senha, $idprovincia, $idcidade, $perfil);

            if ($stmt->execute()) {
                $mensagem = "✅ Usuário <b>$nome $apelido</b> cadastrado com sucesso! Redirecionando...";
                $redirecionar = true;
            } else {
                $mensagem = "❌ Erro ao cadastrar: " . $stmt->error;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Usuário</title>
    
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


body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

 .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          width: 190px;
          height: 100%;
          background:  #0056b3;; 
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
          flex-direction:column;
          gap:10px;
      }

.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}

.conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

        input, select {
            width: 100%;
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

                 a{ text-decoration:none; 
      }
a:hover {
   cursor: pointer; 
background-color:  #024185ff;   
}
</style>

</head>
<body>
  

    <sidebar class="sidebar">
        <h2>Menu Admin</h2>
        <a href="usuarios.php">Voltar aos Usuários</a>
        <a href="logout.php">Sair</a>
    </sidebar>

<div class="conteudo">

    <form method="post" action="">
        
    <h2>Cadastro de Novo Usuário</h2>
        <label>Nome:</label>
        <input type="text" name="nome" required><br>

        <label>Apelido:</label>
        <input type="text" name="apelido" required><br>

        <label>Telefone:</label>
        <input type="text" name="numero" required placeholder="84/87/83 *******"><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Senha:</label>
        <input type="password" name="senha" required><br>

        <label>Confirme a Senha:</label>
        <input type="password" name="conf" required><br>

        <label>Província:</label>
        <select name="provincia" id="provincia" onchange="carregarCidades()" required>
            <option value="">Selecione a Província</option>
            <?php while ($p = $províncias->fetch_assoc()) { ?>
                <option value="<?= $p['idprovíncia'] ?>"><?= $p['nome_província'] ?></option>
            <?php } ?>
        </select><br>

        <label>Cidade:</label>
        <select name="cidade" id="cidade" required>
            <option value="">Cidade</option>
        </select><br>

        <label>Perfil:</label>
        <select name="opcao" required>
            <option value="Funcionário">Funcionário</option>
            <option value="Cliente">Cliente</option>
        </select><br><br>

        <button type="submit">Cadastrar</button>
    </form>
            </div>


            <?php if (!empty($mensagem)): ?>
    <div style="max-width: 600px; margin: 20px auto; padding: 15px; background: <?= $redirecionar ? '#d4edda' : '#f8d7da' ?>; color: <?= $redirecionar ? '#155724' : '#721c24' ?>; border-radius: 8px; font-weight: bold; text-align: center;">
        <?= $mensagem ?>
    </div>

    <?php if ($redirecionar): ?>
        <script>
            setTimeout(() => {
                window.location.href = 'usuarios.php';
            }, 3000);
        </script>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
