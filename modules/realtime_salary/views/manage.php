<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="rs-container">

                    <!-- Header Card -->
                    <div class="rs-header-card">
                        <div class="rs-header-titles">
                            <h3><i class="fa-solid fa-money-bill-wave text-success" aria-hidden="true"></i> <?php echo _l('realtime_salary_admin_title'); ?></h3>
                            <p><?php echo _l('realtime_salary_admin_subtitle'); ?></p>
                        </div>
                        <div class="rs-controls">
                            <!-- Month Navigation -->
                            <div class="rs-month-nav">
                                <?php 
                                    $status_query = (!empty($staff_status) && $staff_status !== 'active') ? '&status=' . $staff_status : '';
                                    $prev_url = admin_url('realtime_salary?month=' . $prev_month . $status_query);
                                    $next_url = $can_go_next ? admin_url('realtime_salary?month=' . $next_month . $status_query) : '#';
                                ?>
                                <a href="<?php echo $prev_url; ?>" class="btn-nav" title="<?php echo _l('realtime_salary_previous_month'); ?>">
                                    <i class="fa fa-chevron-left" aria-hidden="true"></i> <?php echo _l('realtime_salary_previous_month'); ?>
                                </a>

                                <div class="rs-current-month-display">
                                    <i class="fa fa-calendar-o" aria-hidden="true"></i> <?php echo date('F Y', strtotime($selected_month . '-01')); ?>
                                </div>

                                <a href="<?php echo $next_url; ?>" class="btn-nav <?php echo !$can_go_next ? 'disabled' : ''; ?>" title="<?php echo _l('realtime_salary_next_month'); ?>" <?php echo !$can_go_next ? 'disabled aria-disabled="true"' : ''; ?>>
                                    <?php echo _l('realtime_salary_next_month'); ?> <i class="fa fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Top Navigation Tabs -->
                    <div class="horizontal-scrollable-tabs" style="margin-bottom: 20px;">
                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li role="presentation" class="active">
                                <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month . $status_query); ?>">
                                    <i class="fa fa-users" aria-hidden="true"></i> <?php echo _l('realtime_salary_all_staff_tab'); ?>
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="<?php echo admin_url('realtime_salary?view=my_salary&month=' . $selected_month); ?>">
                                    <i class="fa fa-user" aria-hidden="true"></i> <?php echo _l('realtime_salary_my_salary_tab'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- 4 Company KPI Cards -->
                    <div class="rs-stat-grid">
                        <!-- Total Accrued Realtime Payroll -->
                        <div class="rs-stat-card primary-accent">
                            <div class="rs-stat-label"><?php echo _l('realtime_salary_total_accrued_all'); ?></div>
                            <div class="rs-stat-val earned-highlight">
                                <?php echo app_format_money($overview['total_company_accrued'], get_base_currency()); ?>
                            </div>
                            <div class="rs-stat-sub">
                                <?php 
                                    $overall_pct = $overview['total_company_gross'] > 0 ? round(($overview['total_company_accrued'] / $overview['total_company_gross']) * 100, 1) : 0;
                                ?>
                                <i class="fa fa-line-chart text-success" aria-hidden="true"></i> Accrued: <strong><?php echo $overall_pct; ?>%</strong> of total monthly payroll
                            </div>
                        </div>

                        <!-- Total Monthly Gross Payroll -->
                        <div class="rs-stat-card secondary-accent">
                            <div class="rs-stat-label"><?php echo _l('realtime_salary_total_gross_payroll'); ?></div>
                            <div class="rs-stat-val">
                                <?php echo app_format_money($overview['total_company_gross'], get_base_currency()); ?>
                            </div>
                            <div class="rs-stat-sub">
                                For active staff contracts in <?php echo $overview['month_label']; ?>
                            </div>
                        </div>

                        <!-- Active Staff with Contracts -->
                        <div class="rs-stat-card info-accent">
                            <div class="rs-stat-label"><?php echo _l('realtime_salary_contracts_count'); ?></div>
                            <div class="rs-stat-val">
                                <?php echo $overview['staff_with_contracts']; ?> <span style="font-size: 16px; font-weight: normal; color: #64748b;">/ <?php echo $overview['total_staff']; ?> Staff</span>
                            </div>
                            <div class="rs-stat-sub">
                                Valid contracts active in this month
                            </div>
                        </div>

                        <!-- Company Attendance Metrics -->
                        <div class="rs-stat-card warning-accent">
                            <div class="rs-stat-label">Attended / Approved Days</div>
                            <div class="rs-stat-val">
                                <?php echo $overview['total_present'] + $overview['total_leave']; ?> <span style="font-size: 16px; font-weight: normal; color: #64748b;">Paid Days</span>
                            </div>
                            <div class="rs-stat-sub">
                                🟢 <?php echo $overview['total_present']; ?> Present &bull; 🔵 <?php echo $overview['total_leave']; ?> Leaves &bull; 🔴 <?php echo $overview['total_absent']; ?> Absents
                            </div>
                        </div>
                    </div>

                    <!-- All Staff Real-time Salary Table -->
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="row" style="margin-bottom: 15px;">
                                <div class="col-md-5">
                                    <h4 class="no-margin font-bold text-dark" style="margin-top: 5px;">
                                        <i class="fa fa-list" aria-hidden="true"></i> <?php echo _l('realtime_salary_all_staff_tab'); ?> (<?php echo $overview['month_label']; ?>)
                                    </h4>
                                </div>
                                <div class="col-md-7 text-right">
                                    <div class="btn-group" role="group" aria-label="Staff Status Filter">
                                        <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month . '&status=active'); ?>" 
                                           class="btn btn-sm <?php echo $staff_status === 'active' ? 'btn-info active' : 'btn-default'; ?>" style="font-weight: 600;">
                                            <i class="fa fa-circle text-success" style="font-size: 10px;" aria-hidden="true"></i> <?php echo _l('realtime_salary_filter_active'); ?> 
                                            <span class="badge" style="background: <?php echo $staff_status === 'active' ? '#fff' : '#10b981'; ?>; color: <?php echo $staff_status === 'active' ? '#0284c7' : '#fff'; ?>; font-size: 11px; margin-left: 4px;"><?php echo $overview['count_active']; ?></span>
                                        </a>
                                        <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month . '&status=inactive'); ?>" 
                                           class="btn btn-sm <?php echo $staff_status === 'inactive' ? 'btn-info active' : 'btn-default'; ?>" style="font-weight: 600;">
                                            <i class="fa fa-circle text-danger" style="font-size: 10px;" aria-hidden="true"></i> <?php echo _l('realtime_salary_filter_inactive'); ?> 
                                            <span class="badge" style="background: <?php echo $staff_status === 'inactive' ? '#fff' : '#ef4444'; ?>; color: <?php echo $staff_status === 'inactive' ? '#0284c7' : '#fff'; ?>; font-size: 11px; margin-left: 4px;"><?php echo $overview['count_inactive']; ?></span>
                                        </a>
                                        <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month . '&status=all'); ?>" 
                                           class="btn btn-sm <?php echo $staff_status === 'all' ? 'btn-info active' : 'btn-default'; ?>" style="font-weight: 600;">
                                            <i class="fa fa-users" aria-hidden="true"></i> <?php echo _l('realtime_salary_filter_all'); ?> 
                                            <span class="badge" style="background: <?php echo $staff_status === 'all' ? '#fff' : '#64748b'; ?>; color: <?php echo $staff_status === 'all' ? '#0284c7' : '#fff'; ?>; font-size: 11px; margin-left: 4px;"><?php echo $overview['count_total']; ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped dt-table" data-order-col="8" data-order-type="desc">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('realtime_salary_staff_member'); ?></th>
                                            <th><?php echo _l('realtime_salary_role_dept'); ?></th>
                                            <th><?php echo _l('realtime_salary_contract'); ?></th>
                                            <th class="text-right"><?php echo _l('realtime_salary_monthly_gross'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_working_days'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_present_days'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_approved_leaves'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_absent_days'); ?></th>
                                            <th class="text-right"><?php echo _l('realtime_salary_accrued_to_date'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_progress'); ?></th>
                                            <th class="text-center"><?php echo _l('realtime_salary_action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($overview['records'] as $r): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('realtime_salary?staff_id=' . $r['staff_id'] . '&month=' . $selected_month); ?>" style="font-weight: 700; color: #0284c7;">
                                                        <?php echo html_escape($r['staff_name']); ?>
                                                    </a>
                                                    <?php if ($r['is_active']): ?>
                                                        <span class="label label-success" style="font-size: 10px; margin-left: 4px;" title="Active Employee"><?php echo _l('realtime_salary_status_active'); ?></span>
                                                    <?php else: ?>
                                                        <span class="label label-danger" style="font-size: 10px; margin-left: 4px;" title="Inactive Employee"><?php echo _l('realtime_salary_status_inactive'); ?></span>
                                                    <?php endif; ?>
                                                    <div class="text-muted" style="font-size: 11px;"><?php echo html_escape($r['email']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="label label-default"><?php echo html_escape($r['role_name']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($r['has_contract']): ?>
                                                        <span class="label label-success"><?php echo html_escape($r['contract_code']); ?></span>
                                                    <?php else: ?>
                                                        <span class="label label-danger">No Active Contract</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($r['has_contract']): ?>
                                                        <strong><?php echo app_format_money($r['total_gross'], get_base_currency()); ?></strong>
                                                        <div class="text-muted" style="font-size: 11px;"><?php echo app_format_money($r['per_day_salary'], get_base_currency()); ?>/day</div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $r['has_contract'] ? $r['working_days'] : '-'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($r['has_contract']): ?>
                                                        <span class="badge" style="background-color: #10b981;"><?php echo $r['present_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($r['has_contract']): ?>
                                                        <span class="badge" style="background-color: #3b82f6;"><?php echo $r['leave_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($r['has_contract']): ?>
                                                        <span class="badge" style="background-color: #ef4444;"><?php echo $r['absent_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($r['has_contract']): ?>
                                                        <span style="font-size: 14px; font-weight: 700; color: #059669;">
                                                            <?php echo app_format_money($r['realtime_earned'], get_base_currency()); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">₹0.00</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center" style="vertical-align: middle; min-width: 100px;">
                                                    <?php if ($r['has_contract']): ?>
                                                        <div class="progress" style="margin-bottom: 0; height: 16px; background-color: #e2e8f0;" data-toggle="tooltip" title="<?php echo $r['progress_percent']; ?>% accrued">
                                                            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $r['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo min(100, $r['progress_percent']); ?>%; font-size: 10px; line-height: 16px;">
                                                                <?php echo $r['progress_percent']; ?>%
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="<?php echo admin_url('realtime_salary?staff_id=' . $r['staff_id'] . '&month=' . $selected_month); ?>" class="btn btn-default btn-xs" title="<?php echo _l('realtime_salary_view_breakdown'); ?>">
                                                        <i class="fa fa-eye" aria-hidden="true"></i> <?php echo _l('realtime_salary_view_breakdown'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- Information Notice -->
                    <div class="rs-info-box">
                        <i class="fa fa-info-circle fa-lg text-success" aria-hidden="true"></i>
                        <div><?php echo _l('realtime_salary_summary_note'); ?></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo module_dir_url('realtime_salary', 'assets/js/realtime_salary.js') . '?v=' . time(); ?>"></script>

<?php init_tail(); ?>
</body>
</html>
