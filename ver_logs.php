<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";



$usuario = $_SESSION['usuario'];
// Consulta os logs com nome do usuário
$sql = "
SELECT logs.id_log, logs.data_hora,perfil.nome_perfil as Perfil, 
   CONCAT(usuario.nome, ' ', usuario.apelido) AS nome_completo 
FROM usuario
INNER JOIN perfil ON usuario.idperfil = perfil.idperfil
INNER JOIN logs ON logs.id_usuário = usuario.id_usuário
ORDER BY logs.data_hora DESC
";

$result = $conexao->query($sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registros de Log</title>
     
        <script src="logout_auto.js"></script>
       
    <style>
       * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f7fa;
  color: #333;
}


 .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          width: 190px;
          height: 100%;
          background:  #0056b3;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
            color: white;
          flex-direction:column;
          gap:10px;
      }

   
.sidebar a:hover       {
   cursor: pointer; 
background-color:  #024185ff;   
 transform:scale(1.1);  

}

.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}


 
#tabela {
  width: 80%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

#tabela th,
#tabela td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: center;
}

#tabela th {
  background: #f4f4f4;
  font-weight: bold;
}



.conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }


        h2 {
            text-align: center;
        
        }



        </style>
</head>
<body>
<!-- Saudação-->


<sidebar class="sidebar">

        <h2>Menu Admin</h2>

        <a href="dashboard.php"> Voltar ao Menu Principal</a>
                
        <a href="logout.php">Sair</a>
          
    </sidebar>

<div class="conteudo">


  
   <h2>Histórico de Ações dos Usuários</h2>  <br>
    
    <table id="tabela">
        <tr>
            <th>ID do Log</th>
            <th>Data e Hora</th>
            <th>Usuário</th>
            <th>Perfil</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while($linha = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $linha["id_log"] . "</td>";
                echo "<td>" . $linha["data_hora"] . "</td>";
                echo "<td>" . htmlspecialchars($linha["nome_completo" ]) . "</td>";
                          echo "<td>" . $linha["Perfil"] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Nenhum log encontrado.</td></tr>";
        }
        ?>

    </table>
  
</div>

</body>
</html>

<?php
$conexao->close();
?>
