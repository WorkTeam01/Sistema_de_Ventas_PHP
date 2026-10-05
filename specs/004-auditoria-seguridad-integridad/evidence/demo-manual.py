#!/usr/bin/env python3
"""Demo manual spec 004 contra la app real (Apache + BD de desarrollo). Credenciales por entorno."""
import os, re, json, time, threading, subprocess, urllib.request, urllib.parse, http.cookiejar
from html.parser import HTMLParser

B = "http://localhost/Sistema_de_Ventas_PHP/public"
DB = dict(h="127.0.0.1", u=os.environ["DB_USER"], p=os.environ["DB_PASS"], n=os.environ["DB_NAME"])
RES = []

def sql(q):
    out = subprocess.run(["/opt/lampp/bin/mysql", "-h", DB["h"], "-u", DB["u"], f"-p{DB['p']}", "-N", "-B", DB["n"], "-e", q],
                         capture_output=True, text=True)
    if out.returncode: raise RuntimeError(out.stderr)
    return [l.split("\t") for l in out.stdout.strip().splitlines()] if out.stdout.strip() else []
def one(q):
    r = sql(q); return r[0][0] if r else None

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None

class S:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)
    def raw(self, method, path, data=None, headers=None):
        url = path if path.startswith("http") else B + path
        body = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
        rq = urllib.request.Request(url, data=body, method=method, headers=headers or {})
        t = time.time()
        try: r = self.op.open(rq, timeout=60); st, h, b = r.status, r.headers, r.read()
        except urllib.error.HTTPError as e: st, h, b = e.code, e.headers, e.read()
        return st, h, b.decode("utf-8", "replace"), time.time() - t
    def go(self, method, path, data=None, headers=None):
        """Sigue redirecciones. Devuelve (estado_final, cuerpo, ruta_final, estado_primera_respuesta, segundos)."""
        st, h, b, dt = self.raw(method, path, data, headers); first = st; cur = path
        for _ in range(6):
            if st in (301, 302, 303) and h.get("Location"):
                cur = h["Location"]; st, h, b, _ = self.raw("GET", cur)
            else: break
        return st, b, cur.replace(B, ""), first, dt
    def get(self, p): return self.go("GET", p)
    def post(self, p, d, **k): return self.go("POST", p, d, **k)
    def csrf(self, body=None):
        if body is None: body = self.get("/profile")[1]
        m = re.search(r'name="csrf_token"\s+value="([^"]+)"', body); return m.group(1) if m else None
    def login(self, email, pw, remember=False):
        b = self.get("/auth")[1]; d = {"csrf_token": self.csrf(b), "email": email, "password_user": pw}
        if remember: d["remember"] = "1"
        st, body, path, first, _ = self.post("/auth/login", d)
        return path.rstrip("/") == "", body, flash(body)
    def cookie(self, name):
        return next((c.value for c in self.jar if c.name == name), None)
    def drop(self, name):
        for c in list(self.jar):
            if c.name == name: self.jar.clear(c.domain, c.path, c.name)

def flash(body):
    m = re.search(r"const msg\s*=\s*(\".*?\");", body); return json.loads(m.group(1)) if m else None
def nro_of(body):
    m = re.search(r'name="nro_venta"\s+value="(\d+)"', body); return int(m.group(1)) if m else None
class Form(HTMLParser):
    def __init__(s): super().__init__(); s.forms = []; s.cur = None; s.sel = None; s.ta = None
    def handle_starttag(s, tag, a):
        a = dict(a)
        if tag == "form": s.cur = {"action": a.get("action", ""), "f": {}}; s.forms.append(s.cur)
        elif s.cur is None: return
        elif tag == "input" and a.get("name"):
            if a.get("type") in ("checkbox", "radio") and "checked" not in a: return
            if a.get("type") in ("file", "submit", "button"): return
            s.cur["f"][a["name"]] = a.get("value", "")
        elif tag == "select" and a.get("name"): s.sel = a["name"]; s.cur["f"].setdefault(s.sel, "")
        elif tag == "option" and s.sel and ("selected" in a or not s.cur["f"][s.sel]): s.cur["f"][s.sel] = a.get("value", "")
        elif tag == "textarea" and a.get("name"): s.ta = a["name"]; s.cur["f"][s.ta] = ""
    def handle_endtag(s, tag):
        if tag == "form": s.cur = None
        if tag == "select": s.sel = None
        if tag == "textarea": s.ta = None
    def handle_data(s, d):
        if s.ta and s.cur: s.cur["f"][s.ta] += d
