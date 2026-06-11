<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use JetBrains\PhpStorm\NoReturn;

class ClientController extends Controller
{
    /**
     * Muestra el listado de todos los clientes con modales para crear y editar.
     */
    public function index(): void
    {
        $clientModel = new Client();
        $clients_datos = $clientModel->all();

        $this->renderWithLayout('views/clients/index.php', array_merge(
            $this->sessionData(),
            [
                'clients_datos' => $clients_datos,
                'csrf_token'    => Auth::generateCsrfToken(),
                'pageScripts'   => ['/js/modules/clients/clients-datatable.js', '/js/modules/clients/clients-modals.js'],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * Guarda un nuevo cliente (AJAX).
     */
    public function store(): void
    {
        $this->validateCsrfOrFailJson();

        $nombre_cliente = trim($this->input('nombre_cliente') ?? '');
        $nit_ci_cliente = trim($this->input('nit_ci_cliente') ?? '');
        $celular_cliente = trim($this->input('celular_cliente') ?? '');
        $email_cliente = trim($this->input('email_cliente') ?? '');

        if ($nombre_cliente === '' || $nit_ci_cliente === '' || $celular_cliente === '' || $email_cliente === '') {
            $this->json(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        }

        $clientModel = new Client();

        if (!$clientModel->isValidEmail($email_cliente)) {
            $this->json(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
        }

        if ($clientModel->nitCiExists($nit_ci_cliente)) {
            $this->json(['success' => false, 'message' => 'Ya existe un cliente con ese NIT/CI.']);
        }

        if ($clientModel->emailExists($email_cliente)) {
            $this->json(['success' => false, 'message' => 'Ya existe un cliente con ese correo electrónico.']);
        }

        $newId = $clientModel->create([
            'nombre_cliente' => $nombre_cliente,
            'nit_ci_cliente' => $nit_ci_cliente,
            'celular_cliente' => $celular_cliente,
            'email_cliente' => $email_cliente,
        ]);

        if ($newId) {
            $this->json([
                'success' => true,
                'message' => 'El cliente se registró exitosamente.',
                'data' => [
                    'id_cliente'      => $newId,
                    'nombre_cliente'  => $nombre_cliente,
                    'nit_ci_cliente'  => $nit_ci_cliente,
                    'celular_cliente' => $celular_cliente,
                    'email_cliente'   => $email_cliente,
                ],
            ]);
        }

        $this->json(['success' => false, 'message' => 'Error al registrar el cliente.']);
    }

    /**
     * Retorna los datos de un cliente en JSON (AJAX — para pre-llenar el modal de edición).
     *
     * @param int|null $id
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de cliente inválido.']);
        }

        $clientModel = new Client();
        $client = $clientModel->find($id);

        if ($client) {
            $this->json(['success' => true, 'data' => $client]);
        }

        $this->json(['success' => false, 'message' => 'Cliente no encontrado.']);
    }

    /**
     * Actualiza un cliente existente (AJAX).
     *
     * @param int|null $id
     */
    public function update(?int $id = null): void
    {
        $this->validateCsrfOrFailJson();

        $id = $id ?? (int)($_POST['id'] ?? 0);

        $nombre_cliente = trim($this->input('nombre_cliente') ?? '');
        $nit_ci_cliente = trim($this->input('nit_ci_cliente') ?? '');
        $celular_cliente = trim($this->input('celular_cliente') ?? '');
        $email_cliente = trim($this->input('email_cliente') ?? '');

        if ($id <= 0 || $nombre_cliente === '' || $nit_ci_cliente === '' || $celular_cliente === '' || $email_cliente === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos para actualizar el cliente.']);
        }

        $clientModel = new Client();

        if (!$clientModel->isValidEmail($email_cliente)) {
            $this->json(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
        }

        if (!$clientModel->find($id)) {
            $this->json(['success' => false, 'message' => 'Cliente no encontrado.']);
        }

        if ($clientModel->nitCiExists($nit_ci_cliente, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe otro cliente con ese NIT/CI.']);
        }

        if ($clientModel->emailExists($email_cliente, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe otro cliente con ese correo electrónico.']);
        }

        if ($clientModel->update($id, [
            'nombre_cliente' => $nombre_cliente,
            'nit_ci_cliente' => $nit_ci_cliente,
            'celular_cliente' => $celular_cliente,
            'email_cliente' => $email_cliente,
        ])) {
            $this->json(['success' => true, 'message' => 'El cliente se actualizó exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el cliente.']);
    }

    /**
     * Elimina un cliente si no tiene ventas asociadas (AJAX).
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFailJson();

        $id = (int)($this->input('id_cliente') ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Cliente inválido.']);
        }

        $clientModel = new Client();

        if ($clientModel->isReferenced($id)) {
            $this->json(['success' => false, 'message' => 'No se puede eliminar el cliente porque tiene ventas registradas.']);
        }

        $snapshot = $clientModel->find($id);

        if ($clientModel->delete($id)) {
            if ($snapshot) {
                ActivityLog::record(
                    'delete', 'client', $id,
                    "Cliente '{$snapshot['nombre_cliente']}' (NIT/CI: {$snapshot['nit_ci_cliente']}) eliminado.",
                    ['nombre_cliente' => $snapshot['nombre_cliente'], 'nit_ci_cliente' => $snapshot['nit_ci_cliente'], 'email_cliente' => $snapshot['email_cliente']]
                );
            }
            $this->json(['success' => true, 'message' => 'El cliente se eliminó exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al eliminar el cliente.']);
    }

    /**
     * Verifica si el NIT/CI ya existe (AJAX — jQuery Validate remote).
     *
     * jQuery Validate espera:
     * - true  → validación pasa (NIT/CI disponible)
     * - string → validación falla (mensaje de error)
     */
    #[NoReturn]
    public function checkNitCi(): void
    {
        $nit_ci = trim($this->input('nit_ci_cliente') ?? '');
        $id = $this->input('id');

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        if ($nit_ci === '') {
            echo json_encode(true);
            exit;
        }

        $clientModel = new Client();
        $exists = $clientModel->nitCiExists($nit_ci, $id);

        echo json_encode($exists ? 'Ya existe un cliente con este NIT/CI.' : true);
        exit;
    }

    /**
     * Verifica si el correo electrónico ya existe (AJAX — jQuery Validate remote).
     *
     * jQuery Validate espera:
     * - true  → validación pasa (email disponible)
     * - string → validación falla (mensaje de error)
     */
    #[NoReturn]
    public function checkEmail(): void
    {
        $email = trim($this->input('email_cliente') ?? '');
        $id = $this->input('id');

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        if ($email === '') {
            echo json_encode(true);
            exit;
        }

        $clientModel = new Client();
        $exists = $clientModel->emailExists($email, $id);

        echo json_encode($exists ? 'Ya existe un cliente con este correo electrónico.' : true);
        exit;
    }
}
