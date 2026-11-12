<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Builds the profit & loss figures for a project.
 *
 * Revenue  = sales invoices on the project (excluding Draft and Cancelled)
 * Purchase = approved purchase orders on the project (skipping POs converted to an expense,
 *            because that expense is already counted below)
 * Expenses = expenses on the project (same basis as the core "Expenses" overview box)
 *
 * Every figure is kept both before tax ("net") and including tax ("gross").
 */
class Project_pnl_model extends App_Model
{
    private const PO_APPROVED = 2;
    private const PO_PENDING  = 1;

    // Keep the monthly chart readable
    private const MAX_MONTHS = 24;

    public function get_project_pnl($project)
    {
        $projectId = (int) $project->id;

        $this->load->model('projects_model');
        $currency = $this->projects_model->get_currency($projectId);

        $revenue  = $this->get_revenue($projectId);
        $purchase = $this->get_purchase($projectId);
        $expenses = $this->get_expenses($projectId);

        $costNet   = $purchase['net'] + $expenses['net'];
        $costGross = $purchase['gross'] + $expenses['gross'];

        $profitNet   = $revenue['net'] - $costNet;
        $profitGross = $revenue['gross'] - $costGross;

        $projectValue = (float) ($project->project_cost ?? 0);

        return [
            'currency'      => $currency,
            'revenue'       => $revenue,
            'purchase'      => $purchase,
            'expenses'      => $expenses,
            'cost_net'      => $costNet,
            'cost_gross'    => $costGross,
            'profit_net'    => $profitNet,
            'profit_gross'  => $profitGross,
            'margin'        => $revenue['net'] > 0 ? ($profitNet / $revenue['net']) * 100 : null,
            'project_value' => $projectValue,
            'monthly'       => $this->build_monthly_series($revenue['rows'], $purchase['rows'], $expenses['rows']),
            'cost_split'    => $this->build_cost_split($purchase, $expenses),
        ];
    }

    private function get_revenue($projectId)
    {
        $this->load->model('invoices_model');

        $this->db->select('id, date, status, total, total_tax');
        $this->db->where('project_id', $projectId);
        $this->db->where_not_in('status', [Invoices_model::STATUS_DRAFT, Invoices_model::STATUS_CANCELLED]);
        $this->db->order_by('date', 'DESC');
        $invoices = $this->db->get(db_prefix() . 'invoices')->result_array();

        $rows     = [];
        $net      = 0.0;
        $gross    = 0.0;
        $ids      = [];
        foreach ($invoices as $invoice) {
            $rowGross = (float) $invoice['total'];
            $rowNet   = $rowGross - (float) $invoice['total_tax'];
            $net += $rowNet;
            $gross += $rowGross;
            $ids[] = (int) $invoice['id'];

            $rows[] = [
                'id'     => (int) $invoice['id'],
                'date'   => $invoice['date'],
                'status' => (int) $invoice['status'],
                'net'    => $rowNet,
                'gross'  => $rowGross,
            ];
        }

        $received = 0.0;
        if (count($ids) > 0) {
            $this->db->select_sum('amount');
            $this->db->where_in('invoiceid', $ids);
            $received = (float) ($this->db->get(db_prefix() . 'invoicepaymentrecords')->row()->amount ?? 0);
        }

        return [
            'rows'     => $rows,
            'net'      => $net,
            'gross'    => $gross,
            'received' => $received,
        ];
    }

    private function get_purchase($projectId)
    {
        $result = [
            'available'     => false,
            'rows'          => [],
            'net'           => 0.0,
            'gross'         => 0.0,
            'pending_count' => 0,
            'pending_net'   => 0.0,
        ];

        $table = db_prefix() . 'pur_orders';
        if (!$this->db->table_exists($table) || !$this->db->field_exists('project', $table)) {
            return $result;
        }
        $result['available'] = true;

        $hasExpenseConvert = $this->db->field_exists('expense_convert', $table);
        $vendorTable       = db_prefix() . 'pur_vendor';
        $hasVendorTable    = $this->db->table_exists($vendorTable);

        $this->db->select('po.id, po.pur_order_number, po.order_date, po.approve_status, po.total, po.total_tax'
            . ($hasExpenseConvert ? ', po.expense_convert' : '')
            . ($hasVendorTable ? ', v.company AS vendor_name' : ''));
        $this->db->from($table . ' po');
        if ($hasVendorTable) {
            $this->db->join($vendorTable . ' v', 'v.userid = po.vendor', 'left');
        }
        $this->db->where('po.project', $projectId);
        $this->db->where_in('po.approve_status', [self::PO_APPROVED, self::PO_PENDING]);
        $this->db->order_by('po.order_date', 'DESC');
        $orders = $this->db->get()->result_array();

        foreach ($orders as $order) {
            $rowGross = (float) $order['total'];
            $rowNet   = $rowGross - (float) $order['total_tax'];

            if ((int) $order['approve_status'] === self::PO_PENDING) {
                $result['pending_count']++;
                $result['pending_net'] += $rowNet;
                continue;
            }

            // Converted to an expense: that expense is counted in get_expenses()
            if ($hasExpenseConvert && !empty($order['expense_convert'])) {
                continue;
            }

            $result['net'] += $rowNet;
            $result['gross'] += $rowGross;
            $result['rows'][] = [
                'id'     => (int) $order['id'],
                'number' => $order['pur_order_number'],
                'date'   => $order['order_date'],
                'vendor' => $order['vendor_name'] ?? '',
                'net'    => $rowNet,
                'gross'  => $rowGross,
            ];
        }

        return $result;
    }

