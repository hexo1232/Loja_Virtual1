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
        <script src="js/hamburger.js" defer></script>
    
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
   

</head>
<body>
    
<?php
if ($usuario) {
    $nome2        = $usuario['nome']    ?? '';
    $apelido      = $usuario['apelido'] ?? '';
    $email        = $usuario['email']   ?? '';
    $iniciais     = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
    $nomeCompleto = trim("$nome2 $apelido");

    function gerarCor($texto) {
        $hash = md5($texto);
        return 'rgb(' . hexdec(substr($hash,0,2)) . ',' . hexdec(substr($hash,2,2)) . ',' . hexdec(substr($hash,4,2)) . ')';
    }
    $corAvatar = gerarCor($nomeCompleto);
}
?>

<!-- ── Botão hamburger ────────────────────────────────────── -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu" aria-expanded="false">
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
</button>

<!-- ── Overlay mobile ────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <span class="sidebar-logo">&#9679; Loja</span>
    </div>

    <nav class="sidebar-nav">
        <a href="verprodutos.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            Continuar a Comprar
        </a>
        <a href="carrinho.php" class="sidebar-link ativo">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Ver carrinho
        </a>
        <?php if ($usuario): ?>
        <a href="historico_compras.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Histórico de Compras
        </a>
        <?php endif; ?>
    </nav>

    <?php if ($usuario): ?>
    <div class="sidebar-footer">
        <button class="sidebar-user" id="sidebarUserBtn" aria-haspopup="true" aria-expanded="false">
            <div class="sidebar-avatar" style="background-color: <?= $corAvatar ?>"><?= $iniciais ?></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($nomeCompleto) ?></span>
                <?php if ($email): ?>
                    <span class="sidebar-user-email"><?= htmlspecialchars($email) ?></span>
                <?php endif; ?>
            </div>
            <svg class="sidebar-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"/>
            </svg>
        </button>
        <div class="sidebar-dropdown" id="sidebarDropdown" role="menu">
            <a href="alterar_senha.php" class="sidebar-dropdown-item" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Alterar senha
            </a>
            <div class="sidebar-dropdown-divider"></div>
            <a href="logout.php" class="sidebar-dropdown-item sidebar-dropdown-item--danger" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sair
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-login-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Fazer login
        </a>
    </div>
    <?php endif; ?>

</aside>

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
