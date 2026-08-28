<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                    <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                    <button type="button" class="btn btn-default" onclick="kpi_reload_transactions_table();">
                        <i class="fa-solid fa-rotate tw-mr-1"></i> Refresh
                    </button>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row mbot15">
                            <div class="col-md-4">
                                <div class="form-group select-placeholder">
                                    <label for="filter_staff_id" class="control-label"><?php echo _l('staff'); ?></label>
                                    <select id="filter_staff_id" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($staff as $s) { ?>
                                            <option value="<?php echo (int) $s['staffid']; ?>">
                                                <?php echo e($s['staffid'] . ' # ' . $s['full_name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group select-placeholder">
                                    <label for="filter_module" class="control-label">Module</label>
                                    <select id="filter_module" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($modules as $m) { ?>
                                            <option value="<?php echo e($m); ?>"><?php echo e(ucfirst($m)); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_date_input('filter_date', 'Date'); ?>
                            </div>
                        </div>

                        <?php
                            render_datatable([
                                _l('the_number_sign'),
                                'Staff',
                                'Rule',
                                'Module',
                                'Points',
                                'Date',
                                'Status',
                                'Remarks',
                                'Created',
                                'Actions',
                            ], 'kpi-transactions');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    var kpi_transactions_table;

    $(function () {
        var serverParams = {
            'staff_id': '#filter_staff_id',
            'module': '#filter_module',
            'date': '#filter_date'
        };

        kpi_transactions_table = initDataTable(
            '.table-kpi-transactions',
            admin_url + 'kpi_management/transactions/table',
            [9],
            [9],
            serverParams
        );

        $('#filter_staff_id, #filter_module').on('changed.bs.select', function () {
            kpi_reload_transactions_table();
        });
        $('#filter_date').on('change', function () {
            kpi_reload_transactions_table();
        });
    });

    function kpi_reload_transactions_table() {
        if (kpi_transactions_table) {
            kpi_transactions_table.ajax.reload();
        }
    }

    function kpi_delete_transaction(id) {
        if (!id) return;

        if (!confirm('Are you sure you want to delete this transaction?')) {
            return;
        }

        $.post(admin_url + 'kpi_management/transactions/delete/' + id, {}, function (response) {
            if (response && response.success) {
                alert_float('success', response.message || 'Deleted');
                kpi_reload_transactions_table();
            } else {
                alert_float('danger', (response && response.message) ? response.message : 'Delete failed');
            }
        }, 'json').fail(function () {
            alert_float('danger', 'Request failed');
        });
    }

    function kpi_update_transaction_status(id, status) {
        if (!id || !status) return;

        var actionLabel = status === 'approved' ? 'Approve' : 'Reject';
        if (!confirm(actionLabel + ' this transaction?')) {
            return;
        }

        var remarks = prompt('Remarks (optional):', '');
        if (remarks === null) {
            return;
        }

        $.post(admin_url + 'kpi_management/transactions/update_status/' + id, { status: status, remarks: remarks }, function (response) {
            if (response && response.success) {
                alert_float('success', response.message || 'Updated');
                kpi_reload_transactions_table();
            } else {
                alert_float('danger', (response && response.message) ? response.message : 'Update failed');
            }
        }, 'json').fail(function () {
            alert_float('danger', 'Request failed');
        });
    }
</script>
</body>
</html>

