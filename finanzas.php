<?php
// finanzas.php - Gestión de Caja Chica para el Emprendedor MYPE
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['monto'])) {
    $monto       = floatval($_POST['monto']);
    $tipo        = $_POST['tipo'] ?? 'gasto';
    $categoria   = htmlspecialchars($_POST['categoria'] ?? 'General');
    $descripcion = htmlspecialchars($_POST['descripcion'] ?? '');
    $id_usuario  = 1; // ID fijo de tu MYPE demo

    try {
        if ($tipo === 'gasto') {
            // Guardamos directo en la tabla independiente de Gastos
            $sql = "INSERT INTO Gasto (monto, categoria, descripcion, id_usuario)
                    VALUES (:monto, :cat, :descr, :uid)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':cat'   => $categoria,
                ':descr' => $descripcion,
                ':uid'   => $id_usuario
            ]);
        } else {
            // Guardamos directo en la tabla de Ingresos (con id_pedido NULL por ser manual)
            $sql = "INSERT INTO Ingreso (monto, descripcion, id_pedido)
                    VALUES (:monto, :descr, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':descr' => '[' . $categoria . '] ' . $descripcion
            ]);
        }

        header("Location: emprendedor.php?status=finanzas_ok");
        exit();

    } catch (PDOException $e) {
        die("Error al registrar movimiento financiero: " . $e->getMessage());
    }
} else {
    header("Location: emprendedor.php");
    exit();
}
?>
