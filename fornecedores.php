<?php 

include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

$usuario = $_SESSION['usuario'];

// Paramîtros GET
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'nome_fornecedor';
$ordem = (isset($_GET['ordem']) && $_GET['ordem'] === 'desc') ? 'DESC' : 'ASC';

// Validação da coluna de ordenação
$colunas_validas = ['nome_fornecedor'];
if (!in_array($ordenar_por, $colunas_validas)) {
    $ordenar_por = 'nome_fornecedor';
}

// Paginação
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Inicializa variáveis para SQL dinâmico
$sql_where = "1=1";
$tipos = "";
$parametros = [];

if (!empty($pesquisa)) {
    $sql_where .= " AND (nome_fornecedor LIKE ?)";
    $tipos .= "s";
    $parametros[] = "%$pesquisa%";
}

// Contagem total de registros
$sql_total = "SELECT COUNT(*) AS total FROM vw_fornecedores WHERE $sql_where";
$stmt_total = $conexao->prepare($sql_total);
if (!empty($pesquisa)) {
    $stmt_total->bind_param($tipos, ...$parametros);
}
$stmt_total->execute();
$resultado_total = $stmt_total->get_result();
$total_registros = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);
$stmt_total->close();

// Consulta de dados principais
$sql = "SELECT * FROM vw_fornecedores WHERE $sql_where ORDER BY $ordenar_por $ordem LIMIT $limite OFFSET $offset";
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
    <title>Gerenciar Fornecedores</title>

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
               color: white;
          background:  #0056b3;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
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

      
#tabela-forneco {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

#tabela-forneco th,
#tabela-forneco td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: center;
}

#tabela-forneco th {
  background: #f4f4f4;
  font-weight: bold;
}




#editar {
  padding: 10px;

  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
#editar a { text-decoration:none; color:#fff;
      }
#editar:hover {
  background-color: #06926fff;
   cursor: pointer; 
    transform:scale(1.1);  
}
  #excluir {
  padding: 10px;

  background-color: #ee0000ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
#excluir a { text-decoration:none; color:#fff;    }
#excluir:hover {
  background-color: #7e0e0eff;
   cursor: pointer; 
    transform:scale(1.1);  
}

#busca {
  padding: 10px;
   width: 80px;
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
.sidebar a:hover {
   cursor: pointer; 
background-color:  #024185ff;   
 transform:scale(1.1);  

}

</style>
</head>
<body>


<sidebar class="sidebar">
  
        <h2>Menu Admin</h2>
        <a href="dashboard.php">Voltar ao Menu Principal</a>
    <a href="cadastrarfornecedor.php">Cadastrar Fornecedor</a>
                 <a href="logout.php">Sair</a>
   
</sidebar>

<div class="conteudo">
   

    <h3>Buscar Fornecedores</h3>
    <form method="GET" action="">
        <input  id="texto" type="search" name="pesquisa" placeholder="Nome do fornecedor" value="<?= htmlspecialchars($pesquisa); ?>">
        <input id="busca" type="submit" value="Buscar">
    </form>

    <p>Ordenar por:
        <a href="?pesquisa=<?= urlencode($pesquisa) ?>&ordenar_por=nome_fornecedor&ordem=<?= ($ordenar_por == 'nome_fornecedor' && $ordem == 'ASC') ? 'desc' : 'asc'; ?>">
            Nome <?= ($ordenar_por == 'nome_fornecedor') ? ($ordem == 'ASC' ? '↑' : '↓') : '' ?>
        </a>
    </p>

    <h3>Total de Fornecedores Encontrados: <?= $total_registros ?></h3>



    <table id="tabela-forneco" >
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Província</th>
                <th>Cidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado->num_rows > 0): ?>
                <?php while ($linha = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= $linha['id_fornecedor'] ?></td>
                        <td><?= htmlspecialchars($linha['nome_fornecedor']) ?></td>
                        <td><?= htmlspecialchars($linha['email']) ?></td>
                        <td><?= htmlspecialchars($linha['telefone']) ?></td>
                        <td><?= htmlspecialchars($linha['Provincia']) ?></td>
                        <td><?= htmlspecialchars($linha['Cidade']) ?></td>
                        <td>
                            <a href='editarfornecedor.php?id_fornecedor=<?= $linha['id_fornecedor'] ?>'><button id="editar">Editar</button></a> 
                            <a href='excluirfornecedor.php?id_fornecedor=<?= $linha['id_fornecedor'] ?>' onclick="return confirm('Tem certeza que deseja excluir?');">
                                <button id="excluir">Excluir</button></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">Nenhum fornecedor encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
            </div>

    <?php if ($total_paginas > 1): ?>
        <p>Páginas:
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <?php if ($i == $pagina): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?pesquisa=<?= urlencode($pesquisa) ?>&pagina=<?= $i ?>&ordenar_por=<?= $ordenar_por ?>&ordem=<?= $ordem ?>"> <?= $i ?> </a>
            <?php endif; ?>
        <?php endfor; ?>
        </p>
    <?php endif; ?>
</div>
</body>
</html>

<?php
$conexao->close();
?>
