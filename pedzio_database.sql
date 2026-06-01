-- ============================================================
--  PEDZIO — Script de Creación de Base de Datos (CORREGIDO)
--  Sistema: Gestión de Delivery para MYPEs
-- ============================================================

USE pedzio_db;

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
-- ============================================================
CREATE TABLE Usuario (
  id_usuario  INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  correo      VARCHAR(120)   NOT NULL,
  contrasena  VARCHAR(255)   NOT NULL,
  telefono    VARCHAR(20)        NULL,
  rol         ENUM('Emprendedor', 'SuperAdmin') NOT NULL DEFAULT 'Emprendedor',
  activo      TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_correo_usr (correo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Emprendedores MYPE y SuperAdmin';


-- ============================================================
-- TABLA 2: Cliente
-- ============================================================
CREATE TABLE Cliente (
  id_cliente  INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  telefono    VARCHAR(20)        NULL,
  direccion   VARCHAR(255)       NULL,
  correo      VARCHAR(120)   NOT NULL,
  contrasena  VARCHAR(255)   NOT NULL,
  activo      TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uq_correo_cli (correo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Compradores finales';


-- ============================================================
-- TABLA 3: Producto
-- ============================================================
CREATE TABLE Producto (
  id_producto INT            NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100)   NOT NULL,
  precio      DECIMAL(10,2)  NOT NULL,
  descripcion TEXT               NULL,
  imagen      VARCHAR(255)       NULL,
  id_usuario  INT            NOT NULL,
  activo      TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_producto),
  CONSTRAINT fk_producto_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de platos por MYPE';

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
-- ============================================================
CREATE TABLE Pedido (
  id_pedido       INT            NOT NULL AUTO_INCREMENT,
  fecha_registro  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado          ENUM('Pendiente', 'En Camino', 'Entregado', 'Cancelado') NOT NULL DEFAULT 'Pendiente',
  total           DECIMAL(10,2)  NOT NULL,
  id_cliente      INT            NOT NULL,
  id_usuario      INT            NOT NULL,
  activo          TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_pedido),
  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (id_cliente) REFERENCES Cliente(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_estado (estado),
  INDEX idx_fecha (fecha_registro),
  INDEX idx_emprendedor (id_usuario)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Cabecera de órdenes';

INSERT INTO Pedido (fecha_registro, estado, total, id_cliente, id_usuario) VALUES
('2026-06-01 09:10:00', 'Entregado',  38.00, 1, 1),
('2026-06-01 09:45:00', 'Pendiente',  22.50, 2, 1),
('2026-06-01 10:05:00', 'Entregado',  45.00, 3, 1),
('2026-06-01 10:30:00', 'Pendiente',  18.50, 4, 1),
('2026-06-01 11:00:00', 'Cancelado',  30.00, 5, 1),
('2026-06-01 11:20:00', 'Entregado',  16.00, 6, 1);


-- ============================================================
-- TABLA 5: DetallePedido
-- ============================================================
CREATE TABLE DetallePedido (
  id_detalle      INT            NOT NULL AUTO_INCREMENT,
  cantidad        INT            NOT NULL,
  precio_unitario DECIMAL(10,2)  NOT NULL,
  subtotal        DECIMAL(10,2)  NOT NULL,
  id_pedido       INT            NOT NULL,
  id_producto     INT            NOT NULL,
  --
  PRIMARY KEY (id_detalle),
  CONSTRAINT fk_detalle_pedido
    FOREIGN KEY (id_pedido)   REFERENCES Pedido(id_pedido)
    ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_pedido_det (id_pedido),
  INDEX idx_producto_det (id_producto)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Líneas de cada pedido';

INSERT INTO DetallePedido (cantidad, precio_unitario, subtotal, id_pedido, id_producto) VALUES
(1, 22.00, 22.00, 1, 1),
(1, 16.00, 16.00, 1, 5),
(1, 22.50, 22.50, 2, 3),
(2, 14.50, 29.00, 3, 6),
(1, 16.00, 16.00, 3, 5),
(1, 18.50, 18.50, 4, 2),
(1, 19.00, 19.00, 5, 7),
(1,  8.00,  8.00, 5, 4),
(1,  3.00,  3.00, 5, 4),
(1, 16.00, 16.00, 6, 5);


-- ============================================================
-- TABLA 6: Gasto
-- ============================================================
CREATE TABLE Gasto (
  id_gasto    INT            NOT NULL AUTO_INCREMENT,
  fecha_gasto DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto       DECIMAL(10,2)  NOT NULL,
  categoria   VARCHAR(60)        NULL,
  descripcion VARCHAR(255)       NULL,
  id_usuario  INT            NOT NULL,
  activo      TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_gasto),
  CONSTRAINT fk_gasto_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_gasto_usuario (id_usuario),
  INDEX idx_gasto_fecha (fecha_gasto)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Egresos manuales del emprendedor';

INSERT INTO Gasto (fecha_gasto, monto, categoria, descripcion, id_usuario) VALUES
('2026-06-01 08:00:00',  80.00, 'Ingredientes', 'Compra de insumos en mercado mayorista', 1),
('2026-06-01 08:30:00',  35.00, 'Envío',         'Combustible moto repartidor',            1),
('2026-06-01 09:00:00',  27.00, 'Servicios',     'Gas del mes',                            1);


-- ============================================================
-- TABLA 7: Ingreso (CORREGIDO: agregado id_usuario)
-- ============================================================
CREATE TABLE Ingreso (
  id_ingreso    INT            NOT NULL AUTO_INCREMENT,
  fecha_ingreso TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto         DECIMAL(10,2)  NOT NULL,
  descripcion   VARCHAR(255)       NULL,
  id_pedido     INT                NULL,
  id_usuario    INT            NOT NULL,  -- ✅ AGREGADO: FK al emprendedor
  activo        TINYINT(1)     NOT NULL DEFAULT 1,
  --
  PRIMARY KEY (id_ingreso),
  UNIQUE KEY uq_pedido_ingreso (id_pedido),
  CONSTRAINT fk_ingreso_pedido
    FOREIGN KEY (id_pedido) REFERENCES Pedido(id_pedido)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_ingreso_usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_ingreso_fecha (fecha_ingreso),
  INDEX idx_ingreso_usuario (id_usuario)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Flujo de caja entrante (CORREGIDO con id_usuario)';

INSERT INTO Ingreso (monto, descripcion, id_pedido, id_usuario) VALUES
(38.00, 'Venta automática pedido #1 — Carlos M.',   1, 1),
(22.50, 'Venta automática pedido #2 — Ana R.',      2, 1),
(45.00, 'Venta automática pedido #3 — Luis T.',     3, 1),
(18.50, 'Venta automática pedido #4 — María G.',    4, 1),
(16.00, 'Venta automática pedido #6 — Lucia F.',    6, 1);


-- ============================================================
-- USUARIOS DE PRUEBA (password: "pedzio123" para emprendedor, "cliente123" para cliente)
-- ============================================================
INSERT INTO Usuario (nombre, correo, contrasena, telefono, rol, activo) VALUES
('Sabores del Norte', 'sabores@pedzio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '987654321', 'Emprendedor', 1);

INSERT INTO Cliente (nombre, telefono, direccion, correo, contrasena, activo) VALUES
('Carlos Mendoza', '999888777', 'Av. Principal 123, Lima', 'carlos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Ana Rodriguez', '998777666', 'Calle Las Flores 456', 'ana@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Luis Torres', '997666555', 'Jr. Unión 789', 'luis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Maria Gonzalez', '996555444', 'Blvd. Los Pinos 321', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Lucia Fernandez', '995444333', 'Panamericana Sur 654', 'lucia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);


-- ============================================================
-- VISTAS ÚTILES
-- ============================================================

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
