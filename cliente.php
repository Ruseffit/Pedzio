<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = (int) $_POST['id_producto'];

    if ($id_producto > 0) {
        if (!isset($_SESSION['cart'][$id_producto])) {
            $_SESSION['cart'][$id_producto] = [
                'cantidad' => 1
            ];
        } else {
            $_SESSION['cart'][$id_producto]['cantidad']++;
        }

        header("Location: cliente.php?status=agregado");
        exit();
    } else {
        header("Location: cliente.php?error=producto_invalido");
        exit();
    }
}

try {
    $stmt = $pdo->query("SELECT id_producto, nombre, descripcion, precio, imagen, id_usuario FROM Producto WHERE activo = 1");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    error_log("Error cargando productos: " . $e->getMessage());
}

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int) ($item['cantidad'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedzio — Sistema de Gestión para Delivery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --orange: #FF6B2B;
    --orange-hover: #e85a1e;
    --navy: #1A2E4A;
    --navy-light: #243d61;
    --white: #FFFFFF;
    --gray-bg: #F5F5F5;
    --gray-text: #6B7280;
    --gray-border: #E5E7EB;
    --shadow-sm: 0 2px 8px rgba(26,46,74,0.08);
    --shadow-md: 0 8px 32px rgba(26,46,74,0.14);
    --shadow-lg: 0 20px 60px rgba(26,46,74,0.18);
    --radius: 12px;
    --radius-lg: 20px;
  }

  html { scroll-behavior: smooth; }
  body { font-family: 'Poppins', sans-serif; background: var(--gray-bg); color: var(--navy); overflow-x: hidden; }

  .navbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    background: var(--white);
    box-shadow: 0 2px 16px rgba(26,46,74,0.08);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 5%; height: 70px;
  }
  .navbar-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
  .navbar-brand img { height: 38px; width: 38px; object-fit: contain; border-radius: 8px; background: var(--gray-bg); }
  .navbar-brand .logo-placeholder {
    height: 38px; width: 38px; border-radius: 8px;
    background: linear-gradient(135deg, var(--orange), var(--navy));
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: 16px;
  }
  .navbar-brand span { font-size: 1.25rem; font-weight: 700; color: var(--navy); letter-spacing: -0.5px; }
  .navbar-brand span em { font-style: normal; color: var(--orange); }

  .navbar-links { display: flex; align-items: center; gap: 36px; list-style: none; }
  .navbar-links a { text-decoration: none; color: var(--navy); font-size: 0.9rem; font-weight: 500; transition: color 0.25s; }
  .navbar-links a:hover { color: var(--orange); }

  .btn-cta {
    background: var(--navy); color: var(--white);
    border: none; border-radius: 24px;
    padding: 10px 24px; font-family: 'Poppins', sans-serif;
    font-size: 0.875rem; font-weight: 600; cursor: pointer;
    transition: background 0.25s, transform 0.2s, box-shadow 0.25s;
    text-decoration: none; display: inline-block;
  }
  .btn-cta:hover { background: var(--navy-light); transform: translateY(-1px); box-shadow: var(--shadow-md); }

  .hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; }
  .hamburger span { display: block; width: 24px; height: 2px; background: var(--navy); border-radius: 2px; transition: 0.3s; }

  .hero {
    min-height: 100vh; padding: 100px 5% 60px;
    background: var(--gray-bg);
    display: flex; align-items: center;
  }
  .hero-inner {
    max-width: 1200px; margin: 0 auto; width: 100%;
    display: flex; gap: 60px; align-items: center;
  }
  .hero-left { flex: 1; }
  .hero-badge {
    display: inline-block;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 2px;
    color: var(--orange); text-transform: uppercase;
    background: rgba(255,107,43,0.1); border-radius: 20px;
    padding: 6px 14px; margin-bottom: 20px;
  }
  .hero-left h1 {
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800; color: var(--navy);
    line-height: 1.15; letter-spacing: -1px;
    margin-bottom: 20px;
  }
  .hero-left h1 span { color: var(--orange); }
  .hero-left p {
    font-size: 1rem; color: var(--gray-text);
    line-height: 1.75; max-width: 480px; margin-bottom: 36px;
  }
  .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
  .btn-primary {
    background: var(--orange); color: var(--white);
    border: none; border-radius: 24px;
    padding: 14px 30px; font-family: 'Poppins', sans-serif;
    font-size: 0.9rem; font-weight: 600; cursor: pointer;
    transition: all 0.25s; text-decoration: none; display: inline-block;
    box-shadow: 0 4px 16px rgba(255,107,43,0.35);
  }
  .btn-primary:hover { background: var(--orange-hover); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,107,43,0.45); }
  .btn-outline {
    background: transparent; color: var(--navy);
    border: 2px solid var(--navy); border-radius: 24px;
    padding: 12px 28px; font-family: 'Poppins', sans-serif;
    font-size: 0.9rem; font-weight: 600; cursor: pointer;
    transition: all 0.25s; text-decoration: none; display: inline-block;
  }
  .btn-outline:hover { background: var(--navy); color: var(--white); transform: translateY(-2px); }

  .hero-right { flex: 1; display: flex; justify-content: center; }
  .hero-img-wrap {
    width: 100%; max-width: 520px;
    border-radius: var(--radius); overflow: hidden;
    box-shadow: var(--shadow-lg);
  }
  .hero-img-wrap img { width: 100%; display: block; border-radius: var(--radius); }
  .hero-img-placeholder {
    width: 100%; aspect-ratio: 4/3;
    background: linear-gradient(135deg, #1A2E4A 0%, #243d61 50%, #FF6B2B22 100%);
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
  }
  .hero-img-placeholder::before {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,0.03) 20px, rgba(255,255,255,0.03) 40px);
  }
  .hero-img-placeholder .plate-icon { font-size: 100px; filter: drop-shadow(0 8px 24px rgba(0,0,0,0.4)); }

  .features { padding: 80px 5%; background: var(--white); }
  .section-label { text-align: center; color: var(--orange); font-size: 0.75rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px; }
  .section-title { text-align: center; font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 800; color: var(--navy); margin-bottom: 48px; letter-spacing: -0.5px; }
  .features-grid { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 28px; }
  .feature-card {
    background: var(--gray-bg); border-radius: var(--radius); padding: 32px 28px;
    transition: transform 0.25s, box-shadow 0.25s;
  }
  .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
  .feature-icon { font-size: 36px; margin-bottom: 16px; }
  .feature-card h3 { font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: 10px; }
  .feature-card p { font-size: 0.875rem; color: var(--gray-text); line-height: 1.65; }

  footer {
    background: var(--navy); color: rgba(255,255,255,0.6);
    text-align: center; padding: 28px 5%;
    font-size: 0.82rem;
  }
  footer strong { color: var(--orange); }

  @media (max-width: 860px) {
    .navbar-links { display: none; position: absolute; top: 70px; left: 0; right: 0; background: var(--white); flex-direction: column; padding: 20px 5%; gap: 16px; box-shadow: var(--shadow-md); }
    .navbar-links.open { display: flex; }
    .hamburger { display: flex; }
    .navbar .btn-cta { display: none; }
    .hero-inner { flex-direction: column; gap: 40px; text-align: center; }
    .hero-left p { margin: 0 auto 36px; }
    .hero-btns { justify-content: center; }
  }
