<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Minha Loja Virtual</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f0f2f5, #ffffff);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    header {
      width: 100%;
      padding: 20px 40px;
      background: #2c3e50;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      animation: fadeDown 1s ease-in-out;
    }

    header h1 {
      font-size: 28px;
    }

    main {
      flex: 1;
      padding: 40px 20px;
    }

    section {
      max-width: 900px;
      margin: 40px auto;
      text-align: center;
      animation: fadeUp 1.2s ease-in-out;
    }

    section h2 {
      font-size: 30px;
      color: #2c3e50;
      margin-bottom: 15px;
    }

    section p {
      font-size: 17px;
      color: #555;
      line-height: 1.6;
    }

    .hero {
      text-align: center;
    }

    .hero h2 {
      font-size: 36px;
      margin-bottom: 15px;
    }

    .hero p {
      font-size: 18px;
      margin-bottom: 30px;
    }

    .hero button {
      padding: 12px 25px;
      background: #3498db;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .hero button:hover {
      background: #2980b9;
    }

    .contact-info {
      margin-top: 20px;
      text-align: center;
      font-size: 16px;
    }

    .social-icons {
      margin-top: 15px;
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .social-icons a {
      text-decoration: none;
      font-size: 20px;
      color: #2c3e50;
      transition: color 0.3s;
    }

    .social-icons a:hover {
      color: #3498db;
    }

    footer {
      margin-top: auto;
      padding: 20px;
      background: #f1f1f1;
      text-align: center;
      color: #666;
      font-size: 14px;
      animation: fadeUp 1s ease-in-out;
    }

    @keyframes fadeUp {
      from { transform: translateY(30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeDown {
      from { transform: translateY(-30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @media (max-width: 600px) {
      header h1 { font-size: 22px; }
      .hero h2 { font-size: 28px; }
      section h2 { font-size: 24px; }
    }
  </style>
</head>
<body>

  <header>
    <h1>Minha Loja</h1>
    <?php if (isset($_SESSION['usuario'])): ?>
      <span>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
    <?php else: ?>
      <a href="login.php" style="color: #ecf0f1; text-decoration: underline;">Entrar</a>
    <?php endif; ?>
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
