<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Muestra el listado de todas las categorías registradas.
     */
    public function index(): void
    {
        $categoryModel = new Category();
        $categories_datos = $categoryModel->all();

        $this->renderWithLayout('views/categories/index.php', array_merge(
            $this->sessionData(),
            [
                'categories_datos' => $categories_datos,
                'pageScripts' => ['/js/modules/categories/categories-datatable.js', '/js/modules/categories/categories-modals.js'],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * AJAX — Crea una nueva categoría y retorna JSON.
     */
    public function store(): void
    {
        $nombre_categoria = trim($this->input('nombre_categoria') ?? '');

        if ($nombre_categoria === '') {
            $this->json(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.']);
            return;
        }

        $categoryModel = new Category();

        if ($categoryModel->nameExists($nombre_categoria)) {
            $this->json(['success' => false, 'message' => 'Ya existe una categoría con ese nombre.']);
            return;
        }

        if ($categoryModel->create(['nombre_categoria' => $nombre_categoria])) {
            $this->json(['success' => true, 'message' => 'Categoría creada exitosamente.']);
            return;
        }

        $this->json(['success' => false, 'message' => 'Error al crear la categoría.']);
    }

    /**
     * AJAX — Retorna los datos de una categoría para pre-llenar el modal de edición.
     *
     * @param int|null $id ID de la categoría
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de categoría inválido.']);
            return;
        }

        $categoryModel = new Category();
        $category = $categoryModel->find($id);

        if (!$category) {
            $this->json(['success' => false, 'message' => 'No se encontró la categoría solicitada.']);
            return;
        }

        $this->json(['success' => true, 'data' => $category]);
    }

    /**
     * AJAX — Actualiza una categoría existente y retorna JSON.
     *
     * @param int|null $id ID de la categoría
     */
    public function update(?int $id = null): void
    {
        $id = $id ?? (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de categoría inválido.']);
            return;
        }

        $nombre_categoria = trim($this->input('nombre_categoria') ?? '');

        if ($nombre_categoria === '') {
            $this->json(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.']);
            return;
        }

        $categoryModel = new Category();

        if (!$categoryModel->find($id)) {
            $this->json(['success' => false, 'message' => 'No se encontró la categoría solicitada.']);
            return;
        }

        if ($categoryModel->nameExists($nombre_categoria, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe una categoría con ese nombre.']);
            return;
        }

        if ($categoryModel->update($id, ['nombre_categoria' => $nombre_categoria])) {
            $this->json(['success' => true, 'message' => 'Categoría actualizada exitosamente.']);
            return;
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar la categoría.']);
    }

    /**
     * AJAX — Verifica si un nombre de categoría ya existe (para jQuery Validate remote).
     * Retorna true si está disponible, o un string de error si está en uso.
     */
    public function checkNombre(): void
    {
        $nombre_categoria = trim($this->input('nombre_categoria') ?? '');
        $id = $this->input('id');
        $excludeId = ($id !== null && $id !== '' && $id !== 'null') ? (int)$id : null;

        if ($nombre_categoria === '') {
            echo json_encode(true);
            exit;
        }

        $categoryModel = new Category();
        $exists = $categoryModel->nameExists($nombre_categoria, $excludeId);

        echo json_encode($exists ? 'Ya existe una categoría con este nombre.' : true);
        exit;
    }
}
