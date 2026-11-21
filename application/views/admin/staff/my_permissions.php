<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Override of admin/staff/permissions.php (Perfex "my_" view override - survives updates).
 * Used by Setup > Roles > Edit role and by Staff > member > Permissions.
 *
 * Same inputs as core (names, ids, classes, data-* attributes, one <tr data-name> per
 * feature inside table.roles), so saving and core JS (view / view own toggling, role
 * auto-fill, administrator toggle) keep working. Only the layout changes:
 *   - collapsible groups: Perfex areas (Sales, CRM, ...) and one group per module
 *   - matrix columns: View (Own) | View (All) | Create | Edit | Delete | Other
 *   - search box, expand/collapse all, select all / clear per group
 */

if (isset($member)) {
    $is_admin = is_admin($member->staffid);
}

$perm_all = get_available_staff_permissions($funcData);

// Perfex core features -> area groups
$perm_core_groups = [
    'sales'    => ['label' => _l('perm_ui_group_sales'),    'features' => ['invoices', 'estimates', 'proposals', 'credit_notes', 'payments', 'subscriptions', 'items', 'estimate_request']],
    'crm'      => ['label' => _l('perm_ui_group_crm'),      'features' => ['customers', 'leads', 'contracts']],
    'projects' => ['label' => _l('perm_ui_group_projects'), 'features' => ['projects', 'tasks', 'checklist_templates']],
    'finance'  => ['label' => _l('perm_ui_group_finance'),  'features' => ['expenses', 'reports', 'bulk_pdf_exporter']],
    'kb'       => ['label' => _l('perm_ui_group_kb'),       'features' => ['knowledge_base']],
    'staff'    => ['label' => _l('perm_ui_group_staff'),    'features' => ['staff', 'roles', 'settings', 'email_templates']],
];

