<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\ActivityLogRenderer;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    private const MAX_RANGE_DAYS = 90;
    private const DEFAULT_RANGE_DAYS = 7;

    /**
     * Listado del log de auditoría con filtros de fecha (obligatorios).
     */
    public function index(): void
    {
        [$from, $to] = $this->resolveRange(
            $_GET['desde'] ?? '',
            $_GET['hasta'] ?? ''
        );

        $filters = [
            'entity'     => $_GET['entity']     ?? '',
            'action'     => $_GET['action']     ?? '',
            'id_usuario' => $_GET['id_usuario'] ?? '',
        ];

        $logModel = new ActivityLog();
        $logs     = $logModel->search($from, $to, $filters);
        $entities = $logModel->availableEntities();
        $actions  = $logModel->availableActions();

        $this->renderWithLayout('views/activity-log/index.php', array_merge(
            $this->sessionData(),
            [
                'logs'     => $logs,
                'entities' => $entities,
                'actions'  => $actions,
                'from'     => $from,
                'to'       => $to,
                'filters'  => $filters,
                'pageScripts' => ['/js/modules/activity-log/activity-log-index.js'],
            ]
        ), true, ['datatable', 'select2']);
    }

    /**
     * Detalle de un registro individual del log.
     *
     * @param int|null $id ID del log
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Registro inválido.', 'error');
            $this->redirect(BASE_URL . '/activity-log');
            return;
        }

        $logModel = new ActivityLog();
        $entry    = $logModel->find($id);

        if (!$entry) {
            $this->flash('No se encontró el registro solicitado.', 'error');
            $this->redirect(BASE_URL . '/activity-log');
            return;
        }

        $this->renderWithLayout('views/activity-log/show.php', array_merge(
            $this->sessionData(),
            [
                'entry'      => $entry,
                'rowsBefore' => ActivityLogRenderer::prepare($entry['datos_anteriores'] ?? null),
                'rowsAfter'  => ActivityLogRenderer::prepare($entry['datos_nuevos']    ?? null),
            ]
        ));
    }

    /**
     * Resuelve y valida el rango de fechas.
     * - Si están vacíos aplica el rango por defecto (últimos N días).
     * - Si el rango supera MAX_RANGE_DAYS lo recorta.
     * - Garantiza que $from <= $to.
     *
     * @return array{0: string, 1: string} [$from, $to] en formato Y-m-d
     */
    private function resolveRange(string $rawFrom, string $rawTo): array
    {
        $today = date('Y-m-d');

        $from = $rawFrom !== '' ? $rawFrom : date('Y-m-d', strtotime("-" . self::DEFAULT_RANGE_DAYS . " days"));
        $to   = $rawTo   !== '' ? $rawTo   : $today;

        // Garantizar from <= to
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // Recortar si excede el máximo
        $diffDays = (int)round((strtotime($to) - strtotime($from)) / 86400);
        if ($diffDays > self::MAX_RANGE_DAYS) {
            $from = date('Y-m-d', strtotime($to . " -" . self::MAX_RANGE_DAYS . " days"));
        }

        return [$from, $to];
    }
}
