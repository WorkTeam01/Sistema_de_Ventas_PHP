<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\ImageUpload;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Muestra el listado de todos los productos del almacén.
     */
    public function index(): void
    {
        $productModel = new Product();
        $products_datos = $productModel->allWithCategories();

        $this->renderWithLayout('views/products/index.php', array_merge(
            $this->sessionData(),
            [
                'products_datos' => $products_datos,
                'pageScripts' => ['/js/modules/products/products-index.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra el formulario para registrar un nuevo producto.
     */
    public function create(): void
    {
        $productModel = new Product();
        $categoryModel = new Category();

        $this->renderWithLayout('views/products/create.php', array_merge(
            $this->sessionData(),
            [
                'next_code' => $productModel->nextCode(),
                'categories' => $categoryModel->all(),
                'email_sesion' => Auth::user()['email'] ?? '',
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/products/create.css'],
                'pageScripts' => ['/js/modules/products/products-form-shared.js', '/js/modules/products/products-create.js'],
            ]
        ), true, ['select2', 'validation']);
    }

    /**
     * Procesa el formulario de creación y guarda el nuevo producto.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombre = trim($_POST['nombre'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $stock = $_POST['stock'] ?? '';
        $stock_minimo = $_POST['stock_minimo'] ?? null;
        $stock_maximo = $_POST['stock_maximo'] ?? null;
        $precio_compra = $_POST['precio_compra'] ?? '';
        $precio_venta = $_POST['precio_venta'] ?? '';
        $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');

        if ($nombre === '' || $id_categoria <= 0 || $stock === '' || $precio_compra === '' || $precio_venta === '' || $fecha_ingreso === '') {
            $this->flash('Los campos Nombre, Categoría, Stock, Precios y Fecha son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/products/create');
            return;
        }

        $validation = (new Product())->validateData([
            'stock' => $stock,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
        ]);

        if ($validation !== true) {
            $this->flash(implode(' ', $validation), 'error');
            $this->redirect(BASE_URL . '/products/create');
            return;
        }

        if ($stock_minimo !== '' && $stock_minimo !== null && !is_numeric($stock_minimo)) {
            $this->flash('El stock mínimo debe ser numérico.', 'error');
            $this->redirect(BASE_URL . '/products/create');
            return;
        }

        if ($stock_maximo !== '' && $stock_maximo !== null && !is_numeric($stock_maximo)) {
            $this->flash('El stock máximo debe ser numérico.', 'error');
            $this->redirect(BASE_URL . '/products/create');
            return;
        }

        if (($stock_minimo !== '' && $stock_minimo !== null && (float) $stock_minimo < 0)
            || ($stock_maximo !== '' && $stock_maximo !== null && (float) $stock_maximo < 0)
        ) {
            $this->flash('El stock mínimo y el stock máximo no pueden ser negativos.', 'error');
            $this->redirect(BASE_URL . '/products/create');
            return;
        }

        $imagen = 'producto_default.png';

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->handleImageUpload($_FILES['image']);
            if ($uploadResult['error']) {
                $this->flash($uploadResult['error'], 'error');
                $this->redirect(BASE_URL . '/products/create');
                return;
            }
            $imagen = $uploadResult['filename'];
        }

        $productModel = new Product();
        $codigo = $productModel->nextCode();
        $id_usuario = Auth::user()['id_usuario'];

        $newId = $productModel->createProduct([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'stock' => $stock,
            'stock_minimo' => $stock_minimo,
            'stock_maximo' => $stock_maximo,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
            'fecha_ingreso' => $fecha_ingreso,
            'imagen' => $imagen,
            'id_usuario' => $id_usuario,
            'id_categoria' => $id_categoria,
        ]);

        if ($newId) {
            ActivityLog::record(
                'create',
                'product',
                (int)$newId,
                "Producto '{$nombre}' registrado",
                null,
                [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'stock' => $stock,
                    'precio_compra' => $precio_compra,
                    'precio_venta' => $precio_venta,
                ]
            );
            $this->flash('El producto se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $this->flash('Error al registrar el producto.', 'error');
        $this->redirect(BASE_URL . '/products/create');
    }

    /**
     * Muestra el detalle completo de un producto.
     *
     * @param int|null $id ID del producto.
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Producto inválido.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $productModel = new Product();
        $product = $productModel->findWithCategory($id);

        if (!$product) {
            $this->flash('No se encontró el producto solicitado.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $this->renderWithLayout('views/products/show.php', array_merge(
            $this->sessionData(),
            [
                'id_producto' => (int)$product['id_producto'],
                'codigo' => $product['codigo'],
                'nombre' => $product['nombre'],
                'descripcion' => $product['descripcion'],
                'stock' => $product['stock'],
                'stock_minimo' => $product['stock_minimo'],
                'stock_maximo' => $product['stock_maximo'],
                'precio_compra' => $product['precio_compra'],
                'precio_venta' => $product['precio_venta'],
                'fecha_ingreso' => $product['fecha_ingreso'],
                'imagen' => $product['imagen'],
                'fyh_creacion' => $product['fyh_creacion'],
                'fyh_actualizacion' => $product['fyh_actualizacion'],
                'nombre_categoria' => $product['nombre_categoria'] ?? '—',
            ]
        ));
    }

    /**
     * Muestra el formulario de edición para un producto existente.
     *
     * @param int|null $id ID del producto a editar.
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Producto inválido.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $productModel = new Product();
        $product = $productModel->find($id);

        if (!$product) {
            $this->flash('No se encontró el producto solicitado.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $categoryModel = new Category();

        $this->renderWithLayout('views/products/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_producto' => (int)$product['id_producto'],
                'codigo' => $product['codigo'],
                'nombre' => $product['nombre'],
                'descripcion' => $product['descripcion'],
                'stock' => $product['stock'],
                'stock_minimo' => $product['stock_minimo'],
                'stock_maximo' => $product['stock_maximo'],
                'precio_compra' => $product['precio_compra'],
                'precio_venta' => $product['precio_venta'],
                'fecha_ingreso' => $product['fecha_ingreso'],
                'imagen' => $product['imagen'],
                'id_categoria' => (int)$product['id_categoria'],
                'categories' => $categoryModel->all(),
                'email_sesion' => Auth::user()['email'] ?? '',
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/products/create.css'],
                'pageScripts' => ['/js/modules/products/products-form-shared.js', '/js/modules/products/products-edit.js'],
            ]
        ), true, ['select2', 'validation']);
    }

    /**
     * Procesa el formulario de edición y actualiza el producto.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_producto = (int)($_POST['id_producto'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $stock_minimo = $_POST['stock_minimo'] ?? null;
        $stock_maximo = $_POST['stock_maximo'] ?? null;
        $precio_compra = $_POST['precio_compra'] ?? '';
        $precio_venta = $_POST['precio_venta'] ?? '';
        $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');

        if ($id_producto <= 0 || $nombre === '' || $id_categoria <= 0 || $precio_compra === '' || $precio_venta === '' || $fecha_ingreso === '') {
            $this->flash('Datos inválidos para actualizar el producto.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $validation = (new Product())->validateData([
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
        ]);

        if ($validation !== true) {
            $this->flash(implode(' ', $validation), 'error');
            $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
            return;
        }

        if (($stock_minimo !== '' && $stock_minimo !== null && !is_numeric($stock_minimo))
            || ($stock_maximo !== '' && $stock_maximo !== null && !is_numeric($stock_maximo))
        ) {
            $this->flash('El stock mínimo y el stock máximo deben ser numéricos.', 'error');
            $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
            return;
        }

        if (($stock_minimo !== '' && $stock_minimo !== null && (float) $stock_minimo < 0)
            || ($stock_maximo !== '' && $stock_maximo !== null && (float) $stock_maximo < 0)
        ) {
            $this->flash('El stock mínimo y el stock máximo no pueden ser negativos.', 'error');
            $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
            return;
        }

        $productModel = new Product();
        $actual = $productModel->find($id_producto);

        if (!$actual) {
            $this->flash('Producto no encontrado.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $imagen = $actual['imagen'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->handleImageUpload($_FILES['image']);
            if ($uploadResult['error']) {
                $this->flash($uploadResult['error'], 'error');
                $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
                return;
            }
            $imagen = $uploadResult['filename'];
        }

        if ($productModel->updateProduct($id_producto, [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'stock_minimo' => $stock_minimo,
            'stock_maximo' => $stock_maximo,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
            'fecha_ingreso' => $fecha_ingreso,
            'imagen' => $imagen,
            'id_categoria' => $id_categoria,
        ])) {
            if ($actual) {
                $before = [
                    'nombre'        => $actual['nombre'],
                    'descripcion'   => $actual['descripcion'] ?: null,
                    'stock_minimo'  => $actual['stock_minimo'] !== null ? (int)$actual['stock_minimo'] : null,
                    'stock_maximo'  => $actual['stock_maximo'] !== null ? (int)$actual['stock_maximo'] : null,
                    'fecha_ingreso' => $actual['fecha_ingreso'],
                    'imagen'        => $actual['imagen'],
                    'id_categoria'  => (int)$actual['id_categoria'],
                ];
                $after = [
                    'nombre'        => $nombre,
                    'descripcion'   => $descripcion !== '' ? $descripcion : null,
                    'stock_minimo'  => $stock_minimo !== '' && $stock_minimo !== null ? (int)$stock_minimo : null,
                    'stock_maximo'  => $stock_maximo !== '' && $stock_maximo !== null ? (int)$stock_maximo : null,
                    'fecha_ingreso' => $fecha_ingreso,
                    'imagen'        => $imagen,
                    'id_categoria'  => $id_categoria,
                ];
                $changed = array_keys(array_diff_assoc($after, $before) + array_diff_assoc($before, $after));

                if ($changed) {
                    ActivityLog::record(
                        'update', 'product', $id_producto,
                        "Producto '{$actual['nombre']}' actualizado",
                        array_intersect_key($before, array_flip($changed)),
                        array_intersect_key($after, array_flip($changed))
                    );
                }
            }
            if ($actual && (
                (float)$actual['precio_venta'] !== (float)$precio_venta ||
                (float)$actual['precio_compra'] !== (float)$precio_compra
            )) {
                ActivityLog::record(
                    'price_change', 'product', $id_producto,
                    "Cambio de precio en '{$actual['nombre']}'",
                    ['precio_venta' => $actual['precio_venta'], 'precio_compra' => $actual['precio_compra']],
                    ['precio_venta' => $precio_venta,           'precio_compra' => $precio_compra]
                );
            }
            $this->flash('El producto se actualizó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $this->flash('Error al actualizar el producto.', 'error');
        $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
    }

    /**
     * Verifica si el producto tiene registros asociados en otras tablas.
     * Responde JSON: { referenced: bool, carrito: int, compras: int, ajustes: int, devoluciones: int }
     *
     * @param int|null $id ID del producto a verificar.
     */
    public function check(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['error' => 'Producto inválido.'], 400);
        }

        $productModel = new Product();

        if (!$productModel->find($id)) {
            $this->json(['error' => 'Producto no encontrado.'], 404);
        }

        $counts = $productModel->getReferenceCount($id);

        $this->json([
            'referenced' => array_sum($counts) > 0,
            'carrito' => $counts['carrito'],
            'compras' => $counts['compras'],
            'ajustes' => $counts['ajustes'],
            'devoluciones' => $counts['devoluciones'],
        ]);
    }

    /**
     * Muestra la pantalla de confirmación antes de eliminar un producto.
     *
     * @param int|null $id ID del producto a eliminar.
     */
    public function delete(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Producto inválido.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $productModel = new Product();
        $product = $productModel->findWithCategory($id);

        if (!$product) {
            $this->flash('No se encontró el producto solicitado.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $this->renderWithLayout('views/products/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_producto' => (int)$product['id_producto'],
                'codigo' => $product['codigo'],
                'nombre' => $product['nombre'],
                'imagen' => $product['imagen'],
                'nombre_categoria' => $product['nombre_categoria'] ?? '—',
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/products/products-delete.js'],
            ]
        ));
    }

    /**
     * Elimina un producto si no está referenciado en compras, ventas, ajustes ni devoluciones.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_producto = (int)($_POST['id_producto'] ?? 0);

        if ($id_producto <= 0) {
            $this->flash('Producto inválido.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $productModel = new Product();
        $snapshot = $productModel->find($id_producto);

        if ($productModel->isReferenced($id_producto)) {
            $this->flash('No se puede eliminar el producto porque tiene compras, ventas, ajustes de stock o devoluciones registradas.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        try {
            $deleted = $productModel->delete($id_producto);
        } catch (\PDOException $e) {
            // Una referencia creada entre isReferenced() y el DELETE la frena la clave foránea
            $this->flash('No se puede eliminar el producto porque tiene compras, ventas, ajustes de stock o devoluciones registradas.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        if ($deleted) {
            if ($snapshot) {
                ActivityLog::record(
                    'delete',
                    'product',
                    $id_producto,
                    "Producto '{$snapshot['nombre']}' eliminado",
                    [
                        'codigo'        => $snapshot['codigo'],
                        'nombre'        => $snapshot['nombre'],
                        'stock'         => $snapshot['stock'],
                        'precio_compra' => $snapshot['precio_compra'],
                        'precio_venta'  => $snapshot['precio_venta'],
                    ]
                );
            }
            $this->flash('El producto se eliminó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        $this->flash('Error al eliminar el producto.', 'error');
        $this->redirect(BASE_URL . '/products');
    }

    /**
     * Valida y mueve una imagen subida al directorio de productos.
     *
     * @param array $file Entrada de $_FILES['image'].
     * @return array{error: string|null, filename: string|null}
     */
    private function handleImageUpload(array $file): array
    {
        $error = ImageUpload::validate($file);
        if ($error !== null) {
            return ['error' => $error, 'filename' => null];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = date('Y-m-d-H-i-s') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = dirname(__DIR__, 2) . '/public/uploads/products/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return ['error' => 'Error al guardar la imagen en el servidor.', 'filename' => null];
        }

        return ['error' => null, 'filename' => $filename];
    }
}