</style>
</head>
<body>

<nav class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 5%; background: var(--white); box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 1000;">
  <a href="#" class="navbar-brand" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--navy); font-weight: 700; font-size: 22px;">
    <div class="logo-placeholder" style="background: var(--orange); color: white; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800;">P</div>
    <span>Ped<em style="color: var(--orange); font-style: normal;">zio</em></span>
  </a>

  <ul class="navbar-links" id="navLinks" style="display: flex; list-style: none; gap: 24px;">
    <li><a href="#" style="text-decoration: none; color: var(--navy); font-weight: 500;">Inicio</a></li>
    <li><a href="#features" style="text-decoration: none; color: var(--navy); font-weight: 500;">Nosotros</a></li>
    <li><a href="#" style="text-decoration: none; color: var(--navy); font-weight: 500;">Contacto</a></li>
  </ul>

  <div class="nav-actions" style="display: flex; gap: 10px; align-items: center;">
    <a href="cliente.php" class="btn-outline" style="text-decoration: none; color: var(--orange); border: 2px solid var(--orange); padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; transition: 0.3s;">
      🛒 Ver Catálogo
    </a>
    <a href="emprendedor.php" class="btn-cta" style="text-decoration: none; background: var(--navy); color: white; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; transition: 0.3s;">
      👨‍🍳 Panel MYPE
    </a>
    <a href="superadmin.php" style="text-decoration: none; color: #9CA3AF; font-size: 12px; margin-left: 5px; font-weight: 500;" title="Consola Global">
      ⚙️ Admin
    </a>
  </div>

  <div class="hamburger" onclick="document.getElementById('navLinks').classList.toggle('open')" style="display: none; flex-direction: column; gap: 5px; cursor: pointer;">
    <span style="width: 25px; height: 3px; background: var(--navy); border-radius: 2px;"></span>
    <span style="width: 25px; height: 3px; background: var(--navy); border-radius: 2px;"></span>
    <span style="width: 25px; height: 3px; background: var(--navy); border-radius: 2px;"></span>
  </div>
</nav>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-left">
      <span class="hero-badge">⚡ Sistema de Gestión Híbrida V1.0</span>
      <h1>Optimiza la <span>Toma de Decisiones</span> en tu Delivery de Alimentos</h1>
      <p>Pedzio es el sistema web gratuito diseñado para emprendedores de delivery en Lima. Registra pedidos, controla ingresos y gastos, y toma decisiones reales basadas en datos — desde cualquier dispositivo.</p>
      <div class="hero-btns">
        <a href="cliente.php" class="btn-primary">🚀 Comenzar gratis</a>
        <a href="#features" class="btn-outline">Ver cómo funciona</a>
      </div>
    </div>
    <div class="hero-right">
      <div class="hero-img-wrap">
        <div class="hero-img-placeholder">
          <span class="plate-icon">🍽️</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="features" id="features">
  <p class="section-label">¿Qué hace Pedzio?</p>
  <h2 class="section-title">Todo lo que tu negocio necesita</h2>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">📦</div>
      <h3>Registro de Pedidos</h3>
      <p>Ingresa, consulta y actualiza tus pedidos en tiempo real. Olvídate de los cuadernos y WhatsApp.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">💰</div>
      <h3>Control Financiero</h3>
      <p>Registra cada ingreso y gasto operativo. Siempre sabrás si tu negocio está ganando o perdiendo.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">📊</div>
      <h3>Reportes Automáticos</h3>
      <p>Gráficos y tablas comprensibles generados automáticamente para que entiendas tu negocio de un vistazo.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">☁️</div>
      <h3>Base de Datos en la Nube</h3>
      <p>Tu información segura y accesible desde cualquier celular o computadora con internet, sin perder nada.</p>
    </div>
  </div>
</section>

<footer>
  <p>© 2026 <strong>Pedzio</strong> — Hecho por Jennifer (Zarai) · Con fines universitarios</p>
</footer>

</body>
</html>
