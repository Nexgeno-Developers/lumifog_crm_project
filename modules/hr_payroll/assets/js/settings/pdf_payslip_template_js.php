<script>
	
	$(function() {
		'use strict';

		var contract_id = '<?php echo isset($template_id) ? $template_id : ''; ?>';
		
		appValidateForm($('.pdf-payslip-template-form'), {
			'name': 'required',
			'payslip_template_id': 'required',
			
		});

		// Ensure TinyMCE inline content is posted with the form
		$('.pdf-payslip-template-form').on('submit', function () {
			var $form = $(this);
			var content = '';

			if (typeof tinymce !== 'undefined') {
				var editor = tinymce.activeEditor || (tinymce.editors && tinymce.editors[0]);
				if (editor) {
					content = editor.getContent();
				}
			}

			if (!content) {
				content = $('.tc-content.editable').html() || '';
			}

			$form.find('input[name="content"], textarea[name="content"]').remove();
			$('<textarea/>', {
				name: 'content',
				css: { display: 'none' }
			}).val(content).appendTo($form);
		});
	});

			
			var _templates = [];

			var editor_settings = {
				selector: 'div.editable',
				inline: true,
				theme: 'silver',
				relative_urls: false,
				remove_script_host: false,
				inline_styles: true,
				verify_html: false,
				cleanup: false,
				apply_source_formatting: false,
				valid_elements: '+*[*]',
				valid_children: "+body[style], +style[type]",
				file_browser_callback: elFinderBrowser,
				table_default_styles: {
					width: '100%'
				},
				fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
				pagebreak_separator: '<p pagebreak="true"></p>',
				plugins: [
				'advlist pagebreak autolink autoresize lists link image charmap hr',
				'searchreplace visualblocks visualchars code',
				'media nonbreaking table contextmenu',
				'paste textcolor colorpicker'
				],
				autoresize_bottom_margin: 50,
				insert_toolbar: 'image media quicktable | bullist numlist | h2 h3 | hr',
				selection_toolbar: 'save_button bold italic underline superscript | forecolor backcolor link | alignleft aligncenter alignright alignjustify | fontselect fontsizeselect h2 h3',
				contextmenu: "image media inserttable | cell row column deletetable | paste pastetext searchreplace | visualblocks pagebreak charmap | code",
				setup: function (editor) {

					editor.addCommand('mceSave', function () {
						save_pdf_payslip_content(true);
					});

					editor.addShortcut('Meta+S', '', 'mceSave');

					editor.on('MouseLeave blur', function () {
						if (tinymce.activeEditor && tinymce.activeEditor.isDirty()) {
							save_pdf_payslip_content();
						}
					});

					editor.on('MouseDown ContextMenu', function () {
						if (!is_mobile() && !$('.left-column').hasClass('hide')) {
							contract_full_view();
						}
					});

					editor.on('blur', function () {
						$.Shortcuts.start();
					});

					editor.on('focus', function () {
						$.Shortcuts.stop();
					});

				}
			}

			if (_templates.length > 0) {
				editor_settings.templates = _templates;
				editor_settings.plugins[3] = 'template ' + editor_settings.plugins[3];
				editor_settings.contextmenu = editor_settings.contextmenu.replace('inserttable', 'inserttable template');
			}

			if(is_mobile()) {

				editor_settings.theme = 'modern';
				editor_settings.mobile    = {};
				editor_settings.mobile.theme = 'mobile';
				editor_settings.mobile.toolbar = _tinymce_mobile_toolbar();

				editor_settings.inline = false;
				window.addEventListener("beforeunload", function (event) {
					if (tinymce.activeEditor && tinymce.activeEditor.isDirty()) {
						save_pdf_payslip_content();
					}
				});
			}

			tinymce.init(editor_settings);


			function insert_merge_field(field) {
				var key = $(field).text();
				tinymce.activeEditor.execCommand('mceInsertContent', false, key);
			}

			function contract_full_view() {
				$('.left-column').toggleClass('hide');
				$('.right-column').toggleClass('col-md-7');
				$('.right-column').toggleClass('col-md-12');
				$(window).trigger('resize');
			}


			function save_pdf_payslip_content(manual) {
				if (!contract_id) {
					return;
				}

				var editor = tinyMCE.activeEditor;
				if (!editor) {
					return;
				}

				var data = {};
				data.contract_id = contract_id;
				data.content = editor.getContent();
				$.post(admin_url + 'hr_payroll/save_pdf_payslip_data', data).done(function (response) {
					response = JSON.parse(response);
					if (typeof (manual) != 'undefined') {
						alert_float('success', response.message);
					}
					editor.save();
				}).fail(function (error) {
					var response = JSON.parse(error.responseText);
					alert_float('danger', response.message);
				});
			}

	</script>
