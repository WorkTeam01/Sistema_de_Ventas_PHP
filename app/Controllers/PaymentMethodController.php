<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    /** Tipos admitidos por tb_metodos_pago.tipo (enum del esquema). */
    private const TIPOS = ['efectivo', 'no_efectivo'];

    /**
     * Muestra el listado de métodos de pago con modales para crear y editar.
     */
    public function index(): void
    {
        $methodModel = new PaymentMethod();
        $methods_datos = $methodModel->all();

        $this->renderWithLayout('views/payment-methods/index.php', array_merge(
            $this->sessionData(),
            [
                'methods_datos' => $methods_datos,
                'csrf_token'    => Auth::generateCsrfToken(),
                'pageScripts'   => [
                    '/js/modules/payment-methods/payment-methods-datatable.js',
                    '/js/modules/payment-methods/payment-methods-modals.js',
                ],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * AJAX — Crea un método de pago y retorna JSON.
     */
    public function store(): void
    {
        $this->validateCsrfOrFailJson();

        $nombre = trim($this->input('nombre') ?? '');
        $tipo   = trim($this->input('tipo') ?? '');

        if ($nombre === '') {
            $this->json(['success' => false, 'message' => 'El nombre del método de pago es obligatorio.']);
        }

        if (!in_array($tipo, self::TIPOS, true)) {
            $this->json(['success' => false, 'message' => 'El tipo debe ser "efectivo" o "no_efectivo".']);
        }

        $methodModel = new PaymentMethod();

        if ($methodModel->nameExists($nombre)) {
            $this->json(['success' => false, 'message' => 'Ya existe un método de pago con ese nombre.']);
        }

        $newId = $methodModel->create([
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'activo' => 1,
        ]);

        if ($newId) {
            ActivityLog::record(
                'create',
                'payment_method',
                (int)$newId,
                "Método de pago '{$nombre}' registrado",
                null,
                ['nombre' => $nombre, 'tipo' => $tipo, 'activo' => 1]
            );
            $this->json(['success' => true, 'message' => 'Método de pago creado exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al crear el método de pago.']);
    }

    /**
     * AJAX — Retorna los datos de un método de pago para pre-llenar el modal de edición.
     *
     * @param int|null $id ID del método de pago
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de método de pago inválido.']);
        }

        $methodModel = new PaymentMethod();
        $method = $methodModel->find($id);

        if (!$method) {
            $this->json(['success' => false, 'message' => 'No se encontró el método de pago solicitado.']);
        }

        $this->json(['success' => true, 'data' => $method]);
    }

    /**
     * AJAX — Actualiza un método de pago (campos y/o activo) y retorna JSON.
     * Activar/desactivar se hace vía este verbo estándar (campo `activo`).
     *
     * @param int|null $id ID del método de pago
     */
    public function update(?int $id = null): void
    {
        $this->validateCsrfOrFailJson();

        $id = $id ?? (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de método de pago inválido.']);
        }

        $methodModel = new PaymentMethod();
        $method = $methodModel->find($id);

        if (!$method) {
            $this->json(['success' => false, 'message' => 'No se encontró el método de pago solicitado.']);
        }

        $nombre = trim($this->input('nombre') ?? $method['nombre']);
        $tipo   = trim($this->input('tipo') ?? $method['tipo']);
        $activo = $this->input('activo');

        if ($nombre === '') {
            $this->json(['success' => false, 'message' => 'El nombre del método de pago es obligatorio.']);
        }

        if (!in_array($tipo, self::TIPOS, true)) {
            $this->json(['success' => false, 'message' => 'El tipo debe ser "efectivo" o "no_efectivo".']);
        }

        if ($methodModel->nameExists($nombre, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe un método de pago con ese nombre.']);
        }

        $payload = [
            'nombre' => $nombre,
            'tipo'   => $tipo,
        ];

        if ($activo !== null && $activo !== '') {
            $payload['activo'] = (int)$activo ? 1 : 0;
        }

        if ($methodModel->update($id, $payload)) {
            ActivityLog::record(
                'update',
                'payment_method',
                $id,
                "Método de pago '{$method['nombre']}' actualizado",
                ['nombre' => $method['nombre'], 'tipo' => $method['tipo'], 'activo' => (int)$method['activo']],
                $payload
            );
            $this->json(['success' => true, 'message' => 'Método de pago actualizado exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el método de pago.']);
    }

    /**
     * AJAX — Elimina un método de pago si no tiene líneas de pago (FR-2).
     * Un método referenciado solo se puede desactivar.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFailJson();

        $id = (int)($this->input('id_metodo_pago') ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Método de pago inválido.']);
        }

        $methodModel = new PaymentMethod();

        if ($methodModel->isReferenced($id)) {
            $this->json([
                'success' => false,
                'message' => 'No se puede eliminar: el método tiene pagos registrados. Desactívalo en su lugar.',
            ]);
        }

        $snapshot = $methodModel->find($id);

        if ($methodModel->delete($id)) {
            if ($snapshot) {
                ActivityLog::record(
                    'delete',
                    'payment_method',
                    $id,
                    "Método de pago '{$snapshot['nombre']}' eliminado",
                    ['nombre' => $snapshot['nombre'], 'tipo' => $snapshot['tipo'], 'activo' => (int)$snapshot['activo']]
                );
            }
            $this->json(['success' => true, 'message' => 'Método de pago eliminado exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al eliminar el método de pago.']);
    }

    /**
     * AJAX — Verifica si un nombre de método de pago ya existe (jQuery Validate remote).
     * Retorna true si está disponible, o un string de error si está en uso.
     */
    public function checkNombre(): void
    {
        $nombre = trim($this->input('nombre') ?? '');
        $id = $this->input('id');
        $excludeId = ($id !== null && $id !== '' && $id !== 'null') ? (int)$id : null;

        if ($nombre === '') {
            echo json_encode(true);
            exit;
        }

        $methodModel = new PaymentMethod();
        $exists = $methodModel->nameExists($nombre, $excludeId);

        echo json_encode($exists ? 'Ya existe un método de pago con este nombre.' : true);
        exit;
    }
}
