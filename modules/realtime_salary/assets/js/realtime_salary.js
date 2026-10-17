$(function() {
    'use strict';

    // Staff dropdown change handler
    $('#rs_staff_selector').on('change', function() {
        var staffId = $(this).val();
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('staff_id', staffId);
        window.location.href = currentUrl.toString();
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
