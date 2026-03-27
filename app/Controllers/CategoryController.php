<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Muestra el listado de todas las categorías registradas.
     */
    public function index(): void
    {
        $categoryModel    = new Category();
        $categories_datos = $categoryModel->all();

        $this->renderWithLayout('views/categories/index.php', array_merge(
            $this->sessionData(),
            ['categories_datos' => $categories_datos]
        ));
    }

    /**
     * Muestra el formulario para crear una nueva categoría.
     */
    public function create(): void
    {
        $this->renderWithLayout('views/categories/create.php', array_merge(
            $this->sessionData(),
            ['csrf_token' => Auth::generateCsrfToken()]
        ));
    }

    /**
     * Procesa el formulario de creación y guarda la nueva categoría.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombre_categoria = trim($_POST['nombre_categoria'] ?? '');

        if ($nombre_categoria === '') {
            $this->flash('El nombre de la categoría es obligatorio.', 'error');
            $this->redirect(BASE_URL . '/categories/create');
            return;
        }

        $categoryModel = new Category();

        if ($categoryModel->create(['nombre_categoria' => $nombre_categoria])) {
            $this->flash('La categoría se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/categories');
            return;
        }

        $this->flash('Error al crear la categoría.', 'error');
        $this->redirect(BASE_URL . '/categories/create');
    }

    /**
     * Muestra el formulario de edición para una categoría existente.
     *
     * @param int|null $id ID de la categoría a editar
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Categoría inválida.', 'error');
            $this->redirect(BASE_URL . '/categories');
            return;
        }

        $categoryModel = new Category();
        $category      = $categoryModel->find($id);

        if (!$category) {
            $this->flash('No se encontró la categoría solicitada.', 'error');
            $this->redirect(BASE_URL . '/categories');
            return;
        }

        $this->renderWithLayout('views/categories/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_categoria'     => (int) $category['id_categoria'],
                'nombre_categoria' => $category['nombre_categoria'],
                'csrf_token'       => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Procesa el formulario de edición y actualiza la categoría.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_categoria     = (int) ($_POST['id_categoria'] ?? 0);
        $nombre_categoria = trim($_POST['nombre_categoria'] ?? '');

        if ($id_categoria <= 0 || $nombre_categoria === '') {
            $this->flash('Datos inválidos para actualizar la categoría.', 'error');
            $this->redirect(BASE_URL . '/categories');
            return;
        }

        $categoryModel = new Category();

        if ($categoryModel->update($id_categoria, ['nombre_categoria' => $nombre_categoria])) {
            $this->flash('La categoría se actualizó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/categories');
            return;
        }

        $this->flash('Error al actualizar la categoría.', 'error');
        $this->redirect(BASE_URL . '/categories/edit/' . $id_categoria);
    }
}
