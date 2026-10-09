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
    </div>
</div>
