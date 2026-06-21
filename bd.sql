-- 0. Usuarios: clientes, cajeros, admins
DROP TABLE IF EXISTS orden_detalles;
DROP TABLE IF EXISTS ordenes;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'cajero', 'admin') NOT NULL DEFAULT 'cliente',
    activo BOOLEAN DEFAULT TRUE,
    reset_token VARCHAR(64) NULL,
    reset_token_expires DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Categorías de productos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Productos del catálogo
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Órdenes (cabecera)
CREATE TABLE ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    cajero_id INT NULL,
    tipo_pedido ENUM('venta_rapida','remoto') NOT NULL DEFAULT 'venta_rapida',
    codigo_qr VARCHAR(50) UNIQUE NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','en_preparacion','listo','entregado','cancelada')
        NOT NULL DEFAULT 'pendiente',  -- 'cancelada': reservado para feature futura (no usado aún)
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (cajero_id) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Detalle de órdenes
CREATE TABLE orden_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATOS SEMILLA — Cafetería de ejemplo (Fase 3, catálogo)
-- ============================================================

-- Categorías
INSERT INTO categorias (nombre, descripcion) VALUES
    ('Bebidas Calientes', 'Café, chocolate y bebidas calientes de la casa'),
    ('Bebidas Frías', 'Limonadas, jugos y frappés refrescantes'),
    ('Postres', 'Brownies, tortas y dulces artesanales'),
    ('Snacks', 'Empanadas, arepas y pasabocas para acompañar');

-- Productos (precios en COP, stock 0-50)
-- Bebidas Calientes
INSERT INTO productos (categoria_id, nombre, precio, stock, imagen) VALUES
    (1, 'Café Americano',    3500.00, 30, NULL),
    (1, 'Capuchino',         4500.00, 25, 'capuchino.jpg'),
    (1, 'Latte',             5000.00, 20, 'latte.jpg'),
-- Bebidas Frías
    (2, 'Limonada de Coco',  4000.00, 40, NULL),
    (2, 'Frappé Caramelo',   6000.00, 18, 'frappe.jpg'),
    (2, 'Jugo Natural',      3500.00,  8, NULL),
-- Postres
    (3, 'Brownie',           4000.00, 12, 'brownie.jpg'),
    (3, 'Cheesecake',        5500.00,  5, NULL),
    (3, 'Torta de Chocolate',6000.00,  6, 'torta.jpg'),
-- Snacks
    (4, 'Empanada',          3000.00, 50, NULL),
    (4, 'Arepa de Queso',    3500.00, 14, 'arepa.jpg'),
    (4, 'Galletas Artesanales',2500.00, 0, NULL);

-- ============================================================
-- USUARIOS SEMILLA — Para demo (eliminar en producción)
-- ============================================================
-- Credenciales:
--   admin@flui.com  / admin123   (rol: admin)
--   cajero@flui.com / cajero123  (rol: cajero)
-- Contraseñas hasheadas con password_hash(PASSWORD_BCRYPT) en PHP 8+
INSERT IGNORE INTO usuarios (usuario, email, password, rol, activo) VALUES
    ('Administrador', 'admin@flui.com', '$2y$12$oRj2MULCq427t3Sr5rsL6.fKyO6LVuC/fXAC93L64huDcDAmB3OLG', 'admin', TRUE),
    ('Cajero Demo', 'cajero@flui.com', '$2y$12$KGd6LZK50QzUX.mPKx65euBMO8C2UPcAkJbh/Dg9Sl0PNoq1B5Ikm', 'cajero', TRUE);

-- ============================================================
-- MIGRACIÓN — Para instalaciones existentes (ejecutar manualmente)
-- Agrega columnas de recuperación de contraseña a la tabla usuarios
-- ============================================================
ALTER TABLE usuarios
    ADD COLUMN reset_token VARCHAR(64) NULL,
    ADD COLUMN reset_token_expires DATETIME NULL;
