<?php
session_start();
include "conexao.php";
 include "verifica_login_opcional.php"; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Produto não encontrado.";
    exit;
}

$id_produto = intval($_GET['id']);

// Busca produto
$stmt = $conexao->prepare("SELECT p.*, c.nome_categoria, m.nome_marca FROM produto p JOIN categoria c ON p.id_categoria = c.id_categoria JOIN marca m ON p.id_marca = m.id_marca WHERE p.id_produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}

// Busca imagens do produto
$imagens = $conexao->query("SELECT * FROM produto_imagem WHERE id_produto = $id_produto");
$galeria = [];
$principal = null;

while ($img = $imagens->fetch_assoc()) {
    if ($img['imagem_principal']) {
        $principal = $img;
    } else {
        $galeria[] = $img;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($produto['nome_produto']) ?> - Detalhes</title>
     <?php if ($usuario): ?>
        <script src="logout_auto.js"></script>
    <?php endif; ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .produto { display: flex; gap: 30px; }
        .galeria { display: flex; flex-direction: column; gap: 10px; }
        .galeria img { width: 60px; height: 60px; cursor: pointer; border: 2px solid #ccc; border-radius: 5px; }
        .imagem-principal img { width: 350px; height: 350px; border-radius: 8px; }
        .info { max-width: 500px; }
        .info h2 { margin-top: 0; }
        .btn-carrinho {
            background-color: green; color: white;
            border: none; padding: 10px 20px;
            font-size: 16px; cursor: pointer; border-radius: 5px;
        }
        .legenda { font-size: 13px; color: #666; margin-top: -5px; }
        #popup {
            position: fixed; top: 20%; left: 50%; transform: translate(-50%, -20%);
            background: white; border: 2px solid #333; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5);
            display: none; z-index: 9999; border-radius: 8px;
        }
        #popup button { margin-top: 10px; margin-right: 10px; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; }
        #popup .continuar { background-color: #007bff; color: white; }
        #popup .carrinho { background-color: orange; color: white; }
        #popup .checkout { background-color: green; color: white; }
        .conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }  .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          width: 190px;
          height: 100%;
          background:#eee;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
          flex-direction:column;
          gap:10px;
      }
      .sidebar a { text-decoration:none; color:#333;}
        .sidebar a:hover{ background-color:gray;
         transform:scale(1.1);  
        }
      .conteudo {
          margin-left: 220px;
      }
      

.sidebar a:hover       {
   cursor: pointer; 
background-color:  gray;

}

#popup {
    display: none;
    position: fixed;
    top: 15%;
    left: 50%;
    transform: translateX(-50%);
    background: #ffffff;
    border: 2px solid #4CAF50;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    padding: 20px 30px;
    z-index: 9999;
    animation: fadeIn 0.3s ease-in-out;
    text-align: center;
    max-width: 400px;
    width: 90%;
}

.popup-content h3 {
    margin-top: 10px;
    margin-bottom: 20px;
    color: #2e7d32;
}

.popup-icon {
    font-size: 2.5em;
    color: #4CAF50;
}

.popup-buttons button {
    margin: 5px 10px;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    background-color: #4CAF50;
    color: white;
    cursor: pointer;
    transition: background 0.3s ease;
}

.popup-buttons button:hover {
    background-color: #388E3C;

}



.end {
  padding: 10px;

  background-color:  #06926fff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.end a { text-decoration:none; color:#fff;
      }
.end:hover {
  background-color: #0c6952ff;
   cursor: pointer;
     transform:scale(1.1); 
}

@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -10%); }
    to   { opacity: 1; transform: translate(-50%, 0); }
}


    </style>
   <script>
    // Altera a imagem principal do produto
    function trocarImagem(src) {
        document.getElementById('img-principal').src = src;
    }

    // Mostra o popup de confirmação e o esconde após 3 segundos
    function mostrarPopup() {
        const popup = document.getElementById('popup');
        popup.style.display = 'block';
        setTimeout(() => popup.style.display = 'none', 3000); // auto-esconde
    }

      
