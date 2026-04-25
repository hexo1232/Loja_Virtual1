<?php

include "conexao.php";
require_once "require_login.php";


if (!isset($_SESSION['usuario']['id_usuário'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario']['id_usuário'];

$sql_pedidos = "SELECT * FROM pedido WHERE id_usuário = ? ORDER BY data_pedido DESC";
$stmt_pedidos = $conexao->prepare($sql_pedidos);
$stmt_pedidos->bind_param("i", $id_usuario);
$stmt_pedidos->execute();
$result_pedidos = $stmt_pedidos->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Compras</title>
  
        <script src="logout_auto.js"></script>
  
 
      
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
    font-family: Arial, sans-serif;
         margin-left: 220px;
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
<h2>Histórico de Compras</h2>

<?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
    <div class="pedido">
        <p><strong>Data do Pedido:</strong> <?= date("d/m/Y H:i", strtotime($pedido['data_pedido'])) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($pedido['status_pedido']) ?></p>
        <p><strong>Total:</strong> <span class="valor">MZN <?= number_format($pedido['valor_total'], 2, ',', '.') ?></span></p>

        <h4>Produtos:</h4>

        <?php
        $id_pedido = $pedido['id_pedido'];
      $sql_itens = "
    SELECT p.nome_produto, ip.quantidade, ip.preco_unitario, ip.subtotal,
           pi.caminho_imagem
    FROM item_pedido ip
    INNER JOIN produto p ON ip.id_produto = p.id_produto
    LEFT JOIN produto_imagem pi ON pi.id_produto = p.id_produto AND pi.imagem_principal = 1
    WHERE ip.id_pedido = ?
";
        $stmt_itens = $conexao->prepare($sql_itens);
        $stmt_itens->bind_param("i", $id_pedido);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();

        while ($item = $result_itens->fetch_assoc()):
            $caminhoImagem = "uploads/sem_imagem.jpg";
            if (!empty($item['caminho_imagem']) && file_exists("uploads/" . basename($item['caminho_imagem']))) {
                $caminhoImagem = "uploads/" . basename($item['caminho_imagem']);
            }
        ?>
        <div class="produto">
            <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="Imagem do Produto">
            <div class="info">
                <p><strong>Produto:</strong> <?= htmlspecialchars($item['nome_produto']) ?></p>
                <p><strong>Quantidade:</strong> <?= $item['quantidade'] ?></p>
                <p><strong>Preço Unitário:</strong> MZN <?= number_format($item['preco_unitario'], 2, ',', '.') ?></p>
                <p><strong>Subtotal:</strong> MZN <?= number_format($item['subtotal'], 2, ',', '.') ?></p>
            </div>
        </div>
        <?php endwhile; ?>

        <?php
        $sql_pagamento = "SELECT status_pagamento, valor_pago, tipo_pagamento.tipo_pagamento 
                  FROM pagamento 
                  INNER JOIN tipo_pagamento ON pagamento.idtipo_pagamento = tipo_pagamento.idtipo_pagamento
                  WHERE id_pedido = ?";
        $stmt_pagamento = $conexao->prepare($sql_pagamento);
        $stmt_pagamento->bind_param("i", $id_pedido);
        $stmt_pagamento->execute();
        $result_pagamento = $stmt_pagamento->get_result();

        if ($pagamento = $result_pagamento->fetch_assoc()):
        ?>
            <h4>Pagamento:</h4>
            <p><strong>Tipo:</strong> <?= $pagamento['tipo_pagamento'] ?></p>
            <p><strong>Status:</strong> <?= $pagamento['status_pagamento'] ?></p>
            <p><strong>Valor Pago:</strong> MZN <?= number_format($pagamento['valor_pago'], 2, ',', '.') ?></p>
        <?php endif; ?>

      
            <a href="gerar_fatura.php?id_pedido=<?= $id_pedido ?>" target="_blank"><button class="imprimir" type="button">
                Imprimir Factura</button></a>

        
    </div>
<?php endwhile; ?>
</div>

</body>
</html>