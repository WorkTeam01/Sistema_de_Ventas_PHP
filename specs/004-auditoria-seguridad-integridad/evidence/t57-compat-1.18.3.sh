#!/bin/bash
B=http://127.0.0.1:8057; J=$SP/jar57; rm -f $J
M="/opt/lampp/bin/mysql -h 127.0.0.1 -u $DB_USER -p$DB_PASS -N sistemadeventas_t57"
fail=0
ok(){ echo "OK   $1"; }; bad(){ echo "FAIL $1"; fail=1; }
csrf(){ grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
code(){ curl -s -b $J -c $J -o /dev/null -w '%{http_code}' -L "$B$1"; }
echo "# T57 — 1.18.3 contra BD migrada 011-014 ($(cd $WT && git describe --tags))"
echo "Migraciones aplicadas: $($M -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('tb_pos_reservas','tb_intentos_auth','tb_sesiones_recordadas')") tablas nuevas"

T=$(curl -s -b $J -c $J $B/auth | csrf)
curl -s -b $J -c $J -o /dev/null -w '' -d "csrf_token=$T&email=vendedor@sistema.com&password_user=vendedor123" $B/auth/login
[ "$(code /sales)" = 200 ] && ok "login vendedor y listado de ventas (200)" || bad "login/listado"

PAGE=$(curl -s -b $J -c $J $B/sales/create); T=$(echo "$PAGE" | csrf)
NRO=$(echo "$PAGE" | grep -o 'name="nro_venta" value="[0-9]*"' | head -1 | grep -o '[0-9]*')
[ -n "$NRO" ] && ok "POS abierto, nro_venta=$NRO" || bad "POS sin nro_venta"
MAXV=$($M -e "SELECT IFNULL(MAX(nro_venta),0) FROM tb_ventas")
[ "$NRO" -gt "$MAXV" ] && ok "nro_venta ($NRO) mayor al máximo de ventas existente ($MAXV)" || bad "nro_venta colisiona"

STOCK0=$($M -e "SELECT stock FROM tb_almacen WHERE id_producto=3")
curl -s -b $J -c $J -o /dev/null -d "csrf_token=$T&nro_venta=$NRO&id_producto=3&cantidad=2" $B/sales/cart/add
[ "$($M -e "SELECT SUM(cantidad) FROM tb_carrito WHERE nro_venta=$NRO")" = 2 ] && ok "producto agregado al carrito" || bad "carrito"

T=$(curl -s -b $J -c $J $B/sales/create | csrf)
curl -s -b $J -c $J -o /dev/null -d "csrf_token=$T&nro_venta=$NRO&id_cliente=0&forma_pago=unico&pagos[1][monto]=100" $B/sales
ROW=$($M -e "SELECT id_venta,total_pagado FROM tb_ventas WHERE nro_venta=$NRO")
IDV=$(echo "$ROW" | cut -f1)
[ "$(echo "$ROW" | cut -f2)" = "90.00" ] && ok "venta registrada: id_venta=$IDV total=90.00" || bad "venta no registrada ($ROW)"
[ "$($M -e "SELECT stock FROM tb_almacen WHERE id_producto=3")" = "$((STOCK0-2))" ] && ok "stock descontado ($STOCK0 -> $((STOCK0-2)))" || bad "stock"
[ "$($M -e "SELECT monto FROM tb_pagos WHERE id_venta=$IDV")" = "100.00" ] && ok "pago guardado por 100.00 (efectivo recibido; el vuelto de 10.00 se deriva, no se persiste)" || bad "pagos"
[ "$(code /sales/show/$IDV)" = 200 ] && ok "detalle de venta (200)" || bad "detalle"
[ "$(code /sales/invoice/$IDV)" = 200 ] && ok "factura PDF (200)" || bad "factura"

# devolución como administrador
rm -f $J; T=$(curl -s -b $J -c $J $B/auth | csrf)
curl -s -b $J -c $J -o /dev/null -d "csrf_token=$T&email=admin@sistema.com&password_user=admin123" $B/auth/login
T=$(curl -s -b $J -c $J "$B/returns/create?sale_id=$IDV" | csrf)
curl -s -b $J -c $J -o /dev/null -d "csrf_token=$T&id_venta=$IDV&id_usuario_venta=2&qty_3=1&motivo=Devolucion de prueba T57" $B/returns
[ "$($M -e "SELECT monto FROM tb_devoluciones WHERE id_venta=$IDV")" = "45.00" ] && ok "devolución registrada por 45.00" || bad "devolución"
[ "$($M -e "SELECT stock FROM tb_almacen WHERE id_producto=3")" = "$((STOCK0-1))" ] && ok "stock repuesto ($((STOCK0-1)))" || bad "reposición"

for r in / /sales /returns /products /purchases /clients /inventory /reports /reports/sales /reports/purchases /reports/top-products /reports/clients /activity-log /users /roles /permissions; do
  c=$(code $r); [ "$c" = 200 ] && ok "GET $r (200)" || bad "GET $r ($c)"
done
echo "--- errores PHP en el log del servidor (se excluye el aviso conocido de 1.18.3: \$_GET['top'] sin parámetro, ReportController.php:120, no depende de la BD):"
grep -aiE "fatal|warning|notice|exception|SQLSTATE" $SP/server57.log | grep -av 'Undefined array key "top"' > $SP/errs57.txt
[ "$(wc -l < $SP/errs57.txt)" = 0 ] && ok "sin errores PHP" || { bad "errores PHP"; head $SP/errs57.txt; }
exit $fail
