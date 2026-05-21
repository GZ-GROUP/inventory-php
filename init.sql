CREATE DATABASE IF NOT EXISTS `inventario_utp`;

\c inventario_utp;

CREATE TABLE productos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    precio NUMERIC(10, 2) NOT NULL,
    cantidad INTEGER NOT NULL DEFAULT 0
);