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
      <link rel="stylesheet" href="css/cliente.css">
          <script src="js/hamburger.js" defer></script>
 
      
    </style>
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
        <a href="carrinho.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Ver carrinho
        </a>
        <?php if ($usuario): ?>
        <a href="historico_compras.php" class="sidebar-link ativo">
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