def forms(body): p = Form(); p.feed(body); return p.forms
def form_with(body, frag):
    return next(f for f in forms(body) if frag in f["action"])

def step(n, titulo): print(f"\n## {n}. {titulo}")
def check(ok, msg):
    ok = bool(ok); RES.append(ok); print(("OK   " if ok else "FAIL ") + msg)

def add_item(s, nro, prod, qty):
    b = s.get("/sales/create")[1]
    return s.post("/sales/cart/add", {"csrf_token": s.csrf(b), "nro_venta": nro, "id_producto": prod, "cantidad": qty})

def parallel(fns):
    out = [None] * len(fns); ts = []
    for i, f in enumerate(fns):
        def run(i=i, f=f): out[i] = f()
        t = threading.Thread(target=run); ts.append(t)
    for t in ts: t.start()
    for t in ts: t.join()
    return out

PW = {"admin": ("admin@sistema.com", "admin123"), "vend": ("vendedor@sistema.com", "vendedor123"),
      "vend2": ("4ndres255@gmail.com", os.environ.get("DEMO_VEND2_PW", "")), "comp": ("comprador@sistema.com", "comprador123")}
print("# Demo manual — spec 004 —", time.strftime("%Y-%m-%d %H:%M"), "— HEAD", subprocess.run(["git", "rev-parse", "--short", "HEAD"], capture_output=True, text=True).stdout.strip())
print("Tablas nuevas presentes:", [r[0] for r in sql("SHOW TABLES LIKE 'tb_pos_reservas'") + sql("SHOW TABLES LIKE 'tb_sesiones_recordadas'") + sql("SHOW TABLES LIKE 'tb_intentos_auth'")])

# --- preparación -----------------------------------------------------------------
step(0, "Preparación (productos DEMO004 con marcador propio y sesiones)")
TS = str(int(time.time()))
NROC = 900000 + int(TS) % 99999
sql("INSERT INTO tb_almacen (codigo,nombre,descripcion,stock,precio_compra,precio_venta,fecha_ingreso,id_usuario,id_categoria) "
    "SELECT 'DEMO004-"+TS+"-P1','DEMO004 Producto 1','demo',50,5,10,CURDATE(),1,MIN(id_categoria) FROM tb_categorias")
sql("INSERT INTO tb_almacen (codigo,nombre,descripcion,stock,precio_compra,precio_venta,fecha_ingreso,id_usuario,id_categoria) "
    "SELECT 'DEMO004-"+TS+"-P2','DEMO004 Producto 2','demo',0,5,10,CURDATE(),1,MIN(id_categoria) FROM tb_categorias")
P1 = int(one("SELECT id_producto FROM tb_almacen WHERE codigo='DEMO004-"+TS+"-P1'")); P2 = int(one("SELECT id_producto FROM tb_almacen WHERE codigo='DEMO004-"+TS+"-P2'"))
print(f"Productos de prueba: P1 id={P1} (stock 50), P2 id={P2} (stock 0)")
S_ = {}
for k, (e, p) in PW.items():
    S_[k] = S(); ok, _, fl = S_[k].login(e, p); check(ok, f"login {e}" + ("" if ok else f" -> {fl}"))
    if not ok: raise SystemExit("No se puede continuar sin sesión " + k)
vend, vend2, admin, comp = S_["vend"], S_["vend2"], S_["admin"], S_["comp"]
vend_id = int(one("SELECT id_usuario FROM tb_usuarios WHERE email='vendedor@sistema.com'"))