function enviarFormulario(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const id_produto = parseInt(formData.get('id_produto'));
    const quantidade = parseInt(formData.get('quantidade'));
    const preco = parseFloat(formData.get('preco')); // ← Adicione um input hidden com o preço no formulário

    if (!id_produto || isNaN(quantidade) || quantidade < 1 || isNaN(preco)) {
        alert("Dados inválidos.");
        return;
    }

    const subtotal = quantidade * preco;

    <?php if ($usuario): ?>
        // 🟢 Usuário logado
        fetch('adicionar_carrinho.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => mostrarPopup())
        .catch(error => {
            console.error("Erro ao adicionar ao carrinho:", error);
            alert("Erro ao adicionar produto.");
        });

    <?php else: ?>
        // 🟡 Visitante → salvar no cookie
        let carrinho = [];

        try {
            const raw = document.cookie.split('; ').find(row => row.startsWith('carrinho='));
            if (raw) carrinho = JSON.parse(decodeURIComponent(raw.split('=')[1])) || [];
        } catch (e) {
            console.warn("Cookie inválido.");
        }

        const idx = carrinho.findIndex(p => p.id_produto === id_produto);
        if (idx !== -1) {
            carrinho[idx].quantidade += quantidade;
            carrinho[idx].subtotal += subtotal;
        } else {
            carrinho.push({ id_produto, quantidade, preco, subtotal });
        }

        document.cookie = `carrinho=${encodeURIComponent(JSON.stringify(carrinho))}; path=/; max-age=604800`;

        mostrarPopup();
    <?php endif; ?>
}



</script>

</head>
<body>


<?php if ($usuario): ?>
     <?php 
 $nome = $usuario['nome'] ?? '';
$apelido = $usuario['apelido'] ?? '';
$iniciais = strtoupper(substr($nome, 0, 1) . substr($apelido, 0, 1));
$nomeCompleto = "$nome $apelido";

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
     <div style="padding: 15px; background: #fffae6; border: 1px solid #ffc107; margin: 15px; margin-left: 250px; border-radius: 5px;">
    <strong>Não tem sessão iniciada?</strong>
    <a href="login.php" style="color: #d35400; text-decoration: underline;">Faça login</a> para adicionar ao carrinho, favoritar produtos e ver histórico.
</div>
<?php endif; ?>


<sidebar class="sidebar">
    <a href= "verprodutos.php">Voltar aos Produtos</a>
       <?php if ($usuario): ?><a href="logout.php">Sair</a><?php endif; ?>
</sidebar>
<div class="conteudo">

<h2><?= htmlspecialchars($produto['nome_produto']) ?></h2>
<div class="produto">
    <div>
        <div class="imagem-principal">
            <img id="img-principal" src="<?= $principal ? $principal['caminho_imagem'] : 'sem_foto.png' ?>" alt="Imagem principal">
        </div>
        <?php if (count($galeria) > 0): ?>
            <div class="galeria">
                <?php foreach ($galeria as $img): ?>
                    <div>
                        <img src="<?= $img['caminho_imagem'] ?>" onclick="trocarImagem(this.src)">
                        <div class="legenda"><?= htmlspecialchars($img['legenda']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="info">
        <h3>Preço: <?= number_format($produto['preco'], 2, ',', '.') ?> MZN</h3>
        <p><strong>Categoria:</strong> <?= htmlspecialchars($produto['nome_categoria']) ?></p>
        <p><strong>Marca:</strong> <?= htmlspecialchars($produto['nome_marca']) ?></p>
        <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
        <p><strong>Estoque:</strong> <?= $produto['quantidade_estoque'] ?> unidades</p>

<form method="post" onsubmit="enviarFormulario(event)">
    <input type="hidden" name="id_produto" value="<?= $id_produto ?>">
    
    <!-- Campo oculto do preço em formato numérico -->
    <input type="hidden" name="preco" value="<?= number_format($produto['preco'], 2, '.', '') ?>">

    <label>Quantidade:</label>
    <input type="number" name="quantidade" min="1" max="<?= $produto['quantidade_estoque'] ?>" value="1" required>
    <br><br>
    <button class="end" type="submit" class="btn-carrinho">Adicionar ao Carrinho</button>
</form>

        <br>
    </div>
</div>

<!-- 🔔 POPUP ESTILIZADO -->
<div id="popup" class="popup">
    <div class="popup-content">
        <span class="popup-icon">✔️</span>
        <h3>Produto adicionado com sucesso!</h3>
        <div class="popup-buttons">
            <button class="continuar" onclick="window.location.href='verprodutos.php'">Continuar a ver produtos</button>
            <button class="carrinho" onclick="window.location.href='carrinho.php'">Ver carrinho</button>
            <button class="checkout" onclick="window.location.href='<?= isset($_SESSION['usuario']) ? 'finalizar_pedido.php' : 'login.php?redir=finalizar_pedido.php' ?>'">Fazer pagamento</button>


        </div>
    </div>
</div>

</div>
