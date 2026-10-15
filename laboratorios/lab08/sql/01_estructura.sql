-- sql/01_estructura.sql — Estructura de la base. MySQL la ejecuta sola al crear un volumen vacío.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS socios (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_socios_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
    id        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    codigo    VARCHAR(10)   NOT NULL,
    nombre    VARCHAR(120)  NOT NULL,
    categoria ENUM('Miel', 'Derivados', 'Cera') NOT NULL,
    precio    DECIMAL(10,2) NOT NULL,          -- dinero: DECIMAL, nunca FLOAT
    stock     INT           NOT NULL DEFAULT 0,
    socio_id  INT UNSIGNED  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_codigo (codigo),   -- impide códigos repetidos
    CONSTRAINT ck_productos_precio CHECK (precio > 0),
    CONSTRAINT ck_productos_stock  CHECK (stock >= 0),
    CONSTRAINT fk_productos_socio  FOREIGN KEY (socio_id) REFERENCES socios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
