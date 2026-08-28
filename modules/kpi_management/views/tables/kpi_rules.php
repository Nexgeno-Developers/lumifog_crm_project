<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'id',
    'title',
    'module',
    'is_active',
    'updated_at',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'kpi_rules';

$where = [];

$module = $CI->input->post('module');
if ($module !== null && $module !== '') {
    $where[] = 'AND module=' . $CI->db->escape($module);
}

$is_active = $CI->input->post('is_active');
if ($is_active !== null && $is_active !== '' && in_array((string) $is_active, ['0', '1'], true)) {
    $where[] = 'AND is_active=' . (int) $is_active;
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, ['description']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $id = (int) $aRow['id'];

    $row[] = $id;

    $title = e($aRow['title']);
    $titleLink = e($title);

    $titleLink .= '<div class="row-options">';
    if (has_permission('kpi_management', '', 'edit')) {
        $titleLink .= '<a href="#" onclick="kpi_open_rule_modal(' . $id . '); return false;">' . _l('edit') . '</a>';
    }
    if (has_permission('kpi_management', '', 'delete')) {
        $titleLink .= ' | <a href="#" class="text-danger" onclick="kpi_delete_rule(' . $id . '); return false;">' . _l('delete') . '</a>';
    }
    $titleLink .= '</div>';

    $row[] = $titleLink;

    $row[] = ucwords(e($aRow['module']));

    $row[] = $aRow['is_active'] == 1 ? '<span class="label label-success">' . _l('is_active_export') . '</span>' : '<span class="label label-default">' . _l('is_not_active_export') . '</span>';

    $updatedAt = $aRow['updated_at'] ?? '';
    if ($updatedAt === '' || $updatedAt === null || $updatedAt === '0000-00-00 00:00:00' || ! is_date($updatedAt)) {
        $row[] = '';
    } else {
        $row[] = '<span data-toggle="tooltip" data-title="' . e(_dt($updatedAt)) . '" class="text-has-action is-date">' . e(time_ago($updatedAt)) . '</span>';
    }

    $actions = '';
    if (has_permission('kpi_management', '', 'edit')) {
        $actions .= '<button type="button" class="btn btn-default btn-sm" onclick="kpi_open_rule_modal(' . $id . ');"><i class="fa-regular fa-pen-to-square"></i></button> ';
    }
    if (has_permission('kpi_management', '', 'delete')) {
        $actions .= '<button type="button" class="btn btn-danger btn-sm" onclick="kpi_delete_rule(' . $id . ');"><i class="fa-regular fa-trash-can"></i></button>';
    }

    $row[] = $actions;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}

