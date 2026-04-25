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
     <link rel="stylesheet" href="css/cliente.css">
     <?php if ($usuario): ?>
        <script src="logout_auto.js"></script>
    <?php endif; ?>
      
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
            Voltar aos Produtos
        </a>
        <a href="carrinho.php" class="sidebar-link">
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

<h2><?= htmlspecialchars($produto['nome_produto']) ?></h2>
<div class="produto">
    <div>
      <div class="imagem-principal">
    <img id="img-principal" src="<?= $principal ? $principal['caminho_imagem'] : 'https://placehold.co/600x600?text=Sem+Foto' ?>" alt="Imagem principal">
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
