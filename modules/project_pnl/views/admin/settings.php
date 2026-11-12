<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?= _l('project_pnl'); ?> — <?= _l('settings'); ?>
                </h4>
                <?= form_open(admin_url('project_pnl/settings')); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?= render_select(
                            'po_required_categories[]',
                            $categories,
                            ['id', 'name'],
                            'project_pnl_po_required_categories',
                            $selected,
                            ['multiple' => true, 'data-actions-box' => true],
                            [],
                            '',
                            '',
                            false
                        ); ?>
                        <p class="text-muted tw-text-sm tw-mb-0"><?= _l('project_pnl_po_required_categories_help'); ?></p>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                    </div>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
