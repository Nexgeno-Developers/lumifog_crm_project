<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('gdpr_model');

$consentContacts = get_option('gdpr_enable_consent_for_contacts');
$aColumns        = [
    db_prefix() . 'contacts.firstname as firstname', 
    db_prefix() . 'contacts.lastname as lastname'
];
if (is_gdpr() && $consentContacts == '1') {
    array_push($aColumns, '1');
}

$aColumns = array_merge($aColumns, [
    db_prefix() . 'contacts.email as email',
    db_prefix() . 'clients.company as company',
    db_prefix() . 'contacts.phonenumber as phonenumber',
    'COALESCE(' . db_prefix() . 'contacts.stakeholder_role, ' . db_prefix() . 'contacts.title) as position_or_role',
    db_prefix() . 'contacts.last_login as last_login',
    db_prefix() . 'contacts.active as active',
]);

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'contacts';
$join         = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid=' . db_prefix() . 'contacts.userid',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id=' . db_prefix() . 'contacts.rel_id AND ' . db_prefix() . 'contacts.rel_type="lead"'
];

$custom_fields = get_table_custom_fields('contacts');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'contacts.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where = [];

// Base visibility: customer contacts and lead stakeholders, optionally filtered
$contactTypeFilter      = $this->ci->input->post('contact_type_filter');
$stakeholderTypeFilter  = $this->ci->input->post('stakeholder_type_filter');

if ($stakeholderTypeFilter) {
    // Only stakeholders with a specific stakeholder type (role)
    array_push($where, 'AND ' . db_prefix() . 'contacts.rel_type = "lead" AND ' . db_prefix() . 'contacts.is_stakeholder = 1 AND ' . db_prefix() . 'contacts.stakeholder_role = ' . $this->ci->db->escape($stakeholderTypeFilter));
} else {
    if ($contactTypeFilter === 'client') {
        // Only customer contacts
        array_push($where, 'AND ' . db_prefix() . 'contacts.rel_type = "customer"');
    } elseif ($contactTypeFilter === 'stakeholder') {
        // Only stakeholders (lead-related contacts marked as stakeholder)
        array_push($where, 'AND ' . db_prefix() . 'contacts.rel_type = "lead" AND ' . db_prefix() . 'contacts.is_stakeholder = 1');
    } else {
        // Default: include both customer contacts and lead stakeholders
        array_push($where, 'AND (' . db_prefix() . 'contacts.rel_type = "customer" OR (' . db_prefix() . 'contacts.rel_type = "lead" AND ' . db_prefix() . 'contacts.is_stakeholder = 1))');
    }
}

if (staff_cant('view', 'customers')) {
    array_push($where, 'AND (' . db_prefix() . 'contacts.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ') OR (' . db_prefix() . 'contacts.rel_type = "lead" AND ' . db_prefix() . 'contacts.is_stakeholder = 1))');
}

