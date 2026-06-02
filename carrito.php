<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_cliente'])) {
    header("Location: login_cliente.php");
    exit();
}

$id_cliente = (int) $_SESSION['id_cliente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = (int) $_POST['id_producto'];

    if ($id_producto <= 0) {
        header("Location: cliente.php?error=producto_invalido");
        exit();
    }

    try {
        $stmt_prod = $pdo->prepare("SELECT precio, id_usuario FROM Producto WHERE id_producto = :id AND activo = 1");
        $stmt_prod->execute([':id' => $id_producto]);
        $producto_data = $stmt_prod->fetch();

        if (!$producto_data) {
            header("Location: cliente.php?error=producto_invalido");
            exit();
        }

        $precio_plato = (float) $producto_data['precio'];
        $id_emprendedor = (int) $producto_data['id_usuario'];

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
}

header("Location: cliente.php");
exit();
?>
