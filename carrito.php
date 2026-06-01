<?php
// carrito.php - Procesa las compras del Marketplace de forma relacional
session_start();
require_once 'conexion.php';

// Simulamos una sesión de cliente iniciada (ID = 1, Carlos Mendoza en tu script SQL) si no existe
if (!isset($_SESSION['id_cliente'])) {
    $_SESSION['id_cliente'] = 1; 
}

// Verificar que recibamos un producto válido por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = intval($_POST['id_producto']);
    $precio_plato = floatval($_POST['precio'] ?? 0);
    $id_emprendedor = 1; // ID de tu MYPE demo 'Sabores del Norte'

    try {
        // Iniciamos una transacción limpia para evitar bloqueos del servidor
        $pdo->beginTransaction();

        // 1. Insertamos la cabecera del Pedido con estado inicial 'Pendiente'
        $sql_pedido = "INSERT INTO Pedido (estado, total, id_cliente, id_usuario)
                       VALUES ('Pendiente', :total, :id_cliente, :id_usuario)";
        
        $stmt_ped = $pdo->prepare($sql_pedido);
        $stmt_ped->execute([
            ':total'      => $precio_plato, 
            ':id_cliente' => $_SESSION['id_cliente'], 
            ':id_usuario' => $id_emprendedor
        ]);
        
        $id_pedido = $pdo->lastInsertId();

        // 2. Insertamos la línea de detalle con el precio histórico del momento de compra
        $sql_detalle = "INSERT INTO DetallePedido (cantidad, precio_unitario, subtotal, id_pedido, id_producto)
                        VALUES (1, :precio, :subtotal, :id_ped, :id_prod)";
        
        $stmt_det = $pdo->prepare($sql_detalle);
        $stmt_det->execute([
            ':precio'  => $precio_plato,
            ':subtotal'=> $precio_plato,
            ':id_ped'  => $id_pedido,
            ':id_prod' => $id_producto
        ]);

        // 3. Insertamos el flujo de caja en la tabla de Ingresos vinculada al pedido
        $sql_ingreso = "INSERT INTO Ingreso (monto, descripcion, id_pedido)
                        VALUES (:monto, :desc, :id_ped)";
        
        $stmt_ing = $pdo->prepare($sql_ingreso);
        $stmt_ing->execute([
            ':monto'  => $precio_plato, 
            ':desc'   => 'Venta automatizada pedido #' . $id_pedido, 
            ':id_ped' => $id_pedido
        ]);

        // Confirmamos todos los datos en las tablas
        $pdo->commit();

        // Redireccionamos de vuelta al marketplace con éxito para ver los cambios reflejados
        header("Location: cliente.php?status=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error procesando la orden en el modelo relacional: " . $e->getMessage());
    }
} else {
    // Si entran sin presionar un botón, se les regresa
    header("Location: cliente.php");
    exit();
}
?>