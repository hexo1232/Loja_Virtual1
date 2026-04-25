<?php
session_start();
include "conexao.php";
include "verifica_login_opcional.php";

$itens_carrinho = [];
$total = 0.00;

// Usuario logado
if (isset($_SESSION['usuario']['id_usuário'])) {
    $id_usuario = $_SESSION['usuario']['id_usuário'];

    $sql = "SELECT id_carrinho FROM carrinho WHERE id_usuário = ? AND status = 'activo'";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $id_carrinho = $res->fetch_assoc()['id_carrinho'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantidades'])) {
            foreach ($_POST['quantidades'] as $id_produto => $qtd) {
                $qtd = max(1, intval($qtd));
                $sql_update = "UPDATE item_carrinho SET quantidade = ?, subtotal = ? * (SELECT preco FROM produto WHERE id_produto = ?) WHERE id_carrinho = ? AND id_produto = ?";
                $stmt_up = $conexao->prepare($sql_update);
                $stmt_up->bind_param("iiiii", $qtd, $qtd, $id_produto, $id_carrinho, $id_produto);
                $stmt_up->execute();
            }
        }

        $sql_itens = "SELECT ic.*, p.nome_produto, p.preco, p.quantidade_estoque,
                        (SELECT caminho_imagem FROM produto_imagem WHERE id_produto = p.id_produto AND imagem_principal = 1 LIMIT 1) AS imagem_principal
                      FROM item_carrinho ic
                      JOIN produto p ON ic.id_produto = p.id_produto
                      WHERE ic.id_carrinho = ?";
        $stmt = $conexao->prepare($sql_itens);
        $stmt->bind_param("i", $id_carrinho);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($item = $result->fetch_assoc()) {
            $itens_carrinho[] = $item;
            $total += $item['subtotal'];
        }
    }
} elseif (isset($_COOKIE['carrinho'])) {
    $carrinho_cookie = json_decode($_COOKIE['carrinho'], true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantidades'])) {
        foreach ($carrinho_cookie as &$item) {
            $id_prod = $item['id_produto'];
            if (isset($_POST['quantidades'][$id_prod])) {
                $nova_qtd = max(1, intval($_POST['quantidades'][$id_prod]));
                $item['quantidade'] = $nova_qtd;
                $item['subtotal'] = $nova_qtd * $item['preco'];
            }
        }
        setcookie('carrinho', json_encode($carrinho_cookie), time() + 86400, '/');
        unset($item);
    }

    foreach ($carrinho_cookie as $item) {
        $stmt = $conexao->prepare("SELECT nome_produto, preco, quantidade_estoque,
                                        (SELECT caminho_imagem FROM produto_imagem WHERE id_produto = ? AND imagem_principal = 1 LIMIT 1) AS imagem_principal
                                   FROM produto WHERE id_produto = ?");
        $stmt->bind_param("ii", $item['id_produto'], $item['id_produto']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $item['nome_produto'] = $res['nome_produto'] ?? 'Produto removido';
        $item['preco'] = $res['preco'] ?? 0;
        $item['imagem_principal'] = $res['imagem_principal'] ?? 'sem_foto.png';
        $item['subtotal'] = $item['preco'] * $item['quantidade'];
        $item['quantidade_estoque'] = $res['quantidade_estoque'] ?? 1;
        $itens_carrinho[] = $item;
        $total += $item['subtotal'];
    }
}

// A parte HTML e Javascript continua intacta abaixo
// Ela não foi incluída neste trecho apenas por questões de organização
// Mas seu código está correto e funcional visualmente
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Carrinho de Compras</title>
         <link rel="stylesheet" href="css/cliente.css">
    <script src="js/cliente.js" defer></script>
    
    <?php if ($usuario): ?>
        <script src="logout_auto.js"></script>
    <?php endif; ?>
    
    <script>
        function atualizarSubtotal(index, preco, estoque) {
            const qtdInput = document.getElementById('qtd-' + index);
            let qtd = parseInt(qtdInput.value);

            if (qtd > estoque) {
                alert("Só temos " + estoque + " unidades em estoque.");
                qtdInput.value = estoque;
               
                qtd = estoque;
            }

            const subtotal = preco * qtd;
            document.getElementById('subtotal-' + index).innerText = subtotal.toFixed(2) + " MZN";

            let total = 0;
            document.querySelectorAll(".subtotal").forEach(s => {
                total += parseFloat(s.innerText.replace(" MZN", ""));
            });
            document.getElementById('total').innerText = total.toFixed(2) + " MZN";
        }
        function alterarQuantidade(index, preco, estoque, delta) {
            const input = document.getElementById('qtd-' + index);
            let valor = parseInt(input.value);
            valor += delta;
            if (valor < 1) valor = 1;
            if (valor > estoque) {
                
                alert("Estoque insuficiente: " + estoque + " unidades disponíveis.");
                valor = estoque;
            }
            input.value = valor;
            atualizarSubtotal(index, preco, estoque);
        }

        
function removerDoCarrinhoCookie(id_produto) {
    let carrinho = [];

    try {
        const cookieData = decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('carrinho='))?.split('=')[1]);
        carrinho = JSON.parse(cookieData) || [];
    } catch (e) {
        carrinho = [];
    }

    carrinho = carrinho.filter(item => item.id_produto != id_produto);

    document.cookie = `carrinho=${encodeURIComponent(JSON.stringify(carrinho))}; path=/; max-age=604800`;
    
    // Recarrega para atualizar a interface
    location.reload();
}
    </script>
    <style>
      /* Estilo simples para usuário logado */
      
      .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          width: 150px;
          height: 100%;
          background:#eee;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
          flex-direction:column;
          gap:10px;
      }
      .sidebar a { text-decoration:none; color:#333;
    margin-bottom: 5px;}
      .sidebar a:hover{ background-color:gray;
     transform:scale(1.1); }
      .conteudo {
          margin-left: 220px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }
      .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 15px 0; background: white; display: flex; gap: 20px; align-items: center;   box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;}
        .card img { width: 25%; height: 25%; border-radius: 8px; cursor: pointer;    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;}

    .card img:hover{
 transform:scale(1.1);

    }

  .save {
  padding: 10px;
   width: 120px;
  background-color: #007bff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}

.save:hover {
  background-color: #0056b3; 
  cursor: pointer;
   transform:scale(1.1); 
}

.end {
  padding: 10px;
   width: 120px;
  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.end a { text-decoration:none; color:#fff;
      }
.end:hover {
  background-color: #06926fff;
   cursor: pointer; 
    transform:scale(1.1); 
}
  .remove {
  padding: 10px;
   width: 120px;
  background-color: #ee0000ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.remove a { 
    text-decoration:none;
     color:#fff;    }

     
.remove:hover {
  background-color: #7e0e0eff;
   cursor: pointer; 
    transform:scale(1.1); 
}
   a { 
    text-decoration:none;
}
    </style>
  

</head>
<body>
    
<?php if ($usuario): ?>
   <?php 
$nome2 = $usuario['nome'] ?? '';
$apelido = $usuario['apelido'] ?? '';
$iniciais = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
$nomeCompleto = "$nome2 $apelido";

// Função para gerar cor única baseada no nome
function gerarCor($texto) {
    $hash = md5($texto);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));
    return "rgb($r, $g, $b)";
}

$corAvatar = gerarCor($nomeCompleto);
?>
<style>
.usuario-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
        margin-left: 200px;
    font-family: Arial, sans-serif;
}

