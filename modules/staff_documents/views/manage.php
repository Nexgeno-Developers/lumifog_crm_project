<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-mb-4"><?php echo _l('staff_documents_manage'); ?></h4>

                        <?php if (has_permission('staff_documents', '', 'create')) { ?>
                        <div class="staff-documents-description">
                            <label for="staff_document_description"><?php echo _l('staff_documents_description'); ?></label>
                            <input type="text" id="staff_document_description" class="form-control" maxlength="500" placeholder="<?php echo _l('staff_documents_description'); ?>">
                        </div>

                        <div class="staff-documents-upload-zone" id="staff-documents-dropzone">
                            <i class="fa fa-cloud-upload"></i>
                            <p><?php echo _l('staff_documents_drag_drop'); ?></p>
                            <p class="allowed-types"><?php echo _l('staff_documents_allowed_types'); ?></p>
                            <span class="label label-info staff-documents-file-badge hide" id="staff-documents-file-badge"></span>
                            <input type="file" id="staff-documents-file-input" name="document_file" accept=".pdf,.csv,.xls,.xlsx,application/pdf,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                        </div>

                        <button type="button" class="btn btn-primary mbot15" id="staff-documents-upload-btn" disabled>
                            <i class="fa fa-upload"></i> <?php echo _l('staff_documents_upload_btn'); ?>
                        </button>

                        <div class="staff-documents-upload-progress" id="staff-documents-upload-progress">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if (!empty($has_global_view)) { ?>
                        <div class="staff-documents-filters mbot20">
                            <?php echo form_open(admin_url('staff_documents'), ['method' => 'get', 'id' => 'staff-documents-filter-form']); ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <?php
                                    echo render_select(
                                        'staff_id',
                                        $staff_members,
                                        ['staffid', ['firstname', 'lastname']],
                                        'staff_member',
                                        $filters['staff_id'],
                                        ['data-none-selected-text' => _l('staff_documents_filter_all_staff')],
                                        [],
                                        '',
                                        '',
                                        false
                                    );
                                    ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo render_date_input('date_from', 'from_date', $filters['date_from_display']); ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo render_date_input('date_to', 'to_date', $filters['date_to_display']); ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label tw-invisible hidden-xs">&nbsp;</label>
                                    <div class="staff-documents-filter-actions">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fa fa-filter"></i> <?php echo _l('filter'); ?>
                                        </button>
                                        <a href="<?php echo admin_url('staff_documents'); ?>" class="btn btn-default btn-block mtop5">
                                            <?php echo _l('clear'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                        <?php } ?>

                        <?php
                        $colspan = !empty($has_global_view) ? 7 : 6;
                        ?>
                        <div class="table-responsive">
                            <table class="table table-striped staff-documents-table" id="staff-documents-table">
                                <thead>
                                    <tr>
                                        <?php if (!empty($has_global_view)) { ?>
                                        <th><?php echo _l('staff_member'); ?></th>
                                        <?php } ?>
                                        <th><?php echo _l('staff_documents_file_name'); ?></th>
                                        <th><?php echo _l('staff_documents_file_type'); ?></th>
                                        <th><?php echo _l('staff_documents_file_size'); ?></th>
                                        <th><?php echo _l('staff_documents_description'); ?></th>
                                        <th><?php echo _l('staff_documents_date_added'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="staff-documents-tbody">
                                    <?php if (empty($documents)) { ?>
                                    <tr class="staff-documents-empty-row">
                                        <td colspan="<?php echo $colspan; ?>" class="text-center text-muted"><?php echo _l('staff_documents_no_documents'); ?></td>
                                    </tr>
                                    <?php } else { ?>
                                    <?php foreach ($documents as $doc) {
                                        $can_delete_doc = has_permission('staff_documents', '', 'delete')
                                            && (!empty($has_global_view) || (int) $doc['staff_id'] === (int) get_staff_user_id());
                                    ?>
                                    <tr data-id="<?php echo (int) $doc['id']; ?>"
                                        data-file-type="<?php echo htmlspecialchars($doc['file_type'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-serve-url="<?php echo admin_url('staff_documents/serve/' . (int) $doc['id']); ?>"
                                        data-download-url="<?php echo admin_url('staff_documents/download/' . (int) $doc['id']); ?>">
                                        <?php if (!empty($has_global_view)) { ?>
                                        <td><?php echo htmlspecialchars(trim(($doc['firstname'] ?? '') . ' ' . ($doc['lastname'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php } ?>
                                        <td><?php echo htmlspecialchars($doc['original_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo strtoupper(htmlspecialchars($doc['file_type'], ENT_QUOTES, 'UTF-8')); ?></td>
                                        <td><?php echo bytesToSize('', (int) $doc['file_size']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo _dt($doc['dateadded']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-default btn-sm staff-documents-preview-btn" title="<?php echo _l('staff_documents_preview'); ?>">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <a href="<?php echo admin_url('staff_documents/download/' . (int) $doc['id']); ?>" class="btn btn-default btn-sm" title="<?php echo _l('staff_documents_download'); ?>">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <?php if ($can_delete_doc) { ?>
                                                <button type="button" class="btn btn-danger btn-sm staff-documents-delete-btn" title="<?php echo _l('staff_documents_delete'); ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staff-documents-preview-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="staff-documents-preview-title"><?php echo _l('staff_documents_preview'); ?></h4>
            </div>
            <div class="modal-body staff-documents-preview-body" id="staff-documents-preview-body">
                <div class="staff-documents-loading">
                    <i class="fa fa-spinner fa-spin"></i>
                    <?php echo _l('staff_documents_preview_loading'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="staff-documents-preview-download" target="_blank">
                    <i class="fa fa-download"></i> <?php echo _l('staff_documents_download'); ?>
                </a>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    var staffDocumentsLang = {
        confirm_delete: <?php echo json_encode(_l('staff_documents_confirm_delete')); ?>,
        preview_error: <?php echo json_encode(_l('staff_documents_preview_error')); ?>,
        no_documents: <?php echo json_encode(_l('staff_documents_no_documents')); ?>,
        sheet: <?php echo json_encode(_l('staff_documents_sheet')); ?>,
        preview: <?php echo json_encode(_l('staff_documents_preview')); ?>,
        download: <?php echo json_encode(_l('staff_documents_download')); ?>,
        delete: <?php echo json_encode(_l('staff_documents_delete')); ?>,
        can_delete: <?php echo json_encode(has_permission('staff_documents', '', 'delete')); ?>,
        can_create: <?php echo json_encode(has_permission('staff_documents', '', 'create')); ?>,
        upload_url: <?php echo json_encode(admin_url('staff_documents/upload')); ?>,
        upload_btn: <?php echo json_encode(_l('staff_documents_upload_btn')); ?>,
        select_file: <?php echo json_encode(_l('staff_documents_select_file')); ?>,
        access_denied: <?php echo json_encode(_l('access_denied')); ?>,
        delete_url_base: <?php echo json_encode(admin_url('staff_documents/delete/')); ?>
    };
</script>
<?php init_tail(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</body>
</html>
