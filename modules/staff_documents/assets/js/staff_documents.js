(function ($) {
    'use strict';

    var lang = typeof staffDocumentsLang !== 'undefined' ? staffDocumentsLang : {};

    function getLang() {
        return typeof staffDocumentsLang !== 'undefined' ? staffDocumentsLang : lang;
    }
    var selectedFile = null;

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) {
            return '0 B';
        }
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function showEmptyRow() {
        var $tbody = $('#staff-documents-tbody');
        $tbody.empty();
        $tbody.append(
            '<tr class="staff-documents-empty-row">' +
            '<td colspan="6" class="text-center text-muted">' + escapeHtml(lang.no_documents || 'No documents found') + '</td>' +
            '</tr>'
        );
    }

    function buildDocumentRow(doc) {
        var deleteBtn = '';
        if (lang.can_delete) {
            deleteBtn =
                '<button type="button" class="btn btn-danger btn-sm staff-documents-delete-btn" title="' + escapeHtml(lang.delete) + '">' +
                '<i class="fa fa-trash"></i></button>';
        }

        return (
            '<tr data-id="' + doc.id + '"' +
            ' data-file-type="' + escapeHtml(doc.file_type) + '"' +
            ' data-serve-url="' + escapeHtml(doc.serve_url) + '"' +
            ' data-download-url="' + escapeHtml(doc.download_url) + '">' +
            '<td>' + doc.original_name + '</td>' +
            '<td>' + escapeHtml(doc.file_type.toUpperCase()) + '</td>' +
            '<td>' + formatFileSize(doc.file_size) + '</td>' +
            '<td>' + doc.description + '</td>' +
            '<td>' + escapeHtml(doc.dateadded) + '</td>' +
            '<td><div class="btn-group">' +
            '<button type="button" class="btn btn-default btn-sm staff-documents-preview-btn" title="' + escapeHtml(lang.preview) + '">' +
            '<i class="fa fa-eye"></i></button>' +
            '<a href="' + escapeHtml(doc.download_url) + '" class="btn btn-default btn-sm" title="' + escapeHtml(lang.download) + '">' +
            '<i class="fa fa-download"></i></a>' +
            deleteBtn +
            '</div></td></tr>'
        );
    }

    function appendDocumentRow(doc) {
        var $tbody = $('#staff-documents-tbody');
        $tbody.find('.staff-documents-empty-row').remove();
        $tbody.prepend(buildDocumentRow(doc));
    }

    function setSelectedFile(file) {
        selectedFile = file;
        var $badge = $('#staff-documents-file-badge');
        var $btn = $('#staff-documents-upload-btn');

        if (file) {
            $badge.text(file.name).removeClass('hide');
            $btn.prop('disabled', false);
        } else {
            $badge.text('').addClass('hide');
            $btn.prop('disabled', true);
            $('#staff-documents-file-input').val('');
        }
    }

    function uploadFile(file) {
        var l = getLang();

        if (!l.can_create || !file) {
            if (!file) {
                alert_float('warning', l.select_file || 'Please select a file first.');
            } else if (!l.can_create) {
                alert_float('danger', l.access_denied || 'You do not have permission to upload documents.');
            }
            return;
        }

        var allowedExtensions = ['pdf', 'csv', 'xls', 'xlsx'];
        var ext = file.name.split('.').pop().toLowerCase();

        if (allowedExtensions.indexOf(ext) === -1) {
            alert_float('danger', 'Invalid file type. Only PDF, CSV, XLS and XLSX are allowed.');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            alert_float('danger', 'File exceeds the maximum size of 10MB.');
            return;
        }

        var formData = new FormData();
        formData.append('document_file', file);
        formData.append('description', $('#staff_document_description').val() || '');

        if (typeof csrfData !== 'undefined') {
            formData.append(csrfData.token_name, csrfData.hash);
        }

        var $btn = $('#staff-documents-upload-btn');
        var btnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + escapeHtml(l.upload_btn || 'Upload'));
        $('#staff-documents-upload-progress').addClass('active');

        $.ajax({
            url: l.upload_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                $('#staff-documents-upload-progress').removeClass('active');
                $btn.html(btnHtml);

                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        response = null;
                    }
                }

                if (response && response.success) {
                    alert_float('success', response.message);
                    setSelectedFile(null);
                    $('#staff_document_description').val('');
                    window.location.reload();
                } else {
                    $btn.prop('disabled', false);
                    alert_float('danger', (response && response.message) ? response.message : 'Upload failed');
                }
            },
            error: function (xhr) {
                $('#staff-documents-upload-progress').removeClass('active');
                $btn.prop('disabled', false).html(btnHtml);

                var message = 'Upload failed';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) {
                            message = parsed.message;
                        }
                    } catch (e) {
                        // keep default message
                    }
                }

                alert_float('danger', message);
            }
        });
    }

    function initDropzone() {
        var $dropzone = $('#staff-documents-dropzone');
        var $fileInput = $('#staff-documents-file-input');

        if (!$dropzone.length) {
            return;
        }

        $dropzone.on('click', function (e) {
            if ($(e.target).is('input')) {
                return;
            }
            $fileInput.trigger('click');
        });

        $fileInput.on('change', function () {
            if (this.files && this.files.length > 0) {
                setSelectedFile(this.files[0]);
            }
        });

        $dropzone.on('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $dropzone.on('dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $dropzone.on('drop', function (e) {
            var files = e.originalEvent.dataTransfer.files;
            if (files && files.length > 0) {
                setSelectedFile(files[0]);
            }
        });

        $('#staff-documents-upload-btn').on('click', function (e) {
            e.preventDefault();
            var file = selectedFile;

            if (!file && $('#staff-documents-file-input')[0] && $('#staff-documents-file-input')[0].files.length) {
                file = $('#staff-documents-file-input')[0].files[0];
                setSelectedFile(file);
            }

            uploadFile(file);
        });
    }

    function buildTableHtml(rows) {
        if (!rows || rows.length === 0) {
            return '<p class="text-muted text-center">' + escapeHtml(lang.preview_error || 'No data') + '</p>';
        }

        var html = '<div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr>';

        rows[0].forEach(function (cell) {
            html += '<th>' + escapeHtml(String(cell !== null && cell !== undefined ? cell : '')) + '</th>';
        });

        html += '</tr></thead><tbody>';

        for (var i = 1; i < rows.length; i++) {
            html += '<tr>';
            rows[i].forEach(function (cell) {
                html += '<td>' + escapeHtml(String(cell !== null && cell !== undefined ? cell : '')) + '</td>';
            });
            html += '</tr>';
        }

        html += '</tbody></table></div>';

        return html;
    }

    function previewCsv(serveUrl, $body) {
        $.ajax({
            url: serveUrl,
            type: 'GET',
            dataType: 'text',
            success: function (csvText) {
                Papa.parse(csvText, {
                    complete: function (results) {
                        if (results.data && results.data.length > 0) {
                            $body.html(buildTableHtml(results.data));
                        } else {
                            $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
                        }
                    },
                    error: function () {
                        $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
                    }
                });
            },
            error: function () {
                $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
            }
        });
    }

    function previewSpreadsheet(serveUrl, $body) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', serveUrl, true);
        xhr.responseType = 'arraybuffer';

        xhr.onload = function () {
            if (xhr.status !== 200) {
                $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
                return;
            }

            try {
                var data = new Uint8Array(xhr.response);
                var workbook = XLSX.read(data, { type: 'array' });
                var sheetNames = workbook.SheetNames;

                if (sheetNames.length === 0) {
                    $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
                    return;
                }

                var tabsHtml = '<ul class="nav nav-tabs staff-documents-sheet-tabs" role="tablist">';
                var contentHtml = '<div class="tab-content">';

                sheetNames.forEach(function (name, index) {
                    var active = index === 0 ? 'active' : '';
                    var tabId = 'staff-doc-sheet-' + index;

                    tabsHtml +=
                        '<li class="nav-item ' + active + '">' +
                        '<a class="nav-link ' + active + '" href="#' + tabId + '" data-toggle="tab" role="tab">' +
                        escapeHtml(lang.sheet || 'Sheet') + ' ' + escapeHtml(name) +
                        '</a></li>';

                    var sheet = workbook.Sheets[name];
                    var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

                    contentHtml +=
                        '<div class="tab-pane ' + active + '" id="' + tabId + '" role="tabpanel">' +
                        buildTableHtml(rows) +
                        '</div>';
                });

                tabsHtml += '</ul>';
                contentHtml += '</div>';

                $body.html(tabsHtml + contentHtml);
            } catch (e) {
                $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
            }
        };

        xhr.onerror = function () {
            $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
        };

        xhr.send();
    }

    function previewDocument($row) {
        var fileType = ($row.data('file-type') || '').toLowerCase();
        var serveUrl = $row.data('serve-url');
        var downloadUrl = $row.data('download-url');
        var fileName = $row.find('td:first').text();

        var $modal = $('#staff-documents-preview-modal');
        var $body = $('#staff-documents-preview-body');
        var $title = $('#staff-documents-preview-title');
        var $download = $('#staff-documents-preview-download');

        $title.text(fileName);
        $download.attr('href', downloadUrl);
        $body.html(
            '<div class="staff-documents-loading">' +
            '<i class="fa fa-spinner fa-spin"></i> Loading...</div>'
        );

        $modal.modal('show');

        if (fileType === 'pdf') {
            $body.html('<iframe src="' + escapeHtml(serveUrl) + '"></iframe>');
        } else if (fileType === 'csv') {
            previewCsv(serveUrl, $body);
        } else if (fileType === 'xls' || fileType === 'xlsx') {
            previewSpreadsheet(serveUrl, $body);
        } else {
            $body.html('<p class="text-danger text-center">' + escapeHtml(lang.preview_error) + '</p>');
        }
    }

    function deleteDocument(id, $row) {
        if (typeof confirm_delete === 'function') {
            if (!confirm_delete()) {
                return;
            }
        } else if (!window.confirm(lang.confirm_delete || 'Are you sure?')) {
            return;
        }

        $.post(lang.delete_url_base + id, function (response) {
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    response = { success: false };
                }
            }

            if (response.success) {
                alert_float('success', response.message);
                $row.remove();

                if ($('#staff-documents-tbody tr').length === 0) {
                    showEmptyRow();
                }
            } else {
                alert_float('danger', response.message || 'Delete failed');
            }
        });
    }

    function bindTableEvents() {
        $(document).on('click', '.staff-documents-preview-btn', function (e) {
            e.preventDefault();
            previewDocument($(this).closest('tr'));
        });

        $(document).on('click', '.staff-documents-delete-btn', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var id = $row.data('id');
            deleteDocument(id, $row);
        });
    }

    $(function () {
        initDropzone();
        bindTableEvents();

        if ($('#staff-documents-filter-form').length) {
            init_selectpicker();
            init_datepicker();
        }

        $('#staff-documents-preview-modal').on('hidden.bs.modal', function () {
            $('#staff-documents-preview-body').empty();
        });
    });
})(jQuery);
