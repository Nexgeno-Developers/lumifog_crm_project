<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Builds the profit & loss figures for a project.
 *
 * Revenue        = sales invoices on the project (excluding Draft and Cancelled)
 * Actual cost    = every project expense (linked to a PO or not) + vendor bills of the project's POs
 * Committed cost = for each approved, open PO: PO value - what was already spent against it (min 0)
 * Expected cost  = actual + committed
 *
 * Money spent against a PO (a linked expense or a vendor bill) is drawn from that PO's budget,
 * so a PO item bought by staff is never counted twice. POs converted to a single expense by the
 * Purchase module are skipped (that expense is already in actual cost).
 *
 * Every figure is kept before tax ("net") and including tax ("gross").
 */
class Project_pnl_model extends App_Model
{
    public const PO_PENDING  = 1;
    public const PO_APPROVED = 2;

    // Keep the monthly chart readable
    private const MAX_MONTHS = 24;

    public function purchase_available()
    {
        $table = db_prefix() . 'pur_orders';

        return $this->db->table_exists($table) && $this->db->field_exists('project', $table);
    }

    public function expense_link_available()
    {
        return $this->db->field_exists('pur_order_id', db_prefix() . 'expenses');
    }

    public function get_project_pnl($project)
    {
        $projectId = (int) $project->id;

        $this->load->model('projects_model');
        $currency = $this->projects_model->get_currency($projectId);

        $revenue  = $this->get_revenue($projectId);
        $expenses = $this->get_expenses($projectId);
        $purchase = $this->get_purchase($projectId, $expenses['linked_by_po']);

        $actualNet   = $expenses['net'] + $purchase['bills_net'];
        $actualGross = $expenses['gross'] + $purchase['bills_gross'];

        $expectedNet   = $actualNet + $purchase['committed_net'];
        $expectedGross = $actualGross + $purchase['committed_gross'];

        $projectValue = (float) ($project->project_cost ?? 0);

        $profitNet   = $revenue['net'] - $actualNet;
        $profitGross = $revenue['gross'] - $actualGross;

        return [
            'currency'          => $currency,
            'revenue'           => $revenue,
            'expenses'          => $expenses,
            'purchase'          => $purchase,
            'actual_net'        => $actualNet,
            'actual_gross'      => $actualGross,
            'po_spend_net'      => $expenses['linked_net'] + $purchase['bills_net'],
            'other_spend_net'   => $expenses['net'] - $expenses['linked_net'],
            'expected_net'      => $expectedNet,
            'expected_gross'    => $expectedGross,
            'profit_net'        => $profitNet,
            'profit_gross'      => $profitGross,
            'margin'            => $revenue['net'] > 0 ? ($profitNet / $revenue['net']) * 100 : null,
            'project_value'     => $projectValue,
            'projected_profit'  => $projectValue > 0 ? $projectValue - $expectedNet : null,
            'projected_margin'  => $projectValue > 0 ? (($projectValue - $expectedNet) / $projectValue) * 100 : null,
            'monthly'           => $this->build_monthly_series($revenue['rows'], $expenses['rows'], $purchase['bill_rows']),
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

        $rows  = [];
        $net   = 0.0;
        $gross = 0.0;
        $ids   = [];
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

        return ['rows' => $rows, 'net' => $net, 'gross' => $gross, 'received' => $received];
    }

    private function get_tax_rates()
    {
        $rates = [];
        foreach ($this->db->select('id, taxrate')->get(db_prefix() . 'taxes')->result_array() as $tax) {
            $rates[$tax['id']] = (float) $tax['taxrate'];
        }

        return $rates;
    }

    private function get_expenses($projectId)
    {
        $taxRates     = $this->get_tax_rates();
        $hasLink      = $this->expense_link_available();
        $requiredCats = $this->get_po_required_categories();

        $this->db->select('e.id, e.amount, e.tax, e.tax2, e.date, e.category, c.name AS category_name'
            . ($hasLink ? ', e.pur_order_id' : ''));
        $this->db->from(db_prefix() . 'expenses e');
        $this->db->join(db_prefix() . 'expenses_categories c', 'c.id = e.category', 'left');
        $this->db->where('e.project_id', $projectId);
        $expenses = $this->db->get()->result_array();

        $rows       = [];
        $categories = [];
        $linkedByPo = [];
        $net        = 0.0;
        $gross      = 0.0;
        $linkedNet  = 0.0;
        $unlinked   = ['count' => 0, 'net' => 0.0];

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

            $poId = $hasLink ? (int) $expense['pur_order_id'] : 0;
            if ($poId > 0) {
                $linkedNet += $amount;
                $linkedByPo[$poId]['net']   = ($linkedByPo[$poId]['net'] ?? 0) + $amount;
                $linkedByPo[$poId]['gross'] = ($linkedByPo[$poId]['gross'] ?? 0) + $amount + $tax;
            } elseif (in_array((int) $expense['category'], $requiredCats, true)) {
                $unlinked['count']++;
                $unlinked['net'] += $amount;
            }

            $categoryName = $expense['category_name'] ?: _l('project_pnl_uncategorized');
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = ['name' => $categoryName, 'count' => 0, 'net' => 0.0, 'linked_net' => 0.0];
            }
            $categories[$categoryName]['count']++;
            $categories[$categoryName]['net'] += $amount;
            if ($poId > 0) {
                $categories[$categoryName]['linked_net'] += $amount;
            }

            $rows[] = ['date' => $expense['date'], 'net' => $amount, 'gross' => $amount + $tax, 'po' => $poId > 0];
        }

