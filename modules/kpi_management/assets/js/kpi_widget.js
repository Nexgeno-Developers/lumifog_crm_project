// KPI Widget JS - guarded wrapper (cache-busted via ?v=filemtime)
(function() {
    'use strict';
    if (typeof $ === 'undefined' || typeof admin_url === 'undefined') {
        return;
    }

    $(document).on('click', '.hide-kpi-widget', function () {
        var widgetId = 'kpi_widget';
        $('#widget-' + widgetId).addClass('hide');
        $('#widget_option_' + widgetId).prop('checked', false);
        $.post(admin_url + 'staff/save_dashboard_widgets_visibility', {
            widgets: [{ id: widgetId, visible: 0 }]
        });
        if (typeof alert_float === 'function') {
            alert_float('success', 'KPI widget hidden!');
        }
    });

    function ensureModalInBody() {
        var modal = $('#kpi_staff_list_modal');
        if (modal.length > 0 && !modal.parent().is('body')) {
            modal.detach().appendTo('body');
        }
        return modal;
    }

    function getTheadRow() {
        var $row = $('#kpi_staff_list_thead');
        if ($row.length === 0) {
            $row = $('#kpi_staff_list_modal thead tr').first();
        }
        return $row;
    }

    function attendanceColor(color) {
        var allowed = ['gold', 'green', 'lime', 'gray-light', 'gray-medium', 'gray-dark', 'gray'];
        color = String(color || 'gray').toLowerCase();
        return allowed.indexOf(color) !== -1 ? color : 'gray';
    }

    function escapeHtml(value) {
        return $('<div>').text(String(value == null ? '' : value)).html();
    }

    function setModalHeaders(type) {
        // Normalize in case a caller passes unexpected casing
        type = String(type || '').toLowerCase();

        var headerHtml = '<th>#</th><th>Name</th><th>Email</th>';
        if (type === 'on_leave') {
            headerHtml += '<th>Type of Leave</th><th>Leave Period</th>';
        } else if (type === 'absent') {
            headerHtml += '<th>Shift Start</th>';
        } else if (type === 'present') {
            headerHtml += '<th>Shift Start</th><th>Check-in Time</th><th>Status</th>';
        }

        var $headRow = getTheadRow();
        if ($headRow.length) {
            $headRow.html(headerHtml);
        }
    }

    function buildStaffRow(type, index, staff) {
        type = String(type || '').toLowerCase();

        var name = staff.full_name || ((staff.firstname || '') + ' ' + (staff.lastname || ''));
        var row = '<tr>';
        row += '<td>' + (index + 1) + '</td>';
        row += '<td><strong>' + name + '</strong></td>';
        row += '<td>' + (staff.email || '-') + '</td>';

        if (type === 'on_leave') {
            var leaveTypeName = escapeHtml(staff.leave_type_name || '-');
            var leaveDetailUrl = staff.leave_detail_url || '';
            var leaveTypeCell = leaveTypeName;
            if (leaveDetailUrl) {
                leaveTypeCell = '<a href="' + escapeHtml(leaveDetailUrl) + '" target="_blank" rel="noopener noreferrer">'
                    + leaveTypeName
                    + '</a>';
            }
            row += '<td>' + leaveTypeCell + '</td>';

            var leavePeriod = '-';
            if (staff.start_time && staff.end_time) {
                leavePeriod = new Date(staff.start_time).toLocaleDateString()
                    + ' to '
                    + new Date(staff.end_time).toLocaleDateString();
            }
            var statusBadge = '';
            if (staff.status === 0 || staff.status === '0') {
                statusBadge = ' <span class="badge badge-warning">Pending</span>';
            } else if (staff.status === 1 || staff.status === '1') {
                statusBadge = ' <span class="badge badge-success">Approved</span>';
            }
            row += '<td>' + leavePeriod + statusBadge + '</td>';
        } else if (type === 'absent') {
            // Backend always sends shift_start_time (H:i or "-")
            row += '<td>' + (staff.shift_start_time || '-') + '</td>';
        } else if (type === 'present') {
            var color = attendanceColor(staff.kpi_badge_color);
            var checkinTime = staff.checkin_time_display || staff.checkin_time || '-';
            var remarks = staff.kpi_remarks || '—';
            row += '<td>' + (staff.shift_start_time || '-') + '</td>';
            row += '<td class="kpi-checkin-time-' + color + '">' + escapeHtml(checkinTime) + '</td>';
            row += '<td><span class="badge badge-' + color + '">' + escapeHtml(remarks) + '</span></td>';
        }

        row += '</tr>';
        return row;
    }

    window.showKpiStaffListModal = function(type) {
        if (typeof $ === 'undefined' || typeof admin_url === 'undefined') {
            alert('Error: Required JavaScript libraries not loaded. Please refresh the page.');
            return;
        }

        type = String(type || '').toLowerCase();

        var modal = ensureModalInBody();
        if (!modal.length) {
            alert('Error: Modal not found. Please refresh the page.');
            return;
        }

        $('#kpi_staff_list_loading').show();
        $('#kpi_staff_list_content').hide();
        $('#kpi_staff_list_empty').hide();
        $('#kpi_staff_list_tbody').empty();

        var titles = {
            active: 'Active Staff',
            present: 'Present Staff',
            absent: 'Absent Staff',
            on_leave: 'Staff On Leave'
        };
        $('#kpi_modal_title').text(titles[type] || 'Staff List');
        setModalHeaders(type);

        modal.modal('show');

        $.ajax({
            url: admin_url + 'kpi_management/widget/get_staff_list',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                $('#kpi_staff_list_loading').hide();

                if (response.success && response.staff && response.staff.length > 0) {
                    // Re-apply headers in case DOM was replaced while loading
                    setModalHeaders(type);

                    var tbody = $('#kpi_staff_list_tbody');
                    tbody.empty();
                    $.each(response.staff, function(index, staff) {
                        tbody.append(buildStaffRow(type, index, staff));
                    });
                    $('#kpi_staff_list_content').show();
                } else {
                    $('#kpi_staff_list_empty').show();
                }
            },
            error: function() {
                $('#kpi_staff_list_loading').hide();
                $('#kpi_staff_list_empty').html('<p class="text-danger">Error loading staff list. Please try again.</p>').show();
            }
        });
    };

    $(document).on('click', '.kpi-clickable-stat', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var type = $(this).attr('data-type') || $(this).data('type');
        if (type) {
            window.showKpiStaffListModal(type);
        }
        return false;
    });

    $(document).ready(function() {
        ensureModalInBody();
    });

    setTimeout(function() {
        ensureModalInBody();
    }, 1000);

})();
