<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                    <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                    <?php if (has_permission('kpi_management', '', 'create')) { ?>
                    <button type="button" class="btn btn-primary" onclick="kpi_open_rule_modal();">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        Add rule
                    </button>
                    <?php } ?>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row mbot15">
                            <div class="col-md-4">
                                <div class="form-group select-placeholder">
                                    <label for="filter_module" class="control-label">Module</label>
                                    <select id="filter_module" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($modules as $key => $label) { ?>
                                            <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group select-placeholder">
                                    <label for="filter_active" class="control-label">Active</label>
                                    <select id="filter_active" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 text-right">
                                <label class="control-label">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-default" onclick="kpi_reload_rules_table();">
                                        <i class="fa-solid fa-rotate tw-mr-1"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php
                            render_datatable([
                                _l('the_number_sign'),
                                'Title',
                                'Module',
                                'Active',
                                'Updated',
                                'Actions',
                            ], 'kpi-rules');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('rules/rule_modal', ['modules' => $modules]); ?>

<?php init_tail(); ?>
<script>
    var kpi_rules_table;

    $(function () {
        var serverParams = {
            'module': '#filter_module',
            'is_active': '#filter_active'
        };

        kpi_rules_table = initDataTable(
            '.table-kpi-rules',
            admin_url + 'kpi_management/rules/table',
            [5],
            [5],
            serverParams
        );

        $('#filter_module, #filter_active').on('changed.bs.select', function () {
            kpi_reload_rules_table();
        });

        // Clean up TinyMCE when modal is closed
        $('#kpiRuleModal').on('hidden.bs.modal', function () {
            if (tinymce.get('kpi_rule_description')) {
                tinymce.get('kpi_rule_description').remove();
            }
        });

        appValidateForm($('#kpi_rule_form'), {
            title: 'required',
            module: 'required',
            rules_json: 'required'
        }, kpi_submit_rule_form);
    });

    function kpi_reload_rules_table() {
        if (kpi_rules_table) {
            kpi_rules_table.ajax.reload();
        }
    }

    function kpi_open_rule_modal(id) {
        $('#kpi_rule_errors').addClass('hide').html('');
        $('#kpi_rule_form')[0].reset();
        $('#kpi_rule_form input[name="id"]').val('');
        $('#kpi_rule_form textarea[name="rules_json"]').val('');
        $('#kpi_rule_description').val('');
        $('#kpi_rule_form input[name="is_active"]').prop('checked', true);

        // Remove existing TinyMCE instance if any
        if (tinymce.get('kpi_rule_description')) {
            tinymce.get('kpi_rule_description').remove();
        }

        $('#kpi_rule_module').selectpicker('val', '');

        if (typeof id === 'undefined' || !id) {
            $('#kpiRuleModalLabel').text('Add KPI Rule');
            $('#kpiRuleModal').modal('show');
            // Initialize TinyMCE for new rule
            setTimeout(function() {
                init_editor('#kpi_rule_description', {
                    height: 200
                });
            }, 300);
            return;
        }

        $('#kpiRuleModalLabel').text('Edit KPI Rule');

        $.get(admin_url + 'kpi_management/rules/get/' + id, function (response) {
            if (!response || response.success !== true) {
                alert_float('danger', (response && response.message) ? response.message : 'Failed to load rule');
                return;
            }

            var rule = response.rule;
            $('#kpi_rule_form input[name="id"]').val(rule.id);
            $('#kpi_rule_form input[name="title"]').val(rule.title);
            $('#kpi_rule_module').selectpicker('val', rule.module);
            $('#kpi_rule_form textarea[name="rules_json"]').val(rule.rules_json);
            $('#kpi_rule_form input[name="is_active"]').prop('checked', rule.is_active == 1);
            $('#kpi_rule_description').val(rule.description || '');

            $('#kpiRuleModal').modal('show');
            
            // Initialize TinyMCE for editing (it will pick up textarea value)
            setTimeout(function() {
                init_editor('#kpi_rule_description', {
                    height: 200
                });
            }, 300);
        }, 'json').fail(function () {
            alert_float('danger', 'Request failed');
        });
    }

    function kpi_submit_rule_form(form) {
        var $form = $(form);
        
        // Trigger TinyMCE save to update textarea
        tinymce.triggerSave();
        
        var data = $form.serialize();

        $.post(admin_url + 'kpi_management/rules/save', data, function (response) {
            if (!response || response.success !== true) {
                var msg = (response && response.message) ? response.message : 'Save failed';
                $('#kpi_rule_errors').removeClass('hide').html('<div class="alert alert-danger mtop10 mbot0">' + msg + '</div>');

                if (response && response.errors) {
                    var html = '<ul class="mbot0">';
                    $.each(response.errors, function (k, v) {
                        html += '<li><strong>' + k + '</strong>: ' + v + '</li>';
                    });
                    html += '</ul>';
                    $('#kpi_rule_errors').append('<div class="alert alert-warning mtop10 mbot0">' + html + '</div>');
                }
                return false;
            }

            // Remove TinyMCE instance before hiding modal
            if (tinymce.get('kpi_rule_description')) {
                tinymce.get('kpi_rule_description').remove();
            }
            
            $('#kpiRuleModal').modal('hide');
            alert_float('success', response.message || 'Saved');
            kpi_reload_rules_table();
            return false;
        }, 'json').fail(function (xhr) {
            $('#kpi_rule_errors').removeClass('hide').html('<div class="alert alert-danger mtop10 mbot0">Request failed</div>');
        });

        return false;
    }

    function kpi_delete_rule(id) {
        if (!id) return;

        if (!confirm('<?php echo _l('delete_confirm'); ?>')) {
            return;
        }

        $.post(admin_url + 'kpi_management/rules/delete/' + id, {}, function (response) {
            if (response && response.success) {
                alert_float('success', response.message || 'Deleted');
                kpi_reload_rules_table();
            } else {
                alert_float('danger', (response && response.message) ? response.message : 'Delete failed');
            }
        }, 'json').fail(function () {
            alert_float('danger', 'Request failed');
        });
    }
</script>
</body>
</html>