# --- 1. doble envío de venta --------------------------------------------------------
step(1, "Doble envío de venta (dos pestañas del mismo vendedor, enviadas a la vez)")
tabA, tabB = vend, S(); tabB.login(*PW["vend"])
nroA = nro_of(tabA.get("/sales/create")[1]); nroB = nro_of(tabB.get("/sales/create")[1])
check(nroA == nroB and nroA, f"las dos pestañas comparten la reserva abierta del vendedor (nro_venta={nroA})")
add_item(tabA, nroA, P1, 2)
tA = tabA.csrf(tabA.get("/sales/create")[1]); tB = tabB.csrf(tabB.get("/sales/create")[1])
mk = lambda s, t: (lambda: s.post("/sales", {"csrf_token": t, "nro_venta": nroA, "id_cliente": 0, "forma_pago": "unico", "pagos[1][monto]": "20"}))
r = parallel([mk(tabA, tA), mk(tabB, tB)])
n_ventas = int(one(f"SELECT COUNT(*) FROM tb_ventas WHERE nro_venta={nroA}"))
check(n_ventas == 1, f"una sola venta con nro_venta={nroA} tras 2 envíos simultáneos (filas={n_ventas})")
SALE1 = int(one(f"SELECT id_venta FROM tb_ventas WHERE nro_venta={nroA}"))
check(one(f"SELECT stock FROM tb_almacen WHERE id_producto={P1}") == "48", "stock descontado una sola vez (50 -> 48)")
check(one(f"SELECT COUNT(*) FROM tb_pagos WHERE id_venta={SALE1}") == "1", "una sola línea de pago")
msgs1 = [flash(x[1]) for x in r]; print("   mensajes de los dos envíos:", msgs1)
check(not any(m and 'permisos' in m for m in msgs1), "ninguna pestaña recibe 'sin permisos': la segunda debe ser un reenvío idempotente (FR-6)")
r3 = tabA.post("/sales", {"csrf_token": tabA.csrf(tabA.get("/sales/create")[1]), "nro_venta": nroA, "id_cliente": 0, "forma_pago": "unico", "pagos[1][monto]": "20"})
check(int(one(f"SELECT COUNT(*) FROM tb_ventas WHERE nro_venta={nroA}")) == 1 and one(f"SELECT stock FROM tb_almacen WHERE id_producto={P1}") == "48", "un tercer reenvío tardío tampoco duplica ni toca stock")

if os.environ.get("DEMO_STOP_AFTER") == "1":
    print("\n## Resumen (solo paso 1)"); print(f"{sum(RES)} OK / {len(RES) - sum(RES)} FAIL"); raise SystemExit(0 if all(RES) else 1)

# --- 2. doble envío de devolución -----------------------------------------------------
step(2, "Doble envío de devolución (mismo formulario, dos envíos simultáneos)")
sa, sb = admin, S(); sb.login(*PW["admin"])
fa = form_with(sa.get(f"/returns/create?sale_id={SALE1}")[1], "/returns")["f"]
fb = dict(fa); fb["csrf_token"] = sb.csrf(sb.get("/profile")[1])
print("   campos del formulario:", sorted(fa))
for f in (fa, fb): f["qty_%d" % P1] = "1"; f["motivo"] = "DEMO-004 devolucion de prueba doble envio"
rr = parallel([lambda: sa.post("/returns", fa), lambda: sb.post("/returns", fb)])
check(int(one(f"SELECT COUNT(*) FROM tb_devoluciones WHERE id_venta={SALE1}")) == 1, "una sola devolución creada para la venta")
check(one(f"SELECT stock FROM tb_almacen WHERE id_producto={P1}") == "49", "stock repuesto una sola vez (48 -> 49)")
print("   mensajes:", [flash(x[1]) for x in rr])

# --- 3. dos vendedores a la vez ---------------------------------------------------------
step(3, "Dos vendedores abriendo el POS a la vez")
vend.post("/sales/cancel", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "nro_venta": nro_of(vend.get("/sales/create")[1])})
rv = parallel([lambda: vend.get("/sales/create"), lambda: vend2.get("/sales/create")])
nv, nv2 = nro_of(rv[0][1]), nro_of(rv[1][1])
check(nv and nv2 and nv != nv2, f"números distintos: vendedor={nv}, vendedor2={nv2}")
rows = sql(f"SELECT id_reserva,id_usuario,IF(fyh_liberacion IS NULL,'abierta','liberada') FROM tb_pos_reservas WHERE id_reserva IN ({nv},{nv2}) ORDER BY id_reserva")
print("   tb_pos_reservas:", rows)
check(len(rows) == 2 and all(r[2] == "abierta" for r in rows), "cada número es una reserva abierta de un usuario distinto")

# --- 4. carrito ajeno ----------------------------------------------------------------------
step(4, "Carrito ajeno rechazado")
add_item(vend2, nv2, P1, 1)
line = one(f"SELECT id_carrito FROM tb_carrito WHERE nro_venta={nv2}")
st, _, path, first, _ = add_item(vend, nv2, P1, 1)
check("/errors/403" in path, f"vendedor agrega al carrito de otro -> redirige a {path}")
check(one(f"SELECT COUNT(*) FROM tb_carrito WHERE nro_venta={nv2}") == "1", "el carrito ajeno no cambió (1 línea)")
st, _, path, _, _ = vend.post("/sales/cart/remove", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "id_carrito": line, "nro_venta": nv2})
check("/errors/403" in path and one(f"SELECT COUNT(*) FROM tb_carrito WHERE id_carrito={line}") == "1", f"quitar la línea ajena -> {path}; la línea sigue")
st, _, path, _, _ = vend.post("/sales/cancel", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "nro_venta": nv2})
check("/errors/403" in path and one(f"SELECT COUNT(*) FROM tb_carrito WHERE nro_venta={nv2}") == "1", f"cancelar el carrito ajeno -> {path}; sigue intacto")
vend2.post("/sales/cancel", {"csrf_token": vend2.csrf(vend2.get("/sales/create")[1]), "nro_venta": nv2})

