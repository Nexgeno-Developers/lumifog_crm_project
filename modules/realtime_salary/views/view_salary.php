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
                            <h3><i class="fa-solid fa-money-bill-wave text-success" aria-hidden="true"></i> <?php echo _l('realtime_salary_heading'); ?></h3>
                            <p><?php echo _l('realtime_salary_subheading'); ?></p>
                        </div>
                        <div class="rs-controls">
                            <?php if ($can_view_others && !empty($is_admin_viewing_staff)): ?>
                                <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month); ?>" class="btn btn-default" style="font-weight: 600;">
                                    <i class="fa fa-arrow-left" aria-hidden="true"></i> <?php echo _l('realtime_salary_back_to_all_staff'); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($can_view_others && !empty($staff_list)): ?>
                                <div class="form-group no-mbot" style="min-width: 220px;">
                                    <select id="rs_staff_selector" class="selectpicker" data-width="100%" data-live-search="true">
                                        <?php foreach ($staff_list as $s): ?>
                                            <option value="<?php echo $s['staffid']; ?>" <?php echo ($s['staffid'] == $staff_id) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($s['firstname'] . ' ' . $s['lastname']) . (empty($s['active']) ? ' (Inactive)' : ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Month Navigation -->
                            <div class="rs-month-nav">
                                <?php 
                                    $prev_url = admin_url('realtime_salary?month=' . $prev_month . ($staff_id != get_staff_user_id() ? '&staff_id=' . $staff_id : '&view=my_salary'));
                                    $next_url = $can_go_next ? admin_url('realtime_salary?month=' . $next_month . ($staff_id != get_staff_user_id() ? '&staff_id=' . $staff_id : '&view=my_salary')) : '#';
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

                    <?php if ($can_view_others): ?>
                        <!-- Admin Navigation Tabs -->
                        <div class="horizontal-scrollable-tabs" style="margin-bottom: 20px;">
                            <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                                <li role="presentation" class="<?php echo empty($is_admin_viewing_staff) && $active_tab == 'all_staff' ? 'active' : ''; ?>">
                                    <a href="<?php echo admin_url('realtime_salary?month=' . $selected_month); ?>">
                                        <i class="fa fa-users" aria-hidden="true"></i> <?php echo _l('realtime_salary_all_staff_tab'); ?>
                                    </a>
                                </li>
                                <li role="presentation" class="<?php echo empty($is_admin_viewing_staff) && $active_tab == 'my_salary' ? 'active' : ''; ?>">
                                    <a href="<?php echo admin_url('realtime_salary?view=my_salary&month=' . $selected_month); ?>">
                                        <i class="fa fa-user" aria-hidden="true"></i> <?php echo _l('realtime_salary_my_salary_tab'); ?>
                                    </a>
                                </li>
                                <?php if (!empty($is_admin_viewing_staff)): ?>
                                    <li role="presentation" class="active">
                                        <a href="#">
                                            <i class="fa fa-user-circle-o" aria-hidden="true"></i> <?php echo html_escape(!empty($salary_data['staff']) ? $salary_data['staff']->firstname . ' ' . $salary_data['staff']->lastname : 'Staff #' . $staff_id); ?> (Month Breakdown)
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!$salary_data['has_contract']): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <?php echo $salary_data['error']; ?>
                        </div>
                    <?php else: ?>

                        <!-- Active Contract Info Strip -->
                        <div class="panel_s" style="margin-bottom: 20px;">
                            <div class="panel-body" style="padding: 12px 20px;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <span class="text-muted"><?php echo _l('realtime_salary_contract_code'); ?>:</span>
                                        <strong><?php echo html_escape($salary_data['contract']->contract_code); ?></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted"><?php echo _l('realtime_salary_contract_validity'); ?>:</span>
                                        <strong><?php echo _d($salary_data['contract']->start_valid); ?> &rarr; <?php echo $salary_data['contract']->end_valid ? _d($salary_data['contract']->end_valid) : 'Ongoing'; ?></strong>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="label label-success"><?php echo ucfirst(html_escape($salary_data['contract']->contract_status)); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Stat Cards -->
                        <div class="rs-stat-grid">
                            <!-- Accrued Realtime Salary -->
                            <div class="rs-stat-card primary-accent">
                                <div class="rs-stat-label"><?php echo _l('realtime_salary_accrued_salary'); ?></div>
                                <div class="rs-stat-val earned-highlight">
                                    <?php echo app_format_money($salary_data['realtime_earned'], get_base_currency()); ?>
                                </div>
                                <div class="rs-stat-sub">
                                    <i class="fa fa-clock-o text-success" aria-hidden="true"></i> <?php echo _l('realtime_salary_days_worked'); ?>: <strong><?php echo $salary_data['paid_days']; ?></strong> / <?php echo $salary_data['total_working_days']; ?> days
                                </div>
                            </div>

                            <!-- Monthly Gross Salary -->
                            <div class="rs-stat-card secondary-accent">
                                <div class="rs-stat-label"><?php echo _l('realtime_salary_monthly_gross'); ?></div>
                                <div class="rs-stat-val">
                                    <?php echo app_format_money($salary_data['total_gross'], get_base_currency()); ?>
                                </div>
                                <div class="rs-stat-sub">
                                    Basic: <?php echo app_format_money($salary_data['salary_breakdown']['basic_salary'], get_base_currency()); ?> | Allowances: <?php echo app_format_money($salary_data['salary_breakdown']['allowances_total'], get_base_currency()); ?>
                                </div>
                            </div>

                            <!-- Per Day Rate -->
                            <div class="rs-stat-card warning-accent">
                                <div class="rs-stat-label"><?php echo _l('realtime_salary_per_day_rate'); ?></div>
                                <div class="rs-stat-val">
                                    <?php echo app_format_money($salary_data['per_day_salary'], get_base_currency()); ?>
                                </div>
                                <div class="rs-stat-sub">
                                    Gross &divide; <?php echo $salary_data['total_working_days']; ?> Scheduled Shift Days
                                </div>
                            </div>

                            <!-- Working Days Count -->
                            <div class="rs-stat-card info-accent">
                                <div class="rs-stat-label"><?php echo _l('realtime_salary_working_days'); ?></div>
                                <div class="rs-stat-val">
                                    <?php echo $salary_data['total_working_days']; ?> <span style="font-size: 16px; font-weight: normal; color: #64748b;">Days</span>
                                </div>
                                <div class="rs-stat-sub">
                                    Excludes <?php echo $salary_data['ns_count']; ?> NS (Off / Holiday) days
                                </div>
                            </div>
                        </div>

                        <!-- Summary Counter Pills -->
                        <div class="rs-counter-pills">
                            <span class="rs-pill-item present" data-toggle="tooltip" title="Full pay credited">
                                <i class="fa fa-check-circle" aria-hidden="true"></i> <?php echo _l('realtime_salary_present_days'); ?>: <strong><?php echo $salary_data['present_count']; ?></strong>
                            </span>
                            <span class="rs-pill-item leave" data-toggle="tooltip" title="Full pay credited">
                                <i class="fa fa-calendar-check-o" aria-hidden="true"></i> <?php echo _l('realtime_salary_approved_leaves'); ?>: <strong><?php echo $salary_data['leave_count']; ?></strong>
                            </span>
                            <span class="rs-pill-item absent" data-toggle="tooltip" title="No pay credited">
                                <i class="fa fa-times-circle" aria-hidden="true"></i> <?php echo _l('realtime_salary_absent_days'); ?>: <strong><?php echo $salary_data['absent_count']; ?></strong>
                            </span>
                            <span class="rs-pill-item ns" data-toggle="tooltip" title="Non-working day, not deducted">
                                <i class="fa fa-coffee" aria-hidden="true"></i> <?php echo _l('realtime_salary_off_days'); ?>: <strong><?php echo $salary_data['ns_count']; ?></strong>
                            </span>
                            <?php if ($salary_data['upcoming_count'] > 0): ?>
                                <span class="rs-pill-item upcoming" data-toggle="tooltip" title="Scheduled remaining shift days">
                                    <i class="fa fa-hourglass-start" aria-hidden="true"></i> <?php echo _l('realtime_salary_upcoming_days'); ?>: <strong><?php echo $salary_data['upcoming_count']; ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Calendar Month Grid View -->
                        <div class="rs-month-grid-container">
                            <div class="rs-grid-header">
                                <h4><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo _l('realtime_salary_month'); ?> Calendar View (<?php echo $salary_data['month_label']; ?>)</h4>
                                <span class="text-muted" style="font-size: 13px;">Daily shift, attendance & pay status</span>
                            </div>

                            <div class="rs-calendar-grid">
                                <div class="rs-grid-day-header">Mon</div>
                                <div class="rs-grid-day-header">Tue</div>
                                <div class="rs-grid-day-header">Wed</div>
                                <div class="rs-grid-day-header">Thu</div>
                                <div class="rs-grid-day-header">Fri</div>
                                <div class="rs-grid-day-header">Sat</div>
                                <div class="rs-grid-day-header">Sun</div>

                                <?php
                                    // Align first day of month to proper day of week (Monday = 1, Sunday = 7)
                                    $first_day_of_month = sprintf('%s-01', $selected_month);
                                    $first_day_weekday = (int) date('N', strtotime($first_day_of_month));
                                    
                                    // Fill empty slots before day 1
                                    for ($i = 1; $i < $first_day_weekday; $i++) {
                                        echo '<div class="rs-day-cell" style="visibility: hidden;"></div>';
                                    }

                                    foreach ($salary_data['days'] as $day):
                                        $badge_class = 'badge-default';
                                        if ($day['status'] == 'present') $badge_class = 'badge-present';
                                        elseif ($day['status'] == 'leave') $badge_class = 'badge-leave';
                                        elseif ($day['status'] == 'absent') $badge_class = 'badge-absent';
                                        elseif ($day['status'] == 'ns') $badge_class = 'badge-ns';
                                        elseif ($day['status'] == 'upcoming') $badge_class = 'badge-upcoming';
                                ?>
                                    <div class="rs-day-cell status-<?php echo $day['status']; ?> <?php echo $day['is_today'] ? 'is-today' : ''; ?>" data-toggle="tooltip" title="<?php echo html_escape($day['detail_note']); ?>">
                                        <div class="rs-cell-top">
                                            <span class="rs-cell-day-num"><?php echo $day['day_num']; ?></span>
                                            <span class="rs-cell-badge <?php echo $badge_class; ?>">
                                                <?php echo html_escape($day['status_text']); ?>
                                            </span>
                                        </div>
                                        <div class="rs-cell-bottom">
                                            <?php if ($day['status'] == 'present' || $day['status'] == 'leave'): ?>
                                                <div class="rs-cell-pay">+<?php echo app_format_money($day['earned_today'], get_base_currency()); ?></div>
                                            <?php elseif ($day['status'] == 'absent'): ?>
                                                <div class="text-danger" style="font-weight: 600;">₹0.00</div>
                                            <?php else: ?>
                                                <div class="text-muted" style="font-size: 10px;">-</div>
                                            <?php endif; ?>
                                            <div class="rs-cell-note"><?php echo html_escape($day['detail_note']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Daily Breakdown Table -->
                        <div class="rs-table-card">
                            <div class="card-header">
                                <h4><i class="fa fa-list-ul" aria-hidden="true"></i> <?php echo _l('realtime_salary_daily_breakdown'); ?></h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table rs-table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('realtime_salary_date'); ?></th>
                                            <th><?php echo _l('realtime_salary_day'); ?></th>
                                            <th><?php echo _l('realtime_salary_shift'); ?></th>
                                            <th><?php echo _l('realtime_salary_status'); ?></th>
                                            <th><?php echo _l('realtime_salary_check_in'); ?></th>
                                            <th><?php echo _l('realtime_salary_leave_info'); ?></th>
                                            <th class="text-right"><?php echo _l('realtime_salary_earned_day'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salary_data['days'] as $day): ?>
                                            <tr class="row-<?php echo $day['status']; ?>">
                                                <td>
                                                    <strong><?php echo _d($day['date']); ?></strong>
                                                    <?php if ($day['is_today']): ?>
                                                        <span class="label label-info" style="margin-left: 5px;">Today</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $day['day_name']; ?></td>
                                                <td>
                                                    <?php if ($day['has_shift']): ?>
                                                        <span class="label label-default"><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Shift Scheduled</span>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fa fa-bed" aria-hidden="true"></i> NS (No Shift)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="label <?php echo $day['badge_class']; ?>">
                                                        <?php echo html_escape($day['status_text']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo !empty($day['check_in_time']) ? '<span class="text-success font-weight-bold"><i class="fa fa-sign-in" aria-hidden="true"></i> ' . $day['check_in_time'] . '</span>' : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php echo !empty($day['leave_reason']) ? '<span class="text-primary"><i class="fa fa-file-text-o" aria-hidden="true"></i> ' . html_escape($day['leave_reason']) . '</span>' : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($day['earned_today'] > 0): ?>
                                                        <strong class="text-success">+<?php echo app_format_money($day['earned_today'], get_base_currency()); ?></strong>
                                                    <?php elseif ($day['status'] == 'absent'): ?>
                                                        <span class="text-danger">₹0.00</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0;">
                                            <td colspan="6" class="text-right"><?php echo _l('realtime_salary_accrued_salary'); ?>:</td>
                                            <td class="text-right text-success" style="font-size: 16px;">
                                                <?php echo app_format_money($salary_data['realtime_earned'], get_base_currency()); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Real-time Information Notice -->
                        <div class="rs-info-box">
                            <i class="fa fa-info-circle fa-lg text-success" aria-hidden="true"></i>
                            <div><?php echo _l('realtime_salary_summary_note'); ?></div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo module_dir_url('realtime_salary', 'assets/js/realtime_salary.js') . '?v=' . time(); ?>"></script>

<?php init_tail(); ?>
</body>
</html>
