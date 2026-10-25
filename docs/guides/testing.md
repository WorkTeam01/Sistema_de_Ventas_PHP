# Testing

> Suites, convenciones, tests contra MariaDB real, inyección del singleton y CI. Comandos y reglas clave en [AGENTS.md](../../AGENTS.md).

## Suites y estrategia

| Suite                   | Directorio                                  | Estrategia                                                                                                                                                     |
| ----------------------- | ------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Unit`                  | `tests/Unit/`                               | Lógica pura sin BD — Helpers, validaciones, cálculos                                                                                                           |
| `Integration`           | `tests/Integration/`                        | SQLite in-memory con schema completo                                                                                                                           |
| `Integration` (MariaDB) | `tests/Integration/Models/*MariaDbTest.php` | MariaDB real vía trait `RefreshMariaDatabase` — solo para funciones de fecha del motor (`CURDATE()`, `NOW()`, `YEAR()`, `DATE_FORMAT()`) que SQLite no soporta |

```bash
composer test             # todas las suites
composer test:unit        # solo Unit (rápido, ideal pre-commit)
composer test:integration # solo Integration
composer test:coverage    # con reporte de cobertura (requiere PCOV)
```

## Convenciones de testing

- PHPUnit 11: usar `#[\PHPUnit\Framework\Attributes\DataProvider('method')]` — `@dataProvider` en docblock está deprecado
- Trait `RefreshDatabase`: BD limpia por test via `setUp as setUpDatabase` (trait aliasing — ver `UserRepositoryTest` como referencia)
- Seeders mínimos por test — solo los registros que el test necesita
- No testear Controllers, Middleware, Vistas ni Router
- Al agregar columnas o tablas a `database/schema.sql`, actualizar también `tests/fixtures/schema.sqlite.sql` con las diferencias de sintaxis:

  | MySQL                         | SQLite equivalente |
  | ----------------------------- | ------------------ |
  | `AUTO_INCREMENT`              | `AUTOINCREMENT`    |
  | `DECIMAL(10,2)`               | `NUMERIC`          |
  | `datetime`                    | `TEXT`             |
  | `INT(11)`                     | `INTEGER`          |
  | `ON UPDATE CURRENT_TIMESTAMP` | (omitir)           |
  | `ENGINE=InnoDB CHARSET=`      | (omitir)           |

## Tests contra MariaDB real (`RefreshMariaDatabase`)

- Solo para casos que dependen de funciones de fecha del motor MySQL/MariaDB (`CURDATE()`, `NOW()`, `YEAR()`,
  `DATE_FORMAT()`, etc.) que SQLite no soporta o soporta con semántica distinta.
- Local: copiar `.env.testing.example` a `.env.testing` y ajustar credenciales; `tests/bootstrap.php` la carga
  automáticamente si existe. Sin ese archivo (o si el servidor no responde), el test se salta con
  `markTestSkipped()` en vez de fallar — la suite completa sigue corriendo sin exigir MariaDB instalada.
- El trait recrea la BD de test (`DROP`/`CREATE DATABASE` + `database/schema.sql`) en cada `setUp()`/`tearDown()`
  sobre **una sola BD de nombre fijo** (`DB_TEST_NAME`, p. ej. `sistemadeventas_test`), nunca la de desarrollo: el
  trait falla si el nombre no contiene `test`. Por eso la suite no se ejecuta en paralelo contra la misma BD; una
  corrida interrumpida deja esa BD, que la siguiente recrea.
- **Nunca calcular la fecha límite con `date()`/`strtotime()` de PHP y comparar contra `NOW()`/`CURDATE()` de la
  BD** — el reloj/timezone del proceso PHP y el del servidor MariaDB pueden diferir. Calcular la fecha límite con
  una expresión SQL evaluada por el propio motor (`NOW() - INTERVAL 1 HOUR`, etc.).
- En CI (`tests.yml`), el job `test` levanta un servicio `mariadb:10.11` con las env vars `DB_TEST_*` ya
  configuradas — no requiere `.env.testing` en ese entorno.

## Inyección del singleton para tests

`Database::set(PDO $pdo)` sobrescribe el singleton para inyectar SQLite en tests. `Database::reset()` lo limpia. `getInstance()` queda intacto para producción.

**Todo test que resetee el singleton debe dejar SQLite inyectado al terminar** (`Database::set(...)`, nunca solo `Database::reset()`): con `executionOrder="random"`, los tests Unit que hacen `new Model()` y corren después intentan conectar a MySQL. Localmente pasa porque el `.env` apunta a una BD real; en CI no existe y fallan. Para reproducir el escenario de CI, correr `vendor/bin/phpunit --testsuite Unit` sin `.env` ni `.env.testing`.

## CI (`.github/workflows/tests.yml`)

Un único job `test`, matrix PHP 8.2/8.3, que corre en cada push a `master` y cada PR:

- Ejecuta `vendor/bin/phpunit` (suites `Unit` + `Integration` en un solo proceso). La suite completa
  son 886 tests y tarda ~2 min en local con MariaDB real; no se filtra por diff ni se paraleliza — el coste real del pipeline es el provisioning del
  runner, no los tests, así que filtrar salía más caro que correr todo.
- `concurrency` con `cancel-in-progress` descarta runs obsoletos cuando llegan pushes seguidos.
- Levanta el servicio `mariadb:10.11` para los tests `*MariaDbTest.php`; el resto corre contra SQLite.
