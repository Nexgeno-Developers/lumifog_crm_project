<?php defined('BASEPATH') or exit('No direct script access allowed');

$row            = $row ?? [];
$can_edit_boq   = ! empty($can_edit_boq);
$can_view_items = ! empty($can_view_items);
$id             = (int) ($row['id'] ?? 0);
$planned        = (float) ($row['planned_qty'] ?? 0);
$orderQty       = (isset($row['order_quantity']) && (float) $row['order_quantity'] > 0) ? (float) $row['order_quantity'] : $planned;
$revised        = (float) ($row['_revised_for_display'] ?? $row['revised_qty'] ?? $orderQty);
$purchased      = (float) ($row['purchased_qty'] ?? 0);
$remaining      = (float) ($row['remaining_qty'] ?? ($revised - $purchased));
$supplyRate     = isset($row['supply_rate']) && $row['supply_rate'] !== null && $row['supply_rate'] !== '' ? (float) $row['supply_rate'] : null;
$itcRate        = isset($row['itc_rate']) && $row['itc_rate'] !== null && $row['itc_rate'] !== '' ? (float) $row['itc_rate'] : null;
$supplyAmount   = isset($row['supply_amount']) && $row['supply_amount'] !== null && $row['supply_amount'] !== '' ? (float) $row['supply_amount'] : null;
$itcAmount      = isset($row['itc_amount']) && $row['itc_amount'] !== null && $row['itc_amount'] !== '' ? (float) $row['itc_amount'] : null;
$taxRate        = isset($row['tax_rate']) && $row['tax_rate'] !== null && $row['tax_rate'] !== '' ? (float) $row['tax_rate'] : null;
$isSummary      = ! empty($row['is_summary_item']);
$internalQty    = isset($row['internal_quantity']) && $row['internal_quantity'] !== null && $row['internal_quantity'] !== '' ? (float) $row['internal_quantity'] : null;
$internalMatRate = isset($row['internal_material_rate']) && $row['internal_material_rate'] !== null && $row['internal_material_rate'] !== '' ? (float) $row['internal_material_rate'] : null;
$internalLabRate = isset($row['internal_labour_rate']) && $row['internal_labour_rate'] !== null && $row['internal_labour_rate'] !== '' ? (float) $row['internal_labour_rate'] : null;
$internalSupplyAmt = isset($row['internal_supply_amount']) && $row['internal_supply_amount'] !== null && $row['internal_supply_amount'] !== '' ? (float) $row['internal_supply_amount'] : null;
$internalItcAmt   = isset($row['internal_itc_amount']) && $row['internal_itc_amount'] !== null && $row['internal_itc_amount'] !== '' ? (float) $row['internal_itc_amount'] : null;

