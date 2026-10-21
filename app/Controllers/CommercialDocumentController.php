<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\CommercialDocument;
use App\Models\Product;
use App\Models\Supplier;

class CommercialDocumentController extends Controller
{
    public function index(): void
    {
        $type = $this->requestType();
        $this->requireAccess($type, false);
        $userId = $this->canViewAll($type) ? null : (int)Auth::user()['id_usuario'];

        $this->renderWithLayout('views/documents/index.php', array_merge(
            $this->sessionData(),
            [
                'type' => $type,
                'typeLabel' => CommercialDocument::TYPES[$type],
                'documents' => (new CommercialDocument())->allWithDetails($type, $userId),
                'canManage' => $this->canManage($type),
            ]
        ), true, ['datatable']);
    }

    public function create(): void
    {
        $type = $this->requestType();
        $this->requireAccess($type, true);
        $this->renderForm($type, null);
    }

    public function store(): void
    {
        $this->validateCsrfOrFail();
        $type = $this->postType();
        $this->requireAccess($type, true);
        $data = $this->formData();
        $model = new CommercialDocument();
        $validation = $model->validateData($type, $data);
        if ($validation !== true || !$this->contactExists($type, (int)$data['id_contacto'])) {
            $this->flash($validation !== true ? $validation : 'El cliente o proveedor seleccionado no existe.', 'error');
            $this->redirect(BASE_URL . '/documents/create?tipo=' . rawurlencode($type));
        }

        $result = $model->createDocument($type, $data, (int)Auth::user()['id_usuario']);
        if (!$result['ok']) {
            $this->flash(
                $result['error'] === 'invalid_items'
                    ? 'Revise los productos y sus cantidades.'
                    : 'No se pudo guardar el documento. Revise los datos e inténtelo nuevamente.',
                'error'
            );
            $this->redirect(BASE_URL . '/documents/create?tipo=' . rawurlencode($type));
        }

        $id = (int)$result['id'];
        ActivityLog::record('create', 'commercial_document', $id, $this->documentLabel($type, $id) . ' creado.');
        $this->flash($this->documentLabel($type, $id) . ' guardado correctamente.', 'success');
        $this->redirect(BASE_URL . '/documents/show/' . $id);
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? 0;
        $model = new CommercialDocument();
        $document = $id > 0 ? $model->findWithDetails($id) : null;
        if ($document === null) {
            $this->flash('No se encontró el documento solicitado.', 'error');
            $this->redirect(BASE_URL . '/documents?tipo=cotizacion');
        }

        $type = (string)$document['tipo'];
        $this->requireAccess($type, false);
        $this->requireOwnerScope($document, $type);
        $this->renderWithLayout('views/documents/show.php', array_merge(
            $this->sessionData(),
            [
                'document' => $document,
                'typeLabel' => CommercialDocument::TYPES[$type],
                'history' => $model->historyFor($id),
                'canManage' => $this->canManage($type),
                'pageStyles' => ['/css/modules/documents/print.css'],
                'pageScripts' => ['/js/modules/documents/documents-show.js'],
            ]
        ));
    }

    public function edit(?int $id = null): void
    {
        $model = new CommercialDocument();
        $document = $id !== null && $id > 0 ? $model->findWithDetails($id) : null;
        if ($document === null) {
            $this->flash('No se encontró el documento solicitado.', 'error');
            $this->redirect(BASE_URL . '/documents?tipo=cotizacion');
        }

        $type = (string)$document['tipo'];
        $this->requireAccess($type, true);
        $this->requireOwnerScope($document, $type);
        $this->renderForm($type, $document);
    }

    public function update(): void
    {
        $this->validateCsrfOrFail();
        $id = $this->postInt('id_documento');
        $model = new CommercialDocument();
        $before = $id > 0 ? $model->findWithDetails($id) : null;
        if ($before === null) {
            $this->flash('No se encontró el documento solicitado.', 'error');
            $this->redirect(BASE_URL . '/documents?tipo=cotizacion');
        }

        $type = (string)$before['tipo'];
        $this->requireAccess($type, true);
        $this->requireOwnerScope($before, $type);
        $data = $this->formData();
        $validation = $model->validateData($type, $data);
        if ($validation !== true || !$this->contactExists($type, (int)$data['id_contacto'])) {
            $this->flash($validation !== true ? $validation : 'El cliente o proveedor seleccionado no existe.', 'error');
            $this->redirect(BASE_URL . '/documents/edit/' . $id);
        }

        $result = $model->updateDocument($id, $type, $data, (int)Auth::user()['id_usuario']);
        if (!$result['ok']) {
            $this->flash(
                $result['error'] === 'invalid_items'
                    ? 'Revise los productos y sus cantidades.'
                    : 'No se pudo actualizar el documento.',
                'error'
            );
            $this->redirect(BASE_URL . '/documents/edit/' . $id);
        }

        $after = $model->findWithDetails($id);
        ActivityLog::record(
            'update',
            'commercial_document',
            $id,
            $this->documentLabel($type, $id) . ' corregido; se conservó la versión anterior.',
            $this->auditFields($before),
            $this->auditFields($after ?? [])
        );
        $this->flash('Documento actualizado. La versión anterior quedó guardada en el historial.', 'success');
        $this->redirect(BASE_URL . '/documents/show/' . $id);
    }

