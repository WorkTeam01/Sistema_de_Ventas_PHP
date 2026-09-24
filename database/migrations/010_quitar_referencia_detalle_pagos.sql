-- =============================================================
-- Migración 010 — Quitar referencia y detalle de tb_pagos
-- Un POS estándar no captura referencia/detalle del pago en caja;
-- la línea de pago queda como método + monto.
-- Aplicar sobre BDs existentes que ya tienen schema.sql migrado.
-- Reversible: ALTER TABLE tb_pagos ADD COLUMN referencia varchar(100) DEFAULT NULL,
--             ADD COLUMN detalle varchar(255) DEFAULT NULL;
-- =============================================================

ALTER TABLE `tb_pagos`
  DROP COLUMN `referencia`,
  DROP COLUMN `detalle`;