# --- 5. vuelto mayor al efectivo ---------------------------------------------------------------
step(5, "Vuelto mayor al efectivo recibido rechazado")
nro5 = nro_of(vend.get("/sales/create")[1]); add_item(vend, nro5, P1, 10)
st5 = one(f"SELECT stock FROM tb_almacen WHERE id_producto={P1}")
res = vend.post("/sales", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "nro_venta": nro5, "id_cliente": 0, "forma_pago": "mixto",
                           "pagos[2][monto]": "150", "pagos[1][monto]": "1"})
check(one(f"SELECT COUNT(*) FROM tb_ventas WHERE nro_venta={nro5}") == "0", "no se creó la venta (total 100, tarjeta 150 + efectivo 1)")
check(one(f"SELECT stock FROM tb_almacen WHERE id_producto={P1}") == st5, "el stock no cambió")
print("   mensaje:", flash(res[1]))
vend.post("/sales/cancel", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "nro_venta": nro5})

# --- 6. compra revertida con stock vendido ----------------------------------------------------------
step(6, "Compra revertida con stock ya vendido rechazada")
pf = form_with(admin.get("/purchases/create")[1], "/purchases")["f"]
pf.update({"id_producto": P2, "nro_compra": NROC, "comprobante": "DEMO-004", "precio_compra": "5", "cantidad": "5",
           "fecha_compra": time.strftime("%Y-%m-%d")})
admin.post("/purchases", pf)
idc = one("SELECT id_compra FROM tb_compras WHERE nro_compra="+str(NROC)+"")
check(idc and one(f"SELECT stock FROM tb_almacen WHERE id_producto={P2}") == "5", f"compra registrada (id_compra={idc}); stock de P2 = 5")
nro6 = nro_of(vend.get("/sales/create")[1]); add_item(vend, nro6, P2, 4)
vend.post("/sales", {"csrf_token": vend.csrf(vend.get("/sales/create")[1]), "nro_venta": nro6, "id_cliente": 0, "forma_pago": "unico", "pagos[1][monto]": "40"})
check(one(f"SELECT stock FROM tb_almacen WHERE id_producto={P2}") == "1", "se vendieron 4 de las 5 unidades (stock = 1)")
dm = admin.post("/purchases/delete", {"csrf_token": admin.csrf(admin.get("/purchases")[1]), "id_compra": idc})
check(one(f"SELECT COUNT(*) FROM tb_compras WHERE id_compra={idc}") == "1" and one(f"SELECT stock FROM tb_almacen WHERE id_producto={P2}") == "1",
      "la compra NO se eliminó y el stock no quedó negativo")
print("   mensaje:", flash(dm[1]))

# --- 7. ficha de producto con stock solo lectura -------------------------------------------------------
step(7, "Ficha de producto con stock de solo lectura")
eb = admin.get(f"/products/edit/{P1}")[1]
check(bool(re.search(r'id="stock"[^>]*\breadonly\b', eb, re.S)), "el campo Stock actual es readonly en la edición")
ef = form_with(eb, "/products/update")["f"]; ef["stock"] = "999"
before = sql(f"SELECT stock,id_usuario FROM tb_almacen WHERE id_producto={P1}")[0]
admin.post("/products/update", ef)
after = sql(f"SELECT stock,id_usuario FROM tb_almacen WHERE id_producto={P1}")[0]
check(before == after, f"POST con stock=999 no cambia stock ni id_usuario ({before} -> {after})")