    private function renderForm(string $type, ?array $document): void
    {
        $this->renderWithLayout('views/documents/form.php', array_merge(
            $this->sessionData(),
            [
                'type' => $type,
                'typeLabel' => CommercialDocument::TYPES[$type],
                'document' => $document,
                'contacts' => $type === 'cotizacion' ? (new Client())->all() : (new Supplier())->all(),
                'products' => (new Product())->all(),
                'statuses' => $type === 'cotizacion'
                    ? ['borrador' => 'Borrador', 'emitida' => 'Emitida', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'vencida' => 'Vencida']
                    : ['borrador' => 'Borrador', 'emitida' => 'Emitida', 'cancelada' => 'Cancelada'],
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/documents/documents-form.js'],
            ]
        ), true, ['select2']);
    }

    private function formData(): array
    {
        return [
            'estado' => $this->postString('estado'),
            'fecha_documento' => $this->postString('fecha_documento'),
            'fecha_vencimiento' => $this->postString('fecha_vencimiento'),
            'id_contacto' => $this->postInt('id_contacto'),
            'observaciones' => $this->postString('observaciones'),
            'items' => is_array($_POST['items'] ?? null) ? $_POST['items'] : [],
            'usuario_nombre' => (string)(Auth::user()['nombres'] ?? ''),
        ];
    }

    private function requestType(): string
    {
        $type = is_string($_GET['tipo'] ?? null) ? $_GET['tipo'] : '';
        if (!isset(CommercialDocument::TYPES[$type])) {
            $this->flash('Seleccione el tipo de documento.', 'warning');
            $this->redirect(BASE_URL . '/documents?tipo=cotizacion');
        }

        return $type;
    }

    private function postType(): string
    {
        $type = $this->postString('tipo');
        if (!isset(CommercialDocument::TYPES[$type])) {
            $this->forbidden('Tipo de documento no válido.');
        }

        return $type;
    }

    private function requireAccess(string $type, bool $manage): void
    {
        if (!isset(CommercialDocument::TYPES[$type])) {
            $this->forbidden();
        }
        $permission = $manage
            ? ($type === 'cotizacion' ? 'manage_sales' : 'manage_purchases')
            : ($type === 'cotizacion' ? 'view_sales' : 'view_purchases');
        if (!Auth::can($permission)) {
            $this->forbidden();
        }
    }

    private function requireOwnerScope(array $document, string $type): void
    {
        if (!$this->canViewAll($type) && (int)$document['id_usuario'] !== (int)Auth::user()['id_usuario']) {
            $this->forbidden('No tienes permiso para ver o modificar este documento.');
        }
    }

    private function canManage(string $type): bool
    {
        return Auth::can($type === 'cotizacion' ? 'manage_sales' : 'manage_purchases');
    }

    private function canViewAll(string $type): bool
    {
        return Auth::can($type === 'cotizacion' ? 'view_sales_all' : 'view_purchases_all');
    }

    private function contactExists(string $type, int $id): bool
    {
        $contact = $type === 'cotizacion' ? (new Client())->find($id) : (new Supplier())->find($id);

        return $contact !== false;
    }

    private function postString(string $key): string
    {
        return is_string($_POST[$key] ?? null) || is_numeric($_POST[$key] ?? null)
            ? trim((string)$_POST[$key])
            : '';
    }

    private function postInt(string $key): int
    {
        $value = $_POST[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return 0;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? 0 : $number;
    }

    private function documentLabel(string $type, int $id): string
    {
        $prefix = $type === 'cotizacion' ? 'COT' : 'OC';

        return $prefix . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }

    private function auditFields(array $document): array
    {
        return [
            'tipo' => $document['tipo'] ?? null,
            'estado' => $document['estado'] ?? null,
            'fecha_documento' => $document['fecha_documento'] ?? null,
            'fecha_vencimiento' => $document['fecha_vencimiento'] ?? null,
            'id_cliente' => $document['id_cliente'] ?? null,
            'id_proveedor' => $document['id_proveedor'] ?? null,
            'observaciones' => $document['observaciones'] ?? null,
            'total' => $document['total'] ?? null,
            'items' => $document['items'] ?? [],
        ];
    }
}
