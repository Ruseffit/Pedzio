<?php
// cliente.php - Marketplace de Alimentos para Clientes
session_start();
require_once 'conexion.php';

// Validar que el cliente esté logueado
session_start();
if (!isset($_SESSION['id_cliente'])) {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

$id_cliente = $_SESSION['id_cliente'];

// Obtener productosufacturados
try {
    $stmt = $pdo->query("SELECT id_producto, nombre, descripcion, precio, imagen, id_usuario FROM Producto WHERE activo = 1");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    error_log("Error cargando productos: " . $e->getMessage());
}

// Obtener contador del carrito (opcional, si quieres mostrar productos en carrito)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedzio — Marketplace de Alimentos</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --orange: #FF6B2B; --orange-hover: #e85a1e;
    --navy: #1A2E4A; --white: #FFFFFF;
    --gray-bg: #F5F5F5; --gray-text: #6B7280;
    --gray-light: #E5E7EB; --shadow-sm: 0 2px 8px rgba(26,46,74,0.08);
    --shadow-md: 0 8px 28px rgba(26,46,74,0.12); --radius: 12px;
  }
  body { font-family: 'Poppins', sans-serif; background: var(--gray-bg); color: var(--navy); }

  /* NAVBAR */
  .navbar {
    position: sticky; top: 0; z-index: 100;
    background: var(--white); border-bottom: 1px solid var(--gray-light);
    display: flex; align-items: center; gap: 20px;
    padding: 0 5%; height: 68px;
  }
  .brand { font-size: 1.2rem; font-weight: 800; white-space: nowrap; color: var(--navy); text-decoration: none; }
  .brand em { font-style: normal; color: var(--orange); }
  .search-wrap { flex: 1; max-width: 520px; margin: 0 auto; position: relative; }
  .search-wrap input {
    width: 100%; padding: 10px 20px 10px 44px;
    border: 1.5px solid var(--gray-light); border-radius: 30px;
    background: var(--gray-bg); font-family: 'Poppins', sans-serif;
    font-size: 0.875rem; color: var(--navy); outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .search-wrap input:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(255,107,43,0.12); }
  .search-wrap .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; }
  .cart-btn {
    position: relative; cursor: pointer; background: none; border: none;
    font-size: 26px; color: var(--navy); transition: transform 0.2s; margin-left: auto;
    text-decoration: none;
  }
  .cart-btn:hover { transform: scale(1.1); }
  .cart-badge {
    position: absolute; top: -5px; right: -7px;
    background: var(--orange); color: var(--white);
    font-size: 0.65rem; font-weight: 700;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
  }

  /* ALERTAS */
  .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
  .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

  /* BANNER */
  .container { max-width: 1200px; margin: 0 auto; padding: 32px 5%; }
  .banner {
    background: var(--navy); border-radius: 15px;
    padding: 36px 44px; margin-bottom: 44px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 20px;
    position: relative; overflow: hidden;
  }
  .banner::before {
    content: ''; position: absolute; right: -40px; top: -40px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,107,43,0.12);
  }
  .banner-text h2 { color: var(--white); font-size: clamp(1.2rem,2.5vw,1.75rem); font-weight: 800; margin-bottom: 6px; }
  .banner-tag { display: inline-block; background: var(--orange); color: var(--white); font-size: 0.8rem; font-weight: 700; border-radius: 20px; padding: 5px 16px; }
  .banner-emoji { font-size: 64px; }

  /* PRODUCTS */
  .products-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
  .products-header h2 { font-size: 1.25rem; font-weight: 700; color: var(--navy); }
  .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 24px; }

  .product-card {
    background: var(--white); border-radius: var(--radius);
    box-shadow: var(--shadow-sm); overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s;
  }
  .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
  .product-img {
    width: 100%; height: 160px;
    background: linear-gradient(135deg, #fde8db, #ffd4c2);
    display: flex; align-items: center; justify-content: center;
    font-size: 64px; overflow: hidden;
  }
  .product-img img { width: 100%; height: 100%; object-fit: cover; }
  .product-body { padding: 16px; }
  .product-name { font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
  .product-desc { font-size: 0.8rem; color: var(--gray-text); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 14px; }
  .product-footer { display: flex; align-items: center; justify-content: space-between; }
  .product-price { font-size: 1.1rem; font-weight: 800; color: var(--navy); }
  .product-price span { font-size: 0.75rem; font-weight: 500; color: var(--gray-text); }

  .btn-add {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--orange); color: var(--white);
    border: none; font-size: 20px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background 0.25s, transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 12px rgba(255,107,43,0.3);
  }
  .btn-add:hover { background: var(--navy); transform: scale(1.1); }

  @media (max-width: 600px) {
    .search-wrap { max-width: 100%; order: 3; width: 100%; }
    .navbar { flex-wrap: wrap; height: auto; padding: 12px 5%; }
    .banner { padding: 24px; }
    .banner-emoji { display: none; }
  }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.html" class="brand">Ped<em>zio</em></a>
  <div class="search-wrap">
    <span class="search-icon">🔍</span>
    <input type="text" placeholder="¿Qué se te antoja comer hoy?...">
  </div>
  <a href="carrito_ver.php" class="cart-btn">
    🛒
    <span class="cart-badge" id="cartCount"><?= $cart_count ?></span>
  </a>
</nav>

<!-- CONTENIDO -->
<div class="container">

  <!-- ALERTAS -->
  <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    <div class="alert alert-success">
      ✅ ¡Pedido realizado exitosamente! Tu美食 está en preparación.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
      ❌ Error: 
      <?php 
      if ($_GET['error'] === 'producto_invalido') echo 'Producto inválido';
      elseif ($_GET['error'] === 'precio_invalido') echo 'Precio inválido';
      elseif ($_GET['error'] === 'orden_fail') echo 'No se pudo procesar tu orden. Inténtalo de nuevo';
      else echo $_GET['error'];
      ?>
    </div>
  <?php endif; ?>

  <!-- BANNER -->
  <div class="banner">
    <div class="banner-text">
      <h2>¡Ofertas de hoy disponibles ahora mismo!</h2>
      <span class="banner-tag">⚡ Hasta 30% de descuento en platos seleccionados</span>
    </div>
    <div class="banner-emoji">🍔</div>
  </div>

  <!-- PRODUCTOS -->
  <div class="products-header">
    <h2>Los platos más solicitados</h2>
  </div>

  <div class="products-grid">
    <?php if (empty($products)): ?>
      <p style="color: var(--gray-text); text-align: center; grid-column: 1/-1; padding: 40px;">
        No hay productos disponibles en este momento.
      </p>
    <?php else: ?>
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <div class="product-img">
            <?php if (!empty($p['imagen']) && filter_var($p['imagen'], FILTER_VALIDATE_URL)): ?>
              <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
            <?php else: ?>
              🍽️
            <?php endif; ?>
          </div>
          <div class="product-body">
            <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
            <div class="product-desc"><?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción') ?></div>
            
            <div class="product-footer">
              <div class="product-price">
                <span>S/. </span><?= number_format($p['precio'], 2) ?>
              </div>
              
              <form method="POST" action="carrito.php" style="margin:0">
                <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                <input type="hidden" name="precio" value="<?= $p['precio'] ?>">
                <input type="hidden" name="id_emprendedor" value="<?= $p['id_usuario'] ?>">
                
                <button type="submit" class="btn-add" title="Añadir al carrito">+</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
