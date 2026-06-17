-- 1. Tabla de Productos (para que el buscador de JS tenga qué mostrar)
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) UNIQUE NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL
);

-- 2. Tabla de Órdenes (Cabecera de la venta)
CREATE TABLE ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL, -- NULL significará "Venta Rápida" o se enlazará al ID del cliente remoto
    tipo_pedido VARCHAR(20) NOT NULL DEFAULT 'venta_rapida', -- 'venta_rapida' o 'remoto'
    total DECIMAL(10, 2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'completada', -- 'completada', 'pendiente', 'cancelada'
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Detalle de las Órdenes (Los productos que lleva cada carrito)
CREATE TABLE orden_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

--agrega tres columnas para modificar base de datos usuarios , para soportar esta logica 
ALTER TABLE usuarios 
ADD COLUMN reset_token_hash VARCHAR(64) NULL DEFAULT NULL,
ADD COLUMN reset_token_expires_at DATETIME NULL DEFAULT NULL;