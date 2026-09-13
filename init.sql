CREATE TABLE IF NOT EXISTS piezas_arqueologicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(150) NOT NULL,
    ubicacion_sitio VARCHAR(200) NOT NULL,
    coordenadas VARCHAR(100) NOT NULL,
    fecha_hallazgo DATE NOT NULL,
    descripcion TEXT NOT NULL,
    estado_conservacion ENUM('Excelente', 'Regular', 'Fragmentado') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO piezas_arqueologicas (nombre_tipo, ubicacion_sitio, coordenadas, fecha_hallazgo, descripcion, estado_conservacion) VALUES
('Vasija ceremonial', 'Sector Rumicucho, Terraza Norte', '-0.0385, -78.4350', '2025-06-15', 'Vasija con iconografía incaica geométrica en borde.', 'Excelente'),
('Hacha de obsidiana', 'Cañón del Chiche', '-0.1650, -78.3812', '2025-07-02', 'Pieza lítica cortante bifacial de color negro translúcido.', 'Regular'),
('Fragmento cerámico', 'Complejo Cochasquí, Tolas', '0.0531, -78.2725', '2025-08-10', 'Borde de vasija utilitaria con desgrasante volcánico.', 'Fragmentado');
