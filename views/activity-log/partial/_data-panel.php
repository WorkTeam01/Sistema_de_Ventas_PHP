<?php

/**
 * Renders a key-value data panel for an activity log entry.
 *
 * Expected variables:
 *   $rows     array   output of ActivityLogRenderer::prepare()
 *   $panelId  string  unique prefix for accordion collapse IDs
 */
?>
<?php if (empty($rows)): ?>
    <p class="text-muted m-3">Sin datos registrados.</p>
<?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <?php foreach ($rows as $row): ?>
            <?php if ($row['type'] === 'list'): ?>
                <tr>
                    <th class="w-35 bg-light align-middle">
                        <?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?>
                    </th>
                    <td class="p-0">
                        <?php
                        $items = $row['value'];
                        include __DIR__ . '/_item-accordion.php';
                        ?>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <th class="w-35 bg-light font-weight-normal text-muted">
                        <?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?>
                    </th>
                    <td><?= htmlspecialchars((string)$row['value'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>