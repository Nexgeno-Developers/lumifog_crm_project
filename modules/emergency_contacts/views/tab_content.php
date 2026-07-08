<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">
		<?php if ($member->staffid == get_staff_user_id() || has_permission('hrm_hr_records', '', 'create') || has_permission('hrm_hr_records', '', 'edit')) { ?>
			<div class="_buttons">
				<a href="#" onclick="new_emergency_contact(); return false;" class="btn btn-primary pull-left display-block">
					<?php echo _l('ec_add_contact'); ?>
				</a>
			</div>
		<?php } ?>
	</div>
</div>

<div class="clearfix"></div>
<br>
<div class="row">
	<div class="col-md-12">
		<?php render_datatable([
			'<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="emergency_contacts_table"><label></label></div>',
			_l('ec_contact_name'),
			_l('ec_relationship'),
			_l('ec_phone'),
			_l('ec_email'),
			_l('ec_is_primary'),
			_l('options'),
		], 'emergency_contacts_table'); ?>
	</div>
</div>

<div class="modal fade" id="emergency_contact_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<?php echo form_open(admin_url('emergency_contacts/save'), ['id' => 'emergency_contact_form']); ?>
		<?php echo form_hidden('staffid', $staffid); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">
					<span class="view-title hide"><?php echo _l('ec_view_contact'); ?></span>
					<span class="edit-title hide"><?php echo _l('ec_edit_contact'); ?></span>
					<span class="add-title"><?php echo _l('ec_add_contact'); ?></span>
				</h4>
			</div>
			<div class="modal-body">
				<div id="emergency_contact_id"></div>
				<div class="form">
					<div class="row">
						<div class="col-md-6">
							<?php echo render_input('contact_name', 'ec_contact_name'); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_input('relationship', 'ec_relationship'); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<?php echo render_input('phone', 'ec_phone'); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_input('alt_phone', 'ec_alt_phone'); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<?php echo render_input('email', 'ec_email', '', 'email'); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<?php echo render_textarea('address', 'ec_address'); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<?php echo render_textarea('notes', 'ec_notes'); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox checkbox-primary">
								<input type="checkbox" name="is_primary" id="is_primary" value="1">
								<label for="is_primary"><?php echo _l('ec_is_primary'); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_close'); ?></button>
				<button type="submit" class="btn btn-primary ec-save-btn"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
</div>