if ($this->ci->input->post('custom_view')) {
    $filter = $this->ci->input->post('custom_view');
    if (startsWith($filter, 'consent_')) {
        array_push($where, 'AND ' . db_prefix() . 'contacts.id IN (SELECT contact_id FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $this->ci->db->escape_str(strafter($filter, 'consent_')) . ' and action="opt-in" AND date IN (SELECT MAX(date) FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $this->ci->db->escape_str(strafter($filter, 'consent_')) . ' AND contact_id=' . db_prefix() . 'contacts.id))');
    }
}

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'contacts.id as id', 
    db_prefix() . 'contacts.userid as userid', 
    db_prefix() . 'contacts.rel_type as rel_type',
    db_prefix() . 'contacts.rel_id as rel_id',
    db_prefix() . 'contacts.is_stakeholder as is_stakeholder',
    db_prefix() . 'contacts.stakeholder_role as stakeholder_role',
    db_prefix() . 'contacts.title as title',
    'COALESCE(' . db_prefix() . 'contacts.stakeholder_role, ' . db_prefix() . 'contacts.title) as position_or_role',
    'is_primary', 
    '(SELECT count(*) FROM ' . db_prefix() . 'contacts c WHERE c.userid=' . db_prefix() . 'contacts.userid) as total_contacts', 
    db_prefix() . 'clients.registration_confirmed as registration_confirmed',
    db_prefix() . 'leads.name as lead_name',
    db_prefix() . 'leads.id as lead_id'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    $isStakeholder = isset($aRow['is_stakeholder']) && $aRow['is_stakeholder'] == 1 && isset($aRow['rel_type']) && $aRow['rel_type'] == 'lead';

    if ($isStakeholder) {
        // Lead stakeholder - link to lead instead of contact
        $rowName = '<img src="' . e(contact_profile_image_url($aRow['id'])) . '" class="client-profile-image-small mright5"><a href="#"  class="tw-font-medium" onclick="init_lead_modal_data(' . $aRow['rel_id'] . '); return false;">' . e($aRow['firstname']) . '</a>';
        $rowName .= '<div class="row-options tw-ml-9">';
        $rowName .= '<a href="#" onclick="init_lead_modal_data(' . $aRow['rel_id'] . '); return false;">' . _l('view') . '</a>';
    } else {
        // Customer contact
        $rowName = '<img src="' . e(contact_profile_image_url($aRow['id'])) . '" class="client-profile-image-small mright5"><a href="#"  class="tw-font-medium" onclick="contact(' . $aRow['userid'] . ',' . $aRow['id'] . ');return false;">' . e($aRow['firstname']) . '</a>';
        $rowName .= '<div class="row-options tw-ml-9">';
        $rowName .= '<a href="#" onclick="contact(' . $aRow['userid'] . ',' . $aRow['id'] . ');return false;">' . _l('edit') . '</a>';

        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1' && is_admin()) {
            $rowName .= ' | <a href="' . admin_url('clients/export/' . $aRow['id']) . '">
                 ' . _l('dt_button_export') . ' (' . _l('gdpr_short') . ')
              </a>';
        }

        if (staff_can('delete', 'customers') || is_customer_admin($aRow['userid'])) {
            if ($aRow['is_primary'] == 0 || ($aRow['is_primary'] == 1 && $aRow['total_contacts'] == 1)) {
                $rowName .= ' | <a href="' . admin_url('clients/delete_contact/' . $aRow['userid'] . '/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
            }
        }
    }

    $rowName .= '</div>';

    $row[] = $rowName;

    $row[] = $aRow['lastname'];

    if (is_gdpr() && $consentContacts == '1' && !$isStakeholder) {
        $consentHTML = '<p class="bold"><a href="#" onclick="view_contact_consent(' . $aRow['id'] . '); return false;">' . _l('view_consent') . '</a></p>';
        $consents    = $this->ci->gdpr_model->get_consent_purposes($aRow['id'], 'contact');

        foreach ($consents as $consent) {
            $consentHTML .= '<p style="margin-bottom:0px;">' . e($consent['name']) . (! empty($consent['consent_given']) ? '<i class="fa fa-check text-success pull-right"></i>' : '<i class="fa fa-remove text-danger pull-right"></i>') . '</p>';
        }
        $row[] = $consentHTML;
    } elseif (is_gdpr() && $consentContacts == '1') {
        $row[] = '-';
    }

    $row[] = !empty($aRow['email']) ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '-';

    if ($isStakeholder) {
        // Show lead name for stakeholders
        if (!empty($aRow['lead_name'])) {
            $row[] = '<a href="#" onclick="init_lead_modal_data(' . $aRow['rel_id'] . '); return false;">' . e($aRow['lead_name']) . '</a>';
        } else {
            $row[] = '-';
        }
    } else {
        // Show company for customer contacts
        if (! empty($aRow['company'])) {
            $row[] = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '">' . e($aRow['company']) . '</a>';
        } else {
            $row[] = '';
        }
    }

    $row[] = !empty($aRow['phonenumber']) ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '-';

    // Show position_or_role (stakeholder_role for stakeholders, title for contacts)
    $row[] = !empty($aRow['position_or_role']) ? e($aRow['position_or_role']) : '-';

    $row[] = (! empty($aRow['last_login']) ? '<span class="text-has-action is-date" data-toggle="tooltip" data-title="' . e(_dt($aRow['last_login'])) . '">' . e(time_ago($aRow['last_login'])) . '</span>' : '');

    // For stakeholders, show active status but don't allow toggling (they're always active)
    if ($isStakeholder) {
        $outputActive = '<div class="onoffswitch">
                    <input type="checkbox" disabled data-switch-url="' . admin_url() . 'clients/change_contact_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '"' . ($aRow['active'] == 1 ? ' checked' : '') . '>
                    <label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
                </div>';
        $outputActive .= '<span class="hide">' . ($aRow['active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';
    } else {
        $outputActive = '<div class="onoffswitch">
                    <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'clients/change_contact_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '"' . ($aRow['active'] == 1 ? ' checked' : '') . '>
                    <label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
                </div>';
        // For exporting
        $outputActive .= '<span class="hide">' . ($aRow['active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';
    }

    $row[] = $outputActive;

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options has-border-left';

    if (!$isStakeholder && isset($aRow['registration_confirmed']) && $aRow['registration_confirmed'] == 0) {
        $row['DT_RowClass'] .= ' row-border-warning requires-confirmation';
        $row['Data_Title']  = _l('customer_requires_registration_confirmation');
        $row['Data_Toggle'] = 'tooltip';
    }
    
    if ($isStakeholder) {
        $row['DT_RowClass'] .= ' stakeholder-row';
    }

    $row = hooks()->apply_filters('all_contacts_table_row', $row, $aRow);

    $output['aaData'][] = $row;
}
