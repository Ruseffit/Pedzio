# 🛵 PEDZIO — Sistema de Gestión de Delivery para MYPEs

**Pedzio** es una plataforma web dinámica diseñada bajo una arquitectura relacional sólida para digitalizar y optimizar el flujo operativo de entrega (delivery) y la gestión de caja chica de las micro y pequeñas empresas (MYPEs). 

Este ecosistema web permite la interacción en tiempo real de tres actores clave mediante paneles e interfaces dedicadas, asegurando la consistencia de los datos financieros y logísticos a través de un backend centralizado en PHP y MySQL.

---

## 👥 Roles del Sistema e Interfases

El sistema está completamente automatizado y se divide en tres entornos dinámicos interconectados:

* **🛒 Panel del Cliente (`cliente.php`):** Un marketplace interactivo donde el usuario final visualiza el catálogo de platos disponibles directamente desde la base de datos, gestiona un carrito transaccional y genera órdenes de compra en tiempo real.
* **📊 Panel Operativo del Emprendedor (`emprendedor.php`):** Diseñado con enfoque en la toma de decisiones. Ofrece al dueño de la MYPE un control analítico mediante KPIs diarios (Pedidos recibidos, Ingresos totales, Gastos de operación, Balance neto) y una bitácora automatizada de pedidos recientes.
* **🛡️ Panel del SuperAdmin (`superadmin.php`):** Entorno de gobernanza global orientado a la auditoría del sistema. Permite monitorear la facturación acumulada de los negocios afiliados, dar de alta/baja nuevos emprendimientos y evaluar métricas críticas de rendimiento general del modelo híbrido.

---

## 🛠️ Tecnologías Utilizadas

* **Frontend:** HTML5, CSS3 (Estructuras de diseño responsivo nativo, cuadrículas CSS Grid y Flexbox) y JavaScript para interacciones asíncronas ligeras.
* **Backend:** PHP (Estructurado de forma modular utilizando PDO para sentencias preparadas y transacciones seguras).
* **Base de Datos:** MySQL (Diseño relacional avanzado con llaves foráneas, restricciones de integridad referencial `ON DELETE/UPDATE` y Vistas de agregación analítica para optimizar las consultas del Dashboard).
* **Infraestructura Cloud:** Desplegado y alojado en servidores reales compartidos de **InfinityFree** bajo el dominio oficial del proyecto.

---

## 📂 Arquitectura de Archivos Críticos

```text
├── index.php              # Landing page pública de bienvenida del sistema
├── conexion.php            # Mapeo y puente de conexión PDO oficial hacia la nube (MySQL Host)
├── cliente.php             # Vista del Marketplace dinámico mapeado con la tabla Producto
├── carrito.php             # Controlador transaccional que procesa la lógica de compra y caja chica
├── emprendedor.php         # Dashboard analítico de control operativo y KPIs de la MYPE
├── finanzas.php            # Procesador manual de egresos e ingresos extraordinarios de caja chica
├── superadmin.php          # Panel administrativo principal de auditoría de negocios
└── pedzio_database.sql     # Script SQL con el esquema relacional, triggers y data de prueba maestra
