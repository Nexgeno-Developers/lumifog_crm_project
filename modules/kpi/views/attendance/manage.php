<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin"><?php echo $title; ?></h4>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                       
                        <div class="row mbot15">
                            <?php if ($is_admin) { ?>
                           
                            <div class="col-md-2">
                                <label for="staff_filter" class="control-label">Staff</label>
                                <select id="staff_filter" class="form-control selectpicker" data-live-search="true" title="Search staff...">
                                    <option value="">All Staff</option>
                                    <?php foreach ($staff_members as $staff) { ?>
                                    <option value="<?php echo $staff['staffid']; ?>">
                                        <?php echo $staff['firstname'] . ' ' . $staff['lastname']; ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php } ?>
                            
                            <div class="col-md-2">
                                <label for="view_filter" class="control-label">View Type</label>
                                <select id="view_filter" class="form-control">
                                    <option value="daily">Daily View</option>
                                    <option value="monthly">Monthly View</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="month_filter" class="control-label">Month</label>
                                <select id="month_filter" class="form-control">
                                    <option value="">Select Month</option>
                                    <?php foreach ($months as $month) { ?>
                                    <option value="<?php echo $month['id']; ?>">
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <?php if ($is_admin) { ?>
                            <!-- ADMIN-ONLY EXTRA FILTERS -->
                            <div class="col-md-2">
                                <label for="from_date_filter" class="control-label">From Date</label>
                                <input type="date" id="from_date_filter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="to_date_filter" class="control-label">To Date</label>
                                <input type="date" id="to_date_filter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="status_filter" class="control-label">Status</label>
                                <select id="status_filter" class="form-control">
                                    <option value="all">All Status</option>
                                    <option value="present">Present Only</option>
                                    <option value="absent">Absent Only</option>
                                    <option value="green">On Time</option>
                                    <option value="yellow">Slightly Late</option>
                                    <option value="red">Late</option>
                                </select>
                            </div>
                            <?php } else { ?>
                           
                            <div class="col-md-4">
                                <label class="control-label">Quick Actions</label>
                                <div class="btn-group btn-block">
                                    <button type="button" class="btn btn-primary btn-sm quick-date active" data-days="0">Today</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-days="7">Last 7 Days</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-month="current">This Month</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                               
                            </div>
                            <?php } ?>
                        </div>

                        <?php if ($is_admin) { ?>
                       
                        <div class="row mbot10">
                            <div class="col-md-10">
                                <div class="btn-group" id="quick-date-buttons">
                                    <button type="button" class="btn btn-primary btn-sm quick-date active" data-days="0">Today</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-days="7">Last 7 Days</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-days="30">Last 30 Days</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-month="current">This Month</button>
                                    <button type="button" class="btn btn-default btn-sm quick-date" data-month="previous">Previous Month</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="clearFilters" class="btn btn-default btn-block">
                                    <i class="fa fa-times"></i> Clear Filters
                                </button>
                            </div>
                        </div>
                        <?php } ?>

                        <!-- DataTable -->
                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                $table_data = [
                                    _l('Staff name'),
                                    _l('Date'),
                                    _l('Check in'),
                                    _l('Check out'), // Changed from Shift start
                                    _l('Total hours'), // Changed from Points
                                ];
                                
                                render_datatable($table_data, 'attendance-kpi', [], [
                                    'id' => 'table-kpi',
                                    'data-last-order-identifier' => 'attendance-kpi',
                                    'data-default-order' => get_table_last_order('attendance-kpi'),
                                ]);
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<style>
.dataTables_filter {
    display: none !important;
}
</style>


<script>
(function($) {
    "use strict";

    var table_kpi = $('table#table-kpi');
    var is_admin = <?php echo $is_admin ? 'true' : 'false'; ?>;
    
    if (table_kpi.length) {
        // ✅ FUNCTION TO UPDATE VIEW FILTER BASED ON SELECTIONS
        // ✅ FUNCTION TO UPDATE VIEW FILTER BASED ON SELECTIONS
function updateViewFilter() {
    var staff_id = is_admin ? ($('#staff_filter').val() || '') : '<?php echo get_staff_user_id(); ?>';
    
    // If all staff selected, force monthly view and disable view selector
    if (is_admin && !staff_id) {
        $('#view_filter').val('monthly').prop('disabled', true);
        $('label[for="view_filter"]').addClass('text-muted');
    } 
    // If specific staff selected, allow both daily and monthly views
    else if (staff_id) {
        $('#view_filter').prop('disabled', false);
        $('label[for="view_filter"]').removeClass('text-muted');
        // If no view is selected yet, default to daily
        if (!$('#view_filter').val()) {
            $('#view_filter').val('daily');
        }
    }
}
        // ✅ FUNCTION TO UPDATE ACTIVE BUTTON STATE
        function updateActiveButton(activeButton) {
            if (is_admin) {
                $('#quick-date-buttons .quick-date').removeClass('active btn-primary').addClass('btn-default');
                $(activeButton).removeClass('btn-default').addClass('active btn-primary');
            } else {
                $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
                $(activeButton).removeClass('btn-default').addClass('active btn-primary');
            }
        }

        // ✅ FORCE SET TODAY'S DATE IN INPUT FIELDS
        function setTodaysDate() {
            var today = new Date().toISOString().split('T')[0];
            if (is_admin) {
                $('#from_date_filter').val(today);
                $('#to_date_filter').val(today);
            }
        }

        // ✅ INITIALIZE BOOTSTRAP SELECT PICKER
        function initializeSelectPicker() {
            if (is_admin && $('#staff_filter').length) {
                $('#staff_filter').selectpicker('refresh');
            }
        }

        // ✅ FUNCTION TO LOAD DATA WITH PERMISSION-BASED FILTERS
        function loadData(view, month, staff_id, from_date, to_date, status_filter) {
            if (typeof _table_api !== 'undefined') {
                _table_api.destroy();
            }
            
            // Auto-limit staff_id for non-admin users
            if (!is_admin) {
                staff_id = '<?php echo get_staff_user_id(); ?>';
            }
            
            // Auto-update view based on selections before loading data
            updateViewFilter();
            view = $('#view_filter').val(); // Get the updated view
            
            var url = admin_url + 'kpi/table';
            var params = [];
            
            if (view) params.push('view=' + view);
            if (month) params.push('month=' + month);
            if (staff_id) params.push('staff_id=' + staff_id);
            if (from_date) params.push('from_date=' + from_date);
            if (to_date) params.push('to_date=' + to_date);
            if (status_filter) params.push('status_filter=' + status_filter);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            console.log('Loading data with URL:', url);
            
            _table_api = initDataTable(table_kpi, url, undefined, undefined, undefined, [1, 'desc']);
        }

        // ✅ CLEAR FILTERS FUNCTION - RESET TO TODAY'S DATA
        function clearFilters() {
            if (is_admin) {
                if ($('#staff_filter').length) {
                    $('#staff_filter').selectpicker('val', '');
                }
                $('#month_filter').val('');
                $('#status_filter').val('all');
                setTodaysDate();
                updateActiveButton($('.quick-date[data-days="0"]'));
            } else {
                $('#month_filter').val('');
                updateActiveButton($('.quick-date[data-days="0"]'));
            }
            
            // Update view filter and reload data
            updateViewFilter();
            var today = new Date().toISOString().split('T')[0];
            loadData(
                $('#view_filter').val(),
                '', 
                is_admin ? '' : '<?php echo get_staff_user_id(); ?>', 
                is_admin ? today : '', 
                is_admin ? today : '', 
                is_admin ? 'all' : ''
            );
        }

        // ✅ QUICK DATE RANGE FUNCTION - WITH ACTIVE BUTTON
        function setQuickDateRange(days, month, buttonElement) {
            var from_date, to_date;
            var today = new Date().toISOString().split('T')[0];
            
            // Update active button
            updateActiveButton(buttonElement);
            
            if (days === 0) {
                // TODAY - Show only today's data (allows daily view for specific staff)
                from_date = to_date = today;
                if (is_admin) {
                    $('#from_date_filter').val(from_date);
                    $('#to_date_filter').val(to_date);
                    $('#month_filter').val(''); // Clear month filter when using date range
                }
            } else if (days) {
                // LAST X DAYS - Show last X days including today (will allow daily view for specific staff)
                to_date = today;
                from_date = new Date();
                from_date.setDate(from_date.getDate() - days);
                from_date = from_date.toISOString().split('T')[0];
                if (is_admin) {
                    $('#from_date_filter').val(from_date);
                    $('#to_date_filter').val(to_date);
                    $('#month_filter').val(''); // Clear month filter when using date range
                }
            } else if (month === 'current') {
                // Current month - show FULL month from 1st to end (will allow daily view for specific staff)
                var now = new Date();
                var currentYear = now.getFullYear();
                var currentMonth = now.getMonth();
                
                from_date = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-01';
                var lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
                to_date = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
                
                if (is_admin) {
                    $('#month_filter').val(currentYear + '-' + String(currentMonth + 1).padStart(2, '0'));
                    $('#from_date_filter').val(from_date);
                    $('#to_date_filter').val(to_date);
                }
            } else if (month === 'previous') {
                // Previous month - show FULL previous month from 1st to end (will allow daily view for specific staff)
                var now = new Date();
                var currentYear = now.getFullYear();
                var previousMonth = now.getMonth() - 1;
                
                if (previousMonth < 0) {
                    previousMonth = 11;
                    currentYear = currentYear - 1;
                }
                
                from_date = currentYear + '-' + String(previousMonth + 1).padStart(2, '0') + '-01';
                var lastDay = new Date(currentYear, previousMonth + 1, 0).getDate();
                to_date = currentYear + '-' + String(previousMonth + 1).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
                
                if (is_admin) {
                    $('#month_filter').val(currentYear + '-' + String(previousMonth + 1).padStart(2, '0'));
                    $('#from_date_filter').val(from_date);
                    $('#to_date_filter').val(to_date);
                }
            }
            
            // Update view filter based on new selections and apply filters
            updateViewFilter();
            applyKPIFilters();
        }

        // ✅ INITIALIZE WITH TODAY'S DATA
        function initializeWithToday() {
            setTodaysDate();
            updateViewFilter(); // Set initial view state
            var today = new Date().toISOString().split('T')[0];
            loadData(
                $('#view_filter').val(), // Use the auto-determined view
                '', 
                is_admin ? '' : '<?php echo get_staff_user_id(); ?>', 
                is_admin ? today : '', 
                is_admin ? today : '', 
                is_admin ? 'all' : ''
            );
        }

        // Initialize with TODAY'S data
        initializeWithToday();

        // Initialize select picker
        setTimeout(initializeSelectPicker, 100);

        // Filter function
        function applyKPIFilters() {
            loadData(
                $('#view_filter').val(), // Use current view filter value
                $('#month_filter').val() || '',
                is_admin ? ($('#staff_filter').val() || '') : '<?php echo get_staff_user_id(); ?>',
                is_admin ? ($('#from_date_filter').val() || '') : '',
                is_admin ? ($('#to_date_filter').val() || '') : '',
                is_admin ? ($('#status_filter').val() || 'all') : ''
            );
        }

        // Quick date buttons
        $('.quick-date').on('click', function() {
            var days = $(this).data('days');
            var month = $(this).data('month');
            setQuickDateRange(days, month, this);
        });

        // Clear Filters button
        $('#clearFilters').on('click', function() {
            clearFilters();
        });

        // Auto-apply when main filters change
        $('#view_filter, #month_filter, #status_filter').on('change', function() {
            applyKPIFilters();
        });

        // Apply when staff filter changes (admin only) - with view update
        if (is_admin && $('#staff_filter').length) {
            $('#staff_filter').on('changed.bs.select', function() {
                updateViewFilter();
                applyKPIFilters();
            });
        }

        // Apply when date filters change (admin only) - with view update
        if (is_admin) {
            $('#from_date_filter, #to_date_filter').on('change', function() {
                updateViewFilter();
                applyKPIFilters();
            });
        }

        // Also update view when month filter changes
        if (is_admin) {
            $('#month_filter').on('change', function() {
                if ($(this).val()) {
                    // When month is selected, set the date range to match the month
                    var month = $(this).val();
                    var start_date = month + '-01';
                    var end_date = month + '-' + new Date(month.split('-')[0], month.split('-')[1], 0).getDate();
                    
                    $('#from_date_filter').val(start_date);
                    $('#to_date_filter').val(end_date);
                    
                    updateViewFilter();
                    applyKPIFilters();
                }
            });
        }

    }

})(jQuery);
</script>