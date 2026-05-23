-- Schema SQLite para tests de integración.
-- Mantener sincronizado con database/schema.sql al agregar columnas o tablas.
-- Diferencias respecto a MySQL: sin ENGINE/CHARSET/COLLATE, AUTO_INCREMENT → AUTOINCREMENT,
-- DECIMAL → NUMERIC, datetime → TEXT, sin ON UPDATE CURRENT_TIMESTAMP, sin ALTER TABLE FK.

CREATE TABLE IF NOT EXISTS tb_roles (
    id_rol              INTEGER PRIMARY KEY AUTOINCREMENT,
    rol                 TEXT    NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_usuarios (
    id_usuario              INTEGER PRIMARY KEY AUTOINCREMENT,
    nombres                 TEXT    NOT NULL,
    email                   TEXT    NOT NULL UNIQUE,
    password_user           TEXT    NOT NULL,
    reset_token             TEXT    DEFAULT NULL,
    reset_token_expiracion  TEXT    DEFAULT NULL,
    remember_token          TEXT    DEFAULT NULL,
    remember_token_expiry   TEXT    DEFAULT NULL,
    login_intentos          INTEGER NOT NULL DEFAULT 0,
    login_bloqueado_hasta   TEXT    DEFAULT NULL,
    id_rol                  INTEGER NOT NULL,
    fyh_creacion            TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion       TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_categorias (
    id_categoria        INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_categoria    TEXT    NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_proveedores (
    id_proveedor        INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_proveedor    TEXT    NOT NULL,
    celular             TEXT    NOT NULL,
    telefono            TEXT    DEFAULT NULL,
    empresa             TEXT    NOT NULL,
    email               TEXT    DEFAULT NULL,
    direccion           TEXT    NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_clientes (
    id_cliente          INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_cliente      TEXT    NOT NULL,
    nit_ci_cliente      TEXT    NOT NULL UNIQUE,
    celular_cliente     TEXT    NOT NULL,
    email_cliente       TEXT    NOT NULL UNIQUE,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_almacen (
    id_producto         INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo              TEXT    NOT NULL UNIQUE,
    nombre              TEXT    NOT NULL,
    descripcion         TEXT    DEFAULT NULL,
    stock               INTEGER NOT NULL,
    stock_minimo        INTEGER DEFAULT NULL,
    stock_maximo        INTEGER DEFAULT NULL,
    precio_compra       NUMERIC NOT NULL,
    precio_venta        NUMERIC NOT NULL,
    fecha_ingreso       TEXT    NOT NULL,
    imagen              TEXT    NOT NULL DEFAULT 'producto_default.png',
    id_usuario          INTEGER NOT NULL,
    id_categoria        INTEGER NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_carrito (
    id_carrito          INTEGER PRIMARY KEY AUTOINCREMENT,
    nro_venta           INTEGER NOT NULL,
    id_producto         INTEGER NOT NULL,
    cantidad            INTEGER NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_ventas (
    id_venta            INTEGER PRIMARY KEY AUTOINCREMENT,
    nro_venta           INTEGER NOT NULL,
    id_cliente          INTEGER NOT NULL,
    total_pagado        NUMERIC NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS tb_compras (
    id_compra           INTEGER PRIMARY KEY AUTOINCREMENT,
    id_producto         INTEGER NOT NULL,
    nro_compra          INTEGER NOT NULL,
    fecha_compra        TEXT    NOT NULL,
    id_proveedor        INTEGER NOT NULL,
    comprobante         TEXT    NOT NULL,
    id_usuario          INTEGER NOT NULL,
    precio_compra       NUMERIC NOT NULL,
    cantidad            INTEGER NOT NULL,
    fyh_creacion        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fyh_actualizacion   TEXT    DEFAULT NULL
);
