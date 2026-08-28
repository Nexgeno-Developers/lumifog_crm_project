<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ALL db calls wrapped - never let widget crash the dashboard
$status = ['checked_in' => false, 'time' => '--'];
$can_view_all = false;
$stats = ['total_active' => 0, 'present' => 0, 'on_leave' => 0, 'absent' => 0];

try {
    $CI =& get_instance();
    $CI->load->model('kpi_management/kpi_widget_model');

    $old_debug = $CI->db->db_debug;
    $CI->db->db_debug = false;

    try { $status = $CI->kpi_widget_model->get_today_checkin_status(); } catch (Throwable $e) { log_message('error', 'KPI Management widget checkin: ' . $e->getMessage()); }

    if (kpi_widget_user_can_view_global()) {
        $can_view_all = true;
        try { $stats = $CI->kpi_widget_model->get_staff_statistics(); } catch (Throwable $e) { log_message('error', 'KPI Management widget stats: ' . $e->getMessage()); }
    }

    $CI->db->db_debug = $old_debug;

} catch (Throwable $e) {
    log_message('error', 'KPI Management widget error: ' . $e->getMessage());
}
?>

<div class="widget" id="widget-kpi_widget" data-name="KPI Widget">
    <div class="panel_s user-data">
        <div class="panel-body">
            <div class="widget-dragger"></div>
            <div class="kpi-widget-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <h4 class="no-margin font-bold" style="flex:1;">
                    <i class="fa fa-line-chart"></i>
                    KPI – Today Check-in Status
                </h4>
                <a href="<?= admin_url('kpi_management/personal_dashboard'); ?>" class="btn btn-default btn-sm" title="Open Personal Dashboard">
                    <i class="fa fa-tachometer"></i> Dashboard
                </a>
            </div>
            <hr>
            <div class="text-center">
                <?php if (!empty($status['checked_in'])) { ?>
                    <h2 style="color: green;">✔ Checked in at <?= htmlspecialchars($status['time']); ?></h2>
                    <button class="btn btn-warning btn-sm hide-kpi-widget">Dismiss</button>
                <?php } else { ?>
                    <h2 style="color: red;">✖ Not checked in today</h2>
                <?php } ?>
            </div>

			<?php if ($can_view_all) { ?>
                <hr>
                <h5 class="text-center" style="margin-bottom: 15px;"><strong>Staff Attendance Statistics</strong></h5>
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="active" onclick="showKpiStaffListModal('active'); return false;" style="cursor: pointer;" title="Click to view staff list">
                            <h3 class="text-primary" style="font-size:32px;margin:10px 0;"><?= (int)$stats['total_active']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Active Staff</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="present" onclick="showKpiStaffListModal('present'); return false;" style="cursor: pointer;" title="Click to view staff list">
                            <h3 class="text-success" style="font-size:32px;margin:10px 0;"><?= (int)$stats['present']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Present</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="on_leave" onclick="showKpiStaffListModal('on_leave'); return false;" style="cursor: pointer;" title="Click to view staff list">
                            <h3 class="text-warning" style="font-size:32px;margin:10px 0;"><?= (int)$stats['on_leave']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>On Leave</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="absent" onclick="showKpiStaffListModal('absent'); return false;" style="cursor: pointer;" title="Click to view staff list">
                            <h3 class="text-danger" style="font-size:32px;margin:10px 0;"><?= (int)$stats['absent']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Absent</strong></p>
                        </div>
					</div>
				</div>
                <hr>
                <p class="text-center text-muted" style="margin:0;font-size:12px;">
                    <i class="fa fa-calendar"></i> <?= date('F d, Y'); ?>
                </p>
            <?php } ?>
            
			<?php if ($can_view_all) { ?>
            <!-- Modal markup only; rendering logic lives in cache-busted assets/js/kpi_widget.js -->
            <div class="modal fade" id="kpi_staff_list_modal" tabindex="-1" role="dialog" aria-labelledby="kpi_modal_title">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title" id="kpi_modal_title">Staff List</h4>
                        </div>
                        <div class="modal-body">
                            <div id="kpi_staff_list_loading" class="text-center" style="padding: 20px;">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p>Loading staff list...</p>
                            </div>
                            <div id="kpi_staff_list_content" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr id="kpi_staff_list_thead">
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kpi_staff_list_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div id="kpi_staff_list_empty" style="display: none; text-align: center; padding: 20px;">
                                <p class="text-muted">No staff found in this category.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>