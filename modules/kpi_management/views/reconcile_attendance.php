<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">KPI Attendance Reconcile</h4>
                        <hr class="hr-panel-heading" />

                        <p class="text-muted">
                            Re-evaluated attendance KPI transactions for all active staff
                            from <strong><?php echo e($result['from']); ?></strong>
                            to <strong><?php echo e($result['to']); ?></strong>.
                        </p>

                        <div class="row mbot15">
                            <div class="col-md-4">
                                <div class="panel panel-default tw-mb-0">
                                    <div class="panel-body text-center">
                                        <div class="text-muted">Active staff</div>
                                        <div class="h2 mtop10 mbot0"><?php echo (int) $result['staff_total']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default tw-mb-0">
                                    <div class="panel-body text-center">
                                        <div class="text-muted">Staff with check-ins</div>
                                        <div class="h2 mtop10 mbot0"><?php echo (int) $result['staff_with_checkins']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default tw-mb-0">
                                    <div class="panel-body text-center">
                                        <div class="text-muted">Check-in days processed</div>
                                        <div class="h2 mtop10 mbot0 text-success"><?php echo (int) $result['days_processed']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($result['staff'])) { ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Staff ID</th>
                                            <th>Staff</th>
                                            <th class="text-right">Days processed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result['staff'] as $row) { ?>
                                            <tr>
                                                <td><?php echo (int) $row['staff_id']; ?></td>
                                                <td><?php echo e($row['staff_name']); ?></td>
                                                <td class="text-right"><?php echo (int) $row['days']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <p class="text-muted">No check-in days were found in this date range.</p>
                        <?php } ?>

                        <p class="mtop20 text-muted" style="font-size: 12px;">
                            Optional query params:
                            <code>?from=2026-07-01&amp;to=2026-07-06</code>
                            (defaults: first day of current month through today).
                        </p>

                        <a href="<?php echo admin_url('kpi_management'); ?>" class="btn btn-default mtop10">Back to KPI Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
