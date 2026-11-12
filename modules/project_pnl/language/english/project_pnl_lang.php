<?php

defined('BASEPATH') or exit('No direct script access allowed');

$lang['project_pnl']                    = 'Project Profit & Loss';
$lang['project_pnl_heading']            = 'Profit & Loss';
$lang['project_pnl_subheading']         = 'Money spent on PO items is drawn from the PO budget, so it is never counted twice. Main figures are before tax.';

// Cards
$lang['project_pnl_revenue']            = 'Revenue';
$lang['project_pnl_actual_cost']        = 'Actual Cost';
$lang['project_pnl_po_spend']           = 'Against POs';
$lang['project_pnl_other_expenses']     = 'Other expenses';
$lang['project_pnl_committed']          = 'Committed on POs';
$lang['project_pnl_committed_help']     = 'Unspent balance of open, approved POs';
$lang['project_pnl_expected_cost']      = 'Expected Total Cost';
$lang['project_pnl_expected_help']      = 'Actual cost + committed on POs';
$lang['project_pnl_profit_to_date']     = 'Profit to Date';
$lang['project_pnl_loss_to_date']       = 'Loss to Date';
$lang['project_pnl_profit_to_date_help'] = 'revenue − actual cost';
$lang['project_pnl_projected_profit']   = 'Projected Profit';
$lang['project_pnl_projected_help']     = 'Project value − expected total cost';
$lang['project_pnl_margin']             = 'Margin';
$lang['project_pnl_with_tax']           = 'With tax';
$lang['project_pnl_received']           = 'Received';
$lang['project_pnl_invoices_count']     = '%s invoice(s)';

// Notes and warnings
$lang['project_pnl_pending_po_note']    = '%s PO(s) awaiting approval (%s before tax) are not counted as committed.';
$lang['project_pnl_over_note']          = 'Spending is over the approved PO value by %s in total — see the PO table below.';
$lang['project_pnl_unlinked_warning']   = '%s expense(s) worth %s in PO-required categories are not linked to a purchase order and may be counted twice.';
$lang['project_pnl_link_now']           = 'Link them now';
$lang['project_pnl_purchase_module_missing'] = 'Purchase module is not active — purchase orders are not included.';

// Progress
$lang['project_pnl_invoiced_of_value']  = 'Invoiced vs project value';
$lang['project_pnl_expected_of_value']  = 'Expected cost vs project value';
$lang['project_pnl_received_of_invoiced'] = 'Received vs invoiced';

// Chart
$lang['project_pnl_monthly_trend']      = 'Monthly Revenue vs Cost';
$lang['project_pnl_cumulative_profit']  = 'Cumulative Profit';
$lang['project_pnl_no_chart_data']      = 'Nothing to chart yet — add invoices, purchase orders or expenses to this project.';

// PO table
$lang['project_pnl_po_utilisation']     = 'Purchase Order Usage';
$lang['project_pnl_po_value']           = 'PO Value';
$lang['project_pnl_spent_against']      = 'Spent Against PO';
$lang['project_pnl_remaining']          = 'Remaining';
$lang['project_pnl_status']             = 'Status';
$lang['project_pnl_over_by']            = 'Over by %s';
$lang['project_pnl_po_open']            = 'Open';
$lang['project_pnl_po_over']            = 'Over budget';
$lang['project_pnl_po_pending']         = 'Awaiting approval';
$lang['project_pnl_po_closed']          = 'Closed';
$lang['project_pnl_close']              = 'Close';
$lang['project_pnl_reopen']             = 'Reopen';
$lang['project_pnl_close_confirm']      = 'Close this PO for costing? Its unspent balance will no longer count as committed cost.';
$lang['project_pnl_po_closed_msg']      = 'Purchase order closed for costing.';
$lang['project_pnl_po_reopened_msg']    = 'Purchase order reopened for costing.';

// Other tables
$lang['project_pnl_sales_invoices']     = 'Sales Invoices';
$lang['project_pnl_expenses_by_category'] = 'Expenses by Category';
$lang['project_pnl_against_po_short']   = 'Against PO';
$lang['project_pnl_uncategorized']      = 'Uncategorized';
$lang['project_pnl_before_tax']         = 'Before Tax';
$lang['project_pnl_share']              = 'Share';
$lang['project_pnl_number']             = 'Number';
$lang['project_pnl_date']               = 'Date';
$lang['project_pnl_vendor']             = 'Vendor';
$lang['project_pnl_category']           = 'Category';
$lang['project_pnl_no_data']            = 'No records yet';

// Expense form
$lang['project_pnl_against_po']         = 'Against Purchase Order';
$lang['project_pnl_not_against_po']     = '— Not against a PO —';
$lang['project_pnl_against_po_help']    = 'Bought an item listed in a PO? Pick that PO so the cost is drawn from it and not counted twice.';
$lang['project_pnl_po_required_msg']    = 'Please select the purchase order this expense was for.';
$lang['project_pnl_po_used']            = 'used';
$lang['project_pnl_select_project_first'] = 'Select a project to see its purchase orders';

// Link screen
$lang['project_pnl_link_expenses']      = 'Link Expenses to POs';
$lang['project_pnl_link_expenses_help'] = 'Pick the purchase order each expense was spent against. Expenses that are not for PO items (travel, food, labour…) stay as "Not against a PO". Highlighted rows are in PO-required categories but not linked yet.';
$lang['project_pnl_needs_po']           = 'Needs PO';
$lang['project_pnl_links_saved']        = 'Saved. %s expense(s) updated.';
$lang['project_pnl_back_to_project']    = 'Back to project';
$lang['project_pnl_upgrade_required']   = 'Please run the Project Profit & Loss database upgrade (Setup → Modules) first.';

// Settings
$lang['project_pnl_po_required_categories']      = 'Expense categories that must be linked to a PO';
$lang['project_pnl_po_required_categories_help'] = 'When an expense in one of these categories is added to a project that has purchase orders, staff must pick the PO it was spent against. Existing unlinked expenses in these categories are flagged on the project P&L.';