        uasort($categories, function ($a, $b) {
            return $b['net'] <=> $a['net'];
        });

        return [
            'rows'                 => $rows,
            'categories'           => array_values($categories),
            'linked_by_po'         => $linkedByPo,
            'linked_net'           => $linkedNet,
            'unlinked_required'    => $unlinked,
            'net'                  => $net,
            'gross'                => $gross,
        ];
    }

    /**
     * Purchase orders of the project with what was spent against each one.
     */
    private function get_purchase($projectId, array $linkedByPo)
    {
        $result = [
            'available'       => false,
            'rows'            => [],
            'bill_rows'       => [],
            'bills_net'       => 0.0,
            'bills_gross'     => 0.0,
            'committed_net'   => 0.0,
            'committed_gross' => 0.0,
            'approved_net'    => 0.0,
            'over_net'        => 0.0,
            'pending_count'   => 0,
            'pending_net'     => 0.0,
        ];

        if (!$this->purchase_available()) {
            return $result;
        }
        $result['available'] = true;

        $orders = $this->get_project_orders($projectId);
        if (count($orders) === 0) {
            return $result;
        }

        $bills  = $this->get_vendor_bills(array_column($orders, 'id'));
        $closed = $this->get_closed_po_ids(array_column($orders, 'id'));

        foreach ($bills as $bill) {
            $result['bills_net'] += $bill['net'];
            $result['bills_gross'] += $bill['gross'];
            $result['bill_rows'][] = $bill;
        }

        foreach ($orders as $order) {
            $id       = (int) $order['id'];
            $valueNet = (float) $order['total'] - (float) $order['total_tax'];
            $valueGr  = (float) $order['total'];

            $spentNet = ($linkedByPo[$id]['net'] ?? 0);
            $spentGr  = ($linkedByPo[$id]['gross'] ?? 0);
            foreach ($bills as $bill) {
                if ($bill['pur_order'] === $id) {
                    $spentNet += $bill['net'];
                    $spentGr += $bill['gross'];
                }
            }

            $approved = (int) $order['approve_status'] === self::PO_APPROVED;
            $isClosed = isset($closed[$id]);

            $committedNet = ($approved && !$isClosed) ? max($valueNet - $spentNet, 0) : 0;
            $committedGr  = ($approved && !$isClosed) ? max($valueGr - $spentGr, 0) : 0;
            $overNet      = $approved ? max($spentNet - $valueNet, 0) : 0;

            if ($approved) {
                $result['approved_net'] += $valueNet;
                $result['committed_net'] += $committedNet;
                $result['committed_gross'] += $committedGr;
                $result['over_net'] += $overNet;
            } else {
                $result['pending_count']++;
                $result['pending_net'] += $valueNet;
            }

            $result['rows'][] = [
                'id'        => $id,
                'number'    => $order['pur_order_number'],
                'date'      => $order['order_date'],
                'vendor'    => $order['vendor_name'] ?? '',
                'approved'  => $approved,
                'closed'    => $isClosed,
                'value'     => $valueNet,
                'spent'     => $spentNet,
                'remaining' => $committedNet,
                'over'      => $overNet,
            ];
        }

        return $result;
    }

    /**
     * Approved and pending POs of a project, excluding POs converted to an expense.
     */
    public function get_project_orders($projectId)
    {
        if (!$this->purchase_available()) {
            return [];
        }

        $table             = db_prefix() . 'pur_orders';
        $hasExpenseConvert = $this->db->field_exists('expense_convert', $table);
        $vendorTable       = db_prefix() . 'pur_vendor';
        $hasVendorTable    = $this->db->table_exists($vendorTable);

        $this->db->select('po.id, po.pur_order_number, po.order_date, po.approve_status, po.total, po.total_tax'
            . ($hasVendorTable ? ', v.company AS vendor_name' : ''));
        $this->db->from($table . ' po');
        if ($hasVendorTable) {
            $this->db->join($vendorTable . ' v', 'v.userid = po.vendor', 'left');
        }
        $this->db->where('po.project', (int) $projectId);
        $this->db->where_in('po.approve_status', [self::PO_APPROVED, self::PO_PENDING]);
        if ($hasExpenseConvert) {
            $this->db->group_start();
            $this->db->where('po.expense_convert', 0);
            $this->db->or_where('po.expense_convert IS NULL', null, false);
            $this->db->group_end();
        }
        $this->db->order_by('po.order_date', 'DESC');

        return $this->db->get()->result_array();
    }

    private function get_vendor_bills(array $poIds)
    {
        $table = db_prefix() . 'pur_invoices';
        if (count($poIds) === 0 || !$this->db->table_exists($table) || !$this->db->field_exists('pur_order', $table)) {
            return [];
        }

        $this->db->select('id, pur_order, invoice_date, total, tax');
        $this->db->where_in('pur_order', $poIds);
        $bills = [];
        foreach ($this->db->get($table)->result_array() as $bill) {
            $gross   = (float) $bill['total'];
            $bills[] = [
                'pur_order' => (int) $bill['pur_order'],
                'date'      => $bill['invoice_date'],
                'net'       => $gross - (float) $bill['tax'],
                'gross'     => $gross,
            ];
        }

        return $bills;
    }

    private function get_closed_po_ids(array $poIds)
    {
        $table = db_prefix() . 'project_pnl_po_closures';
        if (count($poIds) === 0 || !$this->db->table_exists($table)) {
            return [];
        }

        $this->db->select('pur_order_id');
        $this->db->where_in('pur_order_id', $poIds);

        return array_flip(array_map('intval', array_column($this->db->get($table)->result_array(), 'pur_order_id')));
    }

    public function get_po_required_categories()
    {
        $ids = json_decode((string) get_option('project_pnl_po_required_categories'), true);

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    /**
     * PO choices for the expense form: id, label and how much is already used.
     */
    public function get_po_options($projectId, $excludeExpenseId = 0)
    {
        $orders = $this->get_project_orders($projectId);
        if (count($orders) === 0) {
            return [];
        }

        $poIds = array_map('intval', array_column($orders, 'id'));
        $spent = [];
        if ($this->expense_link_available()) {
            $this->db->select('pur_order_id, SUM(amount) AS spent', false);
            $this->db->where_in('pur_order_id', $poIds);
            if ($excludeExpenseId > 0) {
                $this->db->where('id !=', (int) $excludeExpenseId);
            }
            $this->db->group_by('pur_order_id');
            foreach ($this->db->get(db_prefix() . 'expenses')->result_array() as $row) {
                $spent[(int) $row['pur_order_id']] = (float) $row['spent'];
            }
        }
        foreach ($this->get_vendor_bills($poIds) as $bill) {
            $spent[$bill['pur_order']] = ($spent[$bill['pur_order']] ?? 0) + $bill['net'];
        }
        $closed = $this->get_closed_po_ids($poIds);

        $options = [];
        foreach ($orders as $order) {
            $id        = (int) $order['id'];
            $options[] = [
                'id'       => $id,
                'number'   => $order['pur_order_number'],
                'vendor'   => $order['vendor_name'] ?? '',
                'approved' => (int) $order['approve_status'] === self::PO_APPROVED,
                'closed'   => isset($closed[$id]),
                'value'    => (float) $order['total'] - (float) $order['total_tax'],
                'spent'    => $spent[$id] ?? 0,
            ];
        }

        return $options;
    }

    /**
     * True when the PO belongs to the project and can take expenses.
     */
    public function po_belongs_to_project($poId, $projectId)
    {
        foreach ($this->get_project_orders($projectId) as $order) {
            if ((int) $order['id'] === (int) $poId) {
                return true;
            }
        }

        return false;
    }

    public function set_po_closed($poId, $closed)
    {
        $table = db_prefix() . 'project_pnl_po_closures';
        $this->db->where('pur_order_id', (int) $poId);
        $this->db->delete($table);

        if ($closed) {
            $this->db->insert($table, [
                'pur_order_id' => (int) $poId,
                'closed_by'    => get_staff_user_id(),
                'closed_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    public function get_po_project_id($poId)
    {
        if (!$this->purchase_available()) {
            return 0;
        }
        $this->db->select('project');
        $this->db->where('id', (int) $poId);
        $row = $this->db->get(db_prefix() . 'pur_orders')->row();

        return $row ? (int) $row->project : 0;
    }

    /**
     * Project expenses for the "link to PO" screen.
     */
    public function get_project_expenses_for_linking($projectId)
    {
        $this->db->select('e.id, e.amount, e.date, e.expense_name, e.note, e.pur_order_id, e.category, c.name AS category_name');
        $this->db->from(db_prefix() . 'expenses e');
        $this->db->join(db_prefix() . 'expenses_categories c', 'c.id = e.category', 'left');
        $this->db->where('e.project_id', (int) $projectId);
        $this->db->order_by('e.date', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * @param array $links expense id => PO id (0 = not linked)
     */
    public function save_expense_links($projectId, array $links)
    {
        $validPoIds = array_map('intval', array_column($this->get_project_orders($projectId), 'id'));
        $updated    = 0;

        foreach ($links as $expenseId => $poId) {
            $poId = (int) $poId;
            if ($poId !== 0 && !in_array($poId, $validPoIds, true)) {
                continue;
            }
            $this->db->where('id', (int) $expenseId);
            $this->db->where('project_id', (int) $projectId);
            $this->db->update(db_prefix() . 'expenses', ['pur_order_id' => $poId ?: null]);
            $updated += $this->db->affected_rows();
        }

        return $updated;
    }

    /**
     * Before-tax totals per month: revenue, PO spend (linked expenses + vendor bills),
     * other expenses, plus cumulative actual profit.
     */
    private function build_monthly_series(array $revenueRows, array $expenseRows, array $billRows)
    {
        $buckets = ['revenue' => [], 'po_spend' => [], 'other' => []];

        $add = function ($key, $date, $amount) use (&$buckets) {
            if (empty($date) || strtotime($date) === false) {
                return;
            }
            $month                 = date('Y-m', strtotime($date));
            $buckets[$key][$month] = ($buckets[$key][$month] ?? 0) + $amount;
        };

        foreach ($revenueRows as $row) {
            $add('revenue', $row['date'], $row['net']);
        }
        foreach ($expenseRows as $row) {
            $add($row['po'] ? 'po_spend' : 'other', $row['date'], $row['net']);
        }
        foreach ($billRows as $row) {
            $add('po_spend', $row['date'], $row['net']);
        }

        $empty     = ['labels' => [], 'revenue' => [], 'po_spend' => [], 'other' => [], 'cumulative_profit' => []];
        $allMonths = array_merge(array_keys($buckets['revenue']), array_keys($buckets['po_spend']), array_keys($buckets['other']));
        if (count($allMonths) === 0) {
            return $empty;
        }

        sort($allMonths);
        $months = [];
        $cursor = strtotime(reset($allMonths) . '-01');
        $last   = strtotime(end($allMonths) . '-01');
        while ($cursor <= $last) {
            $months[] = date('Y-m', $cursor);
            $cursor   = strtotime('+1 month', $cursor);
        }

        $series     = $empty;
        $cumulative = 0.0;
        foreach ($months as $month) {
            $revenue = round($buckets['revenue'][$month] ?? 0, 2);
            $poSpend = round($buckets['po_spend'][$month] ?? 0, 2);
            $other   = round($buckets['other'][$month] ?? 0, 2);
            $cumulative += $revenue - $poSpend - $other;

            $series['labels'][]            = date('M Y', strtotime($month . '-01'));
            $series['revenue'][]           = $revenue;
            $series['po_spend'][]          = $poSpend;
            $series['other'][]             = $other;
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
}
