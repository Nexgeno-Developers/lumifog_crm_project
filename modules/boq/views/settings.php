<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <h4 class="tw-font-semibold tw-text-base tw-text-neutral-800 tw-mt-0 tw-mb-4">
            <i class="fa-solid fa-list-ol tw-mr-1 text-primary"></i> <?= _l('project_boq_settings'); ?>
        </h4>
        <hr class="hr-panel-heading" />

        <?php render_yes_no_option(
            'boq_po_restriction',
            'project_boq_po_restriction',
            'project_boq_po_restriction_tooltip'
        ); ?>

        <hr class="hr-panel-heading" />

        <div class="form-group">
            <label for="settings[boq_revision_approver]" class="control-label">
                <i class="fa-solid fa-user-check tw-mr-1 text-primary"></i> <?= _l('project_boq_revision_approver'); ?>
            </label>
            <?php
            $CI = &get_instance();
            $CI->load->model('staff_model');
            $staff_members = $CI->staff_model->get('', ['active' => 1]);
            $selected_approver = get_option('boq_revision_approver');
            ?>
            <select name="settings[boq_revision_approver]" id="settings[boq_revision_approver]" class="selectpicker" data-width="100%" data-live-search="true">
                <option value="" <?= empty($selected_approver) ? 'selected' : ''; ?>>
                    <?= _l('project_boq_revision_approver_admins'); ?>
                </option>
                <?php foreach ($staff_members as $member): ?>
                    <option value="<?= (int) $member['staffid']; ?>" <?= ((string)$selected_approver === (string)$member['staffid']) ? 'selected' : ''; ?>>
                        <?= e($member['firstname'] . ' ' . $member['lastname'] . (!empty($member['email']) ? ' (' . $member['email'] . ')' : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-muted mtop5">
                <i class="fa-regular fa-circle-question"></i> <?= _l('project_boq_revision_approver_tooltip'); ?>
            </p>
        </div>
    </div>
</div>
