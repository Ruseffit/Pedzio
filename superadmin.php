<?php
require_once 'conexion.php';

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS totalmypes,
            (SELECT COUNT(*) FROM Pedido WHERE activo = 1) AS totalordenes,
            ROUND((SELECT COUNT(*) FROM Pedido WHERE estado = 'Entregado' AND activo = 1) * 100.0 / NULLIF((SELECT COUNT(*) FROM Pedido WHERE activo = 1), 0), 1) AS efectividadpct
        FROM Usuario
        WHERE rol = 'Emprendedor' AND activo = 1
    ");
    $globalkpis = $stmt->fetch();

    $stmt2 = $pdo->query("
        SELECT 
            u.id_usuario,
            u.nombre AS negocio,
            u.correo,
            u.activo,
            COUNT(DISTINCT pr.id_producto) AS total_productos,
            COUNT(DISTINCT pe.id_pedido) AS total_pedidos,
            COALESCE(SUM(i.monto), 0) AS facturacion_total
        FROM Usuario u
        LEFT JOIN Producto pr ON pr.id_usuario = u.id_usuario AND pr.activo = 1
        LEFT JOIN Pedido pe ON pe.id_usuario = u.id_usuario AND pe.activo = 1
        LEFT JOIN Ingreso i ON i.id_pedido = pe.id_pedido AND i.activo = 1
        WHERE u.rol = 'Emprendedor'
        GROUP BY u.id_usuario, u.nombre, u.correo, u.activo
        ORDER BY facturacion_total DESC
    ");
    $auditoriadb = $stmt2->fetchAll();
} catch (PDOException $e) {
    $globalkpis = ['totalmypes' => 0, 'totalordenes' => 0, 'efectividadpct' => 0];
    $auditoriadb = [];
    error_log("Error superadmin.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedzio — Consola de Administración Global</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --orange: #FF6B2B; --navy: #1A2E4A; --navy-light: #243d61;
    --white: #FFFFFF; --gray-bg: #F5F5F5; --gray-text: #6B7280;
    --gray-border: #E5E7EB; --red: #E53E3E;
    --shadow-sm: 0 2px 8px rgba(26,46,74,0.07); --radius: 10px;
  }
  body { font-family: 'Poppins', sans-serif; background: var(--gray-bg); display: flex; height: 100vh; overflow: hidden; }

  /* SIDEBAR */
  .sidebar {
    width: 250px; min-width: 250px; height: 100vh;
    background: var(--navy); display: flex; flex-direction: column;
    padding: 28px 0; overflow-y: auto;
  }
  .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 0 24px 28px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 24px; }
  .sidebar-brand .logo { width: 36px; height: 36px; background: var(--orange); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 14px; }
  .sidebar-brand span { color: var(--white); font-weight: 700; font-size: 0.95rem; line-height: 1.3; }
  .sidebar-brand span em { font-style: normal; color: var(--orange); }

  .sidebar-nav { display: flex; flex-direction: column; gap: 4px; padding: 0 12px; flex: 1; }
  .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 16px; border-radius: 8px; cursor: pointer; color: rgba(255,255,255,0.55); font-size: 0.855rem; font-weight: 500; transition: all 0.2s; text-decoration: none; }
  .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.9); }
  .nav-item.active { background: var(--orange); color: var(--white); font-weight: 600; }
  .nav-icon { font-size: 16px; width: 20px; text-align: center; }

  /* MAIN */
  .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

  /* TOPBAR */
  .topbar { background: var(--white); border-bottom: 1px solid var(--gray-border); display: flex; align-items: center; justify-content: space-between; padding: 0 28px; height: 65px; min-height: 65px; }
  .topbar h1 { font-size: 1rem; font-weight: 700; color: var(--navy); }
  .topbar-right { display: flex; align-items: center; gap: 18px; }
  .alert-bell { font-size: 20px; cursor: pointer; position: relative; }
  .alert-dot { position: absolute; top: -3px; right: -3px; width: 10px; height: 10px; background: var(--red); border-radius: 50%; border: 2px solid var(--white); }
  .topbar-profile { display: flex; align-items: center; gap: 10px; }
  .avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--orange), var(--navy)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; }
  .profile-info { text-align: right; }
  .profile-info .name { font-size: 0.845rem; font-weight: 700; color: var(--navy); }
  .profile-info .role { font-size: 0.7rem; color: var(--orange); font-weight: 600; }

  /* WORKSPACE */
  .workspace { flex: 1; overflow-y: auto; padding: 28px; background: var(--gray-bg); }

  /* KPI ROW */
  .kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 18px; margin-bottom: 24px; }
  .kpi-card { background: var(--white); border-radius: var(--radius); padding: 22px 20px; box-shadow: var(--shadow-sm); transition: transform 0.2s; }
  .kpi-card:hover { transform: translateY(-2px); }
  .kpi-icon { font-size: 26px; margin-bottom: 10px; }
  .kpi-label { font-size: 0.72rem; color: var(--gray-text); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
  .kpi-value { font-size: 1.9rem; font-weight: 800; color: var(--navy); line-height: 1; }
  .kpi-sub { font-size: 0.72rem; color: var(--orange); font-weight: 600; margin-top: 6px; }
  .kpi-card.alert-card .kpi-value { color: var(--red); }

  /* BOTTOM GRID */
  .bottom-grid { display: grid; grid-template-columns: 1fr 1.6fr; gap: 20px; }
  .card { background: var(--white); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); }
  .card-title { font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--gray-border); }

  /* FORM */
  .form-group { margin-bottom: 14px; }
  .form-group label { display: block; font-size: 0.77rem; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
  .form-group input, .form-group select {
    width: 100%; padding: 9px 13px;
    border: 1.5px solid var(--gray-border); border-radius: 7px;
    font-family: 'Poppins', sans-serif; font-size: 0.855rem; color: var(--navy); outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-group input:focus, .form-group select:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(255,107,43,0.12); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .btn-submit { width: 100%; padding: 11px; background: var(--orange); color: var(--white); border: none; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: background 0.2s, transform 0.2s; margin-top: 4px; }
  .btn-submit:hover { background: #e85a1e; transform: translateY(-1px); }

  /* TABLE */
  .data-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
  .data-table th { text-align: left; padding: 9px 10px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-text); border-bottom: 2px solid var(--gray-border); white-space: nowrap; }
  .data-table td { padding: 11px 10px; border-bottom: 1px solid var(--gray-border); color: var(--navy); vertical-align: middle; }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tr:hover td { background: rgba(245,245,245,0.6); }

  .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 0.68rem; font-weight: 600; }
  .badge-active { background: #d1fae5; color: #065f46; }
  .badge-inactive { background: #fee2e2; color: #991b1b; }
  .badge-review { background: #fff3e0; color: #b45309; }

  .action-btns { display: flex; gap: 6px; }
  .btn-view { padding: 5px 10px; background: var(--navy); color: var(--white); border: none; border-radius: 6px; font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: background 0.2s; font-family: 'Poppins', sans-serif; }
  .btn-view:hover { background: var(--navy-light); }
  .btn-suspend { padding: 5px 10px; background: var(--red); color: var(--white); border: none; border-radius: 6px; font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: background 0.2s; font-family: 'Poppins', sans-serif; }
  .btn-suspend:hover { background: #c53030; }

  @media (max-width: 1100px) { .kpi-row { grid-template-columns: repeat(2,1fr); } }
  @media (max-width: 900px) { .sidebar { display: none; } .bottom-grid { grid-template-columns: 1fr; } }
  @media (max-width: 560px) { .kpi-row { grid-template-columns: 1fr 1fr; } .workspace { padding: 16px; } .form-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo">P</div>
    <span>Ped<em>zio</em><br>Admin</span>
  </div>
  <nav class="sidebar-nav">
    <a href="#" class="nav-item active"><span class="nav-icon">🖥️</span> Consola Global</a>
    <a href="#" class="nav-item"><span class="nav-icon">🏪</span> Gestión de Emprendedores</a>
    <a href="#" class="nav-item"><span class="nav-icon">👥</span> Auditoría de Clientes</a>
    <a href="#" class="nav-item"><span class="nav-icon">📈</span> Métricas del Modelo Híbrido</a>
    <a href="#" class="nav-item"><span class="nav-icon">🔐</span> Seguridad / Roles</a>
  </nav>
</aside>

<div class="main">
  <header class="topbar">
    <h1>Consola de Administración Centralizada</h1>
    <div class="topbar-right">
      <div class="alert-bell" title="Alertas">
        🔔
        <div class="alert-dot"></div>
      </div>
      <div class="topbar-profile">
        <div class="profile-info">
          <div class="name">Administradora</div>
          <div class="role">Super Admin</div>
        </div>
        <div class="avatar">SA</div>
      </div>
    </div>
  </header>

  <main class="workspace">

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon">🏪</div>
        <div class="kpi-label">Total de MYPEs Registradas</div>
        <div class="kpi-value"><?= $total_mypes ?></div>
        <div class="kpi-sub">MYPEs activas en el ecosistema</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">📦</div>
        <div class="kpi-label">Órdenes Totales Procesadas</div>
        <div class="kpi-value"><?= number_format($total_ordenes) ?></div>
        <div class="kpi-sub">Acumulado relacional histórico</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon">⚡</div>
        <div class="kpi-label">Efectividad Modelo Híbrido</div>
        <div class="kpi-value"><?= $efectividad_pct ?>%</div>
        <div class="kpi-sub">Porcentaje de éxito en entregas</div>
      </div>

      <div class="kpi-card alert-card">
        <div class="kpi-icon">🚨</div>
        <div class="kpi-label">Alertas de Servidor / Errores</div>
        <div class="kpi-value">0</div>
        <div class="kpi-sub">Infraestructura estable en nube</div>
      </div>
    </div>

    <div class="bottom-grid">

      <div class="card">
        <div class="card-title">🏪 Alta / Modificación de Emprendedor</div>
        <form method="POST" action="admin_emprendedor.php">
          <div class="form-row">
            <div class="form-group">
              <label>Nombre del negocio</label>
              <input type="text" name="negocio" placeholder="Ej: Delicias del Centro">
            </div>
            <div class="form-group">
              <label>RUC / DNI</label>
              <input type="text" name="ruc" placeholder="00000000000">
            </div>
          </div>
          <div class="form-group">
            <label>Correo electrónico</label>
            <input type="email" name="email" placeholder="negocio@email.com">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Distrito / Sede</label>
              <input type="text" name="distrito" placeholder="Lima, SJL...">
            </div>
            <div class="form-group">
              <label>Rol / Permiso</label>
              <select name="rol">
                <option value="emprendedor">Emprendedor</option>
                <option value="admin">Administrador</option>
                <option value="viewer">Solo lectura</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Estado del servicio</label>
            <select name="estado">
              <option value="activo">✅ Activo</option>
              <option value="revision">⏳ En revisión</option>
              <option value="suspendido">❌ Suspendido</option>
            </select>
          </div>
          <button type="submit" class="btn-submit">Registrar / Actualizar Emprendedor</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title">📋 Tabla de Auditoría General (MYPEs en tiempo real)</div>
        <div style="overflow-x:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Emprendimiento</th>
              <th>Platos</th>
              <th>Órdenes</th>
              <th>Facturación Total</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($auditoria_db)): ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--gray-text);">No hay negocios registrados en la base de datos.</td>
            </tr>
            <?php else: ?>
              <?php 
              foreach ($auditoria_db as $e):
                $badge = $e['activo'] == 1 ? 'badge-active' : 'badge-inactive';
                $label = $e['activo'] == 1 ? 'Activo' : 'Suspendido';
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($e['negocio']) ?></strong><br><span style="font-size:11px; color:var(--gray-text);"><?= htmlspecialchars($e['correo']) ?></span></td>
                <td><?= $e['total_productos'] ?> u.</td>
                <td><?= $e['total_pedidos'] ?> uds.</td>
                <td style="font-weight: 700; color: var(--navy);">S/. <?= number_format($e['facturacion_total'], 2) ?></td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td>
                  <div class="action-btns">
                    <button class="btn-view">Ver</button>
                    <button class="btn-suspend">Dar de baja</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>

    </div>
  </main>
</div>

</body>
</html>
