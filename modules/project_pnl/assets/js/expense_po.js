/*
 * "Against Purchase Order" field for expenses (Project P&L module).
 * Works on the full expense form (#expense-form) and the project "Record Expense" modal
 * (#project-expense-form). The chosen PO is posted as pur_order_id and validated server side.
 */
(function ($) {
    'use strict';

    if (typeof project_pnl_expense_config === 'undefined') {
        return;
    }

    var config = project_pnl_expense_config;
    var L = config.labels;
    var requiredCategories = (config.required_categories || []).map(String);

    function escapeText(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function buildField(selected, expenseId) {
        var $group = $(
            '<div class="form-group project-pnl-po-group hide">' +
                '<label for="project_pnl_pur_order_id" class="control-label">' + escapeText(L.field) + '</label>' +
                '<select name="pur_order_id" id="project_pnl_pur_order_id" class="form-control"></select>' +
                '<p class="text-muted tw-text-xs tw-mt-1 tw-mb-0">' + escapeText(L.help) + '</p>' +
                '<p class="text-danger tw-text-sm tw-mt-1 tw-mb-0 project-pnl-po-error hide">' + escapeText(L.required) + '</p>' +
            '</div>'
        );
        $group.data('selected', String(selected || 0));
        $group.data('expense-id', String(expenseId || 0));

        return $group;
    }

    function optionLabel(po) {
        var label = po.number;
        if (po.vendor) {
            label += ' — ' + po.vendor;
        }
        label += ' (' + po.value_formatted + ', ' + po.spent_formatted + ' ' + L.used + ')';
        if (!po.approved) {
            label += ' · ' + L.pending;
        } else if (po.closed) {
            label += ' · ' + L.closed;
        }

        return label;
    }

    function loadOptions($group, projectId) {
        var $select = $group.find('select');
        var selected = String($select.val() || $group.data('selected') || '0');

        if (!projectId) {
            $select.html('').append($('<option>', { value: '', text: L.none }));
            $group.addClass('hide');
            refreshRequired($group);
            return;
        }

        $.get(config.options_url + encodeURIComponent(projectId) + '/' + $group.data('expense-id')).done(function (response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            var options = (data && data.options) || [];

            $select.html('').append($('<option>', { value: '', text: L.none }));
            options.forEach(function (po) {
                $select.append($('<option>', { value: po.id, text: optionLabel(po) }));
            });

            if (options.length) {
                $select.val($select.find('option[value="' + selected + '"]').length ? selected : '');
                $group.removeClass('hide');
            } else {
                $select.val('');
                $group.addClass('hide');
            }
            refreshRequired($group);
        });
    }

    function isRequired($form, $group) {
        var category = String($form.find('select[name="category"]').val() || '');

        return !$group.hasClass('hide')
            && $group.find('select option').length > 1
            && requiredCategories.indexOf(category) !== -1;
    }

    function refreshRequired($group) {
        var $form = $group.closest('form');
        $group.find('label').toggleClass('project-pnl-required', isRequired($form, $group));
        $group.find('.project-pnl-po-error').addClass('hide');
    }

    function attach($form, $anchor, getProjectId, selected, expenseId) {
        if (!$form.length || $form.find('.project-pnl-po-group').length) {
            return;
        }

        var $group = buildField(selected, expenseId);
        if ($anchor.length) {
            $group.insertAfter($anchor);
        } else {
            $form.append($group);
        }

        $form.on('change', 'select[name="project_id"]', function () {
            loadOptions($group, getProjectId());
        });
        $form.on('change', 'select[name="category"]', function () {
            refreshRequired($group);
        });
        $group.on('change', 'select', function () {
            $group.find('.project-pnl-po-error').addClass('hide');
        });

        // Capture phase: runs before Perfex's validator/submit handler and can stop it
        $form[0].addEventListener('submit', function (e) {
            if (isRequired($form, $group) && !$group.find('select').val()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $group.find('.project-pnl-po-error').removeClass('hide');
                $group.find('select').trigger('focus');
            }
        }, true);

        loadOptions($group, getProjectId());
    }

    $(function () {
        // Full expense form (Expenses → New / Edit)
        var $placeholder = $('#project-pnl-po-field');
        var $expenseForm = $('#expense-form');
        if ($expenseForm.length) {
            attach(
                $expenseForm,
                $expenseForm.find('select[name="project_id"]').closest('.form-group'),
                function () { return $expenseForm.find('select[name="project_id"]').val(); },
                $placeholder.data('selected'),
                $placeholder.data('expense-id')
            );
        }

        // Project page → Expenses → Record Expense (project is fixed)
        var $projectForm = $('#project-expense-form');
        if ($projectForm.length) {
            attach(
                $projectForm,
                $projectForm.find('select[name="category"]').closest('.form-group'),
                function () { return $projectForm.find('input[name="project_id"]').val(); },
                0,
                0
            );
        }
    });
})(jQuery);
