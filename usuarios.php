<?php 
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";


$usuario = $_SESSION['usuario'];
// Parâmetros GET
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'nome';
$ordem = (isset($_GET['ordem']) && $_GET['ordem'] === 'desc') ? 'DESC' : 'ASC';

// Validar campo de ordenação
$colunas_validas = ['nome', 'apelido'];
if (!in_array($ordenar_por, $colunas_validas)) {
    $ordenar_por = 'nome';
}

// Paginação
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Condição base
$condicao = "vw_usuarios_com_detalhes.idperfil IN (2,3)";
$tipos = "";
$parametros = [];
$sql_where = $condicao;

if (!empty($pesquisa)) {
    $sql_where .= " AND (nome LIKE ? OR apelido LIKE ?)";
    $tipos = "ss";
    $parametros[] = "%$pesquisa%";
    $parametros[] = "%$pesquisa%";
}

// Contagem total de registros
$sql_total = "SELECT COUNT(*) AS total FROM vw_usuarios_com_detalhes WHERE $sql_where";

$stmt_total = $conexao->prepare($sql_total);
if (!empty($pesquisa)) {
    $stmt_total->bind_param($tipos, ...$parametros);
}
$stmt_total->execute();
$resultado_total = $stmt_total->get_result();
$total_registros = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);
$stmt_total->close();

// Consulta de dados
$sql = "SELECT * FROM vw_usuarios_com_detalhes WHERE $sql_where ORDER BY $ordenar_por $ordem LIMIT $limite OFFSET $offset";
$stmt = $conexao->prepare($sql);
if (!empty($pesquisa)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
   
        <script src="logout_auto.js"></script>

<style>


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

.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}


.card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
margin-left:230px;
width:80%;
}

.card h3 {
  margin-bottom: 10px;
}
.conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }

      
#tabela-user {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

#tabela-user th,
#tabela-user td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: center;
}

#tabela-user th {
  background: #f4f4f4;
  font-weight: bold;
}




#end {
  padding: 10px;

  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
#end a { text-decoration:none; color:#fff;
      }
#end:hover {
  background-color: #06926fff;
   cursor: pointer;
     transform:scale(1.1); 
}
  #remove {
  padding: 10px;
 
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

#busca {
  padding: 10px;

  background-color: #f1bf1bff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
#busca a { text-decoration:none; color:#fff;    }
#busca:hover {
  background-color: #cc9d01ff;
   cursor: pointer; 
     transform:scale(1.1);
}
  
 #texto {
            width: 15%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }
          a{ text-decoration:none; 
      }

.sidebar a:hover       {
   cursor: pointer; 
background-color:  #024185ff;   

}

</style>
</head>
<body>
<sidebar class="sidebar">
   
        <h2>Menu Admin</h2>
        <a href="dashboard.php">Voltar ao Menu Principal</a>
            <a href="admin_cadastrousuário.php">Cadastrar novo usuário</a>
               
        <a href="logout.php">Sair</a>
  
</sidebar>

<div class="conteudo">
    

    <h3>Buscar Usuários</h3>
    <form method="GET" action="">
        <input type="search" id="texto" name="pesquisa" placeholder="Nome ou apelido" value="<?php echo htmlspecialchars($pesquisa); ?>">
        <input  type="submit" id="busca" value="Buscar">
    </form>

    <p>Ordenar por:
        <a href="?pesquisa=<?= urlencode($pesquisa) ?>&ordenar_por=nome&ordem=<?= ($ordenar_por == 'nome' && $ordem == 'ASC') ? 'desc' : 'asc'; ?>">Nome</a> |
        <a href="?pesquisa=<?= urlencode($pesquisa) ?>&ordenar_por=apelido&ordem=<?= ($ordenar_por == 'apelido' && $ordem == 'ASC') ? 'desc' : 'asc'; ?>">Apelido</a>
    </p>

    <h3>Total de Usuários Encontrados: <?= $total_registros ?></h3>
<table id="tabela-user" border="">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Apelido</th>
            <th>Telefone</th>
            <th>Email</th>
            <th>Província</th>
            <th>Cidade</th>
            
            <th>Perfil</th>
            <th>Ação</th>
        </tr>
        </thead>
        <tbody>
       <?php if ($resultado->num_rows > 0): ?>
         <?php while ($linha = $resultado->fetch_assoc()): ?>
           <tr>
    <td><?= $linha['id_usuário'] ?></td>
    <td><?= $linha['nome'] ?></td>
    <td><?= $linha['apelido'] ?></td>
    <td><?= $linha['telefone'] ?></td>
    <td><?= $linha['email'] ?></td>
    <td><?= $linha['provincia_nome'] ?></td>
    <td><?= $linha['cidade_nome'] ?></td>
    <td><?= $linha['perfil_nome'] ?></td>
    <td>
     
            <a href='editarusuario.php?id_usuário=<?= $linha['id_usuário'] ?>'><button id="end">Editar</button></a> 
                <a href='excluirusuário.php?id_usuário=<?= $linha['id_usuário'] ?>' 
                onclick="return confirm('Tem certeza que deseja excluir?');"><button id="remove">Excluir</button></a>
            
    </td>
</tr>
 <?php endwhile; ?>
    <?php else: ?>
        <p>Nenhum usuário encontrado.</p>
    <?php endif; ?>


       
        </tbody>
    </table> 
    
    

    <?php if ($total_paginas > 1): ?>
        <p>Páginas:
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <?php if ($i == $pagina): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?pesquisa=<?= urlencode($pesquisa) ?>&pagina=<?= $i ?>&ordenar_por=<?= $ordenar_por ?>&ordem=<?= $ordem ?>">
                    <?= $i ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        </p>
    <?php endif; ?>


</div>
</body>
</html>
<?php
if (!empty($pesquisa)) {
    echo "<script>
        setTimeout(function() {
            window.location.href = window.location.pathname;
        }, 4000);
    </script>";
}
$conexao->close();
?>
