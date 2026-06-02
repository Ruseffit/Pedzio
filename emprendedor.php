<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['idusuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = (int) $_SESSION['idusuario'];

try {
    $stmt_kpi = $pdo->prepare("SELECT COUNT(*) AS pedidos_hoy, COALESCE(SUM(i.monto),0) AS ingresos_totales
                               FROM Pedido p
                               LEFT JOIN Ingreso i ON i.id_pedido = p.id_pedido AND i.activo = 1
                               WHERE p.id_usuario = :uid AND p.activo = 1 AND DATE(p.fecha_registro) = CURDATE()");
    $stmt_kpi->execute([':uid' => $id_usuario]);
    $kpi = $stmt_kpi->fetch();

    $stmt_pedidos = $pdo->prepare("SELECT p.id_pedido, c.nombre AS cliente, c.telefono AS telefono_cliente, p.total, p.estado, p.fecha_registro
                                   FROM Pedido p
                                   INNER JOIN Cliente c ON c.id_cliente = p.id_cliente
                                   WHERE p.id_usuario = :uid AND p.activo = 1
                                   ORDER BY p.fecha_registro DESC");
    $stmt_pedidos->execute([':uid' => $id_usuario]);
    $pedidos = $stmt_pedidos->fetchAll();
} catch (PDOException $e) {
    $kpi = ['pedidos_hoy' => 0, 'ingresos_totales' => 0];
    $pedidos = [];
    error_log("Error emprendedor.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedzio — Panel del Emprendedor</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --orange: #FF6B2B; --navy: #1A2E4A; --navy-light: #243d61;
    --white: #FFFFFF; --gray-bg: #F5F5F5; --gray-text: #6B7280;
    --gray-border: #E5E7EB; --shadow-sm: 0 2px 8px rgba(26,46,74,0.07);
    --radius: 10px;
  }
  body { font-family: 'Poppins', sans-serif; background: var(--gray-bg); display: flex; height: 100vh; overflow: hidden; }

  /* SIDEBAR */
  .sidebar {
    width: 250px; min-width: 250px; height: 100vh;
    background: var(--navy); display: flex; flex-direction: column;
    padding: 28px 0; overflow-y: auto;
  }
  .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 0 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 24px; }
  .sidebar-brand .logo { width: 36px; height: 36px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 16px; }
  .sidebar-brand span { color: var(--white); font-weight: 700; font-size: 1.05rem; }
  .sidebar-brand span em { font-style: normal; color: var(--orange); }

  .sidebar-nav { display: flex; flex-direction: column; gap: 4px; padding: 0 12px; flex: 1; }
  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-radius: 8px; cursor: pointer;
    color: rgba(255,255,255,0.55); font-size: 0.875rem; font-weight: 500;
    transition: all 0.2s; text-decoration: none;
  }
  .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.9); }
  .nav-item.active { background: var(--orange); color: var(--white); font-weight: 600; }
  .nav-icon { font-size: 17px; width: 20px; text-align: center; }

  /* MAIN */
  .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

  /* TOPBAR */
  .topbar {
    background: var(--white); border-bottom: 1px solid var(--gray-border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; height: 65px; min-height: 65px;
  }
  .topbar h1 { font-size: 1rem; font-weight: 700; color: var(--navy); }
  .topbar-profile { display: flex; align-items: center; gap: 12px; }
  .avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--orange), var(--navy)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; }
  .topbar-profile span { font-size: 0.875rem; font-weight: 600; color: var(--navy); }

  /* WORKSPACE */
  .workspace { flex: 1; overflow-y: auto; padding: 28px; background: var(--gray-bg); }

  /* KPI CARDS */
  .kpi-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 28px; }
  .kpi-card {
    background: var(--white); border-radius: var(--radius);
    padding: 24px; box-shadow: var(--shadow-sm);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(26,46,74,0.1); }
  .kpi-label { font-size: 0.78rem; color: var(--gray-text); font-weight: 500; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
  .kpi-value { font-size: 2rem; font-weight: 800; color: var(--navy); line-height: 1; }
  .kpi-sub { font-size: 0.75rem; color: var(--orange); font-weight: 600; margin-top: 6px; }
  .kpi-icon { font-size: 28px; float: right; margin-top: -4px; }

  /* BOTTOM GRID */
  .bottom-grid { display: grid; grid-template-columns: 1fr 1.4fr; gap: 20px; }

  .card { background: var(--white); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); }
  .card-title { font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--gray-border); }

  /* FORM */
  .form-group { margin-bottom: 16px; }
  .form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--navy); margin-bottom: 6px; }
  .form-group input, .form-group select {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--gray-border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.875rem;
    color: var(--navy); outline: none; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-group input:focus, .form-group select:focus {
    border-color: var(--orange); box-shadow: 0 0 0 3px rgba(255,107,43,0.12);
  }
  .btn-submit {
    width: 100%; padding: 12px; background: var(--orange); color: var(--white);
    border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
    font-size: 0.9rem; font-weight: 700; cursor: pointer;
    transition: background 0.2s, transform 0.2s; margin-top: 4px;
  }
  .btn-submit:hover { background: #e85a1e; transform: translateY(-1px); }

  /* TABLE */
  .data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
  .data-table th { text-align: left; padding: 10px 12px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-text); border-bottom: 2px solid var(--gray-border); }
  .data-table td { padding: 12px; border-bottom: 1px solid var(--gray-border); color: var(--navy); vertical-align: middle; }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tr:hover td { background: rgba(245,245,245,0.8); }

  .badge {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 600;
  }
  .badge-done { background: #d1fae5; color: #065f46; }
  .badge-pending { background: #fff3e0; color: #b45309; }
  .badge-cancel { background: #fee2e2; color: #991b1b; }

  @media (max-width: 900px) {
    .sidebar { display: none; }
    .kpi-row { grid-template-columns: 1fr 1fr; }
    .bottom-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 560px) {
    .kpi-row { grid-template-columns: 1fr; }
    .workspace { padding: 16px; }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo">P</div>
    <span>Ped<em>zio</em></span>
  </div>
  <nav class="sidebar-nav">
    <a href="#" class="nav-item active"><span class="nav-icon">📊</span> Panel General</a>
    <a href="#" class="nav-item"><span class="nav-icon">📦</span> Pedidos Recibidos</a>
    <a href="#" class="nav-item"><span class="nav-icon">💰</span> Registrar Finanzas</a>
    <a href="#" class="nav-item"><span class="nav-icon">⚙️</span> Configuración</a>
  </nav>
</aside>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <header class="topbar">
    <h1>Panel de Gestión Operativa</h1>
    <div class="topbar-profile">
      <div class="avatar">EP</div>
      <span>Emprendedor Pedzio</span>
    </div>
  </header>

  <!-- WORKSPACE -->
  <main class="workspace">

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon">📦</div>
        <div class="kpi-label">Pedidos de Hoy</div>
        <div class="kpi-value"><?= $pedidos_hoy ?></div>
        <div class="kpi-sub">Actualizado en tiempo real</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-icon">💵</div>
        <div class="kpi-label">Ingresos Totales</div>
        <div class="kpi-value">S/. <?= number_format($ingresos_hoy, 2) ?></div>
        <div class="kpi-sub">Flujo de caja acumulado</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-icon">🧾</div>
        <div class="kpi-label">Gastos Registrados</div>
        <div class="kpi-value">S/. <?= number_format($gastos_hoy, 2) ?></div>
        <div class="kpi-sub" style="font-weight: 600; color: <?= $balance_neto >= 0 ? '#10B981' : '#EF4444' ?>;">
          Margen Neto: S/. <?= number_format($balance_neto, 2) ?>
        </div>
      </div>
    </div>

    <!-- BOTTOM GRID -->
    <div class="bottom-grid">

      <!-- FORM -->
      <div class="card">
        <div class="card-title">📝 Registrar Ingreso / Gasto</div>
        <form method="POST" action="finanzas.php">
          <div class="form-group">
            <label>Tipo de movimiento</label>
            <select name="tipo">
              <option value="ingreso">💰 Ingreso</option>
              <option value="gasto">🧾 Gasto</option>
            </select>
          </div>
          <div class="form-group">
            <label>Monto (S/.)</label>
            <input type="number" name="monto" step="0.01" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label>Categoría</label>
            <input type="text" name="categoria" placeholder="Ej: Ingredientes, Envío, Pedido...">
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="descripcion" placeholder="Descripción breve...">
          </div>
          <button type="submit" class="btn-submit">Registrar Movimiento</button>
        </form>
      </div>

      <!-- TABLE -->
      <div class="card">
        <div class="card-title">📋 Pedidos Recientes</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Teléfono</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pedidos_recientes)): ?>
              <tr>
                <td colspan="6" style="padding: 20px; text-align: center; color: var(--gray-text);">No hay pedidos registrados hoy.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($pedidos_recientes as $pedido): 
                // Asignación de badges según estado
                $badge_class = 'badge-pending';
                $label_texto = $pedido['estado'];
                
                if ($pedido['estado'] === 'Entregado')  $badge_class = 'badge-done';
                if ($pedido['estado'] === 'Cancelado')  $badge_class = 'badge-cancel';
              ?>
              <tr>
                <td><strong>#<?= str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                <td><?= htmlspecialchars($pedido['telefono_cliente']) ?></td>
                <td><strong>S/. <?= number_format($pedido['total'], 2) ?></strong></td>
                <td><span class="badge <?= $badge_class ?>"><?= $label_texto ?></span></td>
                <td style="color: var(--gray-text); font-size: 0.75rem;"><?= date('d/m/Y H:i', strtotime($pedido['fecha_registro'])) ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </main>
</div>

</body>
</html>
