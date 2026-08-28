<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="kpiRuleModal" tabindex="-1" role="dialog" aria-labelledby="kpiRuleModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('kpi_management/rules/save'), ['id' => 'kpi_rule_form']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo _l('close'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="kpiRuleModalLabel">Add KPI Rule</h4>
            </div>
            <div class="modal-body">
                <div id="kpi_rule_errors" class="hide"></div>

                <?php echo form_hidden('id', ''); ?>

                <?php echo render_input('title', 'Title', '', 'text', ['autocomplete' => 'off']); ?>

                <div class="form-group select-placeholder">
                    <label for="kpi_rule_module" class="control-label">Module</label>
                    <select name="module" id="kpi_rule_module" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                        <option value=""></option>
                        <?php foreach ($modules as $key => $label) { ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <?php echo render_textarea('rules_json', 'Rules JSON', '', ['rows' => 10, 'placeholder' => '{ "rule": "...", "based_on": "...", "type": "...", "conditions": [] }']); ?>

                <div class="form-group">
                    <label for="description" class="control-label">Description</label>
                    <textarea name="description" id="kpi_rule_description" class="form-control tinymce" rows="4"></textarea>
                </div>

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="is_active" id="kpi_rule_is_active" value="1" checked>
                    <label for="kpi_rule_is_active">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

