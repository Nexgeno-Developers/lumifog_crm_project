<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_transactions_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', (int) $id);
            return $this->db->get(db_prefix() . 'kpi_transactions')->row_array();
        }

        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'kpi_transactions')->result_array();
    }

    public function get_modules()
    {
        $this->db->select('module');
        $this->db->from(db_prefix() . 'kpi_rules');
        $this->db->group_by('module');
        $this->db->order_by('module', 'ASC');
        $rows = $this->db->get()->result_array();

        return array_values(array_filter(array_map(static function ($r) {
            return $r['module'] ?? null;
        }, $rows)));
    }

    public function update_status($id, $status, $remarks = '')
    {
        $data = [
            'status'  => $status,
            'remarks' => ($remarks !== '' ? $remarks : null),
        ];

        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'kpi_transactions', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('KPI Transaction Status Updated [ID:' . (int) $id . ' Status:' . $status . ' By:' . get_staff_user_id() . ']');
            return true;
        }

        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete(db_prefix() . 'kpi_transactions');

        if ($this->db->affected_rows() > 0) {
            log_activity('KPI Transaction Deleted [ID:' . (int) $id . ' By:' . get_staff_user_id() . ']');
            return true;
        }

        return false;
    }

    /**
     * Insert attendance KPI transaction with duplicate guard:
     * (staff_id + rel_type + rel_id + rule_id)
     */
    public function insertTransactionIfNotExists(array $data)
    {
        $staffId = (int) ($data['staff_id'] ?? 0);
        $ruleId  = (int) ($data['kpi_rule_id'] ?? 0);
        $relType = (string) ($data['rel_type'] ?? '');
        $relId   = (int) ($data['rel_id'] ?? 0);

        $points          = (int) ($data['points'] ?? 0);
        $transactionDate = (string) ($data['transaction_date'] ?? date('Y-m-d'));
        $status          = (string) ($data['status'] ?? 'approved');
        $remarks         = (string) ($data['remarks'] ?? '');
        $createdBy       = $data['created_by'] ?? null;

        if ($staffId <= 0 || $ruleId <= 0 || $relType === '' || $relId <= 0) {
            return false;
        }

        $table = db_prefix() . 'kpi_transactions';

        $sql = "
            INSERT INTO `{$table}`
                (`staff_id`, `kpi_rule_id`, `rel_type`, `rel_id`, `points`, `transaction_date`, `status`, `remarks`, `created_by`)
            SELECT
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1
                FROM `{$table}`
                WHERE `staff_id` = ?
                  AND `rel_type` = ?
                  AND `rel_id` = ?
                  AND `kpi_rule_id` = ?
                LIMIT 1
            )
        ";

        $bindings = [
            $staffId,
            $ruleId,
            $relType,
            $relId,
            $points,
            $transactionDate,
            $status,
            $remarks,
            $createdBy,
            $staffId,
            $relType,
            $relId,
            $ruleId,
        ];

        $this->db->query($sql, $bindings);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Top N staff by summed approved points for the current calendar month.
     *
     * @return list<array{rank:int,staff_name:string,points:int}>
     */
    public function get_top_performers_current_month($limit = 3)
    {
        $limit = max(1, min(50, (int) $limit));

        if (!$this->db->table_exists(db_prefix() . 'kpi_transactions')) {
            return [];
        }

        $from = date('Y-m-01');
        $to   = date('Y-m-t');

        $this->db->select('staff_id, SUM(points) as total_points');
        $this->db->from(db_prefix() . 'kpi_transactions');
        $this->db->where('status', 'approved');
        $this->db->where('transaction_date >=', $from);
        $this->db->where('transaction_date <=', $to);
        $this->db->group_by('staff_id');
        $this->db->order_by('total_points', 'DESC');
        $this->db->limit($limit);

        $rows = $this->db->get()->result_array();
        if (empty($rows)) {
            return [];
        }

        $staffIds = [];
        foreach ($rows as $r) {
            $sid = (int) ($r['staff_id'] ?? 0);
            if ($sid > 0) {
                $staffIds[] = $sid;
            }
        }
        $staffIds = array_values(array_unique($staffIds));

        $namesById = [];
        if (!empty($staffIds)) {
            $this->db->select('staffid, firstname, lastname');
            $this->db->from(db_prefix() . 'staff');
            $this->db->where_in('staffid', $staffIds);
            foreach ($this->db->get()->result_array() as $s) {
                $id = (int) ($s['staffid'] ?? 0);
                $nm = trim(($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? ''));
                $namesById[$id] = $nm !== '' ? $nm : ('#' . $id);
            }
        }

        $out  = [];
        $rank = 1;
        foreach ($rows as $row) {
            $staffId = (int) ($row['staff_id'] ?? 0);
            if ($staffId <= 0) {
                continue;
            }
            $out[] = [
                'rank'       => $rank++,
                'staff_name' => $namesById[$staffId] ?? ('#' . $staffId),
                'points'     => (int) ($row['total_points'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * All active staff with summed approved KPI points for the current calendar month (0 if none).
     * Ordered by points descending, then name ascending.
     *
     * @return list<array{staffid:int,staff_name:string,points:int,rank:int}>
     */
    public function get_staff_leaderboard_current_month()
    {
        if (!$this->db->table_exists(db_prefix() . 'kpi_transactions')
            || !$this->db->table_exists(db_prefix() . 'staff')) {
            return [];
        }

        $from = date('Y-m-01');
        $to   = date('Y-m-t');

        $pfx   = db_prefix();
        $staff = $pfx . 'staff';
        $tx    = $pfx . 'kpi_transactions';

        $sql = "
            SELECT s.staffid,
                   TRIM(CONCAT(COALESCE(s.firstname, ''), ' ', COALESCE(s.lastname, ''))) AS staff_name,
                   COALESCE(t.total_points, 0) AS points
            FROM `{$staff}` s
            LEFT JOIN (
                SELECT staff_id, SUM(points) AS total_points
                FROM `{$tx}`
                WHERE status = 'approved'
                  AND transaction_date >= ?
                  AND transaction_date <= ?
                GROUP BY staff_id
            ) t ON t.staff_id = s.staffid
            WHERE s.active = 1
            ORDER BY points DESC,
                     staff_name ASC
        ";

        $rows = $this->db->query($sql, [$from, $to])->result_array();

        $out  = [];
        $rank = 1;
        foreach ($rows as $row) {
            $sid = (int) ($row['staffid'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $name = trim((string) ($row['staff_name'] ?? ''));
            $out[] = [
                'staffid'    => $sid,
                'staff_name' => $name !== '' ? $name : ('#' . $sid),
                'points'     => (int) ($row['points'] ?? 0),
                'rank'       => $rank++,
            ];
        }

        return $out;
    }
}
