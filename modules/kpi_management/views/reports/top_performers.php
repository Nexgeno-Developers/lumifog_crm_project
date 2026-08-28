<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                            <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                            <button type="button" class="btn btn-default" onclick="kpi_reload_top_performers();">
                                <i class="fa-solid fa-rotate tw-mr-1"></i> Refresh
                            </button>
                        </div>
                        <hr class="hr-panel-heading" />

                        <div class="row mbot15">
                            <div class="col-md-4">
                                <div class="form-group select-placeholder">
                                    <label for="filter_date_range" class="control-label">Period</label>
                                    <select id="filter_date_range" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value="today">Today</option>
                                        <option value="yesterday">Yesterday</option>
                                        <option value="this_month" selected>Current month</option>
                                        <option value="last_month">Last month</option>
                                        <option value="this_year">This year</option>
                                        <option value="last_year">Last year</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-8 hide" id="kpi_date_range_custom">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php echo render_date_input('filter_from_date', 'From'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo render_date_input('filter_to_date', 'To'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        render_datatable(
                            [
                                'Staff',
                                'Total Points',
                            ],
                            'kpi-top-performers'
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
    var kpi_top_performers_table;

    $(function () {
        if (typeof init_datepicker === 'function') {
            init_datepicker();
        }

        var fnServerParams = {
            date_range: '#filter_date_range',
            report_from: '#filter_from_date',
            report_to: '#filter_to_date'
        };

        kpi_top_performers_table = initDataTable(
            '.table-kpi-top-performers',
            admin_url + 'kpi_management/reports/top_performers_table',
            [1],
            [],
            fnServerParams,
            [1, 'desc']
        );

        function applyRangeUI(range) {
            if (range === 'custom') {
                $('#kpi_date_range_custom').removeClass('hide');
                if (typeof init_datepicker === 'function') {
                    init_datepicker();
                }
                return;
            }
            $('#kpi_date_range_custom').addClass('hide');
        }

        applyRangeUI($('#filter_date_range').val());

        $('#filter_date_range').on('changed.bs.select', function () {
            applyRangeUI($(this).val());
            kpi_reload_top_performers();
        });

        $('#filter_from_date, #filter_to_date').on('change', function () {
            kpi_reload_top_performers();
        });
    });

    function kpi_reload_top_performers() {
        if (kpi_top_performers_table) {
            kpi_top_performers_table.ajax.reload();
        }
    }
</script>
</body>
</html>

