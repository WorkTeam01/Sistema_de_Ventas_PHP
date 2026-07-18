<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
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
                'pageScripts' => ['/js/modules/products/products-create.js'],
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

        if (!is_numeric($stock) || !is_numeric($precio_compra) || !is_numeric($precio_venta)) {
            $this->flash('Los valores de stock y precios deben ser numéricos.', 'error');
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
                'pageScripts' => ['/js/modules/products/products-edit.js'],
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
        $stock = $_POST['stock'] ?? '';
        $stock_minimo = $_POST['stock_minimo'] ?? null;
        $stock_maximo = $_POST['stock_maximo'] ?? null;
        $precio_compra = $_POST['precio_compra'] ?? '';
        $precio_venta = $_POST['precio_venta'] ?? '';
        $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');
        $image_text = trim($_POST['image_text'] ?? 'producto_default.png');

        if ($id_producto <= 0 || $nombre === '' || $id_categoria <= 0 || $stock === '' || $precio_compra === '' || $precio_venta === '' || $fecha_ingreso === '') {
            $this->flash('Datos inválidos para actualizar el producto.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        if (!is_numeric($stock) || !is_numeric($precio_compra) || !is_numeric($precio_venta)) {
            $this->flash('Los valores de stock y precios deben ser numéricos.', 'error');
            $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
            return;
        }

        $imagen = $image_text;

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->handleImageUpload($_FILES['image']);
            if ($uploadResult['error']) {
                $this->flash($uploadResult['error'], 'error');
                $this->redirect(BASE_URL . '/products/edit/' . $id_producto);
                return;
            }
            $imagen = $uploadResult['filename'];
        }

        $productModel = new Product();
        $actual = $productModel->find($id_producto);
        $id_usuario = Auth::user()['id_usuario'];

        if ($productModel->updateProduct($id_producto, [
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
        ])) {
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
     * Responde JSON: { referenced: bool, carrito: int, compras: int }
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
            'referenced' => ($counts['carrito'] + $counts['compras']) > 0,
            'carrito' => $counts['carrito'],
            'compras' => $counts['compras'],
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
     * Elimina un producto si no está referenciado en compras ni en ventas.
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

        if ($productModel->isReferenced($id_producto)) {
            $this->flash('No se puede eliminar el producto porque tiene compras o ventas registradas.', 'error');
            $this->redirect(BASE_URL . '/products');
            return;
        }

        if ($productModel->delete($id_producto)) {
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
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $mimes_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime_real = mime_content_type($file['tmp_name']);
        $tamanio_max = 2 * 1024 * 1024;

        if (!in_array($extension, $extensiones_permitidas) || !in_array($mime_real, $mimes_permitidos) || $file['size'] > $tamanio_max) {
            return ['error' => 'Imagen no válida. Solo se permiten JPG, PNG o WEBP de hasta 2MB.', 'filename' => null];
        }

        $filename = date('Y-m-d-H-i-s') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = dirname(__DIR__, 2) . '/public/uploads/products/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return ['error' => 'Error al guardar la imagen en el servidor.', 'filename' => null];
        }

        return ['error' => null, 'filename' => $filename];
    }
}
