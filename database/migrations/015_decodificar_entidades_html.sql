-- =============================================================
-- Migración 015 — decodifica las entidades HTML guardadas por Controller::input()
-- Hasta 1.19.0 Clientes, Proveedores, Categorías, Roles, Permisos y Métodos de pago
-- guardaban el texto ya escapado (O&#039;Brien, &amp;amp; tras cada edición). Desde ahora
-- se guarda el texto tal cual y se escapa al mostrar.
-- Ejecutar UNA sola vez, tras desplegar el código nuevo: un texto que contenga a propósito
-- «&amp;» se decodificaría otra vez si se reejecuta. &amp; se reemplaza el último para que
-- «&amp;lt;» quede «&lt;» y no «<».
-- Un texto que ya se guardó doble escapado (editado varias veces) queda con un nivel menos
-- de escape («&amp;amp;» → «&amp;»); esos casos se corrigen a mano desde la UI.
-- Antes de ejecutar, conviene revisar qué filas cambiarían:
--   SELECT id_cliente, nombre_cliente FROM tb_clientes WHERE nombre_cliente REGEXP '&(amp|lt|gt|quot|#039);';
-- No reversible sin respaldo.
-- =============================================================

UPDATE `tb_clientes` SET
  `nombre_cliente`  = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_cliente`,  '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `nit_ci_cliente`  = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nit_ci_cliente`,  '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `celular_cliente` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`celular_cliente`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `email_cliente`   = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`email_cliente`,   '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE CONCAT_WS('|', `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`) REGEXP '&(amp|lt|gt|quot|#039);';

UPDATE `tb_proveedores` SET
  `nombre_proveedor` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_proveedor`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `celular`          = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`celular`,          '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `telefono`         = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`telefono`,         '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `empresa`          = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`empresa`,          '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `email`            = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`email`,            '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `direccion`        = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`direccion`,        '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE CONCAT_WS('|', `nombre_proveedor`, `celular`, `telefono`, `empresa`, `email`, `direccion`) REGEXP '&(amp|lt|gt|quot|#039);';

UPDATE `tb_categorias` SET
  `nombre_categoria` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_categoria`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE `nombre_categoria` REGEXP '&(amp|lt|gt|quot|#039);';

UPDATE `tb_roles` SET
  `rol` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`rol`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE `rol` REGEXP '&(amp|lt|gt|quot|#039);';

UPDATE `tb_permisos` SET
  `descripcion` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`descripcion`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
  `modulo`      = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`modulo`,      '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE CONCAT_WS('|', `descripcion`, `modulo`) REGEXP '&(amp|lt|gt|quot|#039);';

UPDATE `tb_metodos_pago` SET
  `nombre` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre`, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&')
WHERE `nombre` REGEXP '&(amp|lt|gt|quot|#039);';
