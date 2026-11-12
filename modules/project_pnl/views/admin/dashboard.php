<?php defined('BASEPATH') or exit('No direct script access allowed');

$currency = $pnl['currency'];
$money    = function ($amount) use ($currency) {
    return e(app_format_money($amount, $currency));
};
$percent = function ($part, $whole) {
    return $whole > 0 ? min(round(($part / $whole) * 100, 1), 999) : 0;
};
$pctText = function ($value) {
    return $value === null ? '—' : e(app_format_number($value, true)) . '%';
};

$revenue      = $pnl['revenue'];
$purchase     = $pnl['purchase'];
$expenses     = $pnl['expenses'];
$projectValue = $pnl['project_value'];
$isProfit     = $pnl['profit_net'] >= 0;
$unlinked     = $expenses['unlinked_required'];

$progressRows = [];
if ($projectValue > 0) {
    $progressRows[] = [_l('project_pnl_invoiced_of_value'), $revenue['net'], $projectValue, 'success'];
    $progressRows[] = [_l('project_pnl_expected_of_value'), $pnl['expected_net'], $projectValue, 'warning'];
}
$progressRows[] = [_l('project_pnl_received_of_invoiced'), $revenue['received'], $revenue['gross'], 'info'];

$chartData = [
    'monthly'  => $pnl['monthly'],
    // Perfex's JS format_money() falls back to "$" on this page, so pass the project's currency
    'currency' => [
        'symbol'    => $currency->symbol ?? '',
        'placement' => $currency->placement ?? 'before',
        'decimal'   => $currency->decimal_separator ?? '.',
        'thousand'  => $currency->thousand_separator ?? ',',
        'precision' => (int) get_decimal_places(),
    ],
    'labels'   => [
        'revenue'           => _l('project_pnl_revenue'),
        'po_spend'          => _l('project_pnl_po_spend'),
        'other'             => _l('project_pnl_other_expenses'),
        'cumulative_profit' => _l('project_pnl_cumulative_profit'),
    ],
];
?>
<div id="project-pnl-dashboard" class="project-pnl hide">
    <div class="panel_s">
        <div class="panel-body">
            <div class="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2 tw-mb-4">
                <div>
                    <h4 class="tw-font-semibold tw-text-neutral-700 tw-text-base tw-my-0">
                        <i class="fa-solid fa-scale-balanced tw-mr-1 tw-text-neutral-500"></i>
                        <?= _l('project_pnl_heading'); ?>
                    </h4>
                    <span class="tw-text-sm tw-text-neutral-500"><?= _l('project_pnl_subheading'); ?></span>
                </div>
                <?php if ($can_link && $purchase['available']) { ?>
                <a href="<?= admin_url('project_pnl/link_expenses/' . $project->id); ?>" class="btn btn-default btn-sm">
                    <i class="fa-solid fa-link tw-mr-1"></i><?= _l('project_pnl_link_expenses'); ?>
                </a>
                <?php } ?>
            </div>

            <?php if ($unlinked['count'] > 0) { ?>
            <div class="alert alert-warning tw-mb-4">
                <i class="fa-solid fa-triangle-exclamation tw-mr-1"></i>
                <?= e(_l('project_pnl_unlinked_warning', [$unlinked['count'], app_format_money($unlinked['net'], $currency)])); ?>
                <?php if ($can_link) { ?>
                <a href="<?= admin_url('project_pnl/link_expenses/' . $project->id); ?>" class="alert-link tw-ml-1"><?= _l('project_pnl_link_now'); ?></a>
                <?php } ?>
            </div>
            <?php } ?>

            <!-- KPI cards -->
            <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-3">
                <div class="project-pnl-card project-pnl-card--revenue">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_revenue'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($revenue['net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($revenue['gross']); ?></div>
                    <div class="project-pnl-card__meta">
                        <?= _l('project_pnl_received'); ?>: <?= $money($revenue['received']); ?>
                        · <?= e(_l('project_pnl_invoices_count', (string) count($revenue['rows']))); ?>
                    </div>
                </div>

                <div class="project-pnl-card project-pnl-card--expenses">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_actual_cost'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($pnl['actual_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($pnl['actual_gross']); ?></div>
                    <div class="project-pnl-card__meta">
                        <?= _l('project_pnl_po_spend'); ?>: <?= $money($pnl['po_spend_net']); ?>
                        · <?= _l('project_pnl_other_expenses'); ?>: <?= $money($pnl['other_spend_net']); ?>
                    </div>
                </div>

                <div class="project-pnl-card project-pnl-card--purchase">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_committed'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($purchase['committed_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($purchase['committed_gross']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_committed_help'); ?></div>
                </div>

                <div class="project-pnl-card project-pnl-card--cost">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_expected_cost'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($pnl['expected_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($pnl['expected_gross']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_expected_help'); ?></div>
                </div>

                <div class="project-pnl-card <?= $isProfit ? 'project-pnl-card--profit' : 'project-pnl-card--loss'; ?>">
                    <div class="project-pnl-card__label"><?= $isProfit ? _l('project_pnl_profit_to_date') : _l('project_pnl_loss_to_date'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($pnl['profit_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($pnl['profit_gross']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_margin'); ?>: <?= $pctText($pnl['margin']); ?> · <?= _l('project_pnl_profit_to_date_help'); ?></div>
                </div>

                <?php $projectedOk = $pnl['projected_profit'] === null || $pnl['projected_profit'] >= 0; ?>
                <div class="project-pnl-card <?= $projectedOk ? 'project-pnl-card--profit' : 'project-pnl-card--loss'; ?>">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_projected_profit'); ?></div>
                    <div class="project-pnl-card__value">
                        <?= $pnl['projected_profit'] === null ? '—' : $money($pnl['projected_profit']); ?>
                    </div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_margin'); ?>: <?= $pctText($pnl['projected_margin']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_projected_help'); ?></div>
                </div>
            </div>

            <?php if (!$purchase['available']) { ?>
            <p class="tw-text-sm tw-text-neutral-500 tw-mt-3 tw-mb-0">
                <i class="fa-solid fa-circle-info"></i> <?= _l('project_pnl_purchase_module_missing'); ?>
            </p>
            <?php } else { ?>
            <?php if ($purchase['over_net'] > 0) { ?>
            <p class="tw-text-sm tw-text-danger-600 tw-mt-3 tw-mb-0">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= e(_l('project_pnl_over_note', app_format_money($purchase['over_net'], $currency))); ?>
            </p>
            <?php } ?>
            <?php if ($purchase['pending_count'] > 0) { ?>
            <p class="tw-text-sm tw-text-neutral-500 tw-mt-2 tw-mb-0">
                <i class="fa-solid fa-circle-info"></i>
                <?= e(_l('project_pnl_pending_po_note', [$purchase['pending_count'], app_format_money($purchase['pending_net'], $currency)])); ?>
            </p>
            <?php } ?>
            <?php } ?>

            <!-- Progress against project value -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-6 tw-gap-y-3 tw-mt-5">
                <?php foreach ($progressRows as [$label, $part, $whole, $color]) {
                    $pct = $percent($part, $whole); ?>
                <div>
                    <div class="tw-flex tw-justify-between tw-text-sm tw-mb-1">
                        <span class="tw-text-neutral-600"><?= e($label); ?></span>
                        <span class="tw-font-medium tw-text-neutral-700"><?= e($pct); ?>%</span>
                    </div>
                    <div class="progress progress-bar-mini tw-mb-1">
                        <!-- no-percent-text/not-dynamic: stop Perfex JS writing % text into the thin bar -->
                        <div class="progress-bar progress-bar-<?= e($color); ?> no-percent-text not-dynamic" role="progressbar"
                            data-percent="<?= e(min($pct, 100)); ?>"
                            style="width: <?= e(min($pct, 100)); ?>%"></div>
                    </div>
                    <div class="tw-text-xs tw-text-neutral-500"><?= $money($part); ?> / <?= $money($whole); ?></div>
                </div>
                <?php } ?>
            </div>

            <!-- Monthly chart -->
            <div class="tw-mt-6">
                <h5 class="project-pnl-section-title"><?= _l('project_pnl_monthly_trend'); ?></h5>
                <div class="project-pnl-chart">
                    <?php if (count($pnl['monthly']['labels']) > 0) { ?>
                    <canvas id="project-pnl-monthly-chart"></canvas>
                    <?php } else { ?>
                    <p class="project-pnl-empty"><?= _l('project_pnl_no_chart_data'); ?></p>
                    <?php } ?>
                </div>
            </div>

            <!-- Purchase order utilisation -->
            <?php if ($purchase['available']) { ?>
            <div class="tw-mt-6">
                <h5 class="project-pnl-section-title"><?= _l('project_pnl_po_utilisation'); ?></h5>
                <div class="table-responsive project-pnl-table-wrap">
                    <table class="table table-condensed project-pnl-table">
                        <thead>
                            <tr>
                                <th><?= _l('project_pnl_number'); ?></th>
                                <th><?= _l('project_pnl_vendor'); ?></th>
                                <th class="text-right"><?= _l('project_pnl_po_value'); ?></th>
                                <th style="min-width:180px;"><?= _l('project_pnl_spent_against'); ?></th>
                                <th class="text-right"><?= _l('project_pnl_remaining'); ?></th>
                                <th><?= _l('project_pnl_status'); ?></th>
                                <?php if ($can_close_po) { ?><th></th><?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($purchase['rows']) === 0) { ?>
                            <tr><td colspan="7" class="project-pnl-empty"><?= _l('project_pnl_no_data'); ?></td></tr>
                            <?php } ?>
                            <?php foreach ($purchase['rows'] as $row) {
                                $usedPct = $percent($row['spent'], $row['value']);
                                $barColor = $row['over'] > 0 ? 'danger' : ($usedPct >= 90 ? 'warning' : 'success'); ?>
                            <tr class="<?= $row['approved'] ? '' : 'project-pnl-row-muted'; ?>">
                                <td>
                                    <a href="<?= admin_url('purchase/purchase_order/' . $row['id']); ?>" target="_blank"><?= e($row['number']); ?></a>
                                    <div class="tw-text-xs tw-text-neutral-500"><?= e(_d($row['date'])); ?></div>
                                </td>
                                <td><?= e($row['vendor']); ?></td>
                                <td class="text-right"><?= $money($row['value']); ?></td>
                                <td>
                                    <div class="tw-flex tw-justify-between tw-text-xs">
                                        <span><?= $money($row['spent']); ?></span>
                                        <span><?= e($usedPct); ?>%</span>
                                    </div>
                                    <div class="progress progress-bar-mini tw-my-0.5">
                                        <div class="progress-bar progress-bar-<?= $barColor; ?> no-percent-text not-dynamic"
                                            data-percent="<?= e(min($usedPct, 100)); ?>"
                                            style="width: <?= e(min($usedPct, 100)); ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <?= $money($row['remaining']); ?>
                                    <?php if ($row['over'] > 0) { ?>
                                    <div class="tw-text-xs tw-text-danger-600"><?= e(_l('project_pnl_over_by', app_format_money($row['over'], $currency))); ?></div>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (!$row['approved']) { ?>
                                    <span class="label label-default"><?= _l('project_pnl_po_pending'); ?></span>
                                    <?php } elseif ($row['closed']) { ?>
                                    <span class="label label-info"><?= _l('project_pnl_po_closed'); ?></span>
                                    <?php } elseif ($row['over'] > 0) { ?>
                                    <span class="label label-danger"><?= _l('project_pnl_po_over'); ?></span>
                                    <?php } else { ?>
                                    <span class="label label-success"><?= _l('project_pnl_po_open'); ?></span>
                                    <?php } ?>
                                </td>
                                <?php if ($can_close_po) { ?>
                                <td class="text-right">
                                    <?php if ($row['approved']) { ?>
                                    <?= form_open(admin_url('project_pnl/toggle_po_close'), ['class' => 'tw-inline']); ?>
                                    <input type="hidden" name="pur_order_id" value="<?= (int) $row['id']; ?>">
                                    <input type="hidden" name="closed" value="<?= $row['closed'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn btn-default btn-xs"
                                        <?= $row['closed'] ? '' : 'onclick="return confirm(' . e(json_encode(_l('project_pnl_close_confirm'))) . ');"'; ?>>
                                        <?= $row['closed'] ? _l('project_pnl_reopen') : _l('project_pnl_close'); ?>
                                    </button>
                                    <?= form_close(); ?>
                                    <?php } ?>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

            <!-- Detail tables -->
            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6 tw-mt-6">
                <div>
                    <h5 class="project-pnl-section-title"><?= _l('project_pnl_sales_invoices'); ?></h5>
                    <div class="table-responsive project-pnl-table-wrap">
                        <table class="table table-condensed project-pnl-table">
                            <thead>
                                <tr>
                                    <th><?= _l('project_pnl_number'); ?></th>
                                    <th><?= _l('project_pnl_date'); ?></th>
                                    <th class="text-right"><?= _l('project_pnl_before_tax'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($revenue['rows']) === 0) { ?>
                                <tr><td colspan="3" class="project-pnl-empty"><?= _l('project_pnl_no_data'); ?></td></tr>
                                <?php } ?>
                                <?php foreach ($revenue['rows'] as $row) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= admin_url('invoices/list_invoices/' . $row['id']); ?>" target="_blank">
                                            <?= e(format_invoice_number($row['id'])); ?></a>
                                        <?= format_invoice_status($row['status'], 'tw-ml-1', true); ?>
                                    </td>
                                    <td><?= e(_d($row['date'])); ?></td>
                                    <td class="text-right"><?= $money($row['net']); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h5 class="project-pnl-section-title"><?= _l('project_pnl_expenses_by_category'); ?></h5>
                    <div class="table-responsive project-pnl-table-wrap">
                        <table class="table table-condensed project-pnl-table">
                            <thead>
                                <tr>
                                    <th><?= _l('project_pnl_category'); ?></th>
                                    <th class="text-right"><?= _l('project_pnl_before_tax'); ?></th>
                                    <th class="text-right"><?= _l('project_pnl_against_po_short'); ?></th>
                                    <th class="text-right"><?= _l('project_pnl_share'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($expenses['categories']) === 0) { ?>
                                <tr><td colspan="4" class="project-pnl-empty"><?= _l('project_pnl_no_data'); ?></td></tr>
                                <?php } ?>
                                <?php foreach ($expenses['categories'] as $category) { ?>
                                <tr>
                                    <td>
                                        <?= e($category['name']); ?>
                                        <span class="tw-text-xs tw-text-neutral-500">(<?= e($category['count']); ?>)</span>
                                    </td>
                                    <td class="text-right"><?= $money($category['net']); ?></td>
                                    <td class="text-right"><?= $money($category['linked_net']); ?></td>
                                    <td class="text-right"><?= e($percent($category['net'], $expenses['net'])); ?>%</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var project_pnl_data = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
