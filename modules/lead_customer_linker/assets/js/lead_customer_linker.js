/**
 * Lead Customer Linker - Frontend Enhancement
 * Allows converting repeat leads to existing customers seamlessly
 */
(function($) {
    "use strict";

    function setupConvertModal($modal) {
        var $form = $modal.find('#lead_to_client_form');
        if (!$form.length || $form.find('.lead-convert-mode-container').length > 0) {
            return;
        }

        var leadId = $form.find('input[name="leadid"]').val();

        // 1. Wrap all existing modal-body elements (the original fields) in a container
        var $body = $form.find('.modal-body');
        var $originalChildren = $body.children().not('input[name="leadid"], input[name="default_language"], input[type="hidden"]');
        
        $originalChildren.wrapAll('<div id="linker_new_customer_fields"></div>');

        // 2. Build the Mode Toggle & Existing Customer Section
        var html = `
            <div class="lead-convert-mode-container" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <label class="control-label bold block" style="font-size: 14px; margin-bottom: 12px; color: #1e293b;">
                    <i class="fa fa-exchange text-info mright5"></i> Conversion Mode
                </label>
                <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                    <div class="radio radio-primary" style="margin: 0;">
                        <input type="radio" name="lead_linker_convert_mode" id="linker_mode_new" value="new" checked>
                        <label for="linker_mode_new" style="cursor: pointer; padding-left: 24px;">
                            <strong>Create New Customer</strong>
                        </label>
                    </div>
                    <div class="radio radio-primary" style="margin: 0;">
                        <input type="radio" name="lead_linker_convert_mode" id="linker_mode_existing" value="existing">
                        <label for="linker_mode_existing" style="cursor: pointer; padding-left: 24px;">
                            <strong>Link to Existing Customer</strong> <span class="text-muted">(Repeat Client / Contractor)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="linker_existing_customer_fields" style="display: none; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 18px; margin-bottom: 20px;">
                <div class="form-group select-placeholder">
                    <label for="linker_existing_client_id" class="control-label">
                        <small class="req text-danger">* </small>Select Existing Customer:
                    </label>
                    <select name="existing_client_id" id="linker_existing_client_id" class="ajax-search" data-width="100%" data-live-search="true" data-none-selected-text="Type company name or phone to search customer...">
                    </select>
                </div>

                <div class="row mtop20">
                    <div class="col-md-6">
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="transfer_notes_existing" id="transfer_notes_existing" value="1" checked>
                            <label for="transfer_notes_existing">Transfer Notes</label>
                        </div>
                        <div class="checkbox checkbox-primary mtop10">
                            <input type="checkbox" name="transfer_proposals_existing" id="transfer_proposals_existing" value="1" checked>
                            <label for="transfer_proposals_existing">Transfer Proposals</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="transfer_tasks_existing" id="transfer_tasks_existing" value="1" checked>
                            <label for="transfer_tasks_existing">Transfer Tasks</label>
                        </div>
                        <div class="checkbox checkbox-primary mtop10">
                            <input type="checkbox" name="transfer_reminders_existing" id="transfer_reminders_existing" value="1" checked>
                            <label for="transfer_reminders_existing">Transfer Reminders</label>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Prepend toggle and existing customer section at the top of modal-body
        $body.prepend(html);

        // Initialize Perfex native AJAX customer search
        if (typeof init_ajax_search === 'function') {
            init_ajax_search('customer', '#linker_existing_client_id.ajax-search');
        }

        // Toggle logic
        $form.on('change', 'input[name="lead_linker_convert_mode"]', function() {
            var mode = $(this).val();
            if (mode === 'existing') {
                $('#linker_new_customer_fields').slideUp(150);
                $('#linker_existing_customer_fields').slideDown(150, function() {
                    // Re-initialize ajax search / selectpicker if needed
                    if (typeof init_ajax_search === 'function') {
                        init_ajax_search('customer', '#linker_existing_client_id.ajax-search');
                    }
                });
            } else {
                $('#linker_existing_customer_fields').slideUp(150);
                $('#linker_new_customer_fields').slideDown(150);
            }
        });

        // Intercept form submit
        $form.off('submit.lead_linker').on('submit.lead_linker', function(e) {
            var mode = $('input[name="lead_linker_convert_mode"]:checked').val();

            if (mode === 'existing') {
                e.preventDefault();
                e.stopImmediatePropagation();

                var selectedClientId = $('#linker_existing_client_id').val();
                if (!selectedClientId) {
                    alert_float('warning', 'Please select an existing customer to link this lead.');
                    return false;
                }

                var $btn = $form.find('button[type="submit"]');
                $btn.prop('disabled', true).text('Converting & Linking...');

                var postData = {
                    leadid: leadId,
                    client_id: selectedClientId,
                    transfer_notes: $('#transfer_notes_existing').is(':checked') ? 1 : 0,
                    transfer_proposals: $('#transfer_proposals_existing').is(':checked') ? 1 : 0,
                    transfer_tasks: $('#transfer_tasks_existing').is(':checked') ? 1 : 0,
                    transfer_reminders: $('#transfer_reminders_existing').is(':checked') ? 1 : 0
                };

                // Add CSRF token
                if (typeof leadCustomerLinkerConfig !== 'undefined') {
                    postData[leadCustomerLinkerConfig.csrfTokenName] = leadCustomerLinkerConfig.csrfHash;
                }

                var convertUrl = (typeof leadCustomerLinkerConfig !== 'undefined') 
                    ? leadCustomerLinkerConfig.convertExistingUrl 
                    : admin_url + 'lead_customer_linker/convert_to_existing';

                $.post(convertUrl, postData, function(response) {
                    if (response && response.success) {
                        alert_float('success', response.message);
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 500);
                    } else {
                        $btn.prop('disabled', false).text('Convert');
                        alert_float('danger', (response && response.message) ? response.message : 'Error linking lead.');
                    }
                }, 'json').fail(function() {
                    $btn.prop('disabled', false).text('Convert');
                    alert_float('danger', 'Server error while linking lead to existing customer.');
                });

                return false;
            }
            // If mode is 'new', normal form submission proceeds
        });
    }

    // Bind to modal shown and AJAX completed events
    $(document).on('shown.bs.modal', '#convert_lead_to_client_modal', function() {
        setupConvertModal($(this));
    });

    // Also check on DOM ready or direct modal load
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('leads/convert_to_customer') !== -1) {
            setTimeout(function() {
                var $modal = $('#convert_lead_to_client_modal');
                if ($modal.length) {
                    setupConvertModal($modal);
                }
            }, 100);
        }
    });

})(jQuery);

