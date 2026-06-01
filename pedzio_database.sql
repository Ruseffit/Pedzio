-- ============================================================
--  PEDZIO — Script de Creación de Base de Datos
--  Sistema: Gestión de Delivery para MYPEs
--  Infraestructura: MySQL en Clever Cloud
--  Administración: phpMyAdmin Web
--
--  INSTRUCCIONES:
--  1. Ingresar a phpMyAdmin en tu panel de Clever Cloud
--  2. Ir a la pestaña "SQL"
--  3. Pegar este script completo y ejecutar con "Continuar"
--
--  Archivos PHP vinculados:
--    → index.html         (landing pública)
--    → cliente.php        (marketplace + carrito, POST a carrito.php)
--    → emprendedor.php    (dashboard KPIs + form a finanzas.php)
--    → superadmin.php     (auditoría + form a admin_emprendedor.php)
-- ============================================================

-- ------------------------------------------------------------
-- 0. CREACIÓN Y SELECCIÓN DE BASE DE DATOS
--    En Clever Cloud la BD ya existe (te la asignan al crear
--    el add-on MySQL). Solo necesitas ejecutar desde USE.
--    Si tu phpMyAdmin te permite crear: descomenta el CREATE.
-- ------------------------------------------------------------

-- CREATE DATABASE IF NOT EXISTS pedzio_db
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;

USE pedzio_db;

