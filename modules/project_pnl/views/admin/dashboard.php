<?php defined('BASEPATH') or exit('No direct script access allowed');

$currency = $pnl['currency'];
$money    = function ($amount) use ($currency) {
    return e(app_format_money($amount, $currency));
};
$percent = function ($part, $whole) {
    return $whole > 0 ? min(round(($part / $whole) * 100, 1), 999) : 0;
};

$isProfit     = $pnl['profit_net'] >= 0;
$projectValue = $pnl['project_value'];
$revenue      = $pnl['revenue'];
$purchase     = $pnl['purchase'];
$expenses     = $pnl['expenses'];

$progressRows = [];
if ($projectValue > 0) {
    $progressRows[] = [_l('project_pnl_invoiced_of_value'), $revenue['net'], $projectValue, 'success'];
    $progressRows[] = [_l('project_pnl_cost_of_value'), $pnl['cost_net'], $projectValue, 'warning'];
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
        'purchase'          => _l('project_pnl_purchase_cost'),
        'expenses'          => _l('project_pnl_expenses'),
        'cumulative_profit' => _l('project_pnl_cumulative_profit'),
    ],
];
?>
<div id="project-pnl-dashboard" class="project-pnl hide">
    <div class="panel_s">
        <div class="panel-body">
            <div class="tw-flex tw-flex-wrap tw-items-baseline tw-justify-between tw-gap-2 tw-mb-4">
                <h4 class="tw-font-semibold tw-text-neutral-700 tw-text-base tw-my-0">
                    <i class="fa-solid fa-scale-balanced tw-mr-1 tw-text-neutral-500"></i>
                    <?= _l('project_pnl_heading'); ?>
                </h4>
                <span class="tw-text-sm tw-text-neutral-500"><?= _l('project_pnl_subheading'); ?></span>
            </div>

            <!-- KPI cards -->
            <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-3">
                <div class="project-pnl-card project-pnl-card--revenue">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_revenue'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($revenue['net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($revenue['gross']); ?></div>
                    <div class="project-pnl-card__meta">
                        <?= _l('project_pnl_received'); ?>: <?= $money($revenue['received']); ?>
                        · <?= e(_l('project_pnl_invoices_count', (string) count($revenue['rows']))); ?>
                    </div>
                </div>

                <div class="project-pnl-card project-pnl-card--purchase">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_purchase_cost'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($purchase['net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($purchase['gross']); ?></div>
                    <div class="project-pnl-card__meta"><?= e(_l('project_pnl_po_count', (string) count($purchase['rows']))); ?></div>
                </div>

                <div class="project-pnl-card project-pnl-card--expenses">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_expenses'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($expenses['net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($expenses['gross']); ?></div>
                    <div class="project-pnl-card__meta"><?= e(_l('project_pnl_expense_count', (string) count($expenses['rows']))); ?></div>
                </div>

                <div class="project-pnl-card project-pnl-card--cost">
                    <div class="project-pnl-card__label"><?= _l('project_pnl_total_cost'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($pnl['cost_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($pnl['cost_gross']); ?></div>
                </div>

                <div class="project-pnl-card <?= $isProfit ? 'project-pnl-card--profit' : 'project-pnl-card--loss'; ?>">
                    <div class="project-pnl-card__label"><?= $isProfit ? _l('project_pnl_net_profit') : _l('project_pnl_net_loss'); ?></div>
                    <div class="project-pnl-card__value"><?= $money($pnl['profit_net']); ?></div>
                    <div class="project-pnl-card__meta"><?= _l('project_pnl_with_tax'); ?>: <?= $money($pnl['profit_gross']); ?></div>
                    <div class="project-pnl-card__meta">
                        <?= _l('project_pnl_margin'); ?>:
                        <?= $pnl['margin'] === null ? '—' : e(app_format_number($pnl['margin'], true)) . '%'; ?>
                    </div>
                </div>
            </div>

            <?php if (!$purchase['available']) { ?>
            <p class="tw-text-sm tw-text-warning-600 tw-mt-3 tw-mb-0">
                <i class="fa-solid fa-circle-info"></i> <?= _l('project_pnl_purchase_module_missing'); ?>
            </p>
            <?php } elseif ($purchase['pending_count'] > 0) { ?>
            <p class="tw-text-sm tw-text-neutral-500 tw-mt-3 tw-mb-0">
                <i class="fa-solid fa-circle-info"></i>
                <?= e(_l('project_pnl_pending_po_note', [$purchase['pending_count'], app_format_money($purchase['pending_net'], $currency)])); ?>
            </p>
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

            <!-- Charts -->
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

            <!-- Detail tables -->
            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6 tw-mt-6">
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
                    <h5 class="project-pnl-section-title"><?= _l('project_pnl_purchase_orders'); ?></h5>
                    <div class="table-responsive project-pnl-table-wrap">
                        <table class="table table-condensed project-pnl-table">
                            <thead>
                                <tr>
                                    <th><?= _l('project_pnl_number'); ?></th>
                                    <th><?= _l('project_pnl_vendor'); ?></th>
                                    <th class="text-right"><?= _l('project_pnl_before_tax'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($purchase['rows']) === 0) { ?>
                                <tr><td colspan="3" class="project-pnl-empty"><?= _l('project_pnl_no_data'); ?></td></tr>
                                <?php } ?>
                                <?php foreach ($purchase['rows'] as $row) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= admin_url('purchase/purchase_order/' . $row['id']); ?>" target="_blank">
                                            <?= e($row['number']); ?></a>
                                        <div class="tw-text-xs tw-text-neutral-500"><?= e(_d($row['date'])); ?></div>
                                    </td>
                                    <td><?= e($row['vendor']); ?></td>
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
                                    <th class="text-right"><?= _l('project_pnl_share'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($expenses['categories']) === 0) { ?>
                                <tr><td colspan="3" class="project-pnl-empty"><?= _l('project_pnl_no_data'); ?></td></tr>
                                <?php } ?>
                                <?php foreach ($expenses['categories'] as $category) { ?>
                                <tr>
                                    <td>
                                        <?= e($category['name']); ?>
                                        <span class="tw-text-xs tw-text-neutral-500">(<?= e($category['count']); ?>)</span>
                                    </td>
                                    <td class="text-right"><?= $money($category['net']); ?></td>
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
