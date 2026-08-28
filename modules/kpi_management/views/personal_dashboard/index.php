<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                            <h4 class="no-margin">Personal Dashboard</h4>
                            <div>
                                <a href="<?php echo e($prev_url); ?>" class="btn btn-default btn-sm" title="Previous month">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                                <span class="btn btn-default btn-sm disabled" style="pointer-events:none; opacity:1;">
                                    <?php echo e($period['label']); ?>
                                </span>
                                <a href="<?php echo e($next_url); ?>" class="btn btn-default btn-sm" title="Next month">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                                <?php if ($year != (int) date('Y') || $month != (int) date('n')) { ?>
                                    <a href="<?php echo e($current_url); ?>" class="btn btn-default btn-sm mleft5">This month</a>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                        <div class="row mbot15">
                            <div class="col-md-6 col-sm-6 mbot15">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <div class="text-muted">Monthly Score</div>
                                        <div class="h2 mtop10 mbot0 <?php echo (int) $summary['monthly_score'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo (int) $summary['monthly_score']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 mbot15">
                                <div class="panel panel-default">
                                    <div class="panel-body text-center">
                                        <div class="text-muted">Performance Grade</div>
                                        <div class="mtop10">
                                            <span class="label label-<?php
                                                echo $summary['performance_grade'] === 'Excellent' ? 'success'
                                                    : ($summary['performance_grade'] === 'Good' ? 'info'
                                                    : ($summary['performance_grade'] === 'Average' ? 'warning' : 'danger'));
                                            ?>" style="font-size:14px;"><?php echo e($summary['performance_grade']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <a data-toggle="collapse" href="#kpi-attendance-collapse" aria-expanded="true" aria-controls="kpi-attendance-collapse" class="display-block">
                                    <strong>Attendance</strong>
                                    <span class="pull-right"><i class="fa fa-chevron-down"></i></span>
                                </a>
                            </div>
                            <div id="kpi-attendance-collapse" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <?php if (!empty($period['is_future'])) { ?>
                                        <div class="alert alert-info mbot0">
                                            This period hasn't started yet. Attendance metrics will appear once scheduled work days begin.
                                        </div>
                                    <?php } else { ?>
                                    <div class="row mbot15">
                                        <div class="col-md-3 col-sm-6 mbot15">
                                            <div class="panel panel-default tw-mb-0">
                                                <div class="panel-body text-center">
                                                    <div class="text-muted">On-Time Days</div>
                                                    <div class="h3 text-info mtop10 mbot0"><?php echo (int) $summary['on_time_days']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mbot15">
                                            <div class="panel panel-default tw-mb-0">
                                                <div class="panel-body text-center">
                                                    <div class="text-muted">Late Days</div>
                                                    <div class="h3 text-warning mtop10 mbot0"><?php echo (int) $summary['late_days']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mbot15">
                                            <div class="panel panel-default tw-mb-0">
                                                <div class="panel-body text-center">
                                                    <div class="text-muted">Absences</div>
                                                    <div class="h3 text-danger mtop10 mbot0"><?php echo (int) $summary['absences']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mbot15">
                                            <div class="panel panel-default tw-mb-0">
                                                <div class="panel-body text-center">
                                                    <div class="text-muted">Best Streak</div>
                                                    <div class="h3 mtop10 mbot0"><?php echo (int) $summary['best_streak']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mtop10">
                                        <div class="col-md-8">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">Daily Points</div>
                                                <div class="panel-body">
                                                    <?php if (!empty($chart['labels'])) { ?>
                                                        <canvas id="kpi-personal-points-chart" height="120"></canvas>
                                                    <?php } else { ?>
                                                        <p class="text-muted mbot0">No scheduled work days in this period.</p>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">On-Time Rate</div>
                                                <div class="panel-body text-center">
                                                    <div style="max-width:220px; margin:0 auto;">
                                                        <canvas id="kpi-personal-rate-chart" height="180"></canvas>
                                                    </div>
                                                    <div class="h2 mtop10 mbot0"><?php echo (int) $summary['on_time_rate']; ?>%</div>
                                                    <div class="text-muted">
                                                        <?php echo (int) $summary['on_time_days']; ?> of <?php echo (int) $summary['attendance_day_count']; ?> attendance days
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mtop20">
                                        <div class="col-md-12">
                                            <h5 class="tw-font-semibold tw-mb-3">Monthly Attendance</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Shift Time</th>
                                                            <th>Check-in</th>
                                                            <th class="text-right">Points</th>
                                                            <th>Status</th>
                                                            <th>Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($dates)) { ?>
                                                            <?php foreach ($dates as $row) { ?>
                                                                <tr>
                                                                    <td><?php echo e($row['date_display']); ?></td>
                                                                    <td><?php echo e($row['shift_time']); ?></td>
                                                                    <td><?php echo $row['checkin_time'] !== '' ? e($row['checkin_time']) : '<span class="text-muted">—</span>'; ?></td>
                                                                    <td class="text-right <?php echo (int) $row['points'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                        <?php echo (int) $row['points']; ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="label <?php echo e($row['status_label_class']); ?>"><?php echo e($row['status']); ?></span>
                                                                    </td>
                                                                    <td><?php echo $row['remarks'] !== '' ? e($row['remarks']) : ''; ?></td>
                                                                </tr>
                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <tr>
                                                                <td colspan="6" class="text-muted">No scheduled work days in this period.</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
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
</div>
<?php init_tail(); ?>
<script>
$(function () {
    var chartLabels = <?php echo json_encode($chart['labels'] ?? []); ?>;
    var chartPoints = <?php echo json_encode($chart['points'] ?? []); ?>;
    var chartColors = <?php echo json_encode($chart['colors'] ?? []); ?>;
    var onTimeRate  = <?php echo (int) ($summary['on_time_rate'] ?? 0); ?>;

    if (typeof Chart !== 'undefined' && chartLabels.length > 0) {
        var pointsCtx = document.getElementById('kpi-personal-points-chart');
        if (pointsCtx) {
            new Chart(pointsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Points',
                        data: chartPoints,
                        backgroundColor: chartColors,
                        borderColor: chartColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    legend: { display: false },
                    scales: {
                        xAxes: [{
                            scaleLabel: { display: true, labelString: 'Day of month' }
                        }],
                        yAxes: [{
                            ticks: { beginAtZero: true, precision: 0 }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            title: function (items) {
                                return 'Day ' + (items[0] ? items[0].xLabel : '');
                            }
                        }
                    }
                }
            });
        }
    }

    if (typeof Chart !== 'undefined') {
        var rateCtx = document.getElementById('kpi-personal-rate-chart');
        if (rateCtx && !<?php echo !empty($period['is_future']) ? 'true' : 'false'; ?>) {
            var remaining = Math.max(0, 100 - onTimeRate);
            new Chart(rateCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['On time', 'Other'],
                    datasets: [{
                        data: [onTimeRate, remaining],
                        backgroundColor: ['rgba(40, 167, 69, 0.9)', 'rgba(233, 236, 239, 0.9)'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutoutPercentage: 72,
                    legend: { display: false },
                    tooltips: { enabled: false }
                }
            });
        }
    }
});
</script>
</body>
</html>