$isRevised     = (abs($revised - $orderQty) > 0.0001);
$diff          = $revised - $orderQty;
$diffFormatted = ($diff > 0 ? '+' : '') . app_format_number($diff);
$tooltipText   = sprintf(
    _l('project_boq_revised_tooltip'),
    app_format_number($orderQty),
    app_format_number($revised),
    $diffFormatted
);
?>
<tr class="<?= $isRevised ? 'boq-row-revised' : ''; ?>"
    data-boq-id="<?= e($id); ?>" data-purchased="<?= e($purchased); ?>"
    data-revised="<?= e($revised); ?>"
    data-is-revised="<?= $isRevised ? '1' : '0'; ?>"
    data-item-id="<?= (int) ($row['item_id'] ?? 0); ?>"
    data-item-description="<?= e($row['item_description'] ?? ''); ?>">
    <?php if ($can_view_items) { ?>
    <td class="text-center project-boq-select-cell">
        <input type="checkbox" class="project-boq-select" aria-label="<?= e(_l('project_boq_select_rows')); ?>">
    </td>
    <?php } ?>
    <td><?= e($row['internal_ref_no'] ?? ''); ?></td>
    <td><?= e($row['client_ref_no'] ?? ''); ?></td>
    <td class="text-center"><?= (int) ($row['item_id'] ?? 0); ?></td>
    <td class="project-boq-item-label">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 4px;">
            <span><?= e($row['item_description'] ?? ''); ?></span>
            <?php if ($isRevised) { ?>
            <span class="label label-warning boq-revised-chip" data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="<?= e($tooltipText); ?>" style="flex-shrink: 0; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; cursor: help;">
                <i class="fa-solid fa-arrows-rotate tw-mr-1"></i><?= _l('project_boq_revised_badge'); ?>
            </span>
            <?php } ?>
        </div>
        <?php if (! empty($row['item_unit'])) { ?>
        <span class="text-muted hide"> <?= e($row['item_unit']); ?></span>
        <?php } ?>
    </td>
    <td class="project-boq-desc-cell"><?= e($row['boq_description'] ?? ''); ?></td>
    <td><?= e($row['boq_unit'] ?? ''); ?></td>
    <td class="text-right">
        <div><?= e(app_format_number($orderQty)); ?></div>
        <?php if ($isRevised) { ?>
        <div style="margin-top: 3px;">
            <span class="label label-warning boq-revised-subbadge" data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="<?= e($tooltipText); ?>" style="font-size: 10px; font-weight: 600; padding: 2px 5px; border-radius: 3px; display: inline-block; background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; cursor: help;">
                <i class="fa-solid fa-arrows-rotate tw-mr-1"></i>Rev: <?= e(app_format_number($revised)); ?>
            </span>
        </div>
        <?php } ?>
    </td>
    <td class="text-right"><?= $supplyRate !== null ? e(app_format_number($supplyRate)) : '—'; ?></td>
    <td class="text-right"><?= $itcRate !== null ? e(app_format_number($itcRate)) : '—'; ?></td>
    <td class="text-right"><?= $supplyAmount !== null ? e(app_format_number($supplyAmount)) : '—'; ?></td>
    <td class="text-right"><?= $itcAmount !== null ? e(app_format_number($itcAmount)) : '—'; ?></td>
    <td class="text-right"><?= $taxRate !== null ? e(app_format_number($taxRate)) : '—'; ?></td>
    <td class="text-center"><?= $isSummary ? _l('yes') : _l('no'); ?></td>
    <td class="boq-internal-col"><?= e($row['internal_client_ref_no'] ?? ''); ?></td>
    <td class="boq-internal-col project-boq-internal-desc-cell"><?= e($row['internal_description'] ?? ''); ?></td>
    <td class="boq-internal-col"><?= e($row['internal_unit'] ?? ''); ?></td>
    <td class="text-right boq-internal-col"><?= $internalQty !== null ? e(app_format_number($internalQty)) : '—'; ?></td>
    <td class="text-right boq-internal-col"><?= $internalMatRate !== null ? e(app_format_number($internalMatRate)) : '—'; ?></td>
    <td class="text-right boq-internal-col"><?= $internalLabRate !== null ? e(app_format_number($internalLabRate)) : '—'; ?></td>
    <td class="text-right boq-internal-col"><?= $internalSupplyAmt !== null ? e(app_format_number($internalSupplyAmt)) : '—'; ?></td>
    <td class="text-right boq-internal-col"><?= $internalItcAmt !== null ? e(app_format_number($internalItcAmt)) : '—'; ?></td>
    <td class="text-right">
        <input type="text"
            class="form-control input-sm text-right"
            readonly
            tabindex="-1"
            value="<?= e($row['purchased_qty_display'] ?? app_format_number($purchased)); ?>">
    </td>
    <td class="text-right">
        <span class="project-boq-remaining-display"><?= e($row['remaining_qty_display'] ?? app_format_number($remaining)); ?></span>
    </td>
    <td class="text-right project-boq-revised-cell <?= $isRevised ? 'boq-revised-cell-highlight' : ''; ?>" <?= $isRevised ? 'data-toggle="tooltip" data-placement="top" data-container="body" data-html="true" title="' . e($tooltipText) . '"' : ''; ?>>
        <span style="<?= $isRevised ? 'font-weight: 700; color: #92400e;' : ''; ?>">
            <?= e($row['revised_qty_display'] ?? app_format_number($revised)); ?>
        </span>
        <?php if ($isRevised) { ?>
        <div style="font-size: 10px; font-weight: 600; color: #b45309; line-height: 1.1; margin-top: 1px;">
            (<?= e($diffFormatted); ?>)
        </div>
        <?php } ?>
    </td>
    <?php if ($can_edit_boq && $can_view_items) { ?>
    <td class="text-center">
        <a href="#"
            class="btn btn-default btn-icon project-boq-edit"
            data-boq-id="<?= e($id); ?>"
            data-item-id="<?= (int) ($row['item_id'] ?? 0); ?>"
            data-planned="<?= e($orderQty); ?>"
            data-revised="<?= e($revised); ?>"
            data-toggle="modal"
            data-target="#project_boq_modal"
            title="<?= _l('edit'); ?>">
            <i class="fa-regular fa-pen-to-square"></i>
        </a>
        <a href="#"
            class="btn btn-danger btn-icon project-boq-delete"
            data-boq-id="<?= e($id); ?>"
            title="<?= _l('delete'); ?>">
            <i class="fa-regular fa-trash-can"></i>
        </a>
    </td>
    <?php } elseif ($can_view_items) { ?>
    <td class="text-center">
        <button type="button"
            class="btn btn-default btn-icon project-boq-request-edit"
            data-boq-id="<?= e($id); ?>"
            data-item-id="<?= (int) ($row['item_id'] ?? 0); ?>"
            data-planned="<?= e($orderQty); ?>"
            data-revised="<?= e($revised); ?>"
            title="<?= _l('project_boq_request_edit'); ?>">
            <i class="fa-regular fa-pen-to-square"></i>
        </button>
    </td>
    <?php } ?>
</tr>
