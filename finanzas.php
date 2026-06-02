<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['idusuario'])) {
    header("Location: emprendedor.php");
    exit();
}

$id_usuario = (int) $_SESSION['idusuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['monto'])) {
    $monto = floatval($_POST['monto']);
    $tipo = $_POST['tipo'] ?? '';
    $categoria = htmlspecialchars($_POST['categoria'] ?? 'General');
    $descripcion = htmlspecialchars($_POST['descripcion'] ?? '');

    if ($monto <= 0) {
        header("Location: emprendedor.php?error=monto_invalido");
        exit();
    }

    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        header("Location: emprendedor.php?error=tipo_invalido");
        exit();
    }

    try {
        if ($tipo === 'gasto') {
            $sql = "INSERT INTO Gasto (monto, categoria, descripcion, id_usuario, activo)
                    VALUES (:monto, :cat, :descr, :uid, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':cat' => $categoria,
                ':descr' => $descripcion,
                ':uid' => $id_usuario
            ]);
        } else {
            $sql = "INSERT INTO Ingreso (monto, descripcion, id_pedido, id_usuario, activo)
                    VALUES (:monto, :descr, NULL, :uid, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':descr' => '[' . $categoria . '] ' . $descripcion,
                ':uid' => $id_usuario
            ]);
        }

        header("Location: emprendedor.php?status=finanzas_ok");
        exit();
    } catch (PDOException $e) {
        error_log("Error finanzas.php: " . $e->getMessage());
        header("Location: emprendedor.php?error=registro_fail");
        exit();
    }
} else {
    header("Location: emprendedor.php");
    exit();
}
?>
