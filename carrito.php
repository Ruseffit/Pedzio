<?php
session_start();
require_once 'conexion.php';

// Inicializar carrito
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Agregar producto al carrito
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

// Cargar productos activos
try {
    $stmt = $pdo->query("SELECT id_producto, nombre, descripcion, precio, imagen, id_usuario FROM Producto WHERE activo = 1");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    error_log("Error cargando productos: " . $e->getMessage());
}

// Contador total del carrito
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int) $item['cantidad'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedzio — Marketplace</title>
<style>
/* tu CSS actual queda igual */
</style>
</head>
<body>

<!-- Tu navbar actual -->
<div>
    Carrito: <?php echo $cart_count; ?>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'agregado'): ?>
    <div style="background:#d1fae5;padding:10px;border-radius:8px;margin:10px 0;">
        Plato agregado al carrito correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'producto_invalido'): ?>
    <div style="background:#fee2e2;padding:10px;border-radius:8px;margin:10px 0;">
        Producto inválido.
    </div>
<?php endif; ?>

<div class="products">
<?php if (!empty($products)): ?>
    <?php foreach ($products as $product): ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($product['nombre']); ?></h3>
            <p><?php echo htmlspecialchars($product['descripcion']); ?></p>
            <p>S/. <?php echo number_format($product['precio'], 2); ?></p>

            <form method="POST" action="cliente.php">
                <input type="hidden" name="id_producto" value="<?php echo (int) $product['id_producto']; ?>">
                <button type="submit">Agregar plato</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No hay productos disponibles en este momento.</p>
<?php endif; ?>
</div>

</body>
</html>
