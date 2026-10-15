-- sql/02_datos.sql — Datos iniciales: 3 socios y los 6 productos del catálogo (los mismos del lab 7).
-- Una sentencia por ";" (verificar.php vuelve a cargar este archivo para restaurar los datos de prueba).
SET NAMES utf8mb4;

INSERT INTO socios (id, nombre) VALUES
    (1, 'Rosa Chuvé'),
    (2, 'Mario Surubí'),
    (3, 'Elena Masabí');

INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES
    ('MIE-500', 'Miel multifloral 500 g',        'Miel',      45.00, 32, 1),
    ('MIE-1K',  'Miel multifloral 1 kg',         'Miel',      85.00, 18, 1),
    ('MIE-CUS', 'Miel de cusi 350 g',            'Miel',      55.50,  0, 2),
    ('POL-250', 'Polen & propóleo (pack 250 g)', 'Derivados', 70.00,  9, 3),
    ('PRO-30',  'Propóleo en gotas 30 ml',       'Derivados', 38.00, 25, 3),
    ('CER-1K',  'Cera pura 1 kg',                'Cera',     120.00,  4, 2);
