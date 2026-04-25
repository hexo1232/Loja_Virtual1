<?php
include "conexao.php";
include "verifica_login_opcional.php";

// Filtros
$nome = $_GET['nome'] ?? '';
$preco_min = $_GET['preco_min'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$id_categoria = $_GET['categoria'] ?? '';
$id_marca = $_GET['marca'] ?? '';

// Construir WHERE
$where = "WHERE 1=1";
$param = [];
$tipos = '';

if (!empty($nome)) {
    $where .= " AND p.nome_produto LIKE ?";
    $param[] = "%$nome%";
    $tipos .= 's';
}
if (!empty($preco_min)) {
    $where .= " AND p.preco >= ?";
    $param[] = $preco_min;
    $tipos .= 'd';
}
if (!empty($preco_max)) {
    $where .= " AND p.preco <= ?";
    $param[] = $preco_max;
    $tipos .= 'd';
}
if (!empty($id_categoria)) {
    $where .= " AND p.id_categoria = ?";
    $param[] = $id_categoria;
    $tipos .= 'i';
}
if (!empty($id_marca)) {
    $where .= " AND p.id_marca = ?";
    $param[] = $id_marca;
    $tipos .= 'i';
}

$sql = "SELECT p.*, m.nome_marca, c.nome_categoria, img.caminho_imagem AS imagem_principal
        FROM produto p
        JOIN marca m ON p.id_marca = m.id_marca
        JOIN categoria c ON p.id_categoria = c.id_categoria
        LEFT JOIN produto_imagem img ON img.id_produto = p.id_produto AND img.imagem_principal = 1
        $where";

$stmt = $conexao->prepare($sql);
if (!empty($param)) {
    $stmt->bind_param($tipos, ...$param);
}
$stmt->execute();
$result = $stmt->get_result();

$categorias = $conexao->query("SELECT * FROM categoria");
$marcas = !empty($id_categoria) ?
    $conexao->query("SELECT m.id_marca, m.nome_marca FROM categoria_marca cm JOIN marca m ON cm.id_marca = m.id_marca WHERE cm.id_categoria = $id_categoria") :
    $conexao->query("SELECT * FROM marca");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Produtos</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/cliente.js" defer></script>
    <script src="js/hamburger.js" defer></script>

    <?php if ($usuario): ?>
        <script src="logout_auto.js"></script>
    <?php endif; ?>

    <script>
        function atualizarMarcas() {
            var categoria = document.getElementById('categoria').value;
            window.location.href = 'verprodutos.php?categoria=' + categoria;
        }
    </script>
</head>
<body>

<?php
/* ── Dados do usuário para o avatar ──────────────────────── */
if ($usuario) {
    $nome2       = $usuario['nome']    ?? '';
    $apelido     = $usuario['apelido'] ?? '';
    $email       = $usuario['email']   ?? '';
    $iniciais    = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
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
        <a href="verprodutos.php" class="sidebar-link ativo">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            Produtos
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

    <!-- Rodapé: logado -->
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

         <a href="editarusuario.php" class="sidebar-dropdown-item" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Editar os meus Dados
            </a>

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

    <!-- Rodapé: não logado -->
    <?php else: ?>
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-login-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Fazer login
        </a>
    </div>
    <?php endif; ?>

</aside>

<!-- ── Conteúdo principal ─────────────────────────────────── -->
<div class="conteudo">
    <h2 style="padding: 0 24px 20px;">Produtos Disponíveis</h2>

    <!-- Filtros -->
   <div class="filtro-wrapper">
    <button class="filtro-toggle" onclick="toggleFiltros()" id="btnFiltro">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        Filtros
        <span class="filtro-chevron" id="filtroChevron">▾</span>
    </button>

    <div class="filtro-produtos" id="painelFiltros">
        <form method="get" class="filtro-form">
            <div class="filtro-linha">
                <input type="text" name="nome" placeholder="Nome do produto" value="<?= htmlspecialchars($nome) ?>">
                <input type="number" step="0.01" name="preco_min" placeholder="Preço mín." value="<?= $preco_min ?>">
                <input type="number" step="0.01" name="preco_max" placeholder="Preço máx." value="<?= $preco_max ?>">
                <select name="categoria" id="categoria" onchange="atualizarMarcas()">
                    <option value="">Todas as categorias</option>
                    <?php while ($c = $categorias->fetch_assoc()): ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= $c['id_categoria'] == $id_categoria ? 'selected' : '' ?>>
                            <?= $c['nome_categoria'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="marca">
                    <option value="">Todas as marcas</option>
                    <?php while ($m = $marcas->fetch_assoc()): ?>
                        <option value="<?= $m['id_marca'] ?>" <?= $m['id_marca'] == $id_marca ? 'selected' : '' ?>>
                            <?= $m['nome_marca'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="busca">Filtrar</button>
            </div>
        </form>
    </div>
</div>

    <!-- Produtos -->
    <div class="container-produtos">
        <?php while ($p = $result->fetch_assoc()): ?>
          <div class="card-produto">
    <?php if ($p['imagem_principal']): ?>
        <img src="<?= htmlspecialchars($p['imagem_principal']) ?>" alt="Imagem do produto">
    <?php else: ?>
        <img src="https://placehold.co/600x400?text=Sem+Imagem" alt="Sem imagem">
    <?php endif; ?>
                <div class="info">
                    <div class="titulo"><?= htmlspecialchars($p['nome_produto']) ?></div>
                    <div class="preco"><?= number_format($p['preco'], 2, ',', '.') ?> MZN</div>
                    <p><strong>Categoria:</strong> <?= $p['nome_categoria'] ?></p>
                    <p><strong>Marca:</strong> <?= $p['nome_marca'] ?></p>
                    <a href="detalhesproduto.php?id=<?= $p['id_produto'] ?>" class="botao-detalhes">Ver detalhes</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function toggleFiltros() {
    const painel = document.getElementById('painelFiltros');
    const chevron = document.getElementById('filtroChevron');
    const aberto = painel.classList.toggle('aberto');
    chevron.textContent = aberto ? '▴' : '▾';
}

// Abre automaticamente se há filtros activos
<?php if (!empty($nome) || !empty($preco_min) || !empty($preco_max) || !empty($id_categoria) || !empty($id_marca)): ?>
document.addEventListener('DOMContentLoaded', () => toggleFiltros());
<?php endif; ?>
</script>
</body>
</html>