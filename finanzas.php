<?php
// finanzas.php - Gestión de Caja Chica para el Emprendedor MYPE
session_start();
require_once 'conexion.php';

// Validar que el usuario esté logueado
session_start();
if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
    header("Location: index.php?error=sesion_expirada");
    exit();
}
$id_usuario = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['monto'])) {
    
    // Validaciones
    $monto = floatval($_POST['monto']);
    $tipo = $_POST['tipo'] ?? 'gasto';
    $categoria = htmlspecialchars($_POST['categoria'] ?? 'General');
    $descripcion = htmlspecialchars($_POST['descripcion'] ?? '');

    // Validar monto
    if ($monto <= 0) {
        header("Location: emprendedor.php?error=monto_invalido");
        exit();
    }

    // Validar tipo (solo acepta 'ingreso' o 'gasto')
    if (!in_array($tipo, ['ingreso', 'gasto'])) {
        header("Location: emprendedor.php?error=tipo_invalido");
        exit();
    }

    try {
        if ($tipo === 'gasto') {
            // Guardar en tabla Gastos con id_usuario
            $sql = "INSERT INTO Gasto (monto, categoria, descripcion, id_usuario, fecha_gasto, activo)
                    VALUES (:monto, :cat, :descr, :uid, CURDATE(), 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':cat'   => $categoria,
                ':descr' => $descripcion,
                ':uid'   => $id_usuario
            ]);
        } else {
            // Guardar en tabla Ingresos CON id_usuario (corregido)
            $sql = "INSERT INTO Ingreso (monto, descripcion, id_pedido, id_usuario, activo)
                    VALUES (:monto, :descr, NULL, :uid, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':monto' => $monto,
                ':descr' => '[' . $categoria . '] ' . $descripcion,
                ':uid'   => $id_usuario
            ]);
        }

        // Redirección con mensaje de éxito
        header("Location: emprendedor.php?status=finanzas_ok");
        exit();

    } catch (PDOException $e) {
        // Log del error (para debug)
        error_log("Error finanzas.php: " . $e->getMessage());
        
        // Redirección con error
        header("Location: emprendedor.php?error=registro_fail");
        exit();
    }
} else {
    // Si accede directamente sin POST, redirigir
    header("Location: emprendedor.php");
    exit();
}
?>
