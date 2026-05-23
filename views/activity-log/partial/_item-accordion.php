<?php

/**
 * Renders a collapsible accordion for a list of items.
 *
 * Expected variables (injected by _data-panel.php):
 *   $items    array[]  list of key-value item arrays
 *   $panelId  string   unique prefix for collapse IDs
 */
$count = count($items);
?>
<div class="p-2">
    <?php foreach ($items as $i => $item): ?>
        <?php $collapseId = htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') . 'item' . $i; ?>
        <div class="card card-outline card-secondary mb-1" style="box-shadow:none;">
            <div class="card-header p-0"
                style="cursor:pointer;"
                data-toggle="collapse"
                data-target="#<?= $collapseId ?>"
                aria-expanded="false">
                <h6 class="mb-0 px-3 py-2 d-flex justify-content-between align-items-center">
                    <span>Ítem <?= $i + 1 ?> de <?= $count ?></span>
                    <i class="fas fa-chevron-down"></i>
                </h6>
            </div>
            <div id="<?= $collapseId ?>" class="collapse">
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <?php foreach ($item as $k => $v): ?>
                            <tr>
                                <th class="w-40 bg-light font-weight-normal text-muted pl-3">
                                    <?= htmlspecialchars(\App\Helpers\ActivityLogRenderer::label((string)$k), ENT_QUOTES, 'UTF-8') ?>
                                </th>
                                <td><?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>