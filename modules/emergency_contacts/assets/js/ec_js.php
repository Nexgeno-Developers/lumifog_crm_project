<script>
	(function($) {
		'use strict';

		appValidateForm($('#emergency_contact_form'), {
			contact_name: 'required',
			phone: 'required',
			email: {
				email: true
			}
		});

		var EmergencyContactsServerParams = {
			"memberid": "[name='memberid']",
			"member_view": "[name='member_view']"
		};

		var emergency_contacts_table = $('.table-emergency_contacts_table');
		initDataTable(emergency_contacts_table, admin_url + 'emergency_contacts/table', [0], [0], EmergencyContactsServerParams, [1, 'desc']);

		var hidden_columns = [0];
		$('.table-emergency_contacts_table').DataTable().columns(hidden_columns).visible(false, false);
	})(jQuery);

	function set_emergency_contact_readonly_state(isReadonly) {
		'use strict';

		$('#emergency_contact_form')
			.find('input[type!="hidden"], textarea, select')
			.prop('disabled', isReadonly)
			.prop('readonly', isReadonly);

		$('#is_primary').prop('disabled', isReadonly);
		$('.ec-save-btn').toggleClass('hide', isReadonly);
	}

	function new_emergency_contact() {
		'use strict';

		$('#emergency_contact_form')[0].reset();
		$('#emergency_contact_id').html('');
		$('#is_primary').prop('checked', false);

		set_emergency_contact_readonly_state(false);
		$('.view-title').addClass('hide');
		$('.edit-title').addClass('hide');
		$('.add-title').removeClass('hide');

		$('#emergency_contact_modal').modal('show');
	}

	function edit_emergency_contact(invoker, id) {
		'use strict';

		$('#emergency_contact_form')[0].reset();
		$('#emergency_contact_id').html(hidden_input('id', id));
		$('#emergency_contact_form input[name="contact_name"]').val($(invoker).data('contact_name'));
		$('#emergency_contact_form input[name="relationship"]').val($(invoker).data('relationship'));
		$('#emergency_contact_form input[name="phone"]').val($(invoker).data('phone'));
		$('#emergency_contact_form input[name="alt_phone"]').val($(invoker).data('alt_phone'));
		$('#emergency_contact_form input[name="email"]').val($(invoker).data('email'));
		$('#emergency_contact_form textarea[name="address"]').val($(invoker).data('address'));
		$('#emergency_contact_form textarea[name="notes"]').val($(invoker).data('notes'));
		$('#is_primary').prop('checked', parseInt($(invoker).data('is_primary')) === 1);

		set_emergency_contact_readonly_state(false);
		$('.view-title').addClass('hide');
		$('.add-title').addClass('hide');
		$('.edit-title').removeClass('hide');

		$('#emergency_contact_modal').modal('show');
	}

	function view_emergency_contact(id) {
		'use strict';

		$('#emergency_contact_form')[0].reset();
		$('#emergency_contact_id').html('');

		$.get(admin_url + 'emergency_contacts/view/' + id).done(function(response) {
			var result = response;

			if (typeof response === 'string') {
				try {
					result = JSON.parse(response);
				} catch (e) {
					result = { success: false, message: 'Unexpected response.' };
				}
			}

			if (!result.success) {
				alert_float('danger', result.message);
				return;
			}

			var contact = result.data;
			$('#emergency_contact_form input[name="contact_name"]').val(contact.contact_name);
			$('#emergency_contact_form input[name="relationship"]').val(contact.relationship);
			$('#emergency_contact_form input[name="phone"]').val(contact.phone);
			$('#emergency_contact_form input[name="alt_phone"]').val(contact.alt_phone);
			$('#emergency_contact_form input[name="email"]').val(contact.email);
			$('#emergency_contact_form textarea[name="address"]').val(contact.address);
			$('#emergency_contact_form textarea[name="notes"]').val(contact.notes);
			$('#is_primary').prop('checked', parseInt(contact.is_primary) === 1);

			set_emergency_contact_readonly_state(true);
			$('.add-title').addClass('hide');
			$('.edit-title').addClass('hide');
			$('.view-title').removeClass('hide');

			$('#emergency_contact_modal').modal('show');
		}).fail(function() {
			alert_float('danger', 'Request failed.');
		});
	}

	function delete_emergency_contact(id) {
		'use strict';

		if (!confirm(app.lang.delete_confirm || 'Are you sure?')) {
			return;
		}

		$.get(admin_url + 'emergency_contacts/delete/' + id).done(function(response) {
			var result = response;

			if (typeof response === 'string') {
				try {
					result = JSON.parse(response);
				} catch (e) {
					result = { success: false, message: 'Unexpected response.' };
				}
			}

			if (result.success) {
				alert_float('success', result.message);
				$('.table-emergency_contacts_table').DataTable().ajax.reload(null, false);
			} else {
				alert_float('danger', result.message);
			}
		}).fail(function() {
			alert_float('danger', 'Request failed.');
		});
	}
</script>