# --- 8. perfil del vendedor ---------------------------------------------------------------------------------
step(8, "Perfil del vendedor (rol sin manage_users): cambio de email y de contraseña")
pb = vend.get("/profile")[1]; pf1 = form_with(pb, "/profile/update")["f"]; pf2 = form_with(pb, "/profile/password")["f"]
old_hash = one(f"SELECT password_user FROM tb_usuarios WHERE id_usuario={vend_id}")
ce = vend.post("/profile/check-email", {"csrf_token": pf1["csrf_token"], "email": "vendedor.demo004@sistema.com"})
print("   check-email ->", ce[0], ce[1][:60].strip())
pf1["email"] = "vendedor.demo004@sistema.com"; r1 = vend.post("/profile/update", pf1)
check(one(f"SELECT email FROM tb_usuarios WHERE id_usuario={vend_id}") == "vendedor.demo004@sistema.com", f"email cambiado sin error (estado {r1[0]})")
r2 = vend.post("/profile/password", {**pf2, "password_current": "", "password_user": "nuevaclave1", "password_repeat": "nuevaclave1"})
check(one(f"SELECT password_user FROM tb_usuarios WHERE id_usuario={vend_id}") == old_hash, "sin la contraseña actual el cambio se rechaza"); print("   mensaje:", flash(r2[1]))
r2 = vend.post("/profile/password", {**pf2, "password_current": "incorrecta", "password_user": "nuevaclave1", "password_repeat": "nuevaclave1"})
check(one(f"SELECT password_user FROM tb_usuarios WHERE id_usuario={vend_id}") == old_hash, "con una contraseña actual incorrecta tampoco cambia")
r2 = vend.post("/profile/password", {**pf2, "password_current": "vendedor123", "password_user": "nuevaclave1", "password_repeat": "nuevaclave1"})
check(one(f"SELECT password_user FROM tb_usuarios WHERE id_usuario={vend_id}") != old_hash, "con la actual correcta, el cambio se aplica"); print("   mensaje:", flash(r2[1]))
nuevo = S(); ok, _, _ = nuevo.login("vendedor.demo004@sistema.com", "nuevaclave1"); check(ok, "login con el email y la contraseña nuevos")
ok2, _, _ = S().login("vendedor.demo004@sistema.com", "vendedor123"); check(not ok2, "la contraseña anterior ya no sirve")
# restaurar el estado del vendedor
b = nuevo.get("/profile")[1]; f2 = form_with(b, "/profile/password")["f"]
nuevo.post("/profile/password", {**f2, "password_current": "nuevaclave1", "password_user": "vendedor123", "password_repeat": "vendedor123"})
f1 = form_with(nuevo.get("/profile")[1], "/profile/update")["f"]; f1["email"] = "vendedor@sistema.com"; nuevo.post("/profile/update", f1)
ok3, _, _ = S().login(*PW["vend"]); check(ok3, "estado restaurado: vendedor@sistema.com / vendedor123 vuelve a funcionar")

# --- 9. dos dispositivos recordados -------------------------------------------------------------------------------
step(9, "Dos dispositivos recordados conviven")
d1, d2 = S(), S(); d1.login(*PW["vend"], remember=True); d2.login(*PW["vend"], remember=True)
n_rows = int(one(f"SELECT COUNT(*) FROM tb_sesiones_recordadas WHERE id_usuario={vend_id}"))
check(n_rows >= 2, f"dos filas en tb_sesiones_recordadas para el vendedor (filas={n_rows})")
c1, c2 = d1.cookie("remember_token"), d2.cookie("remember_token"); check(bool(c1 and c2 and c1.split(":")[0] != c2.split(":")[0]), "cada dispositivo tiene su propio selector")
d1.drop("PHPSESSID"); d2.drop("PHPSESSID")
check(d1.get("/sales")[0] == 200 and d2.get("/sales")[0] == 200, "sin sesión PHP, ambos dispositivos entran con su cookie y rotan el validador")
check(d1.cookie("remember_token") != c1, "el validador rotó en el dispositivo 1")
d1.post("/auth/logout", {"csrf_token": d1.csrf(d1.get("/sales")[1])}); d2.drop("PHPSESSID")
check(d2.get("/sales")[0] == 200, "tras cerrar sesión en el dispositivo 1, el 2 sigue recordado")
print("   filas restantes del vendedor:", one(f"SELECT COUNT(*) FROM tb_sesiones_recordadas WHERE id_usuario={vend_id}"))

