<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s panel-table-full">
                    <div class="panel-body">
                        <div class="row mbot15">
                            <?php if (isset($consent_purposes)) { ?>
                            <div class="col-md-3 contacts-filter-column">
                                <div class="select-placeholder">
                                    <select name="custom_view"
                                        title="<?= _l('gdpr_consent'); ?>"
                                        id="custom_view" class="selectpicker" data-width="100%">
                                        <option value=""></option>
                                        <?php foreach ($consent_purposes as $purpose) { ?>
                                        <option
                                            value="consent_<?= e($purpose['id']); ?>">
                                            <?= e($purpose['name']); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <?php } ?>

                            <div class="col-md-3 contacts-filter-column">
                                <div class="select-placeholder">
                                    <select name="stakeholder_type_filter"
                                            title="<?= _l('contacts_filter_stakeholder_type'); ?>"
                                            id="stakeholder_type_filter" class="selectpicker" data-width="100%"
                                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <?php if (isset($stakeholder_types) && is_array($stakeholder_types)) { ?>
                                            <?php foreach ($stakeholder_types as $type) { ?>
                                                <option value="<?= e($type['name']); ?>">
                                                    <?= e($type['name']); ?>
                                                </option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 contacts-filter-column">
                                <div class="select-placeholder">
                                    <select name="contact_type_filter"
                                            title="<?= _l('contacts_filter_contact_type'); ?>"
                                            id="contact_type_filter" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('contacts_filter_contact_type_all'); ?></option>
                                        <option value="client"><?= _l('contacts_filter_contact_type_client'); ?></option>
                                        <option value="stakeholder"><?= _l('contacts_filter_contact_type_stakeholder'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <?php
        $table_data = [_l('client_firstname'), _l('client_lastname')];
if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
    array_push($table_data, [
        'name'     => _l('gdpr_consent') . ' (' . _l('gdpr_short') . ')',
        'th_attrs' => ['id' => 'th-consent', 'class' => 'not-export'],
    ]);
}
$table_data = array_merge($table_data, [
    _l('client_email'),
    _l('clients_list_company'),
    _l('client_phonenumber'),
    _l('contact_position'),
    _l('clients_list_last_login'),
    ['name' => _l('contact_active'), 'th_attrs' => ['class' => 'text-center']],
]);
$custom_fields = get_custom_fields('contacts', ['show_on_table' => 1]);

foreach ($custom_fields as $field) {
    array_push($table_data, [
        'name'     => $field['name'],
        'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
    ]);
}
render_datatable($table_data, 'all-contacts');
?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<?php $this->load->view('admin/clients/client_js'); ?>
<div id="contact_data"></div>
<div id="consent_data"></div>
<script>
    $(function() {
        var optionsHeading = [];
        var allContactsServerParams = {
            "custom_view": "[name='custom_view']",
            "stakeholder_type_filter": "[name='stakeholder_type_filter']",
            "contact_type_filter": "[name='contact_type_filter']",
        }
        <?php if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') { ?>
        optionsHeading.push($('#th-consent').index());
        <?php } ?>
        _table_api = initDataTable('.table-all-contacts', window.location.href, optionsHeading, optionsHeading,
            allContactsServerParams, [0, 'asc']);
        if (_table_api) {
            <?php if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') { ?>
                _table_api.on('draw', function() {
                    var tableData = $('.table-all-contacts').find('tbody tr');
                    $.each(tableData, function() {
                        $(this).find('td:eq(2)').addClass('bg-neutral');
                    });
                });
            <?php } ?>

            $('select[name="custom_view"], select[name="stakeholder_type_filter"], select[name="contact_type_filter"]').on('change', function() {
                _table_api.ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
            });
        }
    });
</script>
</body>

</html>