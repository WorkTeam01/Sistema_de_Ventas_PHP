<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * Muestra el listado de todos los clientes registrados.
     */
    public function index(): void
    {
        $clientModel   = new Client();
        $clients_datos = $clientModel->all();

        $this->renderWithLayout('views/clients/index.php', array_merge(
            $this->sessionData(),
            [
                'clients_datos' => $clients_datos,
                'csrf_token'    => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Muestra el formulario para registrar un nuevo cliente.
     */
    public function create(): void
    {
        $this->renderWithLayout('views/clients/create.php', array_merge(
            $this->sessionData(),
            ['csrf_token' => Auth::generateCsrfToken()]
        ));
    }

    /**
     * Procesa el formulario de creación y guarda el nuevo cliente.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombre_cliente  = trim($_POST['nombre_cliente'] ?? '');
        $nit_ci_cliente  = trim($_POST['nit_ci_cliente'] ?? '');
        $celular_cliente = trim($_POST['celular_cliente'] ?? '');
        $email_cliente   = trim($_POST['email_cliente'] ?? '');

        if ($nombre_cliente === '' || $nit_ci_cliente === '' || $celular_cliente === '' || $email_cliente === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/clients/create');
            return;
        }

        $clientModel = new Client();

        if ($clientModel->create([
            'nombre_cliente'  => $nombre_cliente,
            'nit_ci_cliente'  => $nit_ci_cliente,
            'celular_cliente' => $celular_cliente,
            'email_cliente'   => $email_cliente,
        ])) {
            $this->flash('El cliente se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $this->flash('Error al registrar el cliente.', 'error');
        $this->redirect(BASE_URL . '/clients/create');
    }

    /**
     * Muestra el formulario de edición para un cliente existente.
     *
     * @param int|null $id ID del cliente a editar.
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Cliente inválido.', 'error');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $clientModel = new Client();
        $client      = $clientModel->find($id);

        if (!$client) {
            $this->flash('No se encontró el cliente solicitado.', 'error');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $this->renderWithLayout('views/clients/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_cliente'      => (int) $client['id_cliente'],
                'nombre_cliente'  => $client['nombre_cliente'],
                'nit_ci_cliente'  => $client['nit_ci_cliente'],
                'celular_cliente' => $client['celular_cliente'],
                'email_cliente'   => $client['email_cliente'],
                'csrf_token'      => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Procesa el formulario de edición y actualiza el cliente.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_cliente      = (int) ($_POST['id_cliente'] ?? 0);
        $nombre_cliente  = trim($_POST['nombre_cliente'] ?? '');
        $nit_ci_cliente  = trim($_POST['nit_ci_cliente'] ?? '');
        $celular_cliente = trim($_POST['celular_cliente'] ?? '');
        $email_cliente   = trim($_POST['email_cliente'] ?? '');

        if ($id_cliente <= 0 || $nombre_cliente === '' || $nit_ci_cliente === '' || $celular_cliente === '' || $email_cliente === '') {
            $this->flash('Datos inválidos para actualizar el cliente.', 'error');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $clientModel = new Client();

        if ($clientModel->update($id_cliente, [
            'nombre_cliente'  => $nombre_cliente,
            'nit_ci_cliente'  => $nit_ci_cliente,
            'celular_cliente' => $celular_cliente,
            'email_cliente'   => $email_cliente,
        ])) {
            $this->flash('El cliente se actualizó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $this->flash('Error al actualizar el cliente.', 'error');
        $this->redirect(BASE_URL . '/clients/edit/' . $id_cliente);
    }

    /**
     * Elimina un cliente si no tiene ventas asociadas.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_cliente = (int) ($_POST['id_cliente'] ?? 0);

        if ($id_cliente <= 0) {
            $this->flash('Cliente inválido.', 'error');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $clientModel = new Client();

        if ($clientModel->isReferenced($id_cliente)) {
            $this->flash('No se puede eliminar el cliente porque tiene ventas registradas.', 'error');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        if ($clientModel->delete($id_cliente)) {
            $this->flash('El cliente se eliminó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/clients');
            return;
        }

        $this->flash('Error al eliminar el cliente.', 'error');
        $this->redirect(BASE_URL . '/clients');
    }
}
