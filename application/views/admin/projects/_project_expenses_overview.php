<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Fetch expenses for the active project to compute tax totals safely
$CI->db->select('amount, tax, tax2');
$CI->db->where('project_id', $project->id);
$project_expenses = $CI->db->get(db_prefix() . 'expenses')->result_array();

// Preload taxes into a lookup map to prevent N+1 queries
$taxes_rows = $CI->db->get(db_prefix() . 'taxes')->result_array();
$taxes_map  = [];
foreach ($taxes_rows as $t) {
    $taxes_map[$t['id']] = (float) $t['taxrate'];
}

$basic_expenses          = 0.0;
$tax_expenses            = 0.0;
$total_expenses_with_tax = 0.0;

foreach ($project_expenses as $exp) {
    $amount = (float) $exp['amount'];
    $basic_expenses += $amount;

    $exp_tax = 0.0;
    if (!empty($exp['tax']) && isset($taxes_map[$exp['tax']])) {
        $exp_tax += ($amount / 100.0) * $taxes_map[$exp['tax']];
    }
    if (!empty($exp['tax2']) && isset($taxes_map[$exp['tax2']])) {
        $exp_tax += ($amount / 100.0) * $taxes_map[$exp['tax2']];
    }

    $tax_expenses += $exp_tax;
}

$total_expenses_with_tax = $basic_expenses + $tax_expenses;
?>

<div class="tw-grid tw-grid-cols-2 tw-gap-3 sm:tw-grid-cols-4">
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-muted">
            <?= _l('project_overview_expenses'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id], 'field' => 'amount']), $currency)); ?>
        </p>
    </div>
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-info">
            <?= _l('project_overview_expenses_billable'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'billable' => 1], 'field' => 'amount']), $currency)); ?>
        </p>
    </div>
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-success">
            <?= _l('project_overview_expenses_billed'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'invoiceid !=' => 'NULL', 'billable' => 1], 'field' => 'amount']), $currency)); ?>
        </p>
    </div>
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-danger">
            <?= _l('project_overview_expenses_unbilled'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'invoiceid IS NULL', 'billable' => 1], 'field' => 'amount']), $currency)); ?>
        </p>
    </div>
</div>

<hr class="tw-my-3 tw-border-neutral-200">

<div class="tw-grid tw-grid-cols-1 tw-gap-3 sm:tw-grid-cols-3">
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-muted">
            <?= _l('basic_expenses', 'Basic Expenses'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money($basic_expenses, $currency)); ?>
        </p>
    </div>
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-warning">
            <?= _l('tax_expenses', 'Tax Expenses'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money($tax_expenses, $currency)); ?>
        </p>
    </div>
    <div>
        <p class="tw-mb-0.5 tw-text-sm text-primary">
            <?= _l('total_expenses_with_tax', 'Total Expenses with Tax'); ?>
        </p>
        <p class="tw-font-medium tw-text-sm tw-mb-0">
            <?= e(app_format_money($total_expenses_with_tax, $currency)); ?>
        </p>
    </div>
</div>