# --- 10. reset sin enlace en pantalla ----------------------------------------------------------------------------------
step(10, "Restablecimiento sin enlace en pantalla (APP_DEBUG=true)")
print("   APP_DEBUG =", [l for l in open("/opt/lampp/htdocs/Sistema_de_Ventas_PHP/.env").read().splitlines() if l.startswith("APP_DEBUG")])
prev = sql("SELECT id_intento,tipo,correo,fyh_creacion FROM tb_intentos_auth WHERE tipo='reset' AND correo='4ndres255@gmail.com' AND fyh_creacion >= NOW() - INTERVAL 30 MINUTE")
print("   solicitudes de reset previas de corridas anteriores de esta demo (cooldown de 5 min; vista previa):", prev)
if prev:
    sql("DELETE FROM tb_intentos_auth WHERE tipo='reset' AND correo='4ndres255@gmail.com' AND id_intento IN (%s)" % ",".join(r[0] for r in prev))
out = {}
for etiqueta, mail in (("existente", "4ndres255@gmail.com"), ("inexistente", "no.existe.demo004@example.com")):
    s = S(); b = s.get("/auth/forgot-password")[1]
    st, body, path, first, dt = s.post("/auth/forgot-password", {"csrf_token": s.csrf(b), "email": mail})
    out[etiqueta] = (st, path, round(dt, 3), flash(body), bool(re.search(r"reset-password/[0-9a-f]{20,}", body)))
    print(f"   {etiqueta}: estado={st} ruta={path} tiempo={round(dt,3)}s mensaje={flash(body)!r} enlace_en_html={out[etiqueta][4]}")
check(not out["existente"][4] and not out["inexistente"][4], "el enlace no aparece en pantalla ni con APP_DEBUG=true")
check(out["existente"][3] == out["inexistente"][3], "el mensaje es idéntico exista o no el correo")
rt = one("SELECT reset_token FROM tb_usuarios WHERE email='4ndres255@gmail.com'")
check(bool(rt) and re.fullmatch(r"[0-9a-f]{64}", rt) is not None, "en la BD queda un hash de 64 hex (no el token): " + (rt[:8] + "…" if rt else "NULL"))
sql("UPDATE tb_usuarios SET reset_token=NULL, reset_token_expiracion=NULL WHERE email='4ndres255@gmail.com'")

# --- 11. ráfaga de logins --------------------------------------------------------------------------------------------------
step(11, "Ráfaga paralela de 20 logins contra una cuenta")
ids_before = {r[0] for r in sql("SELECT id_intento FROM tb_intentos_auth WHERE correo='comprador@sistema.com'")}
def intento():
    s = S(); b = s.get("/auth")[1]
    return s.post("/auth/login", {"csrf_token": s.csrf(b), "email": "comprador@sistema.com", "password_user": "incorrecta-demo004"})
rb = parallel([intento] * 20)
msgs = {flash(x[1]) for x in rb}
nuevos = [r for r in sql("SELECT id_intento FROM tb_intentos_auth WHERE correo='comprador@sistema.com' ORDER BY id_intento") if r[0] not in ids_before]
check(len(nuevos) == 20, f"los 20 intentos quedaron registrados antes de verificar (filas nuevas={len(nuevos)})")
check(len(msgs) == 1, f"los 20 fallos muestran el mismo mensaje: {list(msgs)[0]!r}")
okc, _, fl = S().login(*PW["comp"]); check(not okc, "la contraseña CORRECTA también se rechaza durante el bloqueo (mismo mensaje)")
oka, _, _ = S().login(*PW["admin"]); check(not oka, "el límite por IP (20) también rechaza a otra cuenta desde la misma IP durante la ventana")
ids = [r[0] for r in nuevos]
print("   filas de prueba a limpiar (ids):", ids[0], "…", ids[-1], f"({len(ids)})")
print("   vista previa:", sql(f"SELECT COUNT(*),MIN(correo),MAX(correo) FROM tb_intentos_auth WHERE id_intento IN ({','.join(ids)})"))
sql(f"DELETE FROM tb_intentos_auth WHERE id_intento IN ({','.join(ids)}) AND correo='comprador@sistema.com'")
okc, _, _ = S().login(*PW["comp"]); check(okc, "tras limpiar esas 20 filas por id, el comprador vuelve a entrar")
oka, _, _ = S().login(*PW["admin"]); check(oka, "y el admin también (el bloqueo por IP se levanta al limpiar)")

print("\n## Resumen")
print(f"{sum(RES)} OK / {len(RES) - sum(RES)} FAIL")
print("Datos de prueba conservados (marcador DEMO004):", sql("SELECT id_producto,codigo,stock FROM tb_almacen WHERE codigo LIKE 'DEMO004-%' ORDER BY id_producto DESC LIMIT 2"))
raise SystemExit(0 if all(RES) else 1)
