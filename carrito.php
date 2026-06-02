<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['idcliente'])) {
    header("Location: logincliente.php");
    exit();
}

$id_cliente = (int) $_SESSION['idcliente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idproducto'])) {
    $id_producto = (int) $_POST['idproducto'];
    $precio_plato = floatval($_POST['precio'] ?? 0);
    $id_emprendedor = (int) ($_POST['idemprendedor'] ?? 0);

    if ($id_producto <= 0) {
        header("Location: cliente.php?error=productoinvalido");
        exit();
    }

    if ($precio_plato <= 0) {
        header("Location: cliente.php?error=precioinvalido");
        exit();
    }

    if ($id_emprendedor <= 0) {
        header("Location: cliente.php?error=emprendedorinvalido");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $sql_pedido = "INSERT INTO Pedido (estado, total, id_cliente, id_usuario, activo)
                       VALUES ('Pendiente', :total, :id_cliente, :id_usuario, 1)";
        $stmt_ped = $pdo->prepare($sql_pedido);
        $stmt_ped->execute([
            ':total' => $precio_plato,
            ':id_cliente' => $id_cliente,
            ':id_usuario' => $id_emprendedor
        ]);

        $id_pedido = $pdo->lastInsertId();

        $sql_detalle = "INSERT INTO DetallePedido (cantidad, precio_unitario, subtotal, id_pedido, id_producto)
                        VALUES (1, :precio, :subtotal, :id_ped, :id_prod)";
        $stmt_det = $pdo->prepare($sql_detalle);
        $stmt_det->execute([
            ':precio' => $precio_plato,
            ':subtotal' => $precio_plato,
            ':id_ped' => $id_pedido,
            ':id_prod' => $id_producto
        ]);

        $sql_ingreso = "INSERT INTO Ingreso (monto, descripcion, id_pedido, id_usuario, activo)
                        VALUES (:monto, :desc, :id_ped, :uid, 1)";
        $stmt_ing = $pdo->prepare($sql_ingreso);
        $stmt_ing->execute([
            ':monto' => $precio_plato,
            ':desc' => 'Venta automatizada pedido #' . $id_pedido,
            ':id_ped' => $id_pedido,
            ':uid' => $id_emprendedor
        ]);

        $pdo->commit();
        header("Location: cliente.php?status=success");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error carrito.php: " . $e->getMessage());
        header("Location: cliente.php?error=orden_fail");
        exit();
    }
} else {
    header("Location: cliente.php");
    exit();
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
