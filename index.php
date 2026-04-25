<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minha Loja</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f5f7fa;
      color: #333;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 8%;
      background: #0d6efd;
      color: white;
    }

    header h1 {
      font-size: 22px;
    }

    header a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }

    /* HERO */
    .hero {
      position: relative;
      height: 90vh;
      overflow: hidden;
    }

    .slides {
      display: flex;
      width: 200%;
      height: 100%;
      transition: transform 0.6s ease-in-out;
    }

    .slide {
      width: 100%;
      height: 100%;
      position: relative;
    }

    .slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
    }

    .hero-content {
      position: absolute;
      top: 50%;
      left: 8%;
      transform: translateY(-50%);
      color: white;
      max-width: 500px;
      animation: fadeUp 1s ease;
    }

    .hero-content h2 {
      font-size: 42px;
      margin-bottom: 10px;
    }

    .hero-content p {
      margin-bottom: 20px;
    }

    .hero button {
      padding: 12px 25px;
      border: none;
      background: #ff6b00;
      color: white;
      border-radius: 25px;
      cursor: pointer;
      transition: 0.3s;
    }

    .hero button:hover {
      background: #e65c00;
    }

    /* FEATURES */
    .features {
      padding: 60px 8%;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }

    .card {
      background: white;
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: 0.3s;
    }

    .card:hover {
      transform: translateY(-8px);
    }

    .card i {
      font-size: 30px;
      color: #0d6efd;
      margin-bottom: 10px;
    }

    /* ABOUT */
    .about {
      padding: 60px 8%;
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      align-items: center;
    }

    .about img {
      width: 100%;
      max-width: 400px;
      border-radius: 15px;
    }

    .about-text {
      flex: 1;
    }

    /* CONTACT */
    .contact {
      padding: 60px 8%;
      background: white;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }

    .contact-card {
      padding: 20px;
      border-radius: 12px;
      background: #f0f4ff;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #0d6efd;
      color: white;
      margin-top: 40px;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

  </style>
</head>
<body>

<header>
  <h1>Minha Loja</h1>
  <?php if (isset($_SESSION['usuario'])): ?>
    <span>Olá, <?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
  <?php else: ?>
    <a href="login.php">Entrar</a>
  <?php endif; ?>
</header>

<!-- HERO -->
<section class="hero">
  <div class="slides" id="slides">
    <div class="slide">
      <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da" alt="">
      <div class="overlay"></div>
    </div>
    <div class="slide">
      <img src="https://images.unsplash.com/photo-1607083206968-13611e3d76db" alt="">
      <div class="overlay"></div>
    </div>
  </div>

  <div class="hero-content">
    <h2>Compre com Qualidade</h2>
    <p>Os melhores produtos com preços incríveis.</p>
    <button onclick="irParaProdutos()">Ver Produtos</button>
  </div>
</section>

<!-- FEATURES -->
<section class="features">
  <div class="card">
    <i class="fas fa-truck"></i>
    <h3>Entrega Rápida</h3>
    <p>Receba seus produtos em tempo recorde.</p>
  </div>
  <div class="card">
    <i class="fas fa-shield-alt"></i>
    <h3>Compra Segura</h3>
    <p>Pagamentos 100% protegidos.</p>
  </div>
  <div class="card">
    <i class="fas fa-headset"></i>
    <h3>Suporte 24h</h3>
    <p>Estamos sempre disponíveis.</p>
  </div>
</section>

<!-- ABOUT -->
<section class="about">
  <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d" alt="">
  <div class="about-text">
    <h2>Quem Somos</h2>
    <p>Somos uma loja moderna focada em qualidade e experiência do cliente.</p>
  </div>
</section>

<!-- CONTACT -->
<section class="contact">
  <h2>Contactos</h2>
  <div class="contact-grid">
    <div class="contact-card">📞 +258 87 682 1594</div>
    <div class="contact-card">📧 matiasmatavel@gmail.com</div>
    <div class="contact-card">📍 Tete, Moçambique</div>
  </div>
</section>

<footer>
  &copy; <?= date("Y") ?> Loja Virtual
</footer>

<script>
  let index = 0;
  const slides = document.getElementById("slides");

  setInterval(() => {
    index = (index + 1) % 2;
    slides.style.transform = `translateX(-${index * 100}%)`;
  }, 4000);

  function irParaProdutos() {
    window.location.href = "verprodutos.php";
  }
</script>

</body>
</html>