// Module features -> module name, read from each active module's top-level PHP files
// (register_staff_capabilities('feature' | CONSTANT, ...)). Top level only: some modules
// register from a separate init_*.php next to the main file; vendor folders are skipped.
$perm_module_of = (function () {
    $map = [];
    $CI  = &get_instance();
    foreach ($CI->app_modules->get_activated() as $system_name => $module) {
        $files = [];
        if (!empty($module['path']) && is_dir($module['path'])) {
            $files = glob(rtrim($module['path'], '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        }
        if (!empty($module['init_file']) && is_file($module['init_file'])) {
            $files[] = $module['init_file'];
        }
        $src = '';
        foreach (array_unique($files) as $file) {
            $src .= (string) @file_get_contents($file) . "\n";
        }
        if (!preg_match_all('/register_staff_capabilities\(\s*(?:\'([^\']+)\'|"([^"]+)"|([A-Z][A-Z0-9_]*))\s*,/', $src, $hits, PREG_SET_ORDER)) {
            continue;
        }
        $label = trim((string) ($module['headers']['module_name'] ?? '')) ?: ucwords(str_replace('_', ' ', $system_name));
        foreach ($hits as $h) {
            $key = ($h[1] ?? '') !== '' ? $h[1] : (($h[2] ?? '') !== '' ? $h[2] : (defined($h[3] ?? '') ? constant($h[3]) : ''));
            if ($key !== '' && !isset($map[$key])) {
                $map[$key] = $label;
            }
        }
    }

    return $map;
})();

// Build groups: core areas first (fixed order), then modules A-Z, then anything unknown
$perm_groups = [];
foreach ($perm_core_groups as $gid => $g) {
    $perm_groups['core_' . $gid] = ['label' => $g['label'], 'is_module' => false, 'features' => []];
}
$perm_core_lookup = [];
foreach ($perm_core_groups as $gid => $g) {
    foreach ($g['features'] as $f) {
        $perm_core_lookup[$f] = 'core_' . $gid;
    }
}
$perm_module_groups = [];
$perm_other         = [];
foreach ($perm_all as $feature => $permission) {
    if (isset($perm_core_lookup[$feature])) {
        $perm_groups[$perm_core_lookup[$feature]]['features'][$feature] = $permission;
    } elseif (isset($perm_module_of[$feature])) {
        $perm_module_groups[$perm_module_of[$feature]][$feature] = $permission;
    } else {
        $perm_other[$feature] = $permission;
    }
}
ksort($perm_module_groups, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($perm_module_groups as $label => $features) {
    $perm_groups['mod_' . md5($label)] = ['label' => $label, 'is_module' => true, 'features' => $features];
}
if ($perm_other) {
    $perm_groups['core_other'] = ['label' => _l('perm_ui_group_other'), 'is_module' => false, 'features' => $perm_other];
}
$perm_groups = array_filter($perm_groups, function ($g) {
    return !empty($g['features']);
});

$perm_columns = [
    'view_own' => _l('perm_ui_col_view_own'),
    'view'     => _l('perm_ui_col_view'),
    'create'   => _l('perm_ui_col_create'),
    'edit'     => _l('perm_ui_col_edit'),
    'delete'   => _l('perm_ui_col_delete'),
];

/**
 * One capability checkbox - same checked/disabled rules and markup as core permissions.php.
 * $show_label: matrix columns show only the box (label kept for the checkbox style +
 * screen readers); the "Other" column shows the capability name.
 */
$perm_checkbox = function ($feature, $permission, $capability, $name, $show_label) use (&$is_admin, &$member, &$role) {
    $checked  = '';
    $disabled = '';
    if ((isset($is_admin) && $is_admin)
        || (is_array($name) && isset($name['not_applicable']) && $name['not_applicable'])
        || (
            ($capability == 'view_own' || $capability == 'view'
                && array_key_exists('view_own', $permission['capabilities']) && array_key_exists('view', $permission['capabilities']))
            && (
                (isset($member) && staff_can(($capability == 'view' ? 'view_own' : 'view'), $feature, $member->staffid))
                || (isset($role) && has_role_permission($role->roleid, ($capability == 'view' ? 'view_own' : 'view'), $feature))
            )
        )
    ) {
        $disabled = ' disabled ';
    } elseif ((isset($member) && staff_can($capability, $feature, $member->staffid))
        || isset($role) && has_role_permission($role->roleid, $capability, $feature)) {
        $checked = ' checked ';
    }

    $label_text = !is_array($name) ? $name : $name['name'];
    $html  = '<div class="checkbox perm-ui-cb' . ($show_label ? '' : ' perm-ui-cb-matrix') . '">';
    $html .= '<input'
        . ($capability == 'view' ? ' data-can-view' : '')
        . ($capability == 'view_own' ? ' data-can-view-own' : '')
        . ((is_array($name) && isset($name['not_applicable']) && $name['not_applicable']) ? ' data-not-applicable="true"' : '')
        . ' type="checkbox"' . e($checked) . ' class="capability"'
        . ' id="' . $feature . '_' . $capability . '"'
        . ' name="permissions[' . e($feature) . '][]"'
        . ' value="' . e($capability) . '"' . e($disabled) . '>';
    $html .= '<label for="' . $feature . '_' . $capability . '" title="' . e(strip_tags($label_text)) . '">'
        . ($show_label ? $label_text : '<span class="sr-only">' . $label_text . '</span>')
        . '</label>';
    if (isset($permission['help']) && array_key_exists($capability, $permission['help'])) {
        $html .= ' <i class="fa-regular fa-circle-question" data-toggle="tooltip" data-title="' . e($permission['help'][$capability]) . '"></i>';
    }
    $html .= '</div>';

    return $html;
};
?>
<div class="perm-ui">
    <div class="perm-ui-toolbar">
        <div class="perm-ui-search">
            <i class="fa fa-search"></i>
            <input type="text" class="form-control perm-ui-search-input"
                placeholder="<?= e(_l('perm_ui_search')); ?>"
                aria-label="<?= e(_l('perm_ui_search')); ?>">
        </div>
        <div class="perm-ui-toolbar-actions">
            <button type="button" class="btn btn-default btn-sm" data-perm-expand-all><?= _l('perm_ui_expand_all'); ?></button>
            <button type="button" class="btn btn-default btn-sm" data-perm-collapse-all><?= _l('perm_ui_collapse_all'); ?></button>
        </div>
    </div>

    <div class="perm-ui-wrap">
        <table class="table table-bordered roles no-margin perm-ui-table">
            <colgroup>
                <col>
                <?php foreach ($perm_columns as $col_label) { ?>
                <col class="perm-ui-col-cap">
                <?php } ?>
            </colgroup>
            <tbody>
                <?php foreach ($perm_groups as $gid => $group) { ?>
                <tr class="perm-ui-group" data-perm-group="<?= e($gid); ?>">
                    <td colspan="6">
                        <div class="perm-ui-group-row">
                        <button type="button" class="perm-ui-group-toggle" data-perm-toggle="<?= e($gid); ?>" aria-expanded="false">
                            <i class="fa fa-chevron-right perm-ui-chevron"></i>
                            <span class="perm-ui-group-name"><?= e($group['label']); ?></span>
                            <?php if ($group['is_module']) { ?>
                            <span class="perm-ui-tag"><?= _l('perm_ui_module'); ?></span>
                            <?php } ?>
                            <span class="perm-ui-count"><?= e(count($group['features']) === 1 ? _l('perm_ui_features_count_one') : _l('perm_ui_features_count', count($group['features']))); ?></span>
                        </button>
                        <span class="perm-ui-group-actions">
                            <a href="#" data-perm-select-all="<?= e($gid); ?>"><?= _l('perm_ui_select_all'); ?></a>
                            <span class="text-muted">|</span>
                            <a href="#" data-perm-clear="<?= e($gid); ?>"><?= _l('perm_ui_clear'); ?></a>
                        </span>
                        </div>
                    </td>
                </tr>
                <tr class="perm-ui-head hide" data-perm-in="<?= e($gid); ?>">
                    <th><?= _l('perm_ui_col_feature'); ?></th>
                    <?php foreach ($perm_columns as $col_label) { ?>
                    <th class="text-center"><?= e($col_label); ?></th>
                    <?php } ?>
                </tr>
                <?php foreach ($group['features'] as $feature => $permission) { ?>
                <tr data-name="<?= e($feature); ?>" data-perm-in="<?= e($gid); ?>" class="perm-ui-row hide"
                    data-perm-search="<?= e(mb_strtolower(strip_tags($permission['name'] . ' ' . $group['label']))); ?>">
                    <td class="perm-ui-feature">
                        <b><?= e($permission['name']); ?></b>
                        <?php if (isset($permission['before'])) { echo $permission['before']; } ?>
                        <?php
                        // Capabilities outside the 5 standard columns (e.g. "View All Templates"),
                        // listed under the feature name instead of an extra column.
                        $perm_extra = '';
                        foreach ($permission['capabilities'] as $cap => $name) {
                            if (!array_key_exists($cap, $perm_columns)) {
                                $perm_extra .= $perm_checkbox($feature, $permission, $cap, $name, true);
                            }
                        }
                        if ($perm_extra !== '' || isset($permission['after'])) { ?>
                        <div class="perm-ui-extra">
                            <?= $perm_extra; ?>
                            <?php if (isset($permission['after'])) { echo $permission['after']; } ?>
                        </div>
                        <?php } ?>
                    </td>
                    <?php foreach ($perm_columns as $cap => $col_label) { ?>
                    <td class="text-center perm-ui-cell">
                        <?php if (array_key_exists($cap, $permission['capabilities'])) {
                            echo $perm_checkbox($feature, $permission, $cap, $permission['capabilities'][$cap], false);
                        } else { ?>
                        <span class="perm-ui-na">&ndash;</span>
                        <?php } ?>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
                <?php } ?>
                <tr class="perm-ui-empty hide">
                    <td colspan="6" class="text-center text-muted"><?= _l('perm_ui_no_results'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    .perm-ui-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .perm-ui-search { position: relative; flex: 1 1 260px; max-width: 420px; }
    .perm-ui-search .fa-search { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .perm-ui-search input { padding-left: 30px; }
    .perm-ui-toolbar-actions { display: flex; gap: 6px; }
    /* Fits its panel: fixed layout, feature column takes the rest, 5 equal capability columns */
    .perm-ui-wrap { width: 100%; }
    .perm-ui-table { table-layout: fixed; width: 100%; }
    .perm-ui-table col.perm-ui-col-cap { width: 64px; }
    .perm-ui-table > tbody > tr.perm-ui-group > td { background: #f3f4f6; padding: 0; border-left: 0; border-right: 0; }
    .perm-ui-group-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .perm-ui-group-toggle { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; border: 0; background: transparent; padding: 10px 12px; font-weight: 600; color: #111827; text-align: left; }
    .perm-ui-group-toggle:focus { outline: 2px solid #93c5fd; outline-offset: -2px; }
    .perm-ui-chevron { width: 12px; color: #6b7280; transition: transform .15s ease; }
    .perm-ui-group.is-open .perm-ui-chevron { transform: rotate(90deg); }
    .perm-ui-tag { font-size: 11px; font-weight: 500; color: #4338ca; background: #eef2ff; border-radius: 9999px; padding: 1px 8px; }
    .perm-ui-count { font-size: 12px; font-weight: 400; color: #6b7280; }
    .perm-ui-group-actions { padding: 10px 12px; font-size: 12px; white-space: nowrap; }
    .perm-ui-table > tbody > tr.perm-ui-head > th { background: #fafafa; font-size: 11px; font-weight: 600; color: #4b5563; line-height: 1.2; padding: 8px 4px; vertical-align: middle; white-space: normal; }
    .perm-ui-table > tbody > tr.perm-ui-head > th:first-child { padding-left: 12px; }
    .perm-ui-feature { word-wrap: break-word; padding-left: 12px !important; }
    .perm-ui-cell { vertical-align: middle !important; padding: 8px 4px !important; }
    .perm-ui-cb { margin: 0; }
    /* Matrix checkbox: an exact 17x17 box centred in the cell (core draws the box 20px left
       of the label text, which drifts when the text is hidden) */
    .perm-ui-cb.perm-ui-cb-matrix { display: inline-block; position: relative; width: 17px; height: 17px; padding: 0; margin: 0; vertical-align: middle; }
    .perm-ui-cb-matrix input[type=checkbox] { position: absolute; left: 0; top: 0; width: 17px; height: 17px; margin: 0; cursor: pointer; }
    .perm-ui-cb-matrix label { position: absolute; left: 0; top: 0; width: 17px; height: 17px; padding: 0; margin: 0; }
    .perm-ui-cb-matrix label:before, .perm-ui-cb-matrix label:after { left: 0; top: 0; margin-left: 0; }
    .perm-ui-cell .fa-circle-question { margin-left: 4px; vertical-align: middle; color: #9ca3af; }
    .perm-ui-extra { margin-top: 6px; }
    .perm-ui-extra .perm-ui-cb { margin: 2px 0; font-size: 12px; }
    .perm-ui-na { color: #d1d5db; }
</style>

<script>
(function () {
    'use strict';
    function init() {
        if (!window.jQuery) { return setTimeout(init, 50); }
        var $ = window.jQuery;
        var $table = $('.perm-ui-table');
        if (!$table.length || $table.data('permUiReady')) { return; }
        $table.data('permUiReady', true);
        var $search = $('.perm-ui-search-input');

        function rowsOf(gid) { return $table.find('tr[data-perm-in="' + gid + '"]'); }

        function setOpen(gid, open) {
            var $g = $table.find('tr.perm-ui-group[data-perm-group="' + gid + '"]');
            $g.toggleClass('is-open', open).find('[data-perm-toggle]').attr('aria-expanded', open ? 'true' : 'false');
            rowsOf(gid).toggleClass('hide', !open);
        }

        $table.on('click', '[data-perm-toggle]', function () {
            var gid = $(this).attr('data-perm-toggle');
            setOpen(gid, !$table.find('tr.perm-ui-group[data-perm-group="' + gid + '"]').hasClass('is-open'));
        });

        $('[data-perm-expand-all]').on('click', function () {
            $search.val('');
            $table.find('tr.perm-ui-group').removeClass('hide').each(function () { setOpen($(this).attr('data-perm-group'), true); });
            $table.find('tr.perm-ui-empty').addClass('hide');
        });
        $('[data-perm-collapse-all]').on('click', function () {
            $table.find('tr.perm-ui-group').each(function () { setOpen($(this).attr('data-perm-group'), false); });
        });

        // Select all: tick every enabled capability; where a feature has both "view" and
        // "view own", tick "view" (all) only - core JS then disables "view own".
        $table.on('click', '[data-perm-select-all]', function (e) {
            e.preventDefault();
            var gid = $(this).attr('data-perm-select-all');
            setOpen(gid, true);
            $table.find('tr.perm-ui-row[data-perm-in="' + gid + '"]').each(function () {
                var $row = $(this), hasView = $row.find('[data-can-view]').length > 0;
                $row.find('input.capability').each(function () {
                    var $cb = $(this);
                    if ($cb.prop('disabled') || $cb.is('[data-not-applicable="true"]')) { return; }
                    if (hasView && $cb.is('[data-can-view-own]')) { return; }
                    if (!$cb.prop('checked')) { $cb.prop('checked', true).trigger('change'); }
                });
            });
        });

        // Clear: untick everything in the group (core JS re-enables "view own" / "view").
        $table.on('click', '[data-perm-clear]', function (e) {
            e.preventDefault();
            var gid = $(this).attr('data-perm-clear');
            $table.find('tr.perm-ui-row[data-perm-in="' + gid + '"] input.capability').each(function () {
                var $cb = $(this);
                if ($cb.is('[data-not-applicable="true"]')) { return; }
                if ($cb.prop('checked')) { $cb.prop('checked', false).trigger('change'); }
            });
        });

        // Search across feature and group names; matching groups open automatically.
        $search.on('input', function () {
            var q = $.trim($(this).val()).toLowerCase();
            var anyMatch = false;
            $table.find('tr.perm-ui-group').each(function () {
                var gid = $(this).attr('data-perm-group');
                if (q === '') {
                    $(this).removeClass('hide');
                    setOpen(gid, false);
                    return;
                }
                var $rows = $table.find('tr.perm-ui-row[data-perm-in="' + gid + '"]');
                var hits = $rows.filter(function () { return ($(this).attr('data-perm-search') || '').indexOf(q) !== -1; });
                $(this).toggleClass('hide', hits.length === 0);
                $(this).toggleClass('is-open', hits.length > 0);
                $table.find('tr.perm-ui-head[data-perm-in="' + gid + '"]').toggleClass('hide', hits.length === 0);
                $rows.addClass('hide');
                hits.removeClass('hide');
                if (hits.length) { anyMatch = true; }
            });
            $table.find('tr.perm-ui-empty').toggleClass('hide', q === '' || anyMatch);
        });
    }
    init();
})();
</script>
