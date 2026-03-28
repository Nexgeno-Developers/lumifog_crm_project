<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <a href="#" onclick="new_stakeholder_type(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('lead_new_stakeholder_type'); ?>
                    </a>
                </div>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php if (count($stakeholder_types) > 0) { ?>
                        <table class="table dt-table" data-order-col="1" data-order-type="asc">
                            <thead>
                                <th><?= _l('id'); ?></th>
                                <th><?= _l('leads_stakeholder_types_table_name'); ?></th>
                                <th><?= _l('options'); ?></th>
                            </thead>
                            <tbody>
                                <?php foreach ($stakeholder_types as $type) { ?>
                                <tr>
                                    <td><?= e($type['id']); ?></td>
                                    <td>
                                        <a href="#"
                                           class="tw-font-medium"
                                           onclick="edit_stakeholder_type(this,<?= e($type['id']); ?>); return false"
                                           data-name="<?= e($type['name']); ?>">
                                            <?= e($type['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-2">
                                            <a href="#"
                                               onclick="edit_stakeholder_type(this,<?= e($type['id']); ?>); return false"
                                               data-name="<?= e($type['name']); ?>"
                                               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                            </a>
                                            <a href="<?= admin_url('leads/delete_stakeholder_type/' . $type['id']); ?>"
                                               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                <i class="fa-regular fa-trash-can fa-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } else { ?>
                        <p class="no-margin">
                            <?= _l('leads_stakeholder_types_not_found'); ?>
                        </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stakeholder_type" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(admin_url('leads/stakeholder_type')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <span class="edit-title"><?= _l('edit_stakeholder_type'); ?></span>
                    <span class="add-title"><?= _l('lead_new_stakeholder_type'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="stakeholder-type-additional"></div>
                        <?= render_input('name', 'leads_stakeholder_type_add_edit_name'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= _l('close'); ?>
                </button>
                <button type="submit" class="btn btn-primary">
                    <?= _l('submit'); ?>
                </button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<?php init_tail(); ?>
<script>
    $(function() {
        appValidateForm($('form'), {
            name: 'required'
        }, manage_leads_stakeholder_types);

        $('#stakeholder_type').on('hidden.bs.modal', function() {
            $('#stakeholder-type-additional').html('');
            $('#stakeholder_type input[name="name"]').val('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });
    });

    function manage_leads_stakeholder_types(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function() {
            window.location.reload();
        });
        return false;
    }

    function new_stakeholder_type() {
        $('#stakeholder_type').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_stakeholder_type(invoker, id) {
        var name = $(invoker).data('name');
        $('#stakeholder-type-additional').append(hidden_input('id', id));
        $('#stakeholder_type input[name="name"]').val(name);
        $('#stakeholder_type').modal('show');
        $('.add-title').addClass('hide');
    }
</script>
</body>
</html>