    private function get_expenses($projectId)
    {
        $taxRates = [];
        foreach ($this->db->select('id, taxrate')->get(db_prefix() . 'taxes')->result_array() as $tax) {
            $taxRates[$tax['id']] = (float) $tax['taxrate'];
        }

        $this->db->select('e.id, e.amount, e.tax, e.tax2, e.date, e.category, c.name AS category_name');
        $this->db->from(db_prefix() . 'expenses e');
        $this->db->join(db_prefix() . 'expenses_categories c', 'c.id = e.category', 'left');
        $this->db->where('e.project_id', $projectId);
        $expenses = $this->db->get()->result_array();

        $rows       = [];
        $categories = [];
        $net        = 0.0;
        $gross      = 0.0;

        foreach ($expenses as $expense) {
            $amount = (float) $expense['amount'];
            $tax    = 0.0;
            foreach (['tax', 'tax2'] as $taxField) {
                if (!empty($expense[$taxField]) && isset($taxRates[$expense[$taxField]])) {
                    $tax += ($amount / 100) * $taxRates[$expense[$taxField]];
                }
            }

            $net += $amount;
            $gross += $amount + $tax;

            $categoryName = $expense['category_name'] ?: _l('project_pnl_uncategorized');
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = ['name' => $categoryName, 'count' => 0, 'net' => 0.0, 'tax' => 0.0, 'gross' => 0.0];
            }
            $categories[$categoryName]['count']++;
            $categories[$categoryName]['net'] += $amount;
            $categories[$categoryName]['tax'] += $tax;
            $categories[$categoryName]['gross'] += $amount + $tax;

            $rows[] = ['date' => $expense['date'], 'net' => $amount, 'gross' => $amount + $tax];
        }

        uasort($categories, function ($a, $b) {
            return $b['net'] <=> $a['net'];
        });

        return [
            'rows'       => $rows,
            'categories' => array_values($categories),
            'net'        => $net,
            'gross'      => $gross,
        ];
    }

    /**
     * Before-tax totals per month for revenue, purchase and expenses, plus cumulative profit.
     */
    private function build_monthly_series(array $revenueRows, array $purchaseRows, array $expenseRows)
    {
        $buckets = ['revenue' => [], 'purchase' => [], 'expenses' => []];
        $sources = ['revenue' => $revenueRows, 'purchase' => $purchaseRows, 'expenses' => $expenseRows];

        foreach ($sources as $key => $rows) {
            foreach ($rows as $row) {
                if (empty($row['date']) || strtotime($row['date']) === false) {
                    continue;
                }
                $month = date('Y-m', strtotime($row['date']));
                $buckets[$key][$month] = ($buckets[$key][$month] ?? 0) + $row['net'];
            }
        }

        $allMonths = array_merge(array_keys($buckets['revenue']), array_keys($buckets['purchase']), array_keys($buckets['expenses']));
        if (count($allMonths) === 0) {
            return ['labels' => [], 'revenue' => [], 'purchase' => [], 'expenses' => [], 'cumulative_profit' => []];
        }

        sort($allMonths);
        $months = [];
        $cursor = strtotime(reset($allMonths) . '-01');
        $last   = strtotime(end($allMonths) . '-01');
        while ($cursor <= $last) {
            $months[] = date('Y-m', $cursor);
            $cursor   = strtotime('+1 month', $cursor);
        }

        $series     = ['labels' => [], 'revenue' => [], 'purchase' => [], 'expenses' => [], 'cumulative_profit' => []];
        $cumulative = 0.0;
        foreach ($months as $month) {
            $revenue  = round($buckets['revenue'][$month] ?? 0, 2);
            $purchase = round($buckets['purchase'][$month] ?? 0, 2);
            $expense  = round($buckets['expenses'][$month] ?? 0, 2);
            $cumulative += $revenue - $purchase - $expense;

            $series['labels'][]            = date('M Y', strtotime($month . '-01'));
            $series['revenue'][]           = $revenue;
            $series['purchase'][]          = $purchase;
            $series['expenses'][]          = $expense;
            $series['cumulative_profit'][] = round($cumulative, 2);
        }

        // Cumulative profit already includes the older months; only trim what is displayed
        if (count($months) > self::MAX_MONTHS) {
            foreach ($series as $key => $values) {
                $series[$key] = array_slice($values, -self::MAX_MONTHS);
            }
        }

        return $series;
    }

    /**
     * Doughnut data: purchase orders as one slice, each expense category as its own slice.
     */
    private function build_cost_split(array $purchase, array $expenses)
    {
        $labels = [];
        $values = [];

        if ($purchase['net'] > 0) {
            $labels[] = _l('project_pnl_purchase_orders');
            $values[] = round($purchase['net'], 2);
        }
        foreach ($expenses['categories'] as $category) {
            if ($category['net'] > 0) {
                $labels[] = $category['name'];
                $values[] = round($category['net'], 2);
            }
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
