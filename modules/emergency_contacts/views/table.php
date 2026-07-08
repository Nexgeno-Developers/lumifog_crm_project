<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'contact_name',
	'relationship',
	'phone',
	'email',
	'is_primary',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'hr_staff_emergency_contacts';

$join  = [];
$where = [];

if (!is_admin() && !has_permission('hrm_hr_records', '', 'view')) {
	$staff_ids = $this->ci->hr_profile_model->get_staff_by_manager();
	if (count($staff_ids) > 0) {
		$where[] = 'AND ' . db_prefix() . 'hr_staff_emergency_contacts.staffid IN (' . implode(', ', $staff_ids) . ')';
	} else {
		$where[] = 'AND 1=2';
	}
}

$staffid = (int) $this->ci->input->post('memberid');
if ($staffid > 0) {
	$where[] = 'AND staffid = "' . $staffid . '"';
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['staffid', 'alt_phone', 'address', 'notes', 'date_created']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];

	$row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

	$subjectOutput = '<a href="#">' . html_escape($aRow['contact_name']) . '</a>';
	$subjectOutput .= '<div class="row-options">';

	$subjectOutput .= '<a href="#" onclick="view_emergency_contact(' . $aRow['id'] . '); return false;">' . _l('ec_view_contact') . '</a>';

	if (is_admin() || has_permission('hrm_hr_records', '', 'edit') || get_staff_user_id() == $aRow['staffid']) {
		$subjectOutput .= ' | <a href="#" onclick="edit_emergency_contact(this,' . $aRow['id'] . '); return false;"'
			. ' data-contact_name="' . html_escape($aRow['contact_name']) . '"'
			. ' data-relationship="' . html_escape($aRow['relationship']) . '"'
			. ' data-phone="' . html_escape($aRow['phone']) . '"'
			. ' data-alt_phone="' . html_escape($aRow['alt_phone']) . '"'
			. ' data-email="' . html_escape($aRow['email']) . '"'
			. ' data-address="' . html_escape($aRow['address']) . '"'
			. ' data-notes="' . html_escape($aRow['notes']) . '"'
			. ' data-is_primary="' . (int) $aRow['is_primary'] . '"'
			. '>' . _l('hr_edit') . '</a>';
	}

	if (is_admin() || has_permission('hrm_hr_records', '', 'delete')) {
		if (is_admin() || has_permission('hrm_hr_records', '', 'edit') || get_staff_user_id() == $aRow['staffid']) {
			$subjectOutput .= ' | ';
		}
		$subjectOutput .= '<a href="#" class="text-danger" onclick="delete_emergency_contact(' . $aRow['id'] . '); return false;">' . _l('delete') . '</a>';
	}

	$subjectOutput .= '</div>';

	$row[] = $subjectOutput;
	$row[] = html_escape($aRow['relationship']);
	$row[] = html_escape($aRow['phone']);
	$row[] = html_escape($aRow['email']);
	$row[] = ((int) $aRow['is_primary'] === 1)
		? '<span class="label label-success">' . _l('ec_primary') . '</span>'
		: '<span class="label label-default">' . _l('ec_secondary') . '</span>';

	$actions = '';
	$actions .= '<a href="#" class="btn btn-info btn-icon" onclick="view_emergency_contact(' . $aRow['id'] . '); return false;"><i class="fa fa-eye"></i></a>';
	if (is_admin() || has_permission('hrm_hr_records', '', 'edit') || get_staff_user_id() == $aRow['staffid']) {
		$actions .= ' <a href="#" class="btn btn-default btn-icon" onclick="edit_emergency_contact(this,' . $aRow['id'] . '); return false;"'
			. ' data-contact_name="' . html_escape($aRow['contact_name']) . '"'
			. ' data-relationship="' . html_escape($aRow['relationship']) . '"'
			. ' data-phone="' . html_escape($aRow['phone']) . '"'
			. ' data-alt_phone="' . html_escape($aRow['alt_phone']) . '"'
			. ' data-email="' . html_escape($aRow['email']) . '"'
			. ' data-address="' . html_escape($aRow['address']) . '"'
			. ' data-notes="' . html_escape($aRow['notes']) . '"'
			. ' data-is_primary="' . (int) $aRow['is_primary'] . '"'
			. '><i class="fa fa-pencil-square-o"></i></a>';
	}

	if (is_admin() || has_permission('hrm_hr_records', '', 'delete')) {
		$actions .= ' <a href="#" class="btn btn-danger btn-icon" onclick="delete_emergency_contact(' . $aRow['id'] . '); return false;"><i class="fa fa-remove"></i></a>';
	}

	$row[] = $actions;

	$output['aaData'][] = $row;
}
