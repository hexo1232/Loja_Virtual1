<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Lista de Produtos</title>
     
        <script src="logout_auto.js"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        h2 {
            margin: 20px;
        }

        .produtos {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
        }

        .card {
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

          .card img:hover{

             cursor: pointer; 
   transform:scale(1.1);
          }

        .card .info {
            padding: 15px;
        }

        
        .card .info:hover{

             cursor: pointer; 
   transform:scale(1.1);
          }

        .card .info h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .card .info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .card .acoes {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-top: 1px solid #eee;
        }

        .acoes a {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            color: white;
            font-size: 14px;
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
  .excluir {
  padding: 10px;

  background-color: #ee0000ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.excluir a { text-decoration:none; color:#fff;    }
.excluir:hover {
  background-color: #7e0e0eff;
   cursor: pointer; 
   transform:scale(1.1);
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



.conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }

    </style>
</head>
<body>



<sidebar class="sidebar">
       
            <h2>Menu Admin</h2>
                <a href="dashboard.php">Voltar ao Menu Principal</a>
          
            <a href="cadastroprodutos.php">Cadastrar novo Produto</a>
        
            <a href="logout.php">Sair</a>
       
    </sidebar>

        <div class="conteudo">
            
<h2>Produtos Cadastrados</h2>


<?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
    <p style="color: green; padding-left: 20px;">Produto excluído com sucesso!</p>
<?php endif; ?>

<div class="produtos">
    <?php
$sql = "SELECT 
                p.id_produto,
                p.nome_produto,
                p.preco,
                p.quantidade_estoque,
                c.nome_categoria,
                m.nome_marca,
                f.nome_fornecedor,
                pi.caminho_imagem
            FROM produto p
            LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
            LEFT JOIN marca m ON p.id_marca = m.id_marca
            LEFT JOIN fornecedor f ON p.id_fornecedor = f.id_fornecedor
            LEFT JOIN produto_imagem pi ON p.id_produto = pi.id_produto AND pi.imagem_principal = 1
            ORDER BY p.id_produto DESC";

    $resultado = $conexao->query($sql);

    if ($resultado->num_rows > 0) {
        while ($produto = $resultado->fetch_assoc()) {
            $imagem = $produto['caminho_imagem'] ?: 'uploads/sem_imagem.png'; // imagem padrão
            echo "<div class='card'>
                    <img src='{$imagem}' alt='Imagem do Produto'>
                    <div class='info'>
                        <h3>" . htmlspecialchars($produto['nome_produto']) . "</h3>
                        <p><strong>Preço:</strong> MT " . number_format($produto['preco'], 2, ',', '.') . "</p>
                        <p><strong>Estoque:</strong> {$produto['quantidade_estoque']}</p>
                        <p><strong>Categoria:</strong> {$produto['nome_categoria']}</p>
                        <p><strong>Marca:</strong> {$produto['nome_marca']}</p>
                        <p><strong>Fornecedor:</strong> {$produto['nome_fornecedor']}</p>
                    </div>
                    <div class='acoes'>
                        <a href='editarproduto.php?id={$produto['id_produto']}' class='editar'>Editar</a>
                        <a href='excluirproduto.php?id={$produto['id_produto']}' class='excluir' onclick=\"return confirm('Deseja realmente excluir este produto?')\">Excluir</a>
                    </div>
                  </div>";
        }
    } else {
        echo "<p style='padding-left: 20px;'>Nenhum produto encontrado.</p>";
    }
    ?>
</div>
     </div>

</body>
</html>
