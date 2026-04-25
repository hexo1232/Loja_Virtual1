<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Minha Loja Virtual</title>
     <link rel="stylesheet" href="css/cliente.css">
</head>
<body>

  <header>
    <h1>Minha Loja</h1>
    <!-- <?php if (isset($_SESSION['usuario'])): ?>
      <span>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
    <?php else: ?>
      <a href="login.php" style="color: #ecf0f1; text-decoration: underline;">Entrar</a>
    <?php endif; ?> -->
  </header>

  <main>
    <section class="hero">
      <h2>Bem-vindo à melhor loja online!</h2>
      <p>Oferecemos produtos de qualidade com ótimos preços. Explore nosso catálogo agora.</p>
      <button onclick="irParaProdutos()">Ver Produtos</button>
    </section>

    <section>
      <h2>Quem Somos?</h2>
      <p>Somos uma empresa dedicada ao comércio eletrônico, com foco em oferecer os melhores produtos e experiências para nossos clientes.</p>
    </section>

    <section>
      <h2>Nosso Objetivo</h2>
      <p>Nosso objetivo é simplificar a sua experiência de compra online, com segurança, agilidade e excelência no atendimento.</p>
    </section>

    <section>
      <h2>Contactos</h2>
      <div class="contact-info">
        <p>📞 Telefone: +258 84 123 4567</p>
        <p>📧 Email: suporte@minhaloja.co.mz</p>
        <p>📍 Endereço: Av. Julius Nyerere, Maputo - Moçambique</p>
        <div class="social-icons">
          <a href="https://facebook.com" target="_blank">🌐 Facebook</a>
          <a href="https://instagram.com" target="_blank">📸 Instagram</a>
          <a href="https://wa.me/258841234567" target="_blank">💬 WhatsApp</a>
        </div>
      </div>
    </section>
  </main>

  <footer>
    &copy; <?= date("Y") ?> Minha Loja. Todos os direitos reservados.
  </footer>

  <script>
    function irParaProdutos() {
      window.location.href = "verprodutos.php";
    }
  </script>

</body>
</html>
