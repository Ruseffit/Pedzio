<?php
// conexion.php - Conexión Oficial y Mapeada para InfinityFree

$host     = "sql306.infinityfree.com"; 
$db_name  = "if0_42067399_pedzio_db";  // ← Este es el nombre exacto de tu lista
$user     = "if0_42067399";            
$password = "74fyMkbcdFbII";           
$port     = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;port=$port;charset=utf8mb4", 
        $user, 
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>
