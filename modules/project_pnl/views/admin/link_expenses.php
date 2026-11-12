<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2 tw-mb-3">
                    <h4 class="tw-my-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                        <?= _l('project_pnl_link_expenses'); ?>:
                        <a href="<?= admin_url('projects/view/' . $project->id); ?>"><?= e($project->name); ?></a>
                    </h4>
                    <a href="<?= admin_url('projects/view/' . $project->id); ?>" class="btn btn-default">
                        <i class="fa-solid fa-arrow-left tw-mr-1"></i><?= _l('project_pnl_back_to_project'); ?>
                    </a>
                </div>

                <div class="alert alert-info"><?= _l('project_pnl_link_expenses_help'); ?></div>

                <?= form_open(admin_url('project_pnl/link_expenses/' . $project->id)); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (count($expenses) === 0) { ?>
                        <p class="text-muted tw-mb-0"><?= _l('project_pnl_no_data'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?= _l('project_pnl_date'); ?></th>
                                        <th><?= _l('project_pnl_category'); ?></th>
                                        <th><?= _l('expense_name'); ?> / <?= _l('expense_add_edit_note'); ?></th>
                                        <th class="text-right"><?= _l('project_pnl_before_tax'); ?></th>
                                        <th style="min-width:260px;"><?= _l('project_pnl_against_po'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense) {
                                        $needsPo = in_array((int) $expense['category'], $required_cat, true) && empty($expense['pur_order_id']); ?>
                                    <tr class="<?= $needsPo ? 'warning' : ''; ?>">
                                        <td><?= e(_d($expense['date'])); ?></td>
                                        <td>
                                            <?= e($expense['category_name']); ?>
                                            <?php if ($needsPo) { ?>
                                            <span class="label label-warning tw-ml-1"><?= _l('project_pnl_needs_po'); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="<?= admin_url('expenses/list_expenses/' . $expense['id']); ?>" target="_blank">
                                                <?= e($expense['expense_name'] ?: '#' . $expense['id']); ?></a>
                                            <?php if (!empty($expense['note'])) { ?>
                                            <div class="tw-text-xs tw-text-neutral-500"><?= e(mb_strimwidth(strip_tags($expense['note']), 0, 120, '…')); ?></div>
                                            <?php } ?>
                                        </td>
                                        <td class="text-right"><?= e(app_format_money($expense['amount'], $currency)); ?></td>
                                        <td>
                                            <select name="links[<?= (int) $expense['id']; ?>]" class="form-control">
                                                <option value="0"><?= _l('project_pnl_not_against_po'); ?></option>
                                                <?php foreach ($po_options as $po) { ?>
                                                <option value="<?= (int) $po['id']; ?>"
                                                    <?= (int) $expense['pur_order_id'] === (int) $po['id'] ? 'selected' : ''; ?>>
                                                    <?= e($po['number'] . ($po['vendor'] ? ' — ' . $po['vendor'] : '') . ' (' . app_format_money($po['value'], $currency) . ')' . ($po['approved'] ? '' : ' · ' . _l('project_pnl_po_pending'))); ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                    <?php if (count($expenses) > 0) { ?>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                    </div>
                    <?php } ?>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
