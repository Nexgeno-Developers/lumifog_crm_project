<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">KPI Management</h4>
                        <hr class="hr-panel-heading" />

                        <div class="row mbot15">
                            <div class="col-md-3">
                                <a href="<?php echo admin_url('kpi_management/rules'); ?>" class="text-decoration-none">
                                    <div class="panel panel-default tw-mb-0">
                                        <div class="panel-body">
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <div>
                                                    <div class="text-muted tw-text-sm">Total Rules</div>
                                                    <div class="tw-text-2xl tw-font-semibold"><?php echo (int) ($total_rules ?? 0); ?></div>
                                                </div>
                                                <i class="fa-regular fa-rectangle-list tw-text-3xl text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo admin_url('kpi_management/transactions'); ?>" class="text-decoration-none">
                                    <div class="panel panel-default tw-mb-0">
                                        <div class="panel-body">
                                            <div class="tw-flex tw-items-center tw-justify-between">
                                                <div>
                                                    <div class="text-muted tw-text-sm">Total Transactions</div>
                                                    <div class="tw-text-2xl tw-font-semibold"><?php echo (int) ($total_transactions ?? 0); ?></div>
                                                </div>
                                                <i class="fa-solid fa-right-left tw-text-3xl text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h5 class="tw-font-semibold tw-mb-3 tw-text-neutral-800"><?php echo date("F Y"); ?> Month Performance Ranking</h5>
                                <?php if (!empty($top_performers_current_month)) { ?>
                                    <div class="row">
                                        <?php foreach ($top_performers_current_month as $perf) { ?>
                                            <div class="col-md-3 tw-mb-3">
                                                <div class="panel panel-default tw-mb-0 tw-h-full">
                                                    <div class="panel-body">
                                                        <div class="tw-flex tw-items-start tw-justify-between tw-gap-2 tw-mb-2">
                                                            <span class="label label-<?php echo (int) $perf['rank'] === 1 ? 'warning' : ((int) $perf['rank'] === 2 ? 'default' : 'info'); ?>">
                                                                #<?php echo (int) $perf['rank']; ?>
                                                            </span>
                                                            <?php if ((int) $perf['rank'] === 1) { ?>
                                                                <i class="fa-solid fa-trophy text-warning" title="Top"></i>
                                                            <?php } ?>
                                                        </div>
                                                        <!-- <div class="text-muted tw-text-sm tw-mb-1">Staff</div> -->
                                                        <div class="tw-text-lg tw-font-medium tw-mb-3"><?php echo e($perf['staff_name']); ?></div>
                                                        <div class="text-muted tw-text-sm tw-mb-1">Points</div>
                                                        <div class="tw-text-2xl tw-font-semibold text-success"><?php echo (int) $perf['points']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="panel panel-default tw-mb-0">
                                        <div class="panel-body">
                                            <p class="text-muted tw-mb-0">No approved KPI points recorded for the current month yet.</p>
                                            <a href="<?php echo admin_url('kpi_management/reports/top_performers'); ?>" class="tw-text-sm">Open top performer report</a>
                                        </div>
                                    </div>
                                <?php } ?>

                                <?php if (!empty($staff_leaderboard_current_month)) { ?>
                                    <div class="tw-mt-4">
                                        <h6 class="tw-font-semibold tw-mb-2 tw-text-neutral-700" style="font-size: 13px;">Other staff performance</h6>
                                        <div class="kpi-dashboard-leaderboard-wrap" style1="max-height: 380px; overflow-y: auto; border: 1px solid #e4e4e4; border-radius: 3px;">
                                            <table class="table table-condensed table-bordered tw-mb-0 kpi-dashboard-leaderboard-table" style="font-size: 12px;">
                                                <thead>
                                                    <tr style="background: #f9f9f9;">
                                                        <th style="width: 44px; padding: 4px 6px;">#</th>
                                                        <th style="padding: 4px 6px;"><?php echo _l('staff'); ?></th>
                                                        <th class="text-right" style="width: 72px; padding: 4px 6px;">Points</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($staff_leaderboard_current_month as $row) { ?>
                                                        <tr>
                                                            <td style="padding: 3px 6px; color: #888;"><?php echo (int) $row['rank']; ?></td>
                                                            <td style="padding: 3px 6px;"><?php echo e($row['staff_name']); ?></td>
                                                            <td class="text-right" style="padding: 3px 6px; font-weight: 600;"><?php echo (int) $row['points']; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>