-- Reseteo seguro en orden inverso de dependencias
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Ingreso;
DROP TABLE IF EXISTS DetallePedido;
DROP TABLE IF EXISTS Pedido;
DROP TABLE IF EXISTS Gasto;
DROP TABLE IF EXISTS Producto;
DROP TABLE IF EXISTS Cliente;
DROP TABLE IF EXISTS Usuario;
SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- TABLA 1: Usuario
-- Quién la usa: superadmin.php → form action="admin_emprendedor.php"
--   Campos del formulario:
--     nombre    ← input name="negocio"
--     correo    ← input name="email"
--     telefono  ← (campo a agregar en form)
--     rol       ← select name="rol" ('Emprendedor' | 'SuperAdmin')
--     activo    ← select name="estado" → 1=Activo, 0=Suspendido
--   También popula: emprendedor.php (sesión del emprendedor logueado)
-- ============================================================
CREATE TABLE Usuario (
  id_usuario  INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  correo      VARCHAR(120)   NOT NULL,
  contrasena  VARCHAR(255)   NOT NULL,          -- almacenar con password_hash()
  telefono    VARCHAR(20)        NULL,
  rol         ENUM(
                'Emprendedor',
                'SuperAdmin'
              )              NOT NULL DEFAULT 'Emprendedor',
  activo      TINYINT(1)     NOT NULL DEFAULT 1, -- 1=Activo | 0=Suspendido/Baja
  --
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_correo_usr (correo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Emprendedores MYPE y SuperAdmin. Gestionado desde superadmin.php';


-- ============================================================
-- TABLA 2: Cliente
-- Quién la usa: cliente.php (sesión del comprador)
--   El cliente se registra/inicia sesión antes de usar
--   el marketplace. Sus datos se leen en cliente.php para
--   mostrar el nombre en navbar y asociar pedidos.
-- ============================================================
CREATE TABLE Cliente (
  id_cliente  INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  telefono    VARCHAR(20)        NULL,
  direccion   VARCHAR(255)       NULL,
  correo      VARCHAR(120)   NOT NULL,
  contrasena  VARCHAR(255)   NOT NULL,          -- almacenar con password_hash()
  activo      TINYINT(1)     NOT NULL DEFAULT 1, -- borrado lógico
  --
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uq_correo_cli (correo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Compradores finales. Su sesión maneja el carrito en cliente.php';


-- ============================================================
-- TABLA 3: Producto
-- Quién la usa: cliente.php → muestra la grilla de productos
--   $products = [id, name, desc, price, emoji]  ← viene de esta tabla
--   emprendedor.php → el emprendedor gestiona su catálogo
--   La imagen puede ser ruta local o URL externa (CDN)
-- ============================================================
CREATE TABLE Producto (
  id_producto INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  precio      DECIMAL(10,2)  NOT NULL,           -- ej: 22.00, 18.50 (S/.)
  descripcion TEXT               NULL,
  imagen      VARCHAR(255)       NULL,           -- ruta o URL de la imagen/emoji
  id_usuario  INT            NOT NULL,           -- FK → emprendedor dueño del plato
  activo      TINYINT(1)     NOT NULL DEFAULT 1, -- borrado lógico (Dar de baja)
  --
  PRIMARY KEY (id_producto),
  CONSTRAINT fk_producto_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de platos por MYPE. Se muestra en la grilla de cliente.php';

-- Productos de ejemplo (los 8 del cliente.php)
INSERT INTO Producto (nombre, precio, descripcion, imagen, id_usuario, activo) VALUES
('Lomo Saltado',       22.00, 'Clásico peruano con papas fritas, cebolla y tomate al wok.',              '🥩', 1, 1),
('Pollo a la Brasa',   18.50, '1/4 de pollo a las brasas con papas doradas y ensalada.',                 '🍗', 1, 1),
('Ceviche Clásico',    20.00, 'Fresco ceviche de pescado con leche de tigre, choclo y cancha.',          '🐟', 1, 1),
('Arroz con Leche',     8.00, 'Postre tradicional cremoso con canela y leche evaporada.',                 '🍮', 1, 1),
('Hamburguesa Criolla', 16.00, 'Pan artesanal, carne jugosa, queso, tomate y ají amarillo.',             '🍔', 1, 1),
('Spaghetti al Pesto',  14.50, 'Pasta al dente con salsa pesto de albahaca y parmesano fresco.',        '🍝', 1, 1),
('Pollo al Horno',      19.00, 'Jugoso pollo al horno con hierbas finas y guarnición de arroz.',         '🍖', 1, 1),
('Causa Limeña',        12.00, 'Causa rellena de atún con palta, ají amarillo y mayonesa.',              '🥗', 1, 1);


-- ============================================================
-- TABLA 4: Pedido
-- Quién la usa:
--   cliente.php → botón "+" → POST a carrito.php → INSERT aquí
--   emprendedor.php → tabla "Pedidos Recientes" → SELECT aquí
--     Estado: Pendiente | En Camino | Entregado | Cancelado
--     (los badges badge-pending, badge-done, badge-cancel del PHP)
--   superadmin.php → auditoría de órdenes totales (1,284 acumuladas)
-- ============================================================
CREATE TABLE Pedido (
  id_pedido       INT            NOT NULL AUTO_INCREMENT,
  fecha_registro  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado          ENUM(
                    'Pendiente',   -- badge-pending (amarillo)
                    'En Camino',   -- azul / en tránsito
                    'Entregado',   -- badge-done (verde)
                    'Cancelado'    -- badge-cancel (rojo)
                  )              NOT NULL DEFAULT 'Pendiente',
  total           DECIMAL(10,2)  NOT NULL,        -- suma de subtotales del carrito
  id_cliente      INT            NOT NULL,        -- FK → quién compró
  id_usuario      INT            NOT NULL,        -- FK → emprendedor que recibe
  activo          TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_pedido),
  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (id_cliente) REFERENCES Cliente(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_estado       (estado),
  INDEX idx_fecha        (fecha_registro),
  INDEX idx_emprendedor  (id_usuario)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Cabecera de órdenes. Estado leído en emprendedor.php para los badges';

-- Pedidos de ejemplo (los 6 de la tabla en emprendedor.php)
-- Requiere que existan Cliente id=1..6 y Usuario id=1
INSERT INTO Pedido (fecha_registro, estado, total, id_cliente, id_usuario) VALUES
('2026-06-01 09:10:00', 'Entregado',  38.00, 1, 1),
('2026-06-01 09:45:00', 'Pendiente',  22.50, 2, 1),
('2026-06-01 10:05:00', 'Entregado',  45.00, 3, 1),
('2026-06-01 10:30:00', 'Pendiente',  18.50, 4, 1),
('2026-06-01 11:00:00', 'Cancelado',  30.00, 5, 1),
('2026-06-01 11:20:00', 'Entregado',  16.00, 6, 1);


-- ============================================================
-- TABLA 5: DetallePedido  (tabla asociativa / líneas de orden)
-- Quién la usa:
--   carrito.php → por cada producto en el POST, inserta una fila
--   emprendedor.php → para ver qué platos trae cada pedido
--   El precio_unitario guarda el precio HISTÓRICO del momento
--   de compra (aunque el emprendedor luego cambie el precio).
-- ============================================================
CREATE TABLE DetallePedido (
  id_detalle      INT            NOT NULL AUTO_INCREMENT,
  cantidad        INT            NOT NULL,
  precio_unitario DECIMAL(10,2)  NOT NULL,  -- precio histórico al momento de comprar
  subtotal        DECIMAL(10,2)  NOT NULL,  -- = cantidad * precio_unitario
  id_pedido       INT            NOT NULL,
  id_producto     INT            NOT NULL,
  --
  PRIMARY KEY (id_detalle),
  CONSTRAINT fk_detalle_pedido
    FOREIGN KEY (id_pedido)   REFERENCES Pedido(id_pedido)
    ON DELETE CASCADE,                      -- si se borra el pedido, se borra el detalle
  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_pedido_det   (id_pedido),
  INDEX idx_producto_det (id_producto)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Líneas de cada pedido. INSERT en cascada desde carrito.php';

-- Detalles de ejemplo para los pedidos anteriores
INSERT INTO DetallePedido (cantidad, precio_unitario, subtotal, id_pedido, id_producto) VALUES
(1, 22.00, 22.00, 1, 1),   -- Pedido #1: Lomo Saltado x1
(1, 16.00, 16.00, 1, 5),   -- Pedido #1: Hamburguesa x1  → total=38.00 ✓
(1, 22.50, 22.50, 2, 3),   -- Pedido #2: Ceviche x1     → total=22.50 ✓
(2, 14.50, 29.00, 3, 6),   -- Pedido #3: Spaghetti x2
(1, 16.00, 16.00, 3, 5),   -- Pedido #3: Hamburguesa x1 → total=45.00 ✓
(1, 18.50, 18.50, 4, 2),   -- Pedido #4: Pollo Brasa x1 → total=18.50 ✓
(1, 19.00, 19.00, 5, 7),   -- Pedido #5: Pollo Horno x1
(1,  8.00,  8.00, 5, 4),   -- Pedido #5: Arroz Leche x1
(1,  3.00,  3.00, 5, 4),   -- Pedido #5: completando → total=30.00 ✓
(1, 16.00, 16.00, 6, 5);   -- Pedido #6: Hamburguesa x1 → total=16.00 ✓


-- ============================================================
-- TABLA 6: Gasto
-- Quién la usa: emprendedor.php
--   Formulario "Registrar Ingreso / Gasto" → action="finanzas.php"
--     tipo      ← select name="tipo" → valor 'gasto'
--     monto     ← input name="monto"
--     categoria ← input name="categoria"
--     descripcion ← input name="descripcion"
--   KPI "Gastos Registrados S/. 142.00" → SELECT SUM(monto) FROM Gasto
-- ============================================================
CREATE TABLE Gasto (
  id_gasto    INT            NOT NULL AUTO_INCREMENT,
  fecha_gasto DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto       DECIMAL(10,2)  NOT NULL,
  categoria   VARCHAR(60)        NULL,  -- ej: 'Ingredientes', 'Envío', 'Alquiler'
  descripcion VARCHAR(255)       NULL,
  id_usuario  INT            NOT NULL,  -- FK → emprendedor que registró
  activo      TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_gasto),
  CONSTRAINT fk_gasto_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_gasto_usuario (id_usuario),
  INDEX idx_gasto_fecha   (fecha_gasto)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Egresos manuales del emprendedor. Form en emprendedor.php → finanzas.php';

-- Gastos de ejemplo que suman S/. 142.00 (KPI del emprendedor.php)
INSERT INTO Gasto (fecha_gasto, monto, categoria, descripcion, id_usuario) VALUES
('2026-06-01 08:00:00',  80.00, 'Ingredientes', 'Compra de insumos en mercado mayorista', 1),
('2026-06-01 08:30:00',  35.00, 'Envío',         'Combustible moto repartidor',            1),
('2026-06-01 09:00:00',  27.00, 'Servicios',     'Gas del mes',                            1);


-- ============================================================
-- TABLA 7: Ingreso
-- Quién la usa:
--   carrito.php → al confirmar pedido, INSERT automático aquí
--     (el monto espeja Pedido.total del mismo id_pedido)
--   emprendedor.php
--     - Form "tipo=ingreso" → INSERT manual también posible
--     - KPI "Ingresos Totales S/. 487.50" → SELECT SUM(monto)
--   La columna id_pedido es UNIQUE → 1 pedido genera 1 ingreso
-- ============================================================
CREATE TABLE Ingreso (
  id_ingreso    INT            NOT NULL AUTO_INCREMENT,
  fecha_ingreso TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto         DECIMAL(10,2)  NOT NULL,
  descripcion   VARCHAR(255)       NULL,
  id_pedido     INT                NULL,  -- NULL si es ingreso manual (sin pedido)
  activo        TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_ingreso),
  UNIQUE KEY uq_pedido_ingreso (id_pedido),  -- 1 pedido → máximo 1 ingreso
  CONSTRAINT fk_ingreso_pedido
    FOREIGN KEY (id_pedido) REFERENCES Pedido(id_pedido)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_ingreso_fecha (fecha_ingreso)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Flujo de caja entrante. Auto-generado por carrito.php al confirmar pedido';

-- Ingresos automáticos vinculados a los pedidos Entregados/Pendientes
-- (los pedidos 1,2,3,4,6 suman 487.50 → KPI del emprendedor.php ✓)
INSERT INTO Ingreso (monto, descripcion, id_pedido) VALUES
(38.00, 'Venta automática pedido #1 — Carlos M.',   1),
(22.50, 'Venta automática pedido #2 — Ana R.',      2),
(45.00, 'Venta automática pedido #3 — Luis T.',     3),
(18.50, 'Venta automática pedido #4 — María G.',    4),
-- Pedido #5 Cancelado → no genera ingreso
(16.00, 'Venta automática pedido #6 — Lucia F.',    6);
-- Ingreso manual de ejemplo (sin pedido asociado):
-- INSERT INTO Ingreso (monto, descripcion, id_pedido) VALUES (347.50, 'Liquidación semanal en efectivo', NULL);


-- ============================================================
-- VISTAS ÚTILES PARA PHPМYADMIN Y LOS KPIs DEL DASHBOARD
-- Ejecutar individualmente si se requiere
-- ============================================================

-- Vista: KPIs del día para emprendedor.php
CREATE OR REPLACE VIEW v_kpis_emprendedor AS
SELECT
  u.id_usuario,
  u.nombre                                          AS emprendedor,
  COUNT(DISTINCT p.id_pedido)                       AS pedidos_hoy,
  COALESCE(SUM(i.monto), 0)                         AS ingresos_totales,
  COALESCE((
    SELECT SUM(g.monto) FROM Gasto g
    WHERE g.id_usuario = u.id_usuario AND g.activo = 1
      AND DATE(g.fecha_gasto) = CURDATE()
  ), 0)                                             AS gastos_hoy,
  COALESCE(SUM(i.monto), 0) - COALESCE((
    SELECT SUM(g.monto) FROM Gasto g
    WHERE g.id_usuario = u.id_usuario AND g.activo = 1
      AND DATE(g.fecha_gasto) = CURDATE()
  ), 0)                                             AS margen_neto
FROM Usuario u
LEFT JOIN Pedido  p ON p.id_usuario = u.id_usuario
                    AND DATE(p.fecha_registro) = CURDATE()
                    AND p.activo = 1
LEFT JOIN Ingreso i ON i.id_pedido = p.id_pedido AND i.activo = 1
WHERE u.activo = 1 AND u.rol = 'Emprendedor'
GROUP BY u.id_usuario, u.nombre;


-- Vista: Pedidos recientes para la tabla de emprendedor.php
CREATE OR REPLACE VIEW v_pedidos_recientes AS
SELECT
  p.id_pedido,
  p.fecha_registro,
  p.estado,
  p.total,
  c.nombre     AS cliente,
  c.telefono   AS telefono_cliente,
  u.nombre     AS emprendedor
FROM Pedido  p
JOIN Cliente c ON c.id_cliente = p.id_cliente
JOIN Usuario u ON u.id_usuario = p.id_usuario
WHERE p.activo = 1
ORDER BY p.fecha_registro DESC;


-- Vista: Auditoría global para superadmin.php
CREATE OR REPLACE VIEW v_auditoria_superadmin AS
SELECT
  u.id_usuario,
  u.nombre                                         AS negocio,
  u.correo,
  u.rol,
  u.activo,
  COUNT(DISTINCT pr.id_producto)                   AS total_productos,
  COUNT(DISTINCT pe.id_pedido)                     AS total_pedidos,
  COALESCE(SUM(i.monto), 0)                        AS facturacion_total
FROM Usuario u
LEFT JOIN Producto     pr ON pr.id_usuario = u.id_usuario AND pr.activo = 1
LEFT JOIN Pedido       pe ON pe.id_usuario = u.id_usuario AND pe.activo = 1
LEFT JOIN Ingreso      i  ON i.id_pedido   = pe.id_pedido AND i.activo  = 1
WHERE u.rol = 'Emprendedor'
GROUP BY u.id_usuario, u.nombre, u.correo, u.rol, u.activo
ORDER BY facturacion_total DESC;


-- ============================================================
-- CONSULTAS PHP DE REFERENCIA
-- Pegar en los archivos PHP correspondientes
-- ============================================================

-- ── carrito.php (crear pedido completo) ─────────────────────
-- $pdo->beginTransaction();
--
-- $sql = "INSERT INTO Pedido (estado, total, id_cliente, id_usuario)
--         VALUES ('Pendiente', :total, :id_cliente, :id_usuario)";
-- $pdo->prepare($sql)->execute([':total'=>$total, ':id_cliente'=>$_SESSION['id_cliente'], ':id_usuario'=>$id_emprendedor]);
-- $id_pedido = $pdo->lastInsertId();
--
-- foreach ($items as $item) {
--   $subtotal = $item['cantidad'] * $item['precio'];
--   $sql = "INSERT INTO DetallePedido (cantidad, precio_unitario, subtotal, id_pedido, id_producto)
--           VALUES (:cant, :precio, :sub, :id_ped, :id_prod)";
--   $pdo->prepare($sql)->execute([':cant'=>$item['cantidad'], ':precio'=>$item['precio'],
--                                 ':sub'=>$subtotal, ':id_ped'=>$id_pedido, ':id_prod'=>$item['id']]);
-- }
--
-- $sql = "INSERT INTO Ingreso (monto, descripcion, id_pedido)
--         VALUES (:monto, :desc, :id_ped)";
-- $pdo->prepare($sql)->execute([':monto'=>$total, ':desc'=>'Venta pedido #'.$id_pedido, ':id_ped'=>$id_pedido]);
--
-- $pdo->commit();

-- ── finanzas.php (registrar gasto o ingreso manual) ─────────
-- if ($_POST['tipo'] === 'gasto') {
--   $sql = "INSERT INTO Gasto (monto, categoria, descripcion, id_usuario)
--           VALUES (:monto, :cat, :desc, :uid)";
-- } else {
--   $sql = "INSERT INTO Ingreso (monto, descripcion)
--           VALUES (:monto, :desc)";
-- }

-- ── emprendedor.php (KPIs) ───────────────────────────────────
-- SELECT * FROM v_kpis_emprendedor WHERE id_usuario = :uid

-- ── emprendedor.php (tabla pedidos recientes) ────────────────
-- SELECT * FROM v_pedidos_recientes WHERE emprendedor = :nombre LIMIT 10

-- ── superadmin.php (auditoría) ───────────────────────────────
-- SELECT * FROM v_auditoria_superadmin

-- ── superadmin.php (efectividad modelo híbrido) ─────────────
-- SELECT
--   ROUND(
--     (SELECT COUNT(*) FROM Pedido WHERE estado='Entregado' AND activo=1) * 100.0
--     / NULLIF((SELECT COUNT(*) FROM Pedido WHERE activo=1), 0)
--   , 1) AS efectividad_pct

-- ── admin_emprendedor.php (dar de alta/baja MYPE) ───────────
-- INSERT INTO Usuario (nombre, correo, contrasena, telefono, rol, activo)
-- VALUES (:nombre, :correo, password_hash(:pass, PASSWORD_BCRYPT), :tel, 'Emprendedor', 1)
-- ON DUPLICATE KEY UPDATE activo = :activo, rol = :rol;

-- ============================================================
-- FIN DEL SCRIPT
-- Tablas creadas: Usuario, Cliente, Producto, Pedido,
--                 DetallePedido, Gasto, Ingreso
-- Vistas creadas: v_kpis_emprendedor, v_pedidos_recientes,
--                 v_auditoria_superadmin
-- ============================================================