.usuario-iniciais {
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.usuario-nome {
    font-weight: bold;
}
</style>

<div class="usuario-info">
    <div class="usuario-iniciais" style="background-color: <?= $corAvatar ?>"><?= $iniciais ?></div>
    <div class="usuario-nome"><?= $nomeCompleto ?></div>
</div>


<?php else: ?>
    <div style="padding: 15px; background: #fffae6; border: 1px solid #ffc107; margin: 15px; margin-left: 200px; border-radius: 5px;">
    <strong>Não tem sessão iniciada?</strong>
    <a href="login.php" style="color: #d35400; text-decoration: underline;">Faça login</a> para adicionar ao carrinho, favoritar produtos e ver histórico.
</div>
<?php endif; ?>

  
<sidebar class="sidebar">
  <a href="verprodutos.php">Continuar a Comprar</a>
   <?php if ($usuario): ?><a href="logout.php">Sair</a><?php endif; ?>
    </sidebar>

<div class="conteudo">
    <h2>🛒 Carrinho de Compras</h2>

    <?php if (count($itens_carrinho) === 0): ?>
        <p>O carrinho está vazio.</p>
    <?php else: ?>
        <form method="post">
        <?php foreach ($itens_carrinho as $i => $item): ?>
              <div class="card">
      
                <img src="<?= $item['imagem_principal'] ?? 'sem_foto.png' ?>" onclick="window.location='detalhesproduto.php?id=<?= $item['id_produto'] ?>'">
                <div class="info">
                    <h3><?= htmlspecialchars($item['nome_produto']) ?></h3>
                    <p>Preço: <?= number_format($item['preco'], 2, ',', '.') ?> MZN</p>
                    <div class="quantidade">
                        <button type="button" onclick="alterarQuantidade(<?= $i ?>, <?= $item['preco'] ?>, <?= $item['quantidade_estoque'] ?>, -1)">-</button>
                        <input type="number" name="quantidades[<?= $item['id_produto'] ?>]" id="qtd-<?= $i ?>" value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['quantidade_estoque'] ?>" onchange="atualizarSubtotal(<?= $i ?>, <?= $item['preco'] ?>, <?= $item['quantidade_estoque'] ?>)">
                        <button type="button" onclick="alterarQuantidade(<?= $i ?>, <?= $item['preco'] ?>, <?= $item['quantidade_estoque'] ?>, 1)">+</button>
                    </div>
                    <p class="subtotal" id="subtotal-<?= $i ?>"><?= number_format($item['subtotal'], 2, ',', '.') ?> MZN</p>

  <?php if ($usuario): ?>
                    <div class="acoes">
                   

                       

                              <a class='remove' href="remover_item_carrinho.php?id_produto=<?= $item['id_produto'] ?>">  
                              
                                   Remover</a>


                    </div>


                    <?php else: ?>
                     <div class="acoes">

                        <button class='remove' onclick="removerDoCarrinhoCookie(<?= $item['id_produto'] ?>)">Remover</button>

                   

                   


                    </div>
                          <?php endif; ?>
                    
        
                </div>
            </div>
        <?php endforeach; ?>

        <div class="total">
            <strong>Total: <span id="total"><?= number_format($total, 2, ',', '.') ?> MZN</span></strong>
        </div> 

        <br>
        
       

        <?php if ($usuario): ?> <button class="save" type="submit">Salvar Carrinho</button><?php endif; ?>
           <a href="finalizar_pedido.php"><button class="end" type="button">Fazer Pedido</button></a>

          </div>
      
     
        </form>
        
        
    <?php endif; ?>

    <br>
  
        </div>

        

      
</body>
</html>
