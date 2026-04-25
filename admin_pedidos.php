<?php
include("conexao.php");
require_once "require_login.php";
include "usuario_info.php";

// Aceitar pedido
if (isset($_GET['aceitar_pedido'])) {
    $id_pedido = intval($_GET['aceitar_pedido']);
    $sql_update = "UPDATE pedido SET status_pedido = 'aceite' WHERE id_pedido = ?"; // Corrigido aqui
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
}

// Recusar pedido
if (isset($_GET['recusar_pedido'])) {
    $id_pedido = intval($_GET['recusar_pedido']);
    $sql_update = "UPDATE pedido SET status_pedido = 'recusado' WHERE id_pedido = ?"; // Corrigido aqui
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
}

// Consulta todos os pedidos
$sql = "
SELECT 
    p.id_pedido,
    p.data_pedido,
    p.status_pedido,
    u.nome AS nome_usuario,
    u.apelido,
    pr.nome_produto,
    ip.quantidade,
    ip.subtotal
FROM pedido p
JOIN usuario u ON p.id_usuário = u.id_usuário
JOIN item_pedido ip ON p.id_pedido = ip.id_pedido
JOIN produto pr ON ip.id_produto = pr.id_produto
ORDER BY p.data_pedido DESC
";

$result = $conexao->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    
    <title>Dashboard - Pedidos</title>
      
        <script src="logout_auto.js"></script>'
        
    <style>

        body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f7fa;
  color: #333;
}
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        th {
            background-color: #eee;
        }
        .btn {
            padding: 5px 10px;
           
              border: none;
  border-radius: 6px;
       
        }
        .aceitar {
            background-color:  #00ff88ff;
            color: white;
              border: none;
  border-radius: 6px;
        }

        .aceitar:hover {
            cursor: pointer; 
background-color:   #11b86aff; 

 transform:scale(1.1);  
        }

     .recusar {
            background-color: #ff0909ff; 
            color: white;
              border: none;
  border-radius: 6px;
        }

         .recusar:hover{
        cursor: pointer; 
background-color:   #ac1313ff; 

 transform:scale(1.1);  

         }
        .btn:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }
        
    .conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
 .sidebar a:hover {
   cursor: pointer; 
background-color:  #024185ff;   

 transform:scale(1.1);  

}


.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}
    </style>
</head>
<body>
     <sidebar class="sidebar">
    
            <h2>Menu Admin</h2>
            <a href="dashboard.php">Voltar ao Menu Principal</a>
           
            <a href="logout.php">Sair</a>
        </sidebar>
          <div class="conteudo">  
        
    <h2>Lista de Pedidos</h2>
    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Data</th>
                <th>Status</th>
                <th>Usuário</th>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Subtotal</th>
                <th colspan="2">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id_pedido'] ?></td>
                <td><?= $row['data_pedido'] ?></td>
                <td><?= $row['status_pedido'] ?></td>
                <td><?= $row['nome_usuario'] . ' ' . $row['apelido'] ?></td>
                <td><?= $row['nome_produto'] ?></td>
                <td><?= $row['quantidade'] ?></td>
                <td><?= number_format($row['subtotal'], 2) ?> MZN</td>
             <td>
    <?php if ($row['status_pedido'] === 'pendente') { ?>
        <a href="?aceitar_pedido=<?= $row['id_pedido'] ?>" 
           onclick="return confirm('Tem certeza que deseja aceitar este pedido?')">
            <button class="btn aceitar">Aceitar</button>
        </a>
    <?php } else { ?>
        <button class="btn aceitar" disabled>Aceitar</button>
    <?php } ?>
</td>
<td>
    <?php if ($row['status_pedido'] === 'pendente') { ?>
        <a href="?recusar_pedido=<?= $row['id_pedido'] ?>" 
           onclick="return confirm('Tem certeza que deseja recusar este pedido?')">
            <button class="btn recusar">Recusar</button>
        </a>
    <?php } else { ?>
        <button class="btn recusar" disabled>Recusar</button>
    <?php } ?>
</td>

            </tr>
            <?php } ?>
        </tbody>
    </table>
    </div >
</body>
</html>
