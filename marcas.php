<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Lista de Marcas</title>
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
               color: white;
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

      
.tabela {
  width: 85%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.tabela th,
.tabela td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
}

.tabela th {
  background: #f4f4f4;
  font-weight: bold;
}




.Editar {
  padding: 10px;

  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.Editar a { text-decoration:none; color:#fff;
      }
.Editar:hover {
  background-color: #06926fff;
   cursor: pointer; 
    transform:scale(1.1);  
}
  .Excluir {
  padding: 10px;
 
  background-color: #ee0000ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.Excluir a { text-decoration:none; color:#fff;    }
.Excluir:hover {
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
 transform:scale(1.1);  

}
    </style>
</head>
<body>

  
        <sidebar class="sidebar">
   
        <h2>Menu Admin</h2>
        <a href="dashboard.php">Voltar ao Menu Principal</a>
                <a href="inserirmarca.php">Cadastrar nova Marca</a>
        <a href="logout.php">Sair</a>
  
</sidebar>
  <div class="conteudo">
    <h2>Marcas Cadastradas</h2>
    <table class="tabela">
        <tr>
            <th>Nome da marca</th>
                        <th>Categorias Associadas</th>
            <th>Ações</th>
        </tr>

        <?php
        $marcas = $conexao->query("SELECT * FROM Marca");
        while ($mar = $marcas->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($mar['nome_marca']) . "</td>";
          

            // Busca marcas associadas
            $id_marca = $mar['id_marca'];
            $categorias = $conexao->prepare("SELECT c.nome_categoria FROM categoria_marca cm JOIN categoria c ON cm.id_categoria = c.id_categoria WHERE cm.id_marca = ?");
            $categorias->bind_param("i", $id_marca);
            $categorias->execute();
            $res = $categorias->get_result();
            $nomes_categorias = [];
            while ($c = $res->fetch_assoc()) {
                $nomes_categorias[] = $c['nome_categoria'];
            }
            echo "<td>" . implode(", ", $nomes_categorias) . "</td>";

            echo "<td>
                   
                      <a href='editarmarca.php?id=$id_marca'><button class='Editar'>Editar</button></a> 

                    <a href='excluirmarca.php?id=$id_marca' onclick=\"return confirm('Tem certeza que deseja excluir esta marca?')\">
                    
                    <button class='Excluir'>Excluir</button></a>
                  </td>";
            echo "</tr>";
        }
        ?>
    </table>
    <div>
</body>
